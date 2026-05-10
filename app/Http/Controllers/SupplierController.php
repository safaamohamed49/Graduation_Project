<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Services\Accounting\AccountCreationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SupplierController extends Controller
{
    private function authorizePermission(string $permission, string $message): void
    {
        if (!auth()->user()?->hasPermission($permission)) {
            abort(403, $message);
        }
    }

    private function isAdmin(): bool
    {
        return auth()->user()?->hasPermission('*') ?? false;
    }

    public function index(Request $request): Response
    {
        $this->authorizePermission('suppliers.view', 'ليس لديك صلاحية لعرض الموردين.');

        $search = trim((string) $request->get('search', ''));

        $query = Supplier::query()
            ->with(['branch:id,name,code', 'account:id,name,code'])
            ->where('is_deleted', false);

        if (!$this->isAdmin()) {
            $query->where(function ($q) {
                $q->whereNull('branch_id')
                    ->orWhere('branch_id', auth()->user()?->branch_id);
            });
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('account', function ($accountQuery) use ($search) {
                        $accountQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    });
            });
        }

        $suppliers = $query
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Suppliers/Index', [
            'suppliers' => $suppliers,
            'filters' => [
                'search' => $search,
            ],
            'permissions' => [
                'canCreate' => auth()->user()?->hasPermission('suppliers.create') ?? false,
                'canUpdate' => auth()->user()?->hasPermission('suppliers.update') ?? false,
                'canDelete' => auth()->user()?->hasPermission('suppliers.delete') ?? false,
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorizePermission('suppliers.create', 'ليس لديك صلاحية لإضافة مورد.');

        return Inertia::render('Suppliers/Create');
    }

    public function store(Request $request, AccountCreationService $accountCreationService): RedirectResponse
    {
        $this->authorizePermission('suppliers.create', 'ليس لديك صلاحية لإضافة مورد.');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $name = trim($data['name']);
        $code = strtoupper(trim($data['code']));

        $existingByName = Supplier::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();

        if ($existingByName) {
            if ($existingByName->is_deleted) {
                return back()->withErrors([
                    'name' => 'يوجد مورد محذوف سابقًا بنفس الاسم "' . $existingByName->name . '". يفضل مراجعته بدل إنشاء مورد جديد.',
                ])->withInput();
            }

            return back()->withErrors([
                'name' => 'لا يمكن إضافة المورد، لأن الاسم "' . $existingByName->name . '" موجود بالفعل.',
            ])->withInput();
        }

        $existingByCode = Supplier::whereRaw('LOWER(code) = ?', [mb_strtolower($code)])->first();

        if ($existingByCode) {
            if ($existingByCode->is_deleted) {
                return back()->withErrors([
                    'code' => 'يوجد مورد محذوف سابقًا يستخدم الرمز "' . $existingByCode->code . '" واسمه "' . $existingByCode->name . '".',
                ])->withInput();
            }

            return back()->withErrors([
                'code' => 'لا يمكن إضافة المورد، لأن الرمز "' . $existingByCode->code . '" مستخدم بالفعل.',
            ])->withInput();
        }

        DB::transaction(function () use ($request, $data, $name, $code, $accountCreationService) {
            $supplier = Supplier::create([
                'branch_id' => auth()->user()?->branch_id,
                'name' => $name,
                'code' => $code,
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'address' => $data['address'] ?? null,
                'notes' => $data['notes'] ?? null,
                'account_id' => null,
                'is_active' => $request->boolean('is_active', true),
                'is_deleted' => false,
                'is_locked' => false,
            ]);

            $account = $accountCreationService->createSupplierAccount($supplier);

            $supplier->update([
                'account_id' => $account->id,
            ]);
        });

        return redirect('/suppliers')->with('success', 'تمت إضافة المورد وإنشاء حسابه المحاسبي بنجاح.');
    }

    public function edit(Supplier $supplier): Response
    {
        $this->authorizePermission('suppliers.update', 'ليس لديك صلاحية لتعديل الموردين.');

        if ($supplier->is_deleted) {
            abort(404);
        }

        if (!$this->isAdmin() && $supplier->branch_id && (int) $supplier->branch_id !== (int) auth()->user()?->branch_id) {
            abort(403, 'ليس لديك صلاحية لتعديل هذا المورد.');
        }

        $supplier->load('account:id,name,code');

        return Inertia::render('Suppliers/Edit', [
            'supplier' => $supplier,
        ]);
    }

    public function update(Request $request, Supplier $supplier, AccountCreationService $accountCreationService): RedirectResponse
    {
        $this->authorizePermission('suppliers.update', 'ليس لديك صلاحية لتعديل الموردين.');

        if ($supplier->is_locked) {
            return back()->withErrors([
                'edit' => 'لا يمكن تعديل مورد مقفل.',
            ]);
        }

        if (!$this->isAdmin() && $supplier->branch_id && (int) $supplier->branch_id !== (int) auth()->user()?->branch_id) {
            abort(403, 'ليس لديك صلاحية لتعديل هذا المورد.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $name = trim($data['name']);
        $code = strtoupper(trim($data['code']));

        $existingByName = Supplier::where('id', '!=', $supplier->id)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first();

        if ($existingByName) {
            return back()->withErrors([
                'name' => 'لا يمكن تعديل المورد، لأن الاسم "' . $existingByName->name . '" موجود بالفعل.',
            ])->withInput();
        }

        $existingByCode = Supplier::where('id', '!=', $supplier->id)
            ->whereRaw('LOWER(code) = ?', [mb_strtolower($code)])
            ->first();

        if ($existingByCode) {
            return back()->withErrors([
                'code' => 'لا يمكن تعديل المورد، لأن الرمز "' . $existingByCode->code . '" مستخدم بالفعل.',
            ])->withInput();
        }

        DB::transaction(function () use ($request, $supplier, $data, $name, $code, $accountCreationService) {
            $supplier->update([
                'name' => $name,
                'code' => $code,
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'address' => $data['address'] ?? null,
                'notes' => $data['notes'] ?? null,
                'is_active' => $request->boolean('is_active', true),
            ]);

            if (!$supplier->account_id) {
                $account = $accountCreationService->createSupplierAccount($supplier);

                $supplier->update([
                    'account_id' => $account->id,
                ]);
            }
        });

        return redirect('/suppliers')->with('success', 'تم تعديل المورد والتأكد من حسابه المحاسبي بنجاح.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        $this->authorizePermission('suppliers.delete', 'ليس لديك صلاحية لحذف الموردين.');

        if ($supplier->is_locked) {
            return back()->withErrors([
                'delete' => 'لا يمكن حذف مورد مقفل.',
            ]);
        }

        if (!$this->isAdmin() && $supplier->branch_id && (int) $supplier->branch_id !== (int) auth()->user()?->branch_id) {
            abort(403, 'ليس لديك صلاحية لحذف هذا المورد.');
        }

        $supplier->update([
            'is_deleted' => true,
            'is_active' => false,
        ]);

        return redirect('/suppliers')->with('success', 'تم حذف المورد منطقيًا بنجاح.');
    }
}
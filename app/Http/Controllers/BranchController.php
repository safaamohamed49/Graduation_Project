<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class BranchController extends Controller
{
    private function authorizePermission(string $permission, string $message): void
    {
        if (!auth()->user()?->hasPermission($permission)) {
            abort(403, $message);
        }
    }

    public function index(Request $request): Response
    {
        $this->authorizePermission('branches.view', 'ليس لديك صلاحية لعرض الفروع.');

        $search = trim((string) $request->get('search', ''));

        $query = Branch::query()
            ->with('manager:id,name')
            ->withCount([
                'warehouses',
                'users',
                'customers',
                'suppliers',
                'orders',
            ]);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $branches = $query
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Branches/Index', [
            'branches' => $branches,
            'filters' => [
                'search' => $search,
            ],
            'permissions' => [
                'canCreate' => auth()->user()?->hasPermission('branches.create') ?? false,
                'canUpdate' => auth()->user()?->hasPermission('branches.update') ?? false,
                'canDelete' => auth()->user()?->hasPermission('branches.delete') ?? false,
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorizePermission('branches.create', 'ليس لديك صلاحية لإضافة فرع.');

        return Inertia::render('Branches/Create', [
            'users' => $this->usersForManagerSelect(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizePermission('branches.create', 'ليس لديك صلاحية لإضافة فرع.');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'manager_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $name = trim($data['name']);
        $code = strtoupper(trim($data['code']));

        $existingByName = Branch::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();

        if ($existingByName) {
            return back()->withErrors([
                'name' => 'لا يمكن إضافة الفرع، لأن اسم الفرع "' . $existingByName->name . '" موجود بالفعل.',
            ])->withInput();
        }

        $existingByCode = Branch::whereRaw('LOWER(code) = ?', [mb_strtolower($code)])->first();

        if ($existingByCode) {
            return back()->withErrors([
                'code' => 'لا يمكن إضافة الفرع، لأن رمز الفرع "' . $existingByCode->code . '" مستخدم بالفعل.',
            ])->withInput();
        }

        Branch::create([
            'name' => $name,
            'code' => $code,
            'address' => $data['address'] ?? null,
            'phone' => $data['phone'] ?? null,
            'manager_user_id' => $data['manager_user_id'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);

        return redirect()->route('branches.index')->with('success', 'تمت إضافة الفرع بنجاح.');
    }

    public function show(Branch $branch): Response
    {
        $this->authorizePermission('branches.view', 'ليس لديك صلاحية لعرض تفاصيل الفرع.');

        $branch->load([
            'manager:id,name',
            'warehouses:id,branch_id,name,code,type,is_active',
        ]);

        $branch->loadCount([
            'warehouses',
            'users',
            'employees',
            'customers',
            'suppliers',
            'orders',
            'purchaseInvoices',
            'returnInvoices',
            'paymentVouchers',
            'receiptVouchers',
            'journalEntries',
            'fixedAssets',
            'financialAccounts',
        ]);

        return Inertia::render('Branches/Show', [
            'branch' => $branch,
            'permissions' => [
                'canUpdate' => auth()->user()?->hasPermission('branches.update') ?? false,
            ],
        ]);
    }

    public function edit(Branch $branch): Response
    {
        $this->authorizePermission('branches.update', 'ليس لديك صلاحية لتعديل الفروع.');

        return Inertia::render('Branches/Edit', [
            'branch' => $branch,
            'users' => $this->usersForManagerSelect(),
        ]);
    }

    public function update(Request $request, Branch $branch): RedirectResponse
    {
        $this->authorizePermission('branches.update', 'ليس لديك صلاحية لتعديل الفروع.');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'manager_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $name = trim($data['name']);
        $code = strtoupper(trim($data['code']));

        $existingByName = Branch::where('id', '!=', $branch->id)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first();

        if ($existingByName) {
            return back()->withErrors([
                'name' => 'لا يمكن تعديل الفرع، لأن اسم الفرع "' . $existingByName->name . '" موجود بالفعل.',
            ])->withInput();
        }

        $existingByCode = Branch::where('id', '!=', $branch->id)
            ->whereRaw('LOWER(code) = ?', [mb_strtolower($code)])
            ->first();

        if ($existingByCode) {
            return back()->withErrors([
                'code' => 'لا يمكن تعديل الفرع، لأن رمز الفرع "' . $existingByCode->code . '" مستخدم بالفعل.',
            ])->withInput();
        }

        $branch->update([
            'name' => $name,
            'code' => $code,
            'address' => $data['address'] ?? null,
            'phone' => $data['phone'] ?? null,
            'manager_user_id' => $data['manager_user_id'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);

        return redirect()->route('branches.index')->with('success', 'تم تعديل الفرع بنجاح.');
    }

    public function destroy(Branch $branch): RedirectResponse
    {
        $this->authorizePermission('branches.delete', 'ليس لديك صلاحية لحذف الفروع.');

        $hasRelations =
            $branch->warehouses()->exists()
            || $branch->users()->exists()
            || $branch->employees()->exists()
            || $branch->customers()->exists()
            || $branch->suppliers()->exists()
            || $branch->orders()->exists()
            || $branch->purchaseInvoices()->exists()
            || $branch->returnInvoices()->exists()
            || $branch->paymentVouchers()->exists()
            || $branch->receiptVouchers()->exists()
            || $branch->journalEntries()->exists()
            || $branch->fixedAssets()->exists()
            || $branch->financialAccounts()->exists();

        if ($hasRelations) {
            return back()->withErrors([
                'delete' => 'لا يمكن حذف الفرع "' . $branch->name . '" لأنه مرتبط ببيانات أو حركات داخل النظام.',
            ]);
        }

        $branch->delete();

        return redirect()->route('branches.index')->with('success', 'تم حذف الفرع بنجاح.');
    }

    private function usersForManagerSelect()
    {
        return User::query()
            ->select('id', 'name')
            ->where(function ($query) {
                $query->where('is_deleted', false)
                    ->orWhereNull('is_deleted');
            })
            ->orderBy('name')
            ->get();
    }
}
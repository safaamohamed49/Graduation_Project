<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Services\Accounting\AccountCreationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
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
        $this->authorizePermission('customers.view', 'ليس لديك صلاحية لعرض العملاء.');

        $search = trim((string) $request->get('search', ''));

        $query = Customer::query()
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

        $customers = $query
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Customers/Index', [
            'customers' => $customers,
            'filters' => [
                'search' => $search,
            ],
            'permissions' => [
                'canCreate' => auth()->user()?->hasPermission('customers.create') ?? false,
                'canUpdate' => auth()->user()?->hasPermission('customers.update') ?? false,
                'canDelete' => auth()->user()?->hasPermission('customers.delete') ?? false,
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorizePermission('customers.create', 'ليس لديك صلاحية لإضافة عميل.');

        return Inertia::render('Customers/Create');
    }

    public function store(Request $request, AccountCreationService $accountCreationService): RedirectResponse
    {
        $this->authorizePermission('customers.create', 'ليس لديك صلاحية لإضافة عميل.');

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

        $existingByName = Customer::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();

        if ($existingByName) {
            if ($existingByName->is_deleted) {
                return back()->withErrors([
                    'name' => 'يوجد عميل محذوف سابقًا بنفس الاسم "' . $existingByName->name . '". يفضل مراجعته بدل إنشاء عميل جديد.',
                ])->withInput();
            }

            return back()->withErrors([
                'name' => 'لا يمكن إضافة العميل، لأن الاسم "' . $existingByName->name . '" موجود بالفعل.',
            ])->withInput();
        }

        $existingByCode = Customer::whereRaw('LOWER(code) = ?', [mb_strtolower($code)])->first();

        if ($existingByCode) {
            if ($existingByCode->is_deleted) {
                return back()->withErrors([
                    'code' => 'يوجد عميل محذوف سابقًا يستخدم الرمز "' . $existingByCode->code . '" واسمه "' . $existingByCode->name . '".',
                ])->withInput();
            }

            return back()->withErrors([
                'code' => 'لا يمكن إضافة العميل، لأن الرمز "' . $existingByCode->code . '" مستخدم بالفعل.',
            ])->withInput();
        }

        DB::transaction(function () use ($request, $data, $name, $code, $accountCreationService) {
            $customer = Customer::create([
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

            $account = $accountCreationService->createCustomerAccount($customer);

            $customer->update([
                'account_id' => $account->id,
            ]);
        });

        return redirect('/customers')->with('success', 'تمت إضافة العميل وإنشاء حسابه المحاسبي بنجاح.');
    }

    public function edit(Customer $customer): Response
    {
        $this->authorizePermission('customers.update', 'ليس لديك صلاحية لتعديل العملاء.');

        if ($customer->is_deleted) {
            abort(404);
        }

        if (!$this->isAdmin() && $customer->branch_id && (int) $customer->branch_id !== (int) auth()->user()?->branch_id) {
            abort(403, 'ليس لديك صلاحية لتعديل هذا العميل.');
        }

        $customer->load('account:id,name,code');

        return Inertia::render('Customers/Edit', [
            'customer' => $customer,
        ]);
    }

    public function update(Request $request, Customer $customer, AccountCreationService $accountCreationService): RedirectResponse
    {
        $this->authorizePermission('customers.update', 'ليس لديك صلاحية لتعديل العملاء.');

        if ($customer->is_locked) {
            return back()->withErrors([
                'edit' => 'لا يمكن تعديل عميل مقفل.',
            ]);
        }

        if (!$this->isAdmin() && $customer->branch_id && (int) $customer->branch_id !== (int) auth()->user()?->branch_id) {
            abort(403, 'ليس لديك صلاحية لتعديل هذا العميل.');
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

        $existingByName = Customer::where('id', '!=', $customer->id)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first();

        if ($existingByName) {
            return back()->withErrors([
                'name' => 'لا يمكن تعديل العميل، لأن الاسم "' . $existingByName->name . '" موجود بالفعل.',
            ])->withInput();
        }

        $existingByCode = Customer::where('id', '!=', $customer->id)
            ->whereRaw('LOWER(code) = ?', [mb_strtolower($code)])
            ->first();

        if ($existingByCode) {
            return back()->withErrors([
                'code' => 'لا يمكن تعديل العميل، لأن الرمز "' . $existingByCode->code . '" مستخدم بالفعل.',
            ])->withInput();
        }

        DB::transaction(function () use ($request, $customer, $data, $name, $code, $accountCreationService) {
            $customer->update([
                'name' => $name,
                'code' => $code,
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'address' => $data['address'] ?? null,
                'notes' => $data['notes'] ?? null,
                'is_active' => $request->boolean('is_active', true),
            ]);

            if (!$customer->account_id) {
                $account = $accountCreationService->createCustomerAccount($customer);

                $customer->update([
                    'account_id' => $account->id,
                ]);
            }
        });

        return redirect('/customers')->with('success', 'تم تعديل العميل والتأكد من حسابه المحاسبي بنجاح.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $this->authorizePermission('customers.delete', 'ليس لديك صلاحية لحذف العملاء.');

        if ($customer->is_locked) {
            return back()->withErrors([
                'delete' => 'لا يمكن حذف عميل مقفل.',
            ]);
        }

        if (!$this->isAdmin() && $customer->branch_id && (int) $customer->branch_id !== (int) auth()->user()?->branch_id) {
            abort(403, 'ليس لديك صلاحية لحذف هذا العميل.');
        }

        $customer->update([
            'is_deleted' => true,
            'is_active' => false,
        ]);

        return redirect('/customers')->with('success', 'تم حذف العميل منطقيًا بنجاح.');
    }
}
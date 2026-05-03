<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class EmployeeController extends Controller
{
    private function authorizePermission(string $permission, string $message): void
    {
        if (!auth()->user()?->hasPermission($permission)) {
            abort(403, $message);
        }
    }

    private function permissionsGroups(): array
    {
        return [
            [
                'title' => 'لوحة التحكم',
                'items' => [
                    ['key' => 'dashboard.view', 'label' => 'عرض لوحة التحكم'],
                ],
            ],
            [
                'title' => 'الفئات',
                'items' => [
                    ['key' => 'categories.view', 'label' => 'عرض الفئات'],
                    ['key' => 'categories.create', 'label' => 'إضافة فئة'],
                    ['key' => 'categories.update', 'label' => 'تعديل فئة'],
                    ['key' => 'categories.delete', 'label' => 'حذف فئة'],
                ],
            ],
            [
                'title' => 'المنتجات',
                'items' => [
                    ['key' => 'products.view', 'label' => 'عرض المنتجات'],
                    ['key' => 'products.create', 'label' => 'إضافة منتج'],
                    ['key' => 'products.update', 'label' => 'تعديل منتج'],
                    ['key' => 'products.delete', 'label' => 'حذف منتج'],
                ],
            ],
            [
                'title' => 'المخازن',
                'items' => [
                    ['key' => 'warehouses.view', 'label' => 'عرض المخازن'],
                    ['key' => 'warehouses.create', 'label' => 'إضافة مخزن'],
                    ['key' => 'warehouses.update', 'label' => 'تعديل مخزن'],
                    ['key' => 'warehouses.delete', 'label' => 'حذف مخزن'],
                    ['key' => 'warehouses.transfer', 'label' => 'تحويل بين المخازن'],
                ],
            ],
            [
                'title' => 'العملاء',
                'items' => [
                    ['key' => 'customers.view', 'label' => 'عرض العملاء'],
                    ['key' => 'customers.create', 'label' => 'إضافة عميل'],
                    ['key' => 'customers.update', 'label' => 'تعديل عميل'],
                    ['key' => 'customers.delete', 'label' => 'حذف عميل'],
                ],
            ],
            [
                'title' => 'الموردين',
                'items' => [
                    ['key' => 'suppliers.view', 'label' => 'عرض الموردين'],
                    ['key' => 'suppliers.create', 'label' => 'إضافة مورد'],
                    ['key' => 'suppliers.update', 'label' => 'تعديل مورد'],
                    ['key' => 'suppliers.delete', 'label' => 'حذف مورد'],
                ],
            ],
            [
                'title' => 'الموظفين',
                'items' => [
                    ['key' => 'employees.view', 'label' => 'عرض الموظفين'],
                    ['key' => 'employees.create', 'label' => 'إضافة موظف'],
                    ['key' => 'employees.update', 'label' => 'تعديل موظف'],
                    ['key' => 'employees.delete', 'label' => 'حذف موظف'],
                    ['key' => 'employees.permissions', 'label' => 'إدارة صلاحيات الموظفين'],
                ],
            ],
            [
                'title' => 'فواتير الشراء',
                'items' => [
                    ['key' => 'purchase_invoices.view', 'label' => 'عرض فواتير الشراء'],
                    ['key' => 'purchase_invoices.create', 'label' => 'إضافة فاتورة شراء'],
                    ['key' => 'purchase_invoices.update', 'label' => 'تعديل فاتورة شراء'],
                    ['key' => 'purchase_invoices.delete', 'label' => 'حذف فاتورة شراء'],
                ],
            ],
            [
                'title' => 'المبيعات',
                'items' => [
                    ['key' => 'orders.view', 'label' => 'عرض المبيعات'],
                    ['key' => 'orders.create', 'label' => 'إضافة فاتورة بيع'],
                    ['key' => 'orders.update', 'label' => 'تعديل فاتورة بيع'],
                    ['key' => 'orders.delete', 'label' => 'حذف فاتورة بيع'],
                ],
            ],
            [
                'title' => 'المرتجعات',
                'items' => [
                    ['key' => 'returns.view', 'label' => 'عرض المرتجعات'],
                    ['key' => 'returns.create', 'label' => 'إضافة مرتجع'],
                    ['key' => 'returns.delete', 'label' => 'حذف مرتجع'],
                ],
            ],
            [
                'title' => 'السندات والتقارير',
                'items' => [
                    ['key' => 'payments.view', 'label' => 'عرض سندات الصرف'],
                    ['key' => 'payments.create', 'label' => 'إضافة سند صرف'],
                    ['key' => 'receipts.view', 'label' => 'عرض سندات القبض'],
                    ['key' => 'receipts.create', 'label' => 'إضافة سند قبض'],
                    ['key' => 'reports.view', 'label' => 'عرض التقارير'],
                ],
            ],
        ];
    }

    public function index(Request $request)
    {
        $this->authorizePermission('employees.view', 'ليس لديك صلاحية لعرض الموظفين.');

        $search = trim((string) $request->get('search', ''));

        $employees = Employee::query()
            ->with(['branch', 'user.role'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhereHas('branch', function ($branchQuery) use ($search) {
                            $branchQuery->where('name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('username', 'like', "%{$search}%");
                        });
                });
            })
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Employees/Index', [
            'employees' => $employees,
            'filters' => [
                'search' => $search,
            ],
            'permissions' => [
                'canCreate' => auth()->user()?->hasPermission('employees.create') ?? false,
                'canUpdate' => auth()->user()?->hasPermission('employees.update') ?? false,
                'canDelete' => auth()->user()?->hasPermission('employees.delete') ?? false,
                'canManagePermissions' => auth()->user()?->hasPermission('employees.permissions') ?? false,
            ],
        ]);
    }

    public function create()
    {
        $this->authorizePermission('employees.create', 'ليس لديك صلاحية لإضافة موظف.');

        return Inertia::render('Employees/Create', [
            'branches' => Branch::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
            'roles' => Role::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'code', 'permissions']),
            'permissionsGroups' => $this->permissionsGroups(),
            'canManagePermissions' => auth()->user()?->hasPermission('employees.permissions') ?? false,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizePermission('employees.create', 'ليس لديك صلاحية لإضافة موظف.');

        $canManagePermissions = auth()->user()?->hasPermission('employees.permissions') ?? false;

        $data = $request->validate([
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'salary' => ['nullable', 'numeric', 'min:0'],
            'hire_date' => ['nullable', 'date'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string'],

            'create_user' => ['boolean'],
            'username' => ['nullable', 'required_if:create_user,true', 'string', 'max:255', 'unique:users,username'],
            'password' => ['nullable', 'required_if:create_user,true', 'string', 'min:6'],
            'role_id' => ['nullable', 'required_if:create_user,true', 'integer', 'exists:roles,id'],
            'is_locked' => ['boolean'],

            'extra_permissions' => ['nullable', 'array'],
            'extra_permissions.*' => ['string'],
            'denied_permissions' => ['nullable', 'array'],
            'denied_permissions.*' => ['string'],
        ]);

        DB::transaction(function () use ($data, $canManagePermissions) {
            $user = null;

            if (!empty($data['create_user'])) {
                $user = User::create([
                    'branch_id' => $data['branch_id'] ?? null,
                    'role_id' => $data['role_id'] ?? null,
                    'name' => $data['name'],
                    'username' => $data['username'],
                    'password' => $data['password'],
                    'phone' => $data['phone'] ?? null,
                    'email' => $data['email'] ?? null,
                    'address' => $data['address'] ?? null,
                    'salary' => $data['salary'] ?? 0,
                    'extra_permissions' => $canManagePermissions ? array_values($data['extra_permissions'] ?? []) : [],
                    'denied_permissions' => $canManagePermissions ? array_values($data['denied_permissions'] ?? []) : [],
                    'is_active' => (bool) ($data['is_active'] ?? true),
                    'is_deleted' => false,
                    'is_locked' => (bool) ($data['is_locked'] ?? false),
                    'notes' => $data['notes'] ?? null,
                ]);
            }

            Employee::create([
                'user_id' => $user?->id,
                'branch_id' => $data['branch_id'] ?? null,
                'name' => $data['name'],
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'address' => $data['address'] ?? null,
                'salary' => $data['salary'] ?? 0,
                'hire_date' => $data['hire_date'] ?? null,
                'is_active' => (bool) ($data['is_active'] ?? true),
                'notes' => $data['notes'] ?? null,
            ]);
        });

        return redirect()
            ->route('employees.index')
            ->with('success', 'تمت إضافة الموظف بنجاح.');
    }

    public function edit(Employee $employee)
    {
        $this->authorizePermission('employees.update', 'ليس لديك صلاحية لتعديل الموظفين.');

        $employee->load(['branch', 'user.role']);

        return Inertia::render('Employees/Edit', [
            'employee' => $employee,
            'branches' => Branch::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
            'roles' => Role::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'code', 'permissions']),
            'permissionsGroups' => $this->permissionsGroups(),
            'canManagePermissions' => auth()->user()?->hasPermission('employees.permissions') ?? false,
        ]);
    }

    public function update(Request $request, Employee $employee)
    {
        $this->authorizePermission('employees.update', 'ليس لديك صلاحية لتعديل الموظفين.');

        $employee->load('user');
        $userId = $employee->user_id ?? null;
        $canManagePermissions = auth()->user()?->hasPermission('employees.permissions') ?? false;

        $data = $request->validate([
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'salary' => ['nullable', 'numeric', 'min:0'],
            'hire_date' => ['nullable', 'date'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string'],

            'has_user' => ['boolean'],
            'username' => ['nullable', 'required_if:has_user,true', 'string', 'max:255', 'unique:users,username,' . $userId],
            'password' => ['nullable', 'string', 'min:6'],
            'role_id' => ['nullable', 'required_if:has_user,true', 'integer', 'exists:roles,id'],
            'is_locked' => ['boolean'],

            'extra_permissions' => ['nullable', 'array'],
            'extra_permissions.*' => ['string'],
            'denied_permissions' => ['nullable', 'array'],
            'denied_permissions.*' => ['string'],
        ]);

        DB::transaction(function () use ($data, $employee, $canManagePermissions) {
            $employee->update([
                'branch_id' => $data['branch_id'] ?? null,
                'name' => $data['name'],
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'address' => $data['address'] ?? null,
                'salary' => $data['salary'] ?? 0,
                'hire_date' => $data['hire_date'] ?? null,
                'is_active' => (bool) ($data['is_active'] ?? true),
                'notes' => $data['notes'] ?? null,
            ]);

            if (!empty($data['has_user'])) {
                $userData = [
                    'branch_id' => $data['branch_id'] ?? null,
                    'role_id' => $data['role_id'] ?? null,
                    'name' => $data['name'],
                    'username' => $data['username'],
                    'phone' => $data['phone'] ?? null,
                    'email' => $data['email'] ?? null,
                    'address' => $data['address'] ?? null,
                    'salary' => $data['salary'] ?? 0,
                    'is_active' => (bool) ($data['is_active'] ?? true),
                    'is_deleted' => false,
                    'is_locked' => (bool) ($data['is_locked'] ?? false),
                    'notes' => $data['notes'] ?? null,
                ];

                if ($canManagePermissions) {
                    $userData['extra_permissions'] = array_values($data['extra_permissions'] ?? []);
                    $userData['denied_permissions'] = array_values($data['denied_permissions'] ?? []);
                }

                if (!empty($data['password'])) {
                    $userData['password'] = $data['password'];
                }

                if ($employee->user) {
                    $employee->user->update($userData);
                } else {
                    $userData['password'] = $data['password'] ?? '123456';
                    $userData['extra_permissions'] = $canManagePermissions ? array_values($data['extra_permissions'] ?? []) : [];
                    $userData['denied_permissions'] = $canManagePermissions ? array_values($data['denied_permissions'] ?? []) : [];

                    $user = User::create($userData);

                    $employee->update([
                        'user_id' => $user->id,
                    ]);
                }
            } elseif ($employee->user) {
                $employee->user->update([
                    'is_active' => false,
                    'is_deleted' => true,
                ]);

                $employee->update([
                    'user_id' => null,
                ]);
            }
        });

        return redirect()
            ->route('employees.index')
            ->with('success', 'تم تعديل بيانات الموظف بنجاح.');
    }

    public function destroy(Employee $employee)
    {
        $this->authorizePermission('employees.delete', 'ليس لديك صلاحية لحذف الموظفين.');

        DB::transaction(function () use ($employee) {
            if ($employee->user) {
                $employee->user->update([
                    'is_active' => false,
                    'is_deleted' => true,
                ]);
            }

            $employee->delete();
        });

        return redirect()
            ->route('employees.index')
            ->with('success', 'تم حذف الموظف بنجاح.');
    }
}
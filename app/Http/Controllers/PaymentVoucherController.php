<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\ExpenseCategory;
use App\Models\FinancialAccount;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Partner;
use App\Models\PaymentMethod;
use App\Models\PaymentVoucher;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PaymentVoucherController extends Controller
{
    private function authorizePermission(string $permission, string $message): void
    {
        if (!auth()->user()?->hasPermission($permission)) {
            abort(403, $message);
        }
    }

    private function isAdmin(): bool
    {
        $user = auth()->user();

        return $user?->hasPermission('*') === true
            || optional($user?->role)->code === 'admin';
    }

    public function index(Request $request): Response
    {
        $this->authorizePermission('payments.view', 'ليس لديك صلاحية لعرض إيصالات الصرف.');

        $user = auth()->user();
        $search = trim((string) $request->get('search', ''));

        $query = PaymentVoucher::query()
            ->with([
                'branch:id,name',
                'financialAccount:id,name,code,type',
                'paymentMethod:id,name,code',
                'expenseCategory:id,name,code',
            ])
            ->latest('id');

        if (!$this->isAdmin()) {
            $query->where('branch_id', $user->branch_id);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('voucher_number', 'like', "%{$search}%")
                    ->orWhere('beneficiary_type', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return Inertia::render('PaymentVouchers/Index', [
            'paymentVouchers' => $query->paginate(15)->withQueryString(),
            'filters' => [
                'search' => $search,
            ],
            'permissions' => [
                'canCreate' => auth()->user()?->hasPermission('payments.create') ?? false,
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorizePermission('payments.create', 'ليس لديك صلاحية لإضافة إيصال صرف.');

        $user = auth()->user();

        return Inertia::render('PaymentVouchers/Create', [
            'financialAccounts' => FinancialAccount::query()
                ->when(!$this->isAdmin(), fn ($q) => $q->where('branch_id', $user->branch_id))
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'branch_id', 'name', 'code', 'type', 'account_id']),

            'paymentMethods' => PaymentMethod::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'code']),

            'expenseCategories' => ExpenseCategory::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'code', 'expense_account_id']),

            'suppliers' => Supplier::query()
                ->when(!$this->isAdmin(), fn ($q) => $q->where('branch_id', $user->branch_id))
                ->where('is_deleted', false)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'code', 'account_id']),

            'customers' => Customer::query()
                ->when(!$this->isAdmin(), fn ($q) => $q->where('branch_id', $user->branch_id))
                ->where('is_deleted', false)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'code', 'account_id']),

            'employees' => Employee::query()
                ->when(!$this->isAdmin(), fn ($q) => $q->where('branch_id', $user->branch_id))
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'account_id', 'salary']),

            'partners' => Partner::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'current_account_id']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizePermission('payments.create', 'ليس لديك صلاحية لإضافة إيصال صرف.');

        $user = auth()->user();

        $data = $request->validate([
            'voucher_date' => ['required', 'date'],
            'financial_account_id' => ['required', 'integer', 'exists:financial_accounts,id'],
            'payment_method_id' => ['nullable', 'integer', 'exists:payment_methods,id'],
            'beneficiary_type' => ['required', 'string', 'in:supplier,customer,employee,salary,partner,expense'],
            'beneficiary_id' => ['nullable', 'integer'],
            'expense_category_id' => ['nullable', 'integer', 'exists:expense_categories,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string'],
        ]);

        $financialAccount = FinancialAccount::query()
            ->with('account')
            ->findOrFail($data['financial_account_id']);

        if (!$this->isAdmin() && (int) $financialAccount->branch_id !== (int) $user->branch_id) {
            abort(403, 'ليس لديك صلاحية للصرف من هذا الحساب المالي.');
        }

        if (!$financialAccount->account_id) {
            return back()->withErrors([
                'financial_account_id' => 'الحساب المالي المختار غير مربوط بحساب محاسبي.',
            ])->withInput();
        }

        $branchId = $this->isAdmin()
            ? $financialAccount->branch_id
            : $user->branch_id;

        if (!$branchId) {
            return back()->withErrors([
                'financial_account_id' => 'لا يمكن تحديد فرع إيصال الصرف.',
            ])->withInput();
        }

        $debitAccountId = $this->resolveDebitAccountId($data, $branchId);

        if (!$debitAccountId) {
            return back()->withErrors([
                'account_id' => 'لم يتم تحديد الحساب المدين لهذا النوع من الصرف.',
            ])->withInput();
        }

        return DB::transaction(function () use ($data, $user, $branchId, $financialAccount, $debitAccountId) {
            $voucherNumber = $this->generateVoucherNumber();
            $entryNumber = $this->generateJournalEntryNumber();

            $voucher = PaymentVoucher::create([
                'voucher_number' => $voucherNumber,
                'voucher_date' => $data['voucher_date'],
                'branch_id' => $branchId,
                'financial_account_id' => $financialAccount->id,
                'payment_method_id' => $data['payment_method_id'] ?? null,
                'beneficiary_type' => $data['beneficiary_type'],
                'beneficiary_id' => $data['beneficiary_id'] ?? null,
                'expense_category_id' => $data['expense_category_id'] ?? null,
                'account_id' => $debitAccountId,
                'amount' => $data['amount'],
                'description' => $data['description'] ?? null,
                'reference_type' => null,
                'reference_id' => null,
                'journal_entry_id' => null,
                'created_by_user_id' => $user->id,
                'status' => 'posted',
            ]);

            $journalEntry = JournalEntry::create([
                'entry_number' => $entryNumber,
                'entry_date' => $data['voucher_date'],
                'branch_id' => $branchId,
                'description' => 'قيد إيصال صرف رقم ' . $voucher->voucher_number,
                'source_type' => PaymentVoucher::class,
                'source_id' => $voucher->id,
                'created_by_user_id' => $user->id,
                'status' => 'posted',
            ]);

            JournalEntryLine::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $debitAccountId,
                'debit' => $data['amount'],
                'credit' => 0,
                'description' => $data['description'] ?? 'طرف مدين لإيصال صرف',
                'customer_id' => $data['beneficiary_type'] === 'customer' ? ($data['beneficiary_id'] ?? null) : null,
                'supplier_id' => $data['beneficiary_type'] === 'supplier' ? ($data['beneficiary_id'] ?? null) : null,
                'employee_id' => in_array($data['beneficiary_type'], ['employee', 'salary'], true) ? ($data['beneficiary_id'] ?? null) : null,
                'partner_id' => $data['beneficiary_type'] === 'partner' ? ($data['beneficiary_id'] ?? null) : null,
            ]);

            JournalEntryLine::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $financialAccount->account_id,
                'debit' => 0,
                'credit' => $data['amount'],
                'description' => 'صرف من ' . $financialAccount->name,
            ]);

            $voucher->update([
                'journal_entry_id' => $journalEntry->id,
            ]);

            return redirect()
                ->route('payment-vouchers.index')
                ->with('success', 'تم إنشاء إيصال الصرف والقيد المحاسبي بنجاح.');
        });
    }

    private function resolveDebitAccountId(array $data, int $branchId): ?int
    {
        return match ($data['beneficiary_type']) {
            'supplier' => $this->supplierAccountId((int) ($data['beneficiary_id'] ?? 0), $branchId),
            'customer' => $this->customerAccountId((int) ($data['beneficiary_id'] ?? 0), $branchId),
            'employee' => $this->employeeAccountId((int) ($data['beneficiary_id'] ?? 0), $branchId),
            'salary' => $this->salaryExpenseAccountId($data),
            'partner' => $this->partnerCurrentAccountId((int) ($data['beneficiary_id'] ?? 0)),
            'expense' => $this->expenseCategoryAccountId((int) ($data['expense_category_id'] ?? 0)),
            default => null,
        };
    }

    private function supplierAccountId(int $supplierId, int $branchId): ?int
    {
        if ($supplierId <= 0) {
            return null;
        }

        $supplier = Supplier::query()
            ->where('id', $supplierId)
            ->when(!$this->isAdmin(), fn ($q) => $q->where('branch_id', $branchId))
            ->where('is_deleted', false)
            ->first();

        return $supplier?->account_id;
    }

    private function customerAccountId(int $customerId, int $branchId): ?int
    {
        if ($customerId <= 0) {
            return null;
        }

        $customer = Customer::query()
            ->where('id', $customerId)
            ->when(!$this->isAdmin(), fn ($q) => $q->where('branch_id', $branchId))
            ->where('is_deleted', false)
            ->first();

        return $customer?->account_id;
    }

    private function employeeAccountId(int $employeeId, int $branchId): ?int
    {
        if ($employeeId <= 0) {
            return null;
        }

        $employee = Employee::query()
            ->where('id', $employeeId)
            ->when(!$this->isAdmin(), fn ($q) => $q->where('branch_id', $branchId))
            ->first();

        return $employee?->account_id;
    }

    private function partnerCurrentAccountId(int $partnerId): ?int
    {
        if ($partnerId <= 0) {
            return null;
        }

        $partner = Partner::query()->find($partnerId);

        return $partner?->current_account_id;
    }

    private function expenseCategoryAccountId(int $expenseCategoryId): ?int
    {
        if ($expenseCategoryId <= 0) {
            return null;
        }

        $expenseCategory = ExpenseCategory::query()->find($expenseCategoryId);

        return $expenseCategory?->expense_account_id;
    }

    private function salaryExpenseAccountId(array $data): ?int
    {
        if (!empty($data['expense_category_id'])) {
            return $this->expenseCategoryAccountId((int) $data['expense_category_id']);
        }

        return Account::query()
            ->where('code', '5100')
            ->value('id');
    }

    private function generateVoucherNumber(): string
    {
        $prefix = 'PV-' . now()->format('Ymd') . '-';

        $lastId = PaymentVoucher::query()->max('id') + 1;

        return $prefix . str_pad((string) $lastId, 5, '0', STR_PAD_LEFT);
    }

    private function generateJournalEntryNumber(): string
    {
        $prefix = 'JE-' . now()->format('Ymd') . '-';

        $lastId = JournalEntry::query()->max('id') + 1;

        return $prefix . str_pad((string) $lastId, 5, '0', STR_PAD_LEFT);
    }
}
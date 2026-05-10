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
use App\Models\PurchaseInvoice;
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
                'account:id,name,code',
                'createdBy:id,name',
            ])
            ->latest('id');

        if (!$this->isAdmin()) {
            $query->where('branch_id', $user->branch_id);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('voucher_number', 'like', "%{$search}%")
                    ->orWhere('beneficiary_type', 'like', "%{$search}%")
                    ->orWhere('reference_type', 'like', "%{$search}%")
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
                'canUpdate' => $this->isAdmin(),
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorizePermission('payments.create', 'ليس لديك صلاحية لإضافة إيصال صرف.');

        return Inertia::render('PaymentVouchers/Create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizePermission('payments.create', 'ليس لديك صلاحية لإضافة إيصال صرف.');

        $data = $this->validatedData($request);
        $user = auth()->user();

        $financialAccount = FinancialAccount::query()
            ->with('account')
            ->findOrFail($data['financial_account_id']);

        $this->ensureFinancialAccountAccess($financialAccount);

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

        $debitAccountId = $this->resolveDebitAccountId($data, (int) $branchId);

        if (!$debitAccountId) {
            return back()->withErrors([
                'account_id' => 'لم يتم تحديد الحساب المدين لهذا النوع من الصرف.',
            ])->withInput();
        }

        return DB::transaction(function () use ($data, $user, $branchId, $financialAccount, $debitAccountId) {
            $linkedPurchaseInvoice = $this->resolveLinkedPurchaseInvoiceForPayment($data, (int) $branchId);

            if ($linkedPurchaseInvoice) {
                $this->ensureCanPayForPurchaseInvoice(
                    invoice: $linkedPurchaseInvoice,
                    amount: (float) $data['amount']
                );
            }

            $voucher = PaymentVoucher::create([
                'voucher_number' => $this->generateVoucherNumber(),
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
                'reference_type' => $linkedPurchaseInvoice ? 'purchase_invoice' : null,
                'reference_id' => $linkedPurchaseInvoice?->id,
                'journal_entry_id' => null,
                'created_by_user_id' => $user->id,
                'status' => 'posted',
            ]);

            $journalEntry = $this->createJournalEntryForVoucher(
                voucher: $voucher,
                financialAccount: $financialAccount,
                debitAccountId: $debitAccountId,
                data: $data,
                branchId: (int) $branchId,
                userId: (int) $user->id
            );

            $voucher->update([
                'journal_entry_id' => $journalEntry->id,
            ]);

            if ($linkedPurchaseInvoice) {
                $this->refreshPurchaseInvoicePaymentStatus($linkedPurchaseInvoice);
            }

            return redirect()
                ->route('payment-vouchers.index')
                ->with('success', 'تم إنشاء إيصال الصرف والقيد المحاسبي وتحديث فاتورة الشراء المرتبطة بنجاح.');
        });
    }

    public function show(PaymentVoucher $paymentVoucher): Response
    {
        $this->authorizePermission('payments.view', 'ليس لديك صلاحية لعرض إيصال الصرف.');

        $this->ensureVoucherAccess($paymentVoucher);

        $paymentVoucher->load([
            'branch:id,name',
            'financialAccount:id,name,code,type,account_id',
            'paymentMethod:id,name,code',
            'expenseCategory:id,name,code',
            'account:id,name,code',
            'createdBy:id,name',
            'journalEntry.lines.account:id,name,code',
        ]);

        $reverseEntry = JournalEntry::query()
            ->with('lines.account:id,name,code')
            ->where('source_type', PaymentVoucher::class)
            ->where('source_id', $paymentVoucher->id)
            ->where('description', 'like', 'قيد عكسي%')
            ->latest('id')
            ->first();

        $linkedPurchaseInvoice = null;

        if ($paymentVoucher->reference_type === 'purchase_invoice' && $paymentVoucher->reference_id) {
            $linkedPurchaseInvoice = PurchaseInvoice::query()
                ->with(['supplier:id,name,code,phone'])
                ->where('id', $paymentVoucher->reference_id)
                ->first();
        }

        return Inertia::render('PaymentVouchers/Show', [
            'paymentVoucher' => $paymentVoucher,
            'reverseEntry' => $reverseEntry,
            'linkedPurchaseInvoice' => $linkedPurchaseInvoice,
            'beneficiaryName' => $this->beneficiaryName($paymentVoucher),
            'amountInWords' => $this->amountToArabicWords((float) $paymentVoucher->amount),
            'permissions' => [
                'canUpdate' => $this->isAdmin() && $paymentVoucher->status !== 'cancelled',
                'canCancel' => $this->isAdmin() && $paymentVoucher->status === 'posted',
            ],
        ]);
    }

    public function edit(PaymentVoucher $paymentVoucher): Response
    {
        if (!$this->isAdmin()) {
            abort(403, 'تعديل إيصالات الصرف مسموح للأدمن فقط.');
        }

        $this->ensureVoucherAccess($paymentVoucher);

        if ($paymentVoucher->status === 'cancelled') {
            abort(403, 'لا يمكن تعديل إيصال صرف ملغي.');
        }

        $paymentVoucher->load([
            'branch:id,name',
            'financialAccount:id,name,code,type,account_id',
            'paymentMethod:id,name,code',
            'expenseCategory:id,name,code',
            'account:id,name,code',
        ]);

        return Inertia::render('PaymentVouchers/Edit', array_merge(
            [
                'paymentVoucher' => $paymentVoucher,
            ],
            $this->formData()
        ));
    }

    public function update(Request $request, PaymentVoucher $paymentVoucher): RedirectResponse
    {
        if (!$this->isAdmin()) {
            abort(403, 'تعديل إيصالات الصرف مسموح للأدمن فقط.');
        }

        $this->ensureVoucherAccess($paymentVoucher);

        if ($paymentVoucher->status === 'cancelled') {
            abort(403, 'لا يمكن تعديل إيصال صرف ملغي.');
        }

        $data = $this->validatedData($request);
        $user = auth()->user();

        $oldLinkedPurchaseInvoiceId = $paymentVoucher->reference_type === 'purchase_invoice'
            ? $paymentVoucher->reference_id
            : null;

        $financialAccount = FinancialAccount::query()
            ->with('account')
            ->findOrFail($data['financial_account_id']);

        $this->ensureFinancialAccountAccess($financialAccount);

        if (!$financialAccount->account_id) {
            return back()->withErrors([
                'financial_account_id' => 'الحساب المالي المختار غير مربوط بحساب محاسبي.',
            ])->withInput();
        }

        $branchId = $financialAccount->branch_id;

        if (!$branchId) {
            return back()->withErrors([
                'financial_account_id' => 'لا يمكن تحديد فرع إيصال الصرف.',
            ])->withInput();
        }

        $debitAccountId = $this->resolveDebitAccountId($data, (int) $branchId);

        if (!$debitAccountId) {
            return back()->withErrors([
                'account_id' => 'لم يتم تحديد الحساب المدين لهذا النوع من الصرف.',
            ])->withInput();
        }

        return DB::transaction(function () use ($paymentVoucher, $data, $user, $branchId, $financialAccount, $debitAccountId, $oldLinkedPurchaseInvoiceId) {
            $linkedPurchaseInvoice = $this->resolveLinkedPurchaseInvoiceForPayment($data, (int) $branchId);

            if ($linkedPurchaseInvoice) {
                $this->ensureCanPayForPurchaseInvoice(
                    invoice: $linkedPurchaseInvoice,
                    amount: (float) $data['amount'],
                    ignoredVoucherId: $paymentVoucher->id
                );
            }

            $paymentVoucher->update([
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
                'reference_type' => $linkedPurchaseInvoice ? 'purchase_invoice' : null,
                'reference_id' => $linkedPurchaseInvoice?->id,
                'created_by_user_id' => $paymentVoucher->created_by_user_id ?: $user->id,
                'status' => 'posted',
            ]);

            $paymentVoucher->refresh();

            if ($paymentVoucher->journalEntry) {
                $paymentVoucher->journalEntry->lines()->delete();

                $paymentVoucher->journalEntry->update([
                    'entry_date' => $data['voucher_date'],
                    'branch_id' => $branchId,
                    'description' => 'قيد إيصال صرف رقم ' . $paymentVoucher->voucher_number,
                    'source_type' => PaymentVoucher::class,
                    'source_id' => $paymentVoucher->id,
                    'created_by_user_id' => $user->id,
                    'status' => 'posted',
                ]);

                $journalEntry = $paymentVoucher->journalEntry;
            } else {
                $journalEntry = JournalEntry::create([
                    'entry_number' => $this->generateJournalEntryNumber(),
                    'entry_date' => $data['voucher_date'],
                    'branch_id' => $branchId,
                    'description' => 'قيد إيصال صرف رقم ' . $paymentVoucher->voucher_number,
                    'source_type' => PaymentVoucher::class,
                    'source_id' => $paymentVoucher->id,
                    'created_by_user_id' => $user->id,
                    'status' => 'posted',
                ]);

                $paymentVoucher->update([
                    'journal_entry_id' => $journalEntry->id,
                ]);
            }

            $this->createJournalEntryLines(
                journalEntry: $journalEntry,
                financialAccount: $financialAccount,
                debitAccountId: $debitAccountId,
                data: $data
            );

            if ($oldLinkedPurchaseInvoiceId) {
                $oldInvoice = PurchaseInvoice::query()
                    ->where('id', $oldLinkedPurchaseInvoiceId)
                    ->lockForUpdate()
                    ->first();

                if ($oldInvoice) {
                    $this->refreshPurchaseInvoicePaymentStatus($oldInvoice);
                }
            }

            if ($linkedPurchaseInvoice) {
                $this->refreshPurchaseInvoicePaymentStatus($linkedPurchaseInvoice);
            }

            return redirect()
                ->route('payment-vouchers.show', $paymentVoucher)
                ->with('success', 'تم تعديل إيصال الصرف وتحديث القيد وفاتورة الشراء المرتبطة بنجاح.');
        });
    }

    public function cancel(PaymentVoucher $paymentVoucher): RedirectResponse
    {
        if (!$this->isAdmin()) {
            abort(403, 'إلغاء إيصالات الصرف مسموح للأدمن فقط.');
        }

        $this->ensureVoucherAccess($paymentVoucher);

        if ($paymentVoucher->status === 'cancelled') {
            return back()->withErrors([
                'cancel' => 'هذا الإيصال ملغي بالفعل.',
            ]);
        }

        $paymentVoucher->load('journalEntry.lines');

        if (!$paymentVoucher->journalEntry) {
            return back()->withErrors([
                'cancel' => 'لا يمكن إلغاء الإيصال لأنه غير مربوط بقيد محاسبي.',
            ]);
        }

        return DB::transaction(function () use ($paymentVoucher) {
            $user = auth()->user();

            $linkedPurchaseInvoiceId = $paymentVoucher->reference_type === 'purchase_invoice'
                ? $paymentVoucher->reference_id
                : null;

            $reverseEntry = JournalEntry::create([
                'entry_number' => $this->generateJournalEntryNumber(),
                'entry_date' => now(),
                'branch_id' => $paymentVoucher->branch_id,
                'description' => 'قيد عكسي لإلغاء إيصال صرف رقم ' . $paymentVoucher->voucher_number,
                'source_type' => PaymentVoucher::class,
                'source_id' => $paymentVoucher->id,
                'created_by_user_id' => $user->id,
                'status' => 'posted',
            ]);

            foreach ($paymentVoucher->journalEntry->lines as $line) {
                JournalEntryLine::create([
                    'journal_entry_id' => $reverseEntry->id,
                    'account_id' => $line->account_id,
                    'debit' => $line->credit,
                    'credit' => $line->debit,
                    'description' => 'عكس: ' . ($line->description ?? ''),
                    'partner_id' => $line->partner_id,
                    'customer_id' => $line->customer_id,
                    'supplier_id' => $line->supplier_id,
                    'employee_id' => $line->employee_id,
                ]);
            }

            $paymentVoucher->update([
                'status' => 'cancelled',
                'description' => trim(
                    ($paymentVoucher->description ?? '') . "\n" .
                    'تم إلغاء الإيصال بقيد عكسي رقم ' . $reverseEntry->entry_number
                ),
            ]);

            if ($linkedPurchaseInvoiceId) {
                $invoice = PurchaseInvoice::query()
                    ->where('id', $linkedPurchaseInvoiceId)
                    ->lockForUpdate()
                    ->first();

                if ($invoice) {
                    $this->refreshPurchaseInvoicePaymentStatus($invoice);
                }
            }

            return redirect()
                ->route('payment-vouchers.show', $paymentVoucher)
                ->with('success', 'تم إلغاء إيصال الصرف وإنشاء القيد العكسي وتحديث فاتورة الشراء المرتبطة بنجاح.');
        });
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'voucher_date' => ['required', 'date'],
            'financial_account_id' => ['required', 'integer', 'exists:financial_accounts,id'],
            'payment_method_id' => ['nullable', 'integer', 'exists:payment_methods,id'],
            'beneficiary_type' => ['required', 'string', 'in:supplier,customer,employee,salary,partner,expense'],
            'beneficiary_id' => ['nullable', 'integer'],
            'expense_category_id' => ['nullable', 'integer', 'exists:expense_categories,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string'],

            'reference_type' => ['nullable', 'string', 'in:purchase_invoice'],
            'reference_id' => ['nullable', 'integer', 'exists:purchase_invoices,id'],
        ]);
    }

    private function formData(): array
    {
        $user = auth()->user();

        return [
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

            'supplierOpenPurchaseInvoices' => $this->openSupplierPurchaseInvoices(),

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
        ];
    }

    private function openSupplierPurchaseInvoices()
    {
        return PurchaseInvoice::query()
            ->with(['supplier:id,name,code,phone'])
            ->where('is_deleted', false)
            ->whereNotNull('supplier_id')
            ->when(!$this->isAdmin(), fn ($q) => $q->where('branch_id', auth()->user()?->branch_id))
            ->whereRaw('(total_price - paid_amount) > 0')
            ->orderByDesc('invoice_date')
            ->get([
                'id',
                'invoice_number',
                'branch_id',
                'supplier_id',
                'invoice_date',
                'total_price',
                'paid_amount',
                'payment_status',
            ])
            ->map(function (PurchaseInvoice $invoice) {
                return [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'branch_id' => $invoice->branch_id,
                    'supplier_id' => $invoice->supplier_id,
                    'supplier_name' => $invoice->supplier?->name,
                    'invoice_date' => $invoice->invoice_date?->format('Y-m-d'),
                    'total_price' => (float) $invoice->total_price,
                    'paid_amount' => (float) $invoice->paid_amount,
                    'remaining_amount' => max(0, (float) $invoice->total_price - (float) $invoice->paid_amount),
                    'payment_status' => $invoice->payment_status,
                ];
            })
            ->values();
    }

    private function resolveLinkedPurchaseInvoiceForPayment(array $data, int $branchId): ?PurchaseInvoice
    {
        if (($data['reference_type'] ?? null) !== 'purchase_invoice' || empty($data['reference_id'])) {
            return null;
        }

        if ($data['beneficiary_type'] !== 'supplier') {
            abort(422, 'ربط إيصال الصرف بفاتورة شراء متاح فقط عند اختيار الصرف لمورد.');
        }

        $invoice = PurchaseInvoice::query()
            ->where('id', $data['reference_id'])
            ->where('is_deleted', false)
            ->lockForUpdate()
            ->firstOrFail();

        if (!$this->isAdmin() && (int) $invoice->branch_id !== $branchId) {
            abort(403, 'ليس لديك صلاحية للصرف على هذه الفاتورة.');
        }

        if ((int) $invoice->supplier_id !== (int) ($data['beneficiary_id'] ?? 0)) {
            abort(422, 'فاتورة الشراء المختارة لا تخص هذا المورد.');
        }

        return $invoice;
    }

    private function ensureCanPayForPurchaseInvoice(
        PurchaseInvoice $invoice,
        float $amount,
        ?int $ignoredVoucherId = null
    ): void {
        $paymentsQuery = PaymentVoucher::query()
            ->where('status', 'posted')
            ->where('reference_type', 'purchase_invoice')
            ->where('reference_id', $invoice->id);

        if ($ignoredVoucherId) {
            $paymentsQuery->where('id', '!=', $ignoredVoucherId);
        }

        $alreadyPaid = (float) $paymentsQuery->sum('amount');
        $remaining = max(0, (float) $invoice->total_price - $alreadyPaid);

        if ($amount > $remaining) {
            abort(422, 'لا يمكن صرف مبلغ أكبر من المتبقي على فاتورة الشراء. المتبقي: ' . number_format($remaining, 2));
        }
    }

    private function refreshPurchaseInvoicePaymentStatus(PurchaseInvoice $invoice): void
    {
        $invoice->refresh();

        $paymentsTotal = PaymentVoucher::query()
            ->where('status', 'posted')
            ->where('reference_type', 'purchase_invoice')
            ->where('reference_id', $invoice->id)
            ->sum('amount');

        $total = (float) $invoice->total_price;
        $paid = min((float) $paymentsTotal, $total);

        $paymentStatus = match (true) {
            $paid >= $total && $total > 0 => 'paid',
            $paid > 0 => 'partial',
            default => 'due',
        };

        $invoice->update([
            'paid_amount' => $paid,
            'payment_status' => $paymentStatus,
        ]);
    }

    private function ensureFinancialAccountAccess(FinancialAccount $financialAccount): void
    {
        if (!$this->isAdmin() && (int) $financialAccount->branch_id !== (int) auth()->user()->branch_id) {
            abort(403, 'ليس لديك صلاحية لاستخدام هذا الحساب المالي.');
        }
    }

    private function ensureVoucherAccess(PaymentVoucher $paymentVoucher): void
    {
        if (!$this->isAdmin() && (int) $paymentVoucher->branch_id !== (int) auth()->user()->branch_id) {
            abort(403, 'ليس لديك صلاحية للوصول لهذا الإيصال.');
        }
    }

    private function createJournalEntryForVoucher(
        PaymentVoucher $voucher,
        FinancialAccount $financialAccount,
        int $debitAccountId,
        array $data,
        int $branchId,
        int $userId
    ): JournalEntry {
        $journalEntry = JournalEntry::create([
            'entry_number' => $this->generateJournalEntryNumber(),
            'entry_date' => $data['voucher_date'],
            'branch_id' => $branchId,
            'description' => 'قيد إيصال صرف رقم ' . $voucher->voucher_number,
            'source_type' => PaymentVoucher::class,
            'source_id' => $voucher->id,
            'created_by_user_id' => $userId,
            'status' => 'posted',
        ]);

        $this->createJournalEntryLines($journalEntry, $financialAccount, $debitAccountId, $data);

        return $journalEntry;
    }

    private function createJournalEntryLines(
        JournalEntry $journalEntry,
        FinancialAccount $financialAccount,
        int $debitAccountId,
        array $data
    ): void {
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

    private function beneficiaryName(PaymentVoucher $paymentVoucher): string
    {
        if (!$paymentVoucher->beneficiary_id && $paymentVoucher->beneficiary_type !== 'expense') {
            return '-';
        }

        return match ($paymentVoucher->beneficiary_type) {
            'supplier' => Supplier::query()->where('id', $paymentVoucher->beneficiary_id)->value('name') ?? '-',
            'customer' => Customer::query()->where('id', $paymentVoucher->beneficiary_id)->value('name') ?? '-',
            'employee', 'salary' => Employee::query()->where('id', $paymentVoucher->beneficiary_id)->value('name') ?? '-',
            'partner' => Partner::query()->where('id', $paymentVoucher->beneficiary_id)->value('name') ?? '-',
            'expense' => $paymentVoucher->expenseCategory?->name ?? 'مصروف عام',
            default => '-',
        };
    }

    private function supplierAccountId(int $supplierId, int $branchId): ?int
    {
        if ($supplierId <= 0) {
            return null;
        }

        return Supplier::query()
            ->where('id', $supplierId)
            ->when(!$this->isAdmin(), fn ($q) => $q->where('branch_id', $branchId))
            ->where('is_deleted', false)
            ->value('account_id');
    }

    private function customerAccountId(int $customerId, int $branchId): ?int
    {
        if ($customerId <= 0) {
            return null;
        }

        return Customer::query()
            ->where('id', $customerId)
            ->when(!$this->isAdmin(), fn ($q) => $q->where('branch_id', $branchId))
            ->where('is_deleted', false)
            ->value('account_id');
    }

    private function employeeAccountId(int $employeeId, int $branchId): ?int
    {
        if ($employeeId <= 0) {
            return null;
        }

        return Employee::query()
            ->where('id', $employeeId)
            ->when(!$this->isAdmin(), fn ($q) => $q->where('branch_id', $branchId))
            ->value('account_id');
    }

    private function partnerCurrentAccountId(int $partnerId): ?int
    {
        if ($partnerId <= 0) {
            return null;
        }

        return Partner::query()
            ->where('id', $partnerId)
            ->value('current_account_id');
    }

    private function expenseCategoryAccountId(int $expenseCategoryId): ?int
    {
        if ($expenseCategoryId <= 0) {
            return null;
        }

        return ExpenseCategory::query()
            ->where('id', $expenseCategoryId)
            ->value('expense_account_id');
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
        $lastId = ((int) PaymentVoucher::query()->max('id')) + 1;

        return $prefix . str_pad((string) $lastId, 5, '0', STR_PAD_LEFT);
    }

    private function generateJournalEntryNumber(): string
    {
        $prefix = 'JE-' . now()->format('Ymd') . '-';
        $lastId = ((int) JournalEntry::query()->max('id')) + 1;

        return $prefix . str_pad((string) $lastId, 5, '0', STR_PAD_LEFT);
    }

    private function amountToArabicWords(float $amount): string
    {
        $dinars = (int) floor($amount);
        $dirhams = (int) round(($amount - $dinars) * 100);

        $words = 'فقط ' . $this->numberToArabicWords($dinars) . ' دينار';

        if ($dirhams > 0) {
            $words .= ' و' . $this->numberToArabicWords($dirhams) . ' درهم';
        }

        return $words . ' لا غير';
    }

    private function numberToArabicWords(int $number): string
    {
        if ($number === 0) {
            return 'صفر';
        }

        $ones = [
            0 => '',
            1 => 'واحد',
            2 => 'اثنان',
            3 => 'ثلاثة',
            4 => 'أربعة',
            5 => 'خمسة',
            6 => 'ستة',
            7 => 'سبعة',
            8 => 'ثمانية',
            9 => 'تسعة',
            10 => 'عشرة',
            11 => 'أحد عشر',
            12 => 'اثنا عشر',
            13 => 'ثلاثة عشر',
            14 => 'أربعة عشر',
            15 => 'خمسة عشر',
            16 => 'ستة عشر',
            17 => 'سبعة عشر',
            18 => 'ثمانية عشر',
            19 => 'تسعة عشر',
        ];

        $tens = [
            20 => 'عشرون',
            30 => 'ثلاثون',
            40 => 'أربعون',
            50 => 'خمسون',
            60 => 'ستون',
            70 => 'سبعون',
            80 => 'ثمانون',
            90 => 'تسعون',
        ];

        $hundreds = [
            100 => 'مائة',
            200 => 'مائتان',
            300 => 'ثلاثمائة',
            400 => 'أربعمائة',
            500 => 'خمسمائة',
            600 => 'ستمائة',
            700 => 'سبعمائة',
            800 => 'ثمانمائة',
            900 => 'تسعمائة',
        ];

        if ($number < 20) {
            return $ones[$number];
        }

        if ($number < 100) {
            $unit = $number % 10;
            $ten = $number - $unit;

            return $unit
                ? $ones[$unit] . ' و' . $tens[$ten]
                : $tens[$ten];
        }

        if ($number < 1000) {
            $hundred = intdiv($number, 100) * 100;
            $rest = $number % 100;

            return $rest
                ? $hundreds[$hundred] . ' و' . $this->numberToArabicWords($rest)
                : $hundreds[$hundred];
        }

        if ($number < 1000000) {
            $thousands = intdiv($number, 1000);
            $rest = $number % 1000;

            $thousandText = match (true) {
                $thousands === 1 => 'ألف',
                $thousands === 2 => 'ألفان',
                $thousands >= 3 && $thousands <= 10 => $this->numberToArabicWords($thousands) . ' آلاف',
                default => $this->numberToArabicWords($thousands) . ' ألف',
            };

            return $rest
                ? $thousandText . ' و' . $this->numberToArabicWords($rest)
                : $thousandText;
        }

        $millions = intdiv($number, 1000000);
        $rest = $number % 1000000;

        $millionText = match (true) {
            $millions === 1 => 'مليون',
            $millions === 2 => 'مليونان',
            $millions >= 3 && $millions <= 10 => $this->numberToArabicWords($millions) . ' ملايين',
            default => $this->numberToArabicWords($millions) . ' مليون',
        };

        return $rest
            ? $millionText . ' و' . $this->numberToArabicWords($rest)
            : $millionText;
    }
}
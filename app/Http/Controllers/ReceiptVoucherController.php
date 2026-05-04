<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\FinancialAccount;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Partner;
use App\Models\PaymentMethod;
use App\Models\ReceiptVoucher;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ReceiptVoucherController extends Controller
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
        $this->authorizePermission('receipts.view', 'ليس لديك صلاحية لعرض إيصالات القبض.');

        $user = auth()->user();
        $search = trim((string) $request->get('search', ''));

        $query = ReceiptVoucher::query()
            ->with([
                'branch:id,name',
                'financialAccount:id,name,code,type',
                'paymentMethod:id,name,code',
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
                    ->orWhere('received_from_type', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return Inertia::render('ReceiptVouchers/Index', [
            'receiptVouchers' => $query->paginate(15)->withQueryString(),
            'filters' => [
                'search' => $search,
            ],
            'permissions' => [
                'canCreate' => auth()->user()?->hasPermission('receipts.create') ?? false,
                'canUpdate' => $this->isAdmin(),
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorizePermission('receipts.create', 'ليس لديك صلاحية لإضافة إيصال قبض.');

        return Inertia::render('ReceiptVouchers/Create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizePermission('receipts.create', 'ليس لديك صلاحية لإضافة إيصال قبض.');

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
                'financial_account_id' => 'لا يمكن تحديد فرع إيصال القبض.',
            ])->withInput();
        }

        $creditAccountId = $this->resolveCreditAccountId($data, (int) $branchId);

        if (!$creditAccountId) {
            return back()->withErrors([
                'account_id' => 'لم يتم تحديد الحساب الدائن لهذا النوع من القبض.',
            ])->withInput();
        }

        return DB::transaction(function () use ($data, $user, $branchId, $financialAccount, $creditAccountId) {
            $voucher = ReceiptVoucher::create([
                'voucher_number' => $this->generateVoucherNumber(),
                'voucher_date' => $data['voucher_date'],
                'branch_id' => $branchId,
                'financial_account_id' => $financialAccount->id,
                'payment_method_id' => $data['payment_method_id'] ?? null,
                'received_from_type' => $data['received_from_type'],
                'received_from_id' => $data['received_from_id'] ?? null,
                'account_id' => $creditAccountId,
                'amount' => $data['amount'],
                'description' => $data['description'] ?? null,
                'reference_type' => null,
                'reference_id' => null,
                'journal_entry_id' => null,
                'created_by_user_id' => $user->id,
                'status' => 'posted',
            ]);

            $journalEntry = $this->createJournalEntryForVoucher(
                voucher: $voucher,
                financialAccount: $financialAccount,
                creditAccountId: $creditAccountId,
                data: $data,
                branchId: (int) $branchId,
                userId: (int) $user->id
            );

            $voucher->update([
                'journal_entry_id' => $journalEntry->id,
            ]);

            return redirect()
                ->route('receipt-vouchers.index')
                ->with('success', 'تم إنشاء إيصال القبض والقيد المحاسبي بنجاح.');
        });
    }

    public function show(ReceiptVoucher $receiptVoucher): Response
    {
        $this->authorizePermission('receipts.view', 'ليس لديك صلاحية لعرض إيصال القبض.');

        $this->ensureVoucherAccess($receiptVoucher);

        $receiptVoucher->load([
            'branch:id,name',
            'financialAccount:id,name,code,type,account_id',
            'paymentMethod:id,name,code',
            'account:id,name,code',
            'createdBy:id,name',
            'journalEntry.lines.account:id,name,code',
        ]);

        $reverseEntry = JournalEntry::query()
            ->with('lines.account:id,name,code')
            ->where('source_type', ReceiptVoucher::class)
            ->where('source_id', $receiptVoucher->id)
            ->where('description', 'like', 'قيد عكسي%')
            ->latest('id')
            ->first();

        return Inertia::render('ReceiptVouchers/Show', [
            'receiptVoucher' => $receiptVoucher,
            'reverseEntry' => $reverseEntry,
            'receivedFromName' => $this->receivedFromName($receiptVoucher),
            'amountInWords' => $this->amountToArabicWords((float) $receiptVoucher->amount),
            'permissions' => [
                'canUpdate' => $this->isAdmin() && $receiptVoucher->status !== 'cancelled',
                'canCancel' => $this->isAdmin() && $receiptVoucher->status === 'posted',
            ],
        ]);
    }

    public function edit(ReceiptVoucher $receiptVoucher): Response
    {
        if (!$this->isAdmin()) {
            abort(403, 'تعديل إيصالات القبض مسموح للأدمن فقط.');
        }

        $this->ensureVoucherAccess($receiptVoucher);

        if ($receiptVoucher->status === 'cancelled') {
            abort(403, 'لا يمكن تعديل إيصال قبض ملغي.');
        }

        $receiptVoucher->load([
            'branch:id,name',
            'financialAccount:id,name,code,type,account_id',
            'paymentMethod:id,name,code',
            'account:id,name,code',
        ]);

        return Inertia::render('ReceiptVouchers/Edit', array_merge(
            [
                'receiptVoucher' => $receiptVoucher,
            ],
            $this->formData()
        ));
    }

    public function update(Request $request, ReceiptVoucher $receiptVoucher): RedirectResponse
    {
        if (!$this->isAdmin()) {
            abort(403, 'تعديل إيصالات القبض مسموح للأدمن فقط.');
        }

        $this->ensureVoucherAccess($receiptVoucher);

        if ($receiptVoucher->status === 'cancelled') {
            abort(403, 'لا يمكن تعديل إيصال قبض ملغي.');
        }

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

        $branchId = $financialAccount->branch_id;

        if (!$branchId) {
            return back()->withErrors([
                'financial_account_id' => 'لا يمكن تحديد فرع إيصال القبض.',
            ])->withInput();
        }

        $creditAccountId = $this->resolveCreditAccountId($data, (int) $branchId);

        if (!$creditAccountId) {
            return back()->withErrors([
                'account_id' => 'لم يتم تحديد الحساب الدائن لهذا النوع من القبض.',
            ])->withInput();
        }

        return DB::transaction(function () use ($receiptVoucher, $data, $user, $branchId, $financialAccount, $creditAccountId) {
            $receiptVoucher->update([
                'voucher_date' => $data['voucher_date'],
                'branch_id' => $branchId,
                'financial_account_id' => $financialAccount->id,
                'payment_method_id' => $data['payment_method_id'] ?? null,
                'received_from_type' => $data['received_from_type'],
                'received_from_id' => $data['received_from_id'] ?? null,
                'account_id' => $creditAccountId,
                'amount' => $data['amount'],
                'description' => $data['description'] ?? null,
                'created_by_user_id' => $receiptVoucher->created_by_user_id ?: $user->id,
                'status' => 'posted',
            ]);

            $receiptVoucher->refresh();

            if ($receiptVoucher->journalEntry) {
                $receiptVoucher->journalEntry->lines()->delete();

                $receiptVoucher->journalEntry->update([
                    'entry_date' => $data['voucher_date'],
                    'branch_id' => $branchId,
                    'description' => 'قيد إيصال قبض رقم ' . $receiptVoucher->voucher_number,
                    'source_type' => ReceiptVoucher::class,
                    'source_id' => $receiptVoucher->id,
                    'created_by_user_id' => $user->id,
                    'status' => 'posted',
                ]);

                $journalEntry = $receiptVoucher->journalEntry;
            } else {
                $journalEntry = JournalEntry::create([
                    'entry_number' => $this->generateJournalEntryNumber(),
                    'entry_date' => $data['voucher_date'],
                    'branch_id' => $branchId,
                    'description' => 'قيد إيصال قبض رقم ' . $receiptVoucher->voucher_number,
                    'source_type' => ReceiptVoucher::class,
                    'source_id' => $receiptVoucher->id,
                    'created_by_user_id' => $user->id,
                    'status' => 'posted',
                ]);

                $receiptVoucher->update([
                    'journal_entry_id' => $journalEntry->id,
                ]);
            }

            $this->createJournalEntryLines(
                journalEntry: $journalEntry,
                financialAccount: $financialAccount,
                creditAccountId: $creditAccountId,
                data: $data
            );

            return redirect()
                ->route('receipt-vouchers.show', $receiptVoucher)
                ->with('success', 'تم تعديل إيصال القبض وتحديث القيد المحاسبي بنجاح.');
        });
    }

    public function cancel(ReceiptVoucher $receiptVoucher): RedirectResponse
    {
        if (!$this->isAdmin()) {
            abort(403, 'إلغاء إيصالات القبض مسموح للأدمن فقط.');
        }

        $this->ensureVoucherAccess($receiptVoucher);

        if ($receiptVoucher->status === 'cancelled') {
            return back()->withErrors([
                'cancel' => 'هذا الإيصال ملغي بالفعل.',
            ]);
        }

        $receiptVoucher->load('journalEntry.lines');

        if (!$receiptVoucher->journalEntry) {
            return back()->withErrors([
                'cancel' => 'لا يمكن إلغاء الإيصال لأنه غير مربوط بقيد محاسبي.',
            ]);
        }

        return DB::transaction(function () use ($receiptVoucher) {
            $user = auth()->user();

            $reverseEntry = JournalEntry::create([
                'entry_number' => $this->generateJournalEntryNumber(),
                'entry_date' => now(),
                'branch_id' => $receiptVoucher->branch_id,
                'description' => 'قيد عكسي لإلغاء إيصال قبض رقم ' . $receiptVoucher->voucher_number,
                'source_type' => ReceiptVoucher::class,
                'source_id' => $receiptVoucher->id,
                'created_by_user_id' => $user->id,
                'status' => 'posted',
            ]);

            foreach ($receiptVoucher->journalEntry->lines as $line) {
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

            $receiptVoucher->update([
                'status' => 'cancelled',
                'description' => trim(
                    ($receiptVoucher->description ?? '') . "\n" .
                    'تم إلغاء الإيصال بقيد عكسي رقم ' . $reverseEntry->entry_number
                ),
            ]);

            return redirect()
                ->route('receipt-vouchers.show', $receiptVoucher)
                ->with('success', 'تم إلغاء إيصال القبض وإنشاء القيد العكسي بنجاح.');
        });
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'voucher_date' => ['required', 'date'],
            'financial_account_id' => ['required', 'integer', 'exists:financial_accounts,id'],
            'payment_method_id' => ['nullable', 'integer', 'exists:payment_methods,id'],
            'received_from_type' => ['required', 'string', 'in:customer,supplier,employee,partner,other'],
            'received_from_id' => ['nullable', 'integer'],
            'account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string'],
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

            'customers' => Customer::query()
                ->when(!$this->isAdmin(), fn ($q) => $q->where('branch_id', $user->branch_id))
                ->where('is_deleted', false)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'code', 'account_id']),

            'suppliers' => Supplier::query()
                ->when(!$this->isAdmin(), fn ($q) => $q->where('branch_id', $user->branch_id))
                ->where('is_deleted', false)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'code', 'account_id']),

            'employees' => Employee::query()
                ->when(!$this->isAdmin(), fn ($q) => $q->where('branch_id', $user->branch_id))
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'account_id']),

            'partners' => Partner::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'current_account_id']),

            'accounts' => Account::query()
                ->where('is_active', true)
                ->where('is_group', false)
                ->orderBy('code')
                ->get(['id', 'code', 'name', 'type', 'nature']),
        ];
    }

    private function ensureFinancialAccountAccess(FinancialAccount $financialAccount): void
    {
        if (!$this->isAdmin() && (int) $financialAccount->branch_id !== (int) auth()->user()->branch_id) {
            abort(403, 'ليس لديك صلاحية لاستخدام هذا الحساب المالي.');
        }
    }

    private function ensureVoucherAccess(ReceiptVoucher $receiptVoucher): void
    {
        if (!$this->isAdmin() && (int) $receiptVoucher->branch_id !== (int) auth()->user()->branch_id) {
            abort(403, 'ليس لديك صلاحية للوصول لهذا الإيصال.');
        }
    }

    private function createJournalEntryForVoucher(
        ReceiptVoucher $voucher,
        FinancialAccount $financialAccount,
        int $creditAccountId,
        array $data,
        int $branchId,
        int $userId
    ): JournalEntry {
        $journalEntry = JournalEntry::create([
            'entry_number' => $this->generateJournalEntryNumber(),
            'entry_date' => $data['voucher_date'],
            'branch_id' => $branchId,
            'description' => 'قيد إيصال قبض رقم ' . $voucher->voucher_number,
            'source_type' => ReceiptVoucher::class,
            'source_id' => $voucher->id,
            'created_by_user_id' => $userId,
            'status' => 'posted',
        ]);

        $this->createJournalEntryLines($journalEntry, $financialAccount, $creditAccountId, $data);

        return $journalEntry;
    }

    private function createJournalEntryLines(
        JournalEntry $journalEntry,
        FinancialAccount $financialAccount,
        int $creditAccountId,
        array $data
    ): void {
        JournalEntryLine::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $financialAccount->account_id,
            'debit' => $data['amount'],
            'credit' => 0,
            'description' => 'قبض في ' . $financialAccount->name,
        ]);

        JournalEntryLine::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $creditAccountId,
            'debit' => 0,
            'credit' => $data['amount'],
            'description' => $data['description'] ?? 'طرف دائن لإيصال قبض',
            'customer_id' => $data['received_from_type'] === 'customer' ? ($data['received_from_id'] ?? null) : null,
            'supplier_id' => $data['received_from_type'] === 'supplier' ? ($data['received_from_id'] ?? null) : null,
            'employee_id' => $data['received_from_type'] === 'employee' ? ($data['received_from_id'] ?? null) : null,
            'partner_id' => $data['received_from_type'] === 'partner' ? ($data['received_from_id'] ?? null) : null,
        ]);
    }

    private function resolveCreditAccountId(array $data, int $branchId): ?int
    {
        return match ($data['received_from_type']) {
            'customer' => $this->customerAccountId((int) ($data['received_from_id'] ?? 0), $branchId),
            'supplier' => $this->supplierAccountId((int) ($data['received_from_id'] ?? 0), $branchId),
            'employee' => $this->employeeAccountId((int) ($data['received_from_id'] ?? 0), $branchId),
            'partner' => $this->partnerCurrentAccountId((int) ($data['received_from_id'] ?? 0)),
            'other' => !empty($data['account_id']) ? (int) $data['account_id'] : null,
            default => null,
        };
    }

    private function receivedFromName(ReceiptVoucher $receiptVoucher): string
    {
        if (!$receiptVoucher->received_from_id && $receiptVoucher->received_from_type !== 'other') {
            return '-';
        }

        return match ($receiptVoucher->received_from_type) {
            'customer' => Customer::query()->where('id', $receiptVoucher->received_from_id)->value('name') ?? '-',
            'supplier' => Supplier::query()->where('id', $receiptVoucher->received_from_id)->value('name') ?? '-',
            'employee' => Employee::query()->where('id', $receiptVoucher->received_from_id)->value('name') ?? '-',
            'partner' => Partner::query()->where('id', $receiptVoucher->received_from_id)->value('name') ?? '-',
            'other' => $receiptVoucher->account?->name ?? 'قبض آخر',
            default => '-',
        };
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

    private function generateVoucherNumber(): string
    {
        $prefix = 'RV-' . now()->format('Ymd') . '-';
        $lastId = ((int) ReceiptVoucher::query()->max('id')) + 1;

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

            return $unit ? $ones[$unit] . ' و' . $tens[$ten] : $tens[$ten];
        }

        if ($number < 1000) {
            $hundred = intdiv($number, 100) * 100;
            $rest = $number % 100;

            return $rest ? $hundreds[$hundred] . ' و' . $this->numberToArabicWords($rest) : $hundreds[$hundred];
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

            return $rest ? $thousandText . ' و' . $this->numberToArabicWords($rest) : $thousandText;
        }

        $millions = intdiv($number, 1000000);
        $rest = $number % 1000000;

        $millionText = match (true) {
            $millions === 1 => 'مليون',
            $millions === 2 => 'مليونان',
            $millions >= 3 && $millions <= 10 => $this->numberToArabicWords($millions) . ' ملايين',
            default => $this->numberToArabicWords($millions) . ' مليون',
        };

        return $rest ? $millionText . ' و' . $this->numberToArabicWords($rest) : $millionText;
    }
}
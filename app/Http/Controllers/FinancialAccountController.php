<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Branch;
use App\Models\FinancialAccount;
use App\Models\PaymentVoucher;
use App\Models\ReceiptVoucher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FinancialAccountController extends Controller
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

    private function ensureAccess(FinancialAccount $financialAccount): void
    {
        if (!$this->isAdmin() && (int) $financialAccount->branch_id !== (int) auth()->user()->branch_id) {
            abort(403, 'ليس لديك صلاحية للوصول لهذا الحساب المالي.');
        }
    }

    public function index(Request $request): Response
    {
        $this->authorizePermission('financial_accounts.view', 'ليس لديك صلاحية لعرض الخزائن والبنوك.');

        $user = auth()->user();

        $search = trim((string) $request->get('search', ''));
        $type = $request->get('type');
        $branchId = $request->get('branch_id');
        $status = $request->get('status');

        $query = FinancialAccount::query()
            ->with(['branch:id,name,code', 'account:id,name,code'])
            ->withSum(['receiptVouchers as receipts_total' => function ($q) {
                $q->where('status', '!=', 'cancelled');
            }], 'amount')
            ->withSum(['paymentVouchers as payments_total' => function ($q) {
                $q->where('status', '!=', 'cancelled');
            }], 'amount')
            ->latest('id');

        if (!$this->isAdmin()) {
            $query->where('branch_id', $user->branch_id);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        if (!empty($type)) {
            $query->where('type', $type);
        }

        if (!empty($branchId) && $this->isAdmin()) {
            $query->where('branch_id', $branchId);
        }

        if ($status !== null && $status !== '') {
            $query->where('is_active', (bool) $status);
        }

        $accounts = $query->paginate(15)->withQueryString();

        $accounts->getCollection()->transform(function ($account) {
            $receipts = (float) ($account->receipts_total ?? 0);
            $payments = (float) ($account->payments_total ?? 0);
            $opening = (float) ($account->opening_balance ?? 0);

            $account->current_balance = $opening + $receipts - $payments;

            return $account;
        });

        return Inertia::render('FinancialAccounts/Index', [
            'financialAccounts' => $accounts,
            'filters' => [
                'search' => $search,
                'type' => $type,
                'branch_id' => $branchId,
                'status' => $status,
            ],
            'branches' => Branch::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'code']),
            'permissions' => [
                'canCreate' => auth()->user()?->hasPermission('financial_accounts.create') ?? false,
                'canUpdate' => auth()->user()?->hasPermission('financial_accounts.update') ?? false,
            ],
            'isAdmin' => $this->isAdmin(),
        ]);
    }

    public function create(): Response
    {
        $this->authorizePermission('financial_accounts.create', 'ليس لديك صلاحية لإضافة خزينة أو حساب بنكي.');

        return Inertia::render('FinancialAccounts/Create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizePermission('financial_accounts.create', 'ليس لديك صلاحية لإضافة خزينة أو حساب بنكي.');

        $data = $this->validatedData($request);

        if (!$this->isAdmin()) {
            $data['branch_id'] = auth()->user()->branch_id;
        }

        FinancialAccount::create($data);

        return redirect()
            ->route('financial-accounts.index')
            ->with('success', 'تم إنشاء الحساب المالي بنجاح.');
    }

    public function show(Request $request, FinancialAccount $financialAccount): Response
    {
        $this->authorizePermission('financial_accounts.view', 'ليس لديك صلاحية لعرض تفاصيل الخزينة.');

        $this->ensureAccess($financialAccount);

        $financialAccount->load(['branch:id,name,code', 'account:id,name,code']);

        $filters = [
            'search' => trim((string) $request->get('search', '')),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'type' => $request->get('type'),
        ];

        $receiptQuery = ReceiptVoucher::query()
            ->with(['paymentMethod:id,name,code', 'createdBy:id,name'])
            ->where('financial_account_id', $financialAccount->id)
            ->where('status', '!=', 'cancelled');

        $paymentQuery = PaymentVoucher::query()
            ->with(['paymentMethod:id,name,code', 'createdBy:id,name'])
            ->where('financial_account_id', $financialAccount->id)
            ->where('status', '!=', 'cancelled');

        if (!empty($filters['date_from'])) {
            $receiptQuery->whereDate('voucher_date', '>=', $filters['date_from']);
            $paymentQuery->whereDate('voucher_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $receiptQuery->whereDate('voucher_date', '<=', $filters['date_to']);
            $paymentQuery->whereDate('voucher_date', '<=', $filters['date_to']);
        }

        if ($filters['search'] !== '') {
            $search = $filters['search'];

            $receiptQuery->where(function ($q) use ($search) {
                $q->where('voucher_number', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('received_from_type', 'like', "%{$search}%");
            });

            $paymentQuery->where(function ($q) use ($search) {
                $q->where('voucher_number', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('beneficiary_type', 'like', "%{$search}%");
            });
        }

        $movements = collect();

        if ($filters['type'] !== 'payment') {
            $receiptQuery->get()->each(function ($voucher) use ($movements) {
                $movements->push([
                    'id' => 'receipt-' . $voucher->id,
                    'model_id' => $voucher->id,
                    'date' => $voucher->voucher_date,
                    'number' => $voucher->voucher_number,
                    'type' => 'receipt',
                    'type_label' => 'قبض',
                    'description' => $voucher->description,
                    'method' => $voucher->paymentMethod?->name,
                    'created_by' => $voucher->createdBy?->name,
                    'in' => (float) $voucher->amount,
                    'out' => 0,
                    'url' => route('receipt-vouchers.show', $voucher->id),
                ]);
            });
        }

        if ($filters['type'] !== 'receipt') {
            $paymentQuery->get()->each(function ($voucher) use ($movements) {
                $movements->push([
                    'id' => 'payment-' . $voucher->id,
                    'model_id' => $voucher->id,
                    'date' => $voucher->voucher_date,
                    'number' => $voucher->voucher_number,
                    'type' => 'payment',
                    'type_label' => 'صرف',
                    'description' => $voucher->description,
                    'method' => $voucher->paymentMethod?->name,
                    'created_by' => $voucher->createdBy?->name,
                    'in' => 0,
                    'out' => (float) $voucher->amount,
                    'url' => route('payment-vouchers.show', $voucher->id),
                ]);
            });
        }

        $movements = $movements
            ->sortBy([
                ['date', 'asc'],
                ['number', 'asc'],
            ])
            ->values();

        $runningBalance = (float) $financialAccount->opening_balance;

        $movements = $movements->map(function ($movement) use (&$runningBalance) {
            $runningBalance += (float) $movement['in'];
            $runningBalance -= (float) $movement['out'];
            $movement['balance'] = $runningBalance;

            return $movement;
        });

        $totals = [
            'opening_balance' => (float) $financialAccount->opening_balance,
            'total_in' => (float) $movements->sum('in'),
            'total_out' => (float) $movements->sum('out'),
            'current_balance' => $runningBalance,
        ];

        return Inertia::render('FinancialAccounts/Show', [
            'financialAccount' => $financialAccount,
            'movements' => $movements,
            'filters' => $filters,
            'totals' => $totals,
        ]);
    }

    public function edit(FinancialAccount $financialAccount): Response
    {
        $this->authorizePermission('financial_accounts.update', 'ليس لديك صلاحية لتعديل الخزينة أو الحساب البنكي.');

        $this->ensureAccess($financialAccount);

        return Inertia::render('FinancialAccounts/Edit', array_merge(
            ['financialAccount' => $financialAccount],
            $this->formData()
        ));
    }

    public function update(Request $request, FinancialAccount $financialAccount): RedirectResponse
    {
        $this->authorizePermission('financial_accounts.update', 'ليس لديك صلاحية لتعديل الخزينة أو الحساب البنكي.');

        $this->ensureAccess($financialAccount);

        $data = $this->validatedData($request, $financialAccount->id);

        if (!$this->isAdmin()) {
            $data['branch_id'] = auth()->user()->branch_id;
        }

        $financialAccount->update($data);

        return redirect()
            ->route('financial-accounts.show', $financialAccount)
            ->with('success', 'تم تعديل الحساب المالي بنجاح.');
    }

    private function validatedData(Request $request, ?int $ignoreId = null): array
    {
        $uniqueCode = 'unique:financial_accounts,code';

        if ($ignoreId) {
            $uniqueCode .= ',' . $ignoreId;
        }

        return $request->validate([
            'branch_id' => [$this->isAdmin() ? 'required' : 'nullable', 'integer', 'exists:branches,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:100', $uniqueCode],
            'type' => ['required', 'string', 'in:cash,bank'],
            'account_id' => ['required', 'integer', 'exists:accounts,id'],
            'currency' => ['required', 'string', 'max:10'],
            'opening_balance' => ['required', 'numeric'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string'],
        ]);
    }

    private function formData(): array
    {
        return [
            'branches' => Branch::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'code']),

            'accounts' => Account::query()
                ->where('is_active', true)
                ->where('is_group', false)
                ->orderBy('code')
                ->get(['id', 'code', 'name', 'type', 'nature']),

            'isAdmin' => $this->isAdmin(),
            'authBranchId' => auth()->user()?->branch_id,
        ];
    }
}
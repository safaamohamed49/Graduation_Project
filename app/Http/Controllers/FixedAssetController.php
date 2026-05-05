<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Branch;
use App\Models\FinancialAccount;
use App\Models\FixedAsset;
use App\Models\FixedAssetDepreciation;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class FixedAssetController extends Controller
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

    private function ensureAccess(FixedAsset $fixedAsset): void
    {
        if (!$this->isAdmin() && (int) $fixedAsset->branch_id !== (int) auth()->user()->branch_id) {
            abort(403, 'ليس لديك صلاحية للوصول لهذا الأصل.');
        }
    }

    public function index(Request $request): Response
    {
        $this->authorizePermission('fixed_assets.view', 'ليس لديك صلاحية لعرض الأصول.');

        $user = auth()->user();

        $search = trim((string) $request->get('search', ''));
        $branchId = $request->get('branch_id');
        $status = $request->get('status');

        $query = FixedAsset::query()
            ->with([
                'branch:id,name,code',
                'assetAccount:id,name,code',
                'depreciationAccount:id,name,code',
                'depreciationExpenseAccount:id,name,code',
            ])
            ->withSum('depreciations as total_depreciation_sum', 'amount')
            ->latest('id');

        if (!$this->isAdmin()) {
            $query->where('branch_id', $user->branch_id);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('asset_code', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        if (!empty($branchId) && $this->isAdmin()) {
            $query->where('branch_id', $branchId);
        }

        if ($status !== null && $status !== '') {
            $query->where('is_active', (bool) $status);
        }

        $assets = $query->paginate(15)->withQueryString();

        $assets->getCollection()->transform(function ($asset) {
            $totalDepreciation = (float) ($asset->total_depreciation_sum ?? 0);

            $asset->total_depreciation = $totalDepreciation;
            $asset->depreciable_value = max(0, (float) $asset->purchase_value - (float) $asset->salvage_value);
            $asset->book_value = max(0, (float) $asset->purchase_value - $totalDepreciation);
            $asset->monthly_depreciation = $asset->useful_life_years > 0
                ? round($asset->depreciable_value / ($asset->useful_life_years * 12), 2)
                : 0;

            return $asset;
        });

        return Inertia::render('FixedAssets/Index', [
            'assets' => $assets,
            'filters' => [
                'search' => $search,
                'branch_id' => $branchId,
                'status' => $status,
            ],
            'branches' => Branch::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'code']),
            'permissions' => [
                'canCreate' => auth()->user()?->hasPermission('fixed_assets.create') ?? false,
                'canUpdate' => auth()->user()?->hasPermission('fixed_assets.update') ?? false,
                'canReport' => auth()->user()?->hasPermission('fixed_assets.report') ?? false,
            ],
            'isAdmin' => $this->isAdmin(),
        ]);
    }

    public function report(Request $request): Response
    {
        $this->authorizePermission('fixed_assets.report', 'ليس لديك صلاحية لعرض تقرير الأصول.');

        $user = auth()->user();

        $filters = [
            'search' => trim((string) $request->get('search', '')),
            'branch_id' => $request->get('branch_id'),
            'status' => $request->get('status'),
        ];

        $query = FixedAsset::query()
            ->with([
                'branch:id,name,code',
                'financialAccount:id,name,code',
                'assetAccount:id,name,code',
                'depreciationAccount:id,name,code',
                'depreciationExpenseAccount:id,name,code',
            ])
            ->withSum('depreciations as total_depreciation_sum', 'amount')
            ->orderBy('asset_code');

        if (!$this->isAdmin()) {
            $query->where('branch_id', $user->branch_id);
        }

        if ($filters['search'] !== '') {
            $search = $filters['search'];

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('asset_code', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['branch_id']) && $this->isAdmin()) {
            $query->where('branch_id', $filters['branch_id']);
        }

        if ($filters['status'] !== null && $filters['status'] !== '') {
            $query->where('is_active', (bool) $filters['status']);
        }

        $assets = $query->get()->map(function ($asset) {
            $totalDepreciation = (float) ($asset->total_depreciation_sum ?? 0);
            $depreciableValue = max(0, (float) $asset->purchase_value - (float) $asset->salvage_value);

            $asset->total_depreciation = $totalDepreciation;
            $asset->depreciable_value = $depreciableValue;
            $asset->book_value = max(0, (float) $asset->purchase_value - $totalDepreciation);
            $asset->monthly_depreciation = $asset->useful_life_years > 0
                ? round($depreciableValue / ($asset->useful_life_years * 12), 2)
                : 0;
            $asset->remaining_depreciation = max(0, $depreciableValue - $totalDepreciation);

            return $asset;
        });

        return Inertia::render('FixedAssets/Report', [
            'assets' => $assets,
            'filters' => $filters,
            'branches' => Branch::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'code']),
            'isAdmin' => $this->isAdmin(),
            'totals' => [
                'purchase_value' => (float) $assets->sum('purchase_value'),
                'total_depreciation' => (float) $assets->sum('total_depreciation'),
                'book_value' => (float) $assets->sum('book_value'),
                'remaining_depreciation' => (float) $assets->sum('remaining_depreciation'),
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorizePermission('fixed_assets.create', 'ليس لديك صلاحية لإضافة أصل.');

        return Inertia::render('FixedAssets/Create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizePermission('fixed_assets.create', 'ليس لديك صلاحية لإضافة أصل.');

        $data = $this->validatedData($request);

        if (!$this->isAdmin()) {
            $data['branch_id'] = auth()->user()->branch_id;
        }

        $financialAccount = FinancialAccount::query()->findOrFail($data['financial_account_id']);

        if (!$financialAccount->account_id) {
            return back()->withErrors([
                'financial_account_id' => 'الخزينة أو البنك المختار غير مربوط بحساب محاسبي.',
            ])->withInput();
        }

        if (!$this->isAdmin() && (int) $financialAccount->branch_id !== (int) auth()->user()->branch_id) {
            abort(403, 'ليس لديك صلاحية لاستخدام هذه الخزينة.');
        }

        if ((float) ($data['salvage_value'] ?? 0) >= (float) $data['purchase_value']) {
            return back()->withErrors([
                'salvage_value' => 'القيمة المتبقية يجب أن تكون أقل من قيمة الشراء.',
            ])->withInput();
        }

        return DB::transaction(function () use ($data, $financialAccount) {
            $asset = FixedAsset::create([
                ...$data,
                'journal_entry_id' => null,
            ]);

            $entry = JournalEntry::create([
                'entry_number' => $this->generateJournalEntryNumber(),
                'entry_date' => $data['purchase_date'],
                'branch_id' => $data['branch_id'],
                'description' => 'شراء أصل ثابت: ' . $asset->name,
                'source_type' => FixedAsset::class,
                'source_id' => $asset->id,
                'created_by_user_id' => auth()->id(),
                'status' => 'posted',
            ]);

            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $asset->asset_account_id,
                'debit' => $asset->purchase_value,
                'credit' => 0,
                'description' => 'إثبات أصل ثابت: ' . $asset->name,
            ]);

            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $financialAccount->account_id,
                'debit' => 0,
                'credit' => $asset->purchase_value,
                'description' => 'دفع قيمة أصل ثابت من ' . $financialAccount->name,
            ]);

            $asset->update([
                'journal_entry_id' => $entry->id,
            ]);

            return redirect()
                ->route('fixed-assets.show', $asset)
                ->with('success', 'تم إنشاء الأصل وتسجيل قيد الشراء بنجاح.');
        });
    }

    public function show(FixedAsset $fixedAsset): Response
    {
        $this->authorizePermission('fixed_assets.view', 'ليس لديك صلاحية لعرض الأصل.');

        $this->ensureAccess($fixedAsset);

        $fixedAsset->load([
            'branch:id,name,code',
            'financialAccount:id,name,code,type',
            'assetAccount:id,name,code',
            'depreciationAccount:id,name,code',
            'depreciationExpenseAccount:id,name,code',
            'journalEntry.lines.account:id,name,code',
            'depreciations' => fn ($q) => $q->orderBy('period_year')->orderBy('period_month')->orderBy('depreciation_date'),
            'depreciations.journalEntry.lines.account:id,name,code',
        ]);

        $summary = $this->assetSummary($fixedAsset);
        $currentPeriod = now();

        return Inertia::render('FixedAssets/Show', [
            'asset' => $fixedAsset,
            'totals' => $summary,
            'currentPeriod' => [
                'year' => (int) $currentPeriod->year,
                'month' => (int) $currentPeriod->month,
                'label' => $currentPeriod->translatedFormat('F Y'),
                'already_depreciated' => $fixedAsset->depreciations
                    ->where('period_year', (int) $currentPeriod->year)
                    ->where('period_month', (int) $currentPeriod->month)
                    ->isNotEmpty(),
            ],
            'permissions' => [
                'canUpdate' => auth()->user()?->hasPermission('fixed_assets.update') ?? false,
                'canDepreciate' => auth()->user()?->hasPermission('fixed_assets.depreciate') ?? false,
            ],
        ]);
    }

    public function edit(FixedAsset $fixedAsset): Response
    {
        $this->authorizePermission('fixed_assets.update', 'ليس لديك صلاحية لتعديل الأصل.');

        $this->ensureAccess($fixedAsset);

        return Inertia::render('FixedAssets/Edit', array_merge(
            ['asset' => $fixedAsset],
            $this->formData()
        ));
    }

    public function update(Request $request, FixedAsset $fixedAsset): RedirectResponse
    {
        $this->authorizePermission('fixed_assets.update', 'ليس لديك صلاحية لتعديل الأصل.');

        $this->ensureAccess($fixedAsset);

        $data = $this->validatedData($request, $fixedAsset->id);

        if (!$this->isAdmin()) {
            $data['branch_id'] = auth()->user()->branch_id;
        }

        if ((float) ($data['salvage_value'] ?? 0) >= (float) $data['purchase_value']) {
            return back()->withErrors([
                'salvage_value' => 'القيمة المتبقية يجب أن تكون أقل من قيمة الشراء.',
            ])->withInput();
        }

        $totalDepreciation = (float) $fixedAsset->depreciations()->sum('amount');
        $newDepreciableValue = max(0, (float) $data['purchase_value'] - (float) ($data['salvage_value'] ?? 0));

        if ($totalDepreciation > $newDepreciableValue) {
            return back()->withErrors([
                'purchase_value' => 'لا يمكن أن تكون القيمة القابلة للإهلاك أقل من إجمالي الإهلاك المسجل سابقاً.',
            ])->withInput();
        }

        $fixedAsset->update($data);

        return redirect()
            ->route('fixed-assets.show', $fixedAsset)
            ->with('success', 'تم تعديل الأصل بنجاح. ملاحظة: تعديل بيانات الأصل لا يعيد بناء قيد الشراء القديم.');
    }

    public function storeMonthlyDepreciation(Request $request, FixedAsset $fixedAsset): RedirectResponse
    {
        $this->authorizePermission('fixed_assets.depreciate', 'ليس لديك صلاحية لإثبات إهلاك الأصل.');

        $this->ensureAccess($fixedAsset);

        $data = $request->validate([
            'period_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'period_month' => ['required', 'integer', 'min:1', 'max:12'],
            'notes' => ['nullable', 'string'],
        ]);

        $date = Carbon::create((int) $data['period_year'], (int) $data['period_month'], 1)->endOfMonth();

        $amount = $this->suggestedDepreciationAmount($fixedAsset);

        if ($amount <= 0) {
            return back()->withErrors([
                'amount' => 'لا يوجد مبلغ إهلاك متبقي لهذا الأصل.',
            ]);
        }

        return $this->createDepreciation(
            fixedAsset: $fixedAsset,
            depreciationDate: $date,
            periodYear: (int) $data['period_year'],
            periodMonth: (int) $data['period_month'],
            amount: $amount,
            notes: $data['notes'] ?? 'إهلاك شهري تلقائي'
        );
    }

    public function storeDepreciation(Request $request, FixedAsset $fixedAsset): RedirectResponse
    {
        $this->authorizePermission('fixed_assets.depreciate', 'ليس لديك صلاحية لإثبات إهلاك الأصل.');

        $this->ensureAccess($fixedAsset);

        $data = $request->validate([
            'depreciation_date' => ['required', 'date'],
            'period_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'period_month' => ['required', 'integer', 'min:1', 'max:12'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string'],
        ]);

        return $this->createDepreciation(
            fixedAsset: $fixedAsset,
            depreciationDate: Carbon::parse($data['depreciation_date']),
            periodYear: (int) $data['period_year'],
            periodMonth: (int) $data['period_month'],
            amount: (float) $data['amount'],
            notes: $data['notes'] ?? null
        );
    }

    private function createDepreciation(
        FixedAsset $fixedAsset,
        Carbon $depreciationDate,
        int $periodYear,
        int $periodMonth,
        float $amount,
        ?string $notes
    ): RedirectResponse {
        if (!$fixedAsset->is_active) {
            return back()->withErrors([
                'amount' => 'لا يمكن إثبات إهلاك لأصل موقوف.',
            ]);
        }

        if (!$fixedAsset->depreciation_expense_account_id || !$fixedAsset->depreciation_account_id) {
            return back()->withErrors([
                'amount' => 'يجب ربط الأصل بحساب مصروف الإهلاك وحساب مجمع الإهلاك.',
            ]);
        }

        $currentTotalDepreciation = (float) $fixedAsset->depreciations()->sum('amount');
        $maxDepreciation = max(0, (float) $fixedAsset->purchase_value - (float) $fixedAsset->salvage_value);
        $remainingDepreciation = round($maxDepreciation - $currentTotalDepreciation, 2);

        if ($remainingDepreciation <= 0) {
            return back()->withErrors([
                'amount' => 'تم إهلاك الأصل بالكامل حتى القيمة المتبقية.',
            ]);
        }

        $amount = min(round($amount, 2), $remainingDepreciation);

        try {
            return DB::transaction(function () use ($fixedAsset, $depreciationDate, $periodYear, $periodMonth, $amount, $notes) {
                $entry = JournalEntry::create([
                    'entry_number' => $this->generateJournalEntryNumber(),
                    'entry_date' => $depreciationDate->toDateString(),
                    'branch_id' => $fixedAsset->branch_id,
                    'description' => 'إهلاك أصل ثابت: ' . $fixedAsset->name . ' عن شهر ' . $periodMonth . '/' . $periodYear,
                    'source_type' => FixedAssetDepreciation::class,
                    'source_id' => null,
                    'created_by_user_id' => auth()->id(),
                    'status' => 'posted',
                ]);

                $depreciation = FixedAssetDepreciation::create([
                    'fixed_asset_id' => $fixedAsset->id,
                    'depreciation_date' => $depreciationDate->toDateString(),
                    'period_year' => $periodYear,
                    'period_month' => $periodMonth,
                    'amount' => $amount,
                    'journal_entry_id' => $entry->id,
                    'notes' => $notes,
                ]);

                $entry->update([
                    'source_id' => $depreciation->id,
                ]);

                JournalEntryLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $fixedAsset->depreciation_expense_account_id,
                    'debit' => $amount,
                    'credit' => 0,
                    'description' => 'مصروف إهلاك: ' . $fixedAsset->name,
                ]);

                JournalEntryLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $fixedAsset->depreciation_account_id,
                    'debit' => 0,
                    'credit' => $amount,
                    'description' => 'مجمع إهلاك: ' . $fixedAsset->name,
                ]);

                return redirect()
                    ->route('fixed-assets.show', $fixedAsset)
                    ->with('success', 'تم إثبات الإهلاك وتسجيل القيد المحاسبي بنجاح.');
            });
        } catch (QueryException $exception) {
            if ((string) $exception->getCode() === '23000') {
                return back()->withErrors([
                    'amount' => 'تم تسجيل إهلاك لهذا الأصل في نفس الشهر مسبقاً.',
                ]);
            }

            throw $exception;
        }
    }

    private function suggestedDepreciationAmount(FixedAsset $fixedAsset): float
    {
        $depreciableValue = max(0, (float) $fixedAsset->purchase_value - (float) $fixedAsset->salvage_value);
        $monthly = $fixedAsset->useful_life_years > 0
            ? round($depreciableValue / ($fixedAsset->useful_life_years * 12), 2)
            : 0;

        $currentTotal = (float) $fixedAsset->depreciations()->sum('amount');
        $remaining = round($depreciableValue - $currentTotal, 2);

        return max(0, min($monthly, $remaining));
    }

    private function assetSummary(FixedAsset $fixedAsset): array
    {
        $totalDepreciation = (float) $fixedAsset->depreciations->sum('amount');
        $purchaseValue = (float) $fixedAsset->purchase_value;
        $salvageValue = (float) $fixedAsset->salvage_value;
        $depreciableValue = max(0, $purchaseValue - $salvageValue);
        $bookValue = max(0, $purchaseValue - $totalDepreciation);
        $remainingDepreciation = max(0, $depreciableValue - $totalDepreciation);
        $monthlyDepreciation = $fixedAsset->useful_life_years > 0
            ? round($depreciableValue / ($fixedAsset->useful_life_years * 12), 2)
            : 0;

        return [
            'purchase_value' => $purchaseValue,
            'salvage_value' => $salvageValue,
            'depreciable_value' => $depreciableValue,
            'total_depreciation' => $totalDepreciation,
            'book_value' => $bookValue,
            'remaining_depreciation' => $remainingDepreciation,
            'monthly_depreciation' => min($monthlyDepreciation, $remainingDepreciation),
            'yearly_depreciation' => $fixedAsset->useful_life_years > 0
                ? round($depreciableValue / $fixedAsset->useful_life_years, 2)
                : 0,
        ];
    }

    private function validatedData(Request $request, ?int $ignoreId = null): array
    {
        $uniqueCode = 'unique:fixed_assets,asset_code';

        if ($ignoreId) {
            $uniqueCode .= ',' . $ignoreId;
        }

        return $request->validate([
            'branch_id' => [$this->isAdmin() ? 'required' : 'nullable', 'integer', 'exists:branches,id'],
            'name' => ['required', 'string', 'max:255'],
            'asset_code' => ['required', 'string', 'max:100', $uniqueCode],
            'purchase_date' => ['required', 'date'],
            'purchase_value' => ['required', 'numeric', 'min:0.01'],
            'salvage_value' => ['nullable', 'numeric', 'min:0'],
            'useful_life_years' => ['required', 'integer', 'min:1'],
            'depreciation_method' => ['required', 'string', 'in:straight_line'],
            'financial_account_id' => ['required', 'integer', 'exists:financial_accounts,id'],
            'asset_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'depreciation_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'depreciation_expense_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string'],
        ]);
    }

    private function formData(): array
    {
        $user = auth()->user();

        return [
            'branches' => Branch::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'code']),

            'financialAccounts' => FinancialAccount::query()
                ->when(!$this->isAdmin(), fn ($q) => $q->where('branch_id', $user->branch_id))
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'branch_id', 'name', 'code', 'type', 'account_id']),

            'accounts' => Account::query()
                ->where('is_active', true)
                ->where('is_group', false)
                ->orderBy('code')
                ->get(['id', 'code', 'name', 'type', 'nature']),

            'isAdmin' => $this->isAdmin(),
            'authBranchId' => $user?->branch_id,
        ];
    }

    private function generateJournalEntryNumber(): string
    {
        $prefix = 'JE-' . now()->format('Ymd') . '-';
        $lastId = ((int) JournalEntry::query()->max('id')) + 1;

        return $prefix . str_pad((string) $lastId, 5, '0', STR_PAD_LEFT);
    }
}
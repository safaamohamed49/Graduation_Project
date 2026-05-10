<?php

namespace App\Http\Controllers;

use App\Models\JournalEntryLine;
use App\Models\Partner;
use App\Models\PaymentVoucher;
use App\Models\ReceiptVoucher;
use App\Services\Accounting\AccountCreationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PartnerController extends Controller
{
    private function authorizePermission(string $permission, string $message): void
    {
        if (!auth()->user()?->hasPermission($permission)) {
            abort(403, $message);
        }
    }

    public function index(Request $request): Response
    {
        $this->authorizePermission('partners.view', 'ليس لديك صلاحية لعرض الشركاء.');

        $search = trim((string) $request->get('search', ''));

        $query = Partner::query()
            ->with([
                'capitalAccount:id,name,code',
                'currentAccount:id,name,code',
            ]);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return Inertia::render('Partners/Index', [
            'partners' => $query->orderByDesc('id')->paginate(15)->withQueryString(),
            'filters' => [
                'search' => $search,
            ],
            'permissions' => [
                'canCreate' => auth()->user()?->hasPermission('partners.create') ?? false,
                'canUpdate' => auth()->user()?->hasPermission('partners.update') ?? false,
                'canDelete' => auth()->user()?->hasPermission('partners.delete') ?? false,
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorizePermission('partners.create', 'ليس لديك صلاحية لإضافة شريك.');

        return Inertia::render('Partners/Create');
    }

    public function store(Request $request, AccountCreationService $accountCreationService): RedirectResponse
    {
        $this->authorizePermission('partners.create', 'ليس لديك صلاحية لإضافة شريك.');

        $data = $this->validatedData($request);

        $existing = Partner::whereRaw('LOWER(name) = ?', [
            mb_strtolower(trim($data['name'])),
        ])->first();

        if ($existing) {
            return back()->withErrors([
                'name' => 'يوجد شريك بنفس الاسم بالفعل.',
            ])->withInput();
        }

        DB::transaction(function () use ($request, $data, $accountCreationService) {
            $partner = Partner::create([
                'name' => trim($data['name']),
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'address' => $data['address'] ?? null,
                'capital_amount' => $data['capital_amount'],
                'ownership_percentage' => 0,
                'capital_account_id' => null,
                'current_account_id' => null,
                'start_date' => $data['start_date'] ?? now()->toDateString(),
                'is_active' => $request->boolean('is_active', true),
                'notes' => $data['notes'] ?? null,
            ]);

            $capitalAccount = $accountCreationService->createPartnerCapitalAccount($partner);
            $currentAccount = $accountCreationService->createPartnerCurrentAccount($partner);

            $partner->update([
                'capital_account_id' => $capitalAccount->id,
                'current_account_id' => $currentAccount->id,
            ]);

            $this->recalculateOwnershipPercentages();
        });

        return redirect()
            ->route('partners.index')
            ->with('success', 'تمت إضافة الشريك وإنشاء حساب رأس المال وحساب الجاري بنجاح.');
    }

    public function edit(Partner $partner): Response
    {
        $this->authorizePermission('partners.update', 'ليس لديك صلاحية لتعديل الشركاء.');

        $partner->load([
            'capitalAccount:id,name,code',
            'currentAccount:id,name,code',
        ]);

        return Inertia::render('Partners/Edit', [
            'partner' => $partner,
        ]);
    }

    public function update(Request $request, Partner $partner, AccountCreationService $accountCreationService): RedirectResponse
    {
        $this->authorizePermission('partners.update', 'ليس لديك صلاحية لتعديل الشركاء.');

        $data = $this->validatedData($request, $partner->id);

        $existing = Partner::where('id', '!=', $partner->id)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($data['name']))])
            ->first();

        if ($existing) {
            return back()->withErrors([
                'name' => 'يوجد شريك بنفس الاسم بالفعل.',
            ])->withInput();
        }

        DB::transaction(function () use ($request, $partner, $data, $accountCreationService) {
            $partner->update([
                'name' => trim($data['name']),
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'address' => $data['address'] ?? null,
                'capital_amount' => $data['capital_amount'],
                'start_date' => $data['start_date'] ?? null,
                'is_active' => $request->boolean('is_active', true),
                'notes' => $data['notes'] ?? null,
            ]);

            if (!$partner->capital_account_id) {
                $capitalAccount = $accountCreationService->createPartnerCapitalAccount($partner);
                $partner->update(['capital_account_id' => $capitalAccount->id]);
            }

            if (!$partner->current_account_id) {
                $currentAccount = $accountCreationService->createPartnerCurrentAccount($partner);
                $partner->update(['current_account_id' => $currentAccount->id]);
            }

            $this->recalculateOwnershipPercentages();
        });

        return redirect()
            ->route('partners.index')
            ->with('success', 'تم تعديل بيانات الشريك والتأكد من حساباته المحاسبية بنجاح.');
    }

    public function destroy(Partner $partner): RedirectResponse
    {
        $this->authorizePermission('partners.delete', 'ليس لديك صلاحية لحذف الشركاء.');

        $hasPayments = PaymentVoucher::where('beneficiary_id', $partner->id)
            ->whereIn('beneficiary_type', ['partner', 'Partner', Partner::class])
            ->exists();

        $hasReceipts = ReceiptVoucher::where('received_from_id', $partner->id)
            ->whereIn('received_from_type', ['partner', 'Partner', Partner::class])
            ->exists();

        $hasJournalLines = JournalEntryLine::where('partner_id', $partner->id)->exists();

        if ($hasPayments || $hasReceipts || $hasJournalLines) {
            return back()->withErrors([
                'delete' => 'لا يمكن حذف هذا الشريك لأنه مرتبط بعمليات مالية أو قيود محاسبية.',
            ]);
        }

        $partner->delete();

        $this->recalculateOwnershipPercentages();

        return back()->with('success', 'تم حذف الشريك بنجاح.');
    }

    public function statement(Request $request, Partner $partner): Response
    {
        $this->authorizePermission('partners.view', 'ليس لديك صلاحية لعرض كشف الشريك.');

        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');

        $partner->load([
            'capitalAccount:id,name,code',
            'currentAccount:id,name,code',
        ]);

        $journalLines = JournalEntryLine::query()
            ->with([
                'journalEntry:id,entry_number,entry_date,description,status',
                'account:id,name,code',
            ])
            ->where('partner_id', $partner->id)
            ->when($fromDate, fn ($q) => $q->whereHas('journalEntry', fn ($entry) => $entry->whereDate('entry_date', '>=', $fromDate)))
            ->when($toDate, fn ($q) => $q->whereHas('journalEntry', fn ($entry) => $entry->whereDate('entry_date', '<=', $toDate)))
            ->get()
            ->sortBy(fn ($line) => $line->journalEntry?->entry_date)
            ->values();

        $balance = 0;

        $lines = $journalLines->map(function ($line) use (&$balance) {
            $debit = (float) $line->debit;
            $credit = (float) $line->credit;

            $balance += $credit - $debit;

            return [
                'id' => $line->id,
                'date' => $line->journalEntry?->entry_date?->format('Y-m-d'),
                'entry_number' => $line->journalEntry?->entry_number,
                'account_name' => $line->account?->name,
                'account_code' => $line->account?->code,
                'description' => $line->description ?: $line->journalEntry?->description,
                'debit' => $debit,
                'credit' => $credit,
                'balance' => $balance,
            ];
        });

        return Inertia::render('Partners/Statement', [
            'partner' => $partner,
            'journalLines' => $lines,
            'filters' => [
                'from_date' => $fromDate,
                'to_date' => $toDate,
            ],
            'summary' => [
                'total_debit' => (float) $lines->sum('debit'),
                'total_credit' => (float) $lines->sum('credit'),
                'balance' => $balance,
            ],
        ]);
    }

    private function validatedData(Request $request, ?int $partnerId = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'capital_amount' => ['required', 'numeric', 'min:0'],
            'start_date' => ['required', 'date'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);
    }

    private function recalculateOwnershipPercentages(): void
    {
        $activePartners = Partner::where('is_active', true)->get();

        $totalCapital = (float) $activePartners->sum('capital_amount');

        foreach ($activePartners as $partner) {
            $percentage = $totalCapital > 0
                ? ((float) $partner->capital_amount / $totalCapital) * 100
                : 0;

            $partner->update([
                'ownership_percentage' => round($percentage, 2),
            ]);
        }

        Partner::where('is_active', false)->update([
            'ownership_percentage' => 0,
        ]);
    }
}
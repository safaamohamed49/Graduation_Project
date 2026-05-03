<?php

namespace App\Http\Controllers;

use App\Models\Partner;
use App\Models\PaymentVoucher;
use App\Models\ReceiptVoucher;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PartnerController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->get('search', ''));

        $query = Partner::query();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $partners = $query
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Partners/Index', [
            'partners' => $partners,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Partners/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'capital_amount' => ['required', 'numeric', 'min:0'],
            'start_date' => ['required', 'date'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $existing = Partner::whereRaw('LOWER(name) = ?', [mb_strtolower(trim($data['name']))])->first();

        if ($existing) {
            return back()->withErrors([
                'name' => 'اسم الشريك "' . $existing->name . '" موجود بالفعل.',
            ])->withInput();
        }

        Partner::create([
            'name' => trim($data['name']),
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'capital_amount' => $data['capital_amount'],
            'ownership_percentage' => 0, // 🔥 تتحسب تلقائي
            'start_date' => $data['start_date'],
            'is_active' => $data['is_active'] ?? true,
            'notes' => $data['notes'] ?? null,
        ]);

        $this->recalculateOwnershipPercentages(); // 🔥

        return redirect()->route('partners.index')->with('success', 'تمت إضافة الشريك بنجاح.');
    }

    public function edit(Partner $partner): Response
    {
        return Inertia::render('Partners/Edit', [
            'partner' => $partner,
        ]);
    }

    public function update(Request $request, Partner $partner): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'capital_amount' => ['required', 'numeric', 'min:0'],
            'start_date' => ['required', 'date'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $existing = Partner::where('id', '!=', $partner->id)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($data['name']))])
            ->first();

        if ($existing) {
            return back()->withErrors([
                'name' => 'لا يمكن التعديل، اسم الشريك "' . $existing->name . '" مستخدم بالفعل.',
            ])->withInput();
        }

        $partner->update([
            'name' => trim($data['name']),
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'capital_amount' => $data['capital_amount'],
            'start_date' => $data['start_date'],
            'is_active' => $data['is_active'] ?? true,
            'notes' => $data['notes'] ?? null,
        ]);

        $this->recalculateOwnershipPercentages(); // 🔥

        return redirect()->route('partners.index')->with('success', 'تم تعديل بيانات الشريك بنجاح.');
    }

    public function destroy(Partner $partner): RedirectResponse
    {
        $hasPayments = PaymentVoucher::where('beneficiary_id', $partner->id)
            ->whereIn('beneficiary_type', ['partner', 'Partner', Partner::class])
            ->exists();

        $hasReceipts = ReceiptVoucher::where('received_from_id', $partner->id)
            ->whereIn('received_from_type', ['partner', 'Partner', Partner::class])
            ->exists();

        if ($hasPayments || $hasReceipts) {
            return back()->withErrors([
                'delete' => 'لا يمكن حذف هذا الشريك لأنه مرتبط بعمليات مالية.',
            ]);
        }

        $partner->delete();

        $this->recalculateOwnershipPercentages(); // 🔥

        return back()->with('success', 'تم حذف الشريك بنجاح.');
    }

    public function statement(Request $request, Partner $partner): Response
    {
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');

        $transactions = collect();

        $transactions->push([
            'date' => $partner->start_date?->format('Y-m-d'),
            'type' => 'رأس المال',
            'description' => 'رأس المال المسجل للشريك',
            'debit' => 0,
            'credit' => (float) $partner->capital_amount,
        ]);

        $receipts = ReceiptVoucher::where('received_from_id', $partner->id)
            ->whereIn('received_from_type', ['partner', 'Partner', Partner::class])
            ->when($fromDate, fn ($q) => $q->whereDate('voucher_date', '>=', $fromDate))
            ->when($toDate, fn ($q) => $q->whereDate('voucher_date', '<=', $toDate))
            ->get();

        foreach ($receipts as $receipt) {
            $transactions->push([
                'date' => $receipt->voucher_date?->format('Y-m-d'),
                'type' => 'إيداع من الشريك',
                'description' => $receipt->description,
                'debit' => 0,
                'credit' => (float) $receipt->amount,
            ]);
        }

        $payments = PaymentVoucher::where('beneficiary_id', $partner->id)
            ->whereIn('beneficiary_type', ['partner', 'Partner', Partner::class])
            ->when($fromDate, fn ($q) => $q->whereDate('voucher_date', '>=', $fromDate))
            ->when($toDate, fn ($q) => $q->whereDate('voucher_date', '<=', $toDate))
            ->get();

        foreach ($payments as $payment) {
            $transactions->push([
                'date' => $payment->voucher_date?->format('Y-m-d'),
                'type' => 'سحب شريك',
                'description' => $payment->description,
                'debit' => (float) $payment->amount,
                'credit' => 0,
            ]);
        }

        $transactions = $transactions->sortBy('date')->values();

        $balance = 0;

        $transactions = $transactions->map(function ($item) use (&$balance) {
            $balance += $item['credit'] - $item['debit'];
            $item['balance'] = $balance;
            return $item;
        });

        return Inertia::render('Partners/Statement', [
            'partner' => $partner,
            'transactions' => $transactions,
            'filters' => [
                'from_date' => $fromDate,
                'to_date' => $toDate,
            ],
            'summary' => [
                'total_debit' => $transactions->sum('debit'),
                'total_credit' => $transactions->sum('credit'),
                'balance' => $balance,
            ],
        ]);
    }

    private function recalculateOwnershipPercentages(): void
    {
        $activePartners = Partner::where('is_active', true)->get();

        $totalCapital = $activePartners->sum('capital_amount');

        foreach ($activePartners as $partner) {
            $percentage = $totalCapital > 0
                ? ($partner->capital_amount / $totalCapital) * 100
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
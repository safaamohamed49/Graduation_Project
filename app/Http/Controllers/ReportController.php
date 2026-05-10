<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\FinancialAccount;
use App\Models\Order;
use App\Models\PaymentVoucher;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\ReceiptVoucher;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
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

    public function index(): Response
    {
        $this->authorizePermission('reports.view', 'ليس لديك صلاحية لعرض التقارير.');

        return Inertia::render('Reports/Index', [
            'cards' => [
                [
                    'title' => 'الملخص المالي',
                    'description' => 'ملخص للخزائن، المقبوضات، المصروفات، والأرصدة.',
                    'href' => '/reports/financial-summary',
                    'color' => 'from-emerald-700 to-emerald-900',
                ],

                [
                    'title' => 'تقرير المبيعات',
                    'description' => 'تحليل المبيعات والأرباح والفواتير.',
                    'href' => '/reports/sales',
                    'color' => 'from-cyan-700 to-cyan-900',
                ],

                [
                    'title' => 'تقرير المشتريات',
                    'description' => 'تحليل المشتريات والمدفوعات للموردين.',
                    'href' => '/reports/purchases',
                    'color' => 'from-indigo-700 to-indigo-900',
                ],

                [
                    'title' => 'تقرير المخزون',
                    'description' => 'الكميات الحالية وحركة المخزون.',
                    'href' => '/reports/inventory',
                    'color' => 'from-orange-700 to-orange-900',
                ],

                [
                    'title' => 'تقرير العملاء',
                    'description' => 'الحسابات المدينة وحركة العملاء.',
                    'href' => '/reports/customers',
                    'color' => 'from-pink-700 to-pink-900',
                ],

                [
                    'title' => 'تقرير الموردين',
                    'description' => 'الحسابات الدائنة وحركة الموردين.',
                    'href' => '/reports/suppliers',
                    'color' => 'from-violet-700 to-violet-900',
                ],

                [
                    'title' => 'تقرير الأصول',
                    'description' => 'الأصول، الإهلاك، والقيمة الدفترية.',
                    'href' => '/reports/assets',
                    'color' => 'from-slate-700 to-slate-900',
                ],
            ],
        ]);
    }

    public function financialSummary(Request $request): Response
    {
        $this->authorizePermission('reports.financial', 'ليس لديك صلاحية لعرض التقرير المالي.');

        $user = auth()->user();

        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');
        $branchId = $request->get('branch_id');

        $receiptQuery = ReceiptVoucher::query();
        $paymentQuery = PaymentVoucher::query();
        $salesQuery = Order::query();
        $purchaseQuery = PurchaseInvoice::query();

        if (!$this->isAdmin()) {
            $receiptQuery->where('branch_id', $user->branch_id);
            $paymentQuery->where('branch_id', $user->branch_id);
            $salesQuery->where('branch_id', $user->branch_id);
            $purchaseQuery->where('branch_id', $user->branch_id);
        }

        if ($this->isAdmin() && !empty($branchId)) {
            $receiptQuery->where('branch_id', $branchId);
            $paymentQuery->where('branch_id', $branchId);
            $salesQuery->where('branch_id', $branchId);
            $purchaseQuery->where('branch_id', $branchId);
        }

        if ($fromDate) {
            $receiptQuery->whereDate('voucher_date', '>=', $fromDate);
            $paymentQuery->whereDate('voucher_date', '>=', $fromDate);
            $salesQuery->whereDate('order_date', '>=', $fromDate);
            $purchaseQuery->whereDate('invoice_date', '>=', $fromDate);
        }

        if ($toDate) {
            $receiptQuery->whereDate('voucher_date', '<=', $toDate);
            $paymentQuery->whereDate('voucher_date', '<=', $toDate);
            $salesQuery->whereDate('order_date', '<=', $toDate);
            $purchaseQuery->whereDate('invoice_date', '<=', $toDate);
        }

        $totalReceipts = (float) $receiptQuery->sum('amount');
        $totalPayments = (float) $paymentQuery->sum('amount');
        $totalSales = (float) $salesQuery->sum('total_price');
        $totalPurchases = (float) $purchaseQuery->sum('total_amount');

        $financialAccounts = FinancialAccount::query()
            ->when(!$this->isAdmin(), fn ($q) => $q->where('branch_id', $user->branch_id))
            ->when($this->isAdmin() && !empty($branchId), fn ($q) => $q->where('branch_id', $branchId))
            ->where('is_active', true)
            ->with('account')
            ->orderBy('name')
            ->get()
            ->map(function ($account) {
                $receipts = (float) $account->receiptVouchers()->sum('amount');
                $payments = (float) $account->paymentVouchers()->sum('amount');

                $account->current_balance =
                    (float) $account->opening_balance
                    + $receipts
                    - $payments;

                return $account;
            });

        return Inertia::render('Reports/FinancialSummary', [
            'filters' => [
                'from_date' => $fromDate,
                'to_date' => $toDate,
                'branch_id' => $branchId,
            ],

            'branches' => Branch::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'code']),

            'isAdmin' => $this->isAdmin(),

            'summary' => [
                'total_receipts' => $totalReceipts,
                'total_payments' => $totalPayments,
                'net_cash_flow' => $totalReceipts - $totalPayments,
                'total_sales' => $totalSales,
                'total_purchases' => $totalPurchases,
            ],

            'financialAccounts' => $financialAccounts,

            'stats' => [
                'customers_count' => Customer::count(),
                'suppliers_count' => Supplier::count(),
                'products_count' => Product::count(),
                'accounts_count' => Account::count(),
            ],
        ]);
    }
}
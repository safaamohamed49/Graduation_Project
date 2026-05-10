<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    ProductController,
    CategoryController,
    CustomerController,
    SupplierController,
    PurchaseInvoiceController,
    PartnerController,
    DashboardController,
    WarehouseController,
    EmployeeController,
    BranchController,
    OrderController,
    PaymentVoucherController,
    StockTransferController,
    ReceiptVoucherController,
    GeneralLedgerController,
    FinancialAccountController,
    FixedAssetController,
    ReportController,
    Auth\LoginController
};

// --- Public Routes ---
Route::get('/', fn() => redirect()->route('login'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.attempt');
});

// --- Authenticated Routes ---
Route::middleware('auth')->group(function () {
    
    // Core & Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // Resources (Full Support)
    Route::resource('branches', BranchController::class);
    Route::resource('warehouses', WarehouseController::class);
    Route::resource('employees', EmployeeController::class);

    // Resources (Except Show/Destroy)
    Route::resource('categories', CategoryController::class)->except(['show']);
    Route::resource('customers', CustomerController::class)->except(['show']);
    Route::resource('suppliers', SupplierController::class)->except(['show']);

    // Products
    Route::post('/products/{product}/restore', [ProductController::class, 'restore'])->name('products.restore');
    Route::resource('products', ProductController::class)->except(['show']);

    // Invoices & Orders
    Route::post('/purchase-invoices/extract-image', [PurchaseInvoiceController::class, 'extractImage'])->name('purchase-invoices.extract-image');
    Route::resource('purchase-invoices', PurchaseInvoiceController::class);


    // Partners
    Route::get('/partners/{partner}/statement', [PartnerController::class, 'statement'])->name('partners.statement');
    Route::resource('partners', PartnerController::class)->except(['show']);

    // Accounting & Vouchers
    Route::resource('payment-vouchers', PaymentVoucherController::class)->only(['index', 'create', 'store', 'show']);
    Route::resource('stock-transfers', StockTransferController::class)->only(['index', 'create', 'store', 'show']);
    
    Route::post('/receipt-vouchers/{receiptVoucher}/cancel', [ReceiptVoucherController::class, 'cancel'])->name('receipt-vouchers.cancel');
    Route::resource('receipt-vouchers', ReceiptVoucherController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update']);

    Route::get('/general-ledger', [GeneralLedgerController::class, 'index'])->name('general-ledger.index');
    Route::resource('financial-accounts', FinancialAccountController::class)->except(['destroy']);

    // Fixed Assets
    Route::prefix('fixed-assets')->name('fixed-assets.')->group(function () {
        Route::get('/report', [FixedAssetController::class, 'report'])->name('report');
        Route::post('/{fixedAsset}/depreciations/monthly', [FixedAssetController::class, 'storeMonthlyDepreciation'])->name('depreciations.monthly');
        Route::post('/{fixedAsset}/depreciations', [FixedAssetController::class, 'storeDepreciation'])->name('depreciations.store');
    });
    Route::resource('fixed-assets', FixedAssetController::class)->except(['destroy']);

    // Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/financial-summary', [ReportController::class, 'financialSummary'])->name('financial-summary');
        Route::get('/sales', [ReportController::class, 'sales'])->name('sales');
        Route::get('/purchases', [ReportController::class, 'purchases'])->name('purchases');
        Route::get('/inventory', [ReportController::class, 'inventory'])->name('inventory');

    });


    Route::post('/orders/extract-image', [OrderController::class, 'extractImage'])
    ->name('orders.extract-image');

    Route::resource('orders', OrderController::class);
    
    
    
});
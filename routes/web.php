<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\PurchaseInvoiceController;
use App\Http\Controllers\PartnerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentVoucherController;
use App\Http\Controllers\StockTransferController;
use App\Http\Controllers\ReceiptVoucherController;
use App\Http\Controllers\GeneralLedgerController;


Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.attempt');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::resource('branches', BranchController::class);

    Route::resource('categories', CategoryController::class)->except(['show']);

    Route::post('/products/{product}/restore', [ProductController::class, 'restore'])
        ->name('products.restore');
    Route::resource('products', ProductController::class)->except(['show']);

    Route::resource('customers', CustomerController::class)->except(['show']);
    Route::resource('suppliers', SupplierController::class)->except(['show']);

    Route::post('/purchase-invoices/extract-image', [PurchaseInvoiceController::class, 'extractImage'])
    ->name('purchase-invoices.extract-image');

    Route::resource('purchase-invoices', PurchaseInvoiceController::class);
    
   Route::get('/partners/{partner}/statement', [PartnerController::class, 'statement'])
    ->name('partners.statement');

   Route::resource('partners', PartnerController::class)->except(['show']);

   

    Route::resource('warehouses', WarehouseController::class);

  


 
    Route::resource('employees', EmployeeController::class);




    Route::post('/orders/extract-image', [OrderController::class, 'extractImage'])
        ->name('orders.extract-image');

    Route::resource('orders', OrderController::class);
    Route::resource('payment-vouchers', PaymentVoucherController::class)->only([
    'index',
    'create',
    'store',
    'show',
]);
Route::resource('stock-transfers', StockTransferController::class)->only([
    'index',
    'create',
    'store',
    'show',
]);
Route::post('/receipt-vouchers/{receiptVoucher}/cancel', [ReceiptVoucherController::class, 'cancel'])
    ->name('receipt-vouchers.cancel');
Route::resource('receipt-vouchers', ReceiptVoucherController::class)->only([
    'index',
    'create',
    'store',
    'show',
    'edit',
    'update',
]);
Route::get('/general-ledger', [GeneralLedgerController::class, 'index'])
    ->name('general-ledger.index');
});
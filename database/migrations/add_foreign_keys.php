<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | BRANCHES
        |--------------------------------------------------------------------------
        */
        Schema::table('branches', function (Blueprint $table) {
            $table->foreign('manager_user_id')->references('id')->on('users')->nullOnDelete();
        });

        /*
        |--------------------------------------------------------------------------
        | PARTNERS
        |--------------------------------------------------------------------------
        */
        Schema::table('partners', function (Blueprint $table) {
            $table->foreign('capital_account_id')->references('id')->on('accounts')->nullOnDelete();
            $table->foreign('current_account_id')->references('id')->on('accounts')->nullOnDelete();
        });

        /*
        |--------------------------------------------------------------------------
        | EMPLOYEES
        |--------------------------------------------------------------------------
        */
        Schema::table('employees', function (Blueprint $table) {
            $table->foreign('account_id')->references('id')->on('accounts')->nullOnDelete();
        });

        /*
        |--------------------------------------------------------------------------
        | FINANCIAL ACCOUNTS
        |--------------------------------------------------------------------------
        */
        Schema::table('financial_accounts', function (Blueprint $table) {
            $table->foreign('account_id')->references('id')->on('accounts')->nullOnDelete();
        });

        /*
        |--------------------------------------------------------------------------
        | EXPENSE CATEGORIES
        |--------------------------------------------------------------------------
        */
        Schema::table('expense_categories', function (Blueprint $table) {
            $table->foreign('expense_account_id')->references('id')->on('accounts')->nullOnDelete();
        });

        /*
        |--------------------------------------------------------------------------
        | CUSTOMERS & SUPPLIERS
        |--------------------------------------------------------------------------
        */
        Schema::table('customers', function (Blueprint $table) {
            $table->foreign('account_id')->references('id')->on('accounts')->nullOnDelete();
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->foreign('account_id')->references('id')->on('accounts')->nullOnDelete();
        });

        /*
        |--------------------------------------------------------------------------
        | ORDERS
        |--------------------------------------------------------------------------
        */
        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('journal_entry_id')->references('id')->on('journal_entries')->nullOnDelete();
        });

        /*
        |--------------------------------------------------------------------------
        | PURCHASE INVOICES
        |--------------------------------------------------------------------------
        */
        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('journal_entry_id')->references('id')->on('journal_entries')->nullOnDelete();
        });

        /*
        |--------------------------------------------------------------------------
        | RETURN INVOICES
        |--------------------------------------------------------------------------
        */
        Schema::table('return_invoices', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('deleted_by_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('journal_entry_id')->references('id')->on('journal_entries')->nullOnDelete();
        });

        /*
        |--------------------------------------------------------------------------
        | VOUCHERS
        |--------------------------------------------------------------------------
        */
        Schema::table('receipt_vouchers', function (Blueprint $table) {
            $table->foreign('account_id')->references('id')->on('accounts')->nullOnDelete();
            $table->foreign('journal_entry_id')->references('id')->on('journal_entries')->nullOnDelete();
            $table->foreign('created_by_user_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('payment_vouchers', function (Blueprint $table) {
            $table->foreign('account_id')->references('id')->on('accounts')->nullOnDelete();
            $table->foreign('journal_entry_id')->references('id')->on('journal_entries')->nullOnDelete();
            $table->foreign('created_by_user_id')->references('id')->on('users')->nullOnDelete();
        });

        /*
        |--------------------------------------------------------------------------
        | STOCK TRANSFERS
        |--------------------------------------------------------------------------
        */
        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });

        /*
        |--------------------------------------------------------------------------
        | JOURNAL ENTRIES
        |--------------------------------------------------------------------------
        */
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->foreign('created_by_user_id')->references('id')->on('users')->nullOnDelete();
        });

        /*
        |--------------------------------------------------------------------------
        | JOURNAL ENTRY LINES
        |--------------------------------------------------------------------------
        */
        Schema::table('journal_entry_lines', function (Blueprint $table) {
            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();

            $table->foreign('partner_id')->references('id')->on('partners')->nullOnDelete();
            $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
            $table->foreign('supplier_id')->references('id')->on('suppliers')->nullOnDelete();
            $table->foreign('employee_id')->references('id')->on('employees')->nullOnDelete();
        });

        /*
        |--------------------------------------------------------------------------
        | FIXED ASSETS
        |--------------------------------------------------------------------------
        */
        Schema::table('fixed_assets', function (Blueprint $table) {
            $table->foreign('asset_account_id')->references('id')->on('accounts')->nullOnDelete();
            $table->foreign('depreciation_account_id')->references('id')->on('accounts')->nullOnDelete();
            $table->foreign('depreciation_expense_account_id')->references('id')->on('accounts')->nullOnDelete();
            $table->foreign('journal_entry_id')->references('id')->on('journal_entries')->nullOnDelete();
        });

        /*
        |--------------------------------------------------------------------------
        | FIXED ASSET DEPRECIATIONS
        |--------------------------------------------------------------------------
        */
        Schema::table('fixed_asset_depreciations', function (Blueprint $table) {
            $table->foreign('journal_entry_id')->references('id')->on('journal_entries')->nullOnDelete();
        });
    }

    public function down(): void
    {
        // تقدر تخليها فاضية حالياً
    }
};
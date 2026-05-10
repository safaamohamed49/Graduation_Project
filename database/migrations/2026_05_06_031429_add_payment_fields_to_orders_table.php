<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'financial_account_id')) {
                $table->foreignId('financial_account_id')
                    ->nullable()
                    ->after('customer_id')
                    ->constrained('financial_accounts')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('orders', 'payment_method_id')) {
                $table->foreignId('payment_method_id')
                    ->nullable()
                    ->after('financial_account_id')
                    ->constrained('payment_methods')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('orders', 'paid_amount')) {
                $table->decimal('paid_amount', 15, 2)
                    ->default(0)
                    ->after('total_profit');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'financial_account_id')) {
                $table->dropConstrainedForeignId('financial_account_id');
            }

            if (Schema::hasColumn('orders', 'payment_method_id')) {
                $table->dropConstrainedForeignId('payment_method_id');
            }

            if (Schema::hasColumn('orders', 'paid_amount')) {
                $table->dropColumn('paid_amount');
            }
        });
    }
};
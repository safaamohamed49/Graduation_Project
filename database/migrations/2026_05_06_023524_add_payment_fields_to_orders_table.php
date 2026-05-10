<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('financial_account_id')
                ->nullable()
                ->after('customer_id')
                ->constrained('financial_accounts')
                ->nullOnDelete();

            $table->foreignId('payment_method_id')
                ->nullable()
                ->after('financial_account_id')
                ->constrained('payment_methods')
                ->nullOnDelete();

            $table->decimal('paid_amount', 15, 2)
                ->default(0)
                ->after('total_profit');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('financial_account_id');
            $table->dropConstrainedForeignId('payment_method_id');
            $table->dropColumn('paid_amount');
        });
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_invoices', 'paid_amount')) {
                $table->decimal('paid_amount', 15, 2)->default(0)->after('total_base_price');
            }

            if (!Schema::hasColumn('purchase_invoices', 'payment_status')) {
                $table->string('payment_status')->default('due')->after('paid_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_invoices', 'payment_status')) {
                $table->dropColumn('payment_status');
            }

            if (Schema::hasColumn('purchase_invoices', 'paid_amount')) {
                $table->dropColumn('paid_amount');
            }
        });
    }
};
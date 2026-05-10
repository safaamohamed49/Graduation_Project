<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('receipt_vouchers', function (Blueprint $table) {
            $table->string('partner_transaction_type')
                ->nullable()
                ->after('received_from_id');
        });
    }

    public function down(): void
    {
        Schema::table('receipt_vouchers', function (Blueprint $table) {
            $table->dropColumn('partner_transaction_type');
        });
    }
};
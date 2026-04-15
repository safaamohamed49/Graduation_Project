<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_finance', function (Blueprint $table) {
            $table->id();
            $table->string('account_name')->unique();
            $table->decimal('balance', 15, 2)->default(0.00);
            $table->timestamp('last_update')->default(DB::raw('CURRENT_TIMESTAMP'))->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_finance');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('fixed_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('name');
            $table->string('asset_code')->unique();
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_value', 15, 2)->default(0);
            $table->decimal('salvage_value', 15, 2)->default(0);
            $table->unsignedInteger('useful_life_years')->default(1);
            $table->string('depreciation_method')->default('straight_line');
            $table->unsignedBigInteger('asset_account_id')->nullable();
            $table->unsignedBigInteger('depreciation_account_id')->nullable();
            $table->unsignedBigInteger('depreciation_expense_account_id')->nullable();
            $table->unsignedBigInteger('journal_entry_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fixed_assets');
    }
};
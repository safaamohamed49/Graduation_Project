<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_accounts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            // الفرع

            $table->string('name');
            // اسم الحساب

            $table->string('code')->unique();
            // كود الحساب

            $table->string('type');
            // cash / bank

            $table->unsignedBigInteger('account_id')->nullable();
            // الحساب المحاسبي (لاحقًا FK)

            $table->string('currency')->default('LYD');
            // العملة

            $table->decimal('opening_balance', 15, 2)->default(0);
            // الرصيد الافتتاحي

            $table->boolean('is_active')->default(true);

            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_accounts');
    }
};
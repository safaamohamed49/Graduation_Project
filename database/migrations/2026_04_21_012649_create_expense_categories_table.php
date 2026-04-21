<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            // اسم نوع المصروف

            $table->string('code')->unique();
            // رمز نوع المصروف

            $table->unsignedBigInteger('expense_account_id')->nullable();
            // الحساب المحاسبي المرتبط - نربطه لاحقًا بـ accounts

            $table->boolean('is_active')->default(true);
            // هل التصنيف فعال

            $table->text('notes')->nullable();
            // ملاحظات

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_categories');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            // المعرف الأساسي للحساب

            $table->foreignId('parent_id')->nullable()->constrained('accounts')->nullOnDelete();
            // الحساب الأب داخل شجرة الحسابات

            $table->string('code')->unique();
            // كود الحساب مثل 1110 أو 5110

            $table->string('name');
            // اسم الحساب

            $table->enum('type', ['asset', 'liability', 'equity', 'revenue', 'expense']);
            // نوع الحساب:
            // asset = أصل
            // liability = خصم
            // equity = حقوق ملكية
            // revenue = إيراد
            // expense = مصروف

            $table->enum('nature', ['debit', 'credit']);
            // طبيعة الحساب:
            // debit = مدين
            // credit = دائن

            $table->unsignedInteger('level')->default(1);
            // مستوى الحساب داخل الشجرة

            $table->boolean('is_group')->default(false);
            // هل الحساب تجميعي (أب) أم نهائي

            $table->boolean('is_active')->default(true);
            // هل الحساب فعال

            $table->boolean('is_system')->default(false);
            // هل هو حساب نظامي لا يُحذف أو لا يُعدل بسهولة

            $table->text('notes')->nullable();
            // ملاحظات إضافية

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
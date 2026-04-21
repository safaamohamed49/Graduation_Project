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
        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            // المعرف الأساسي للشريك

            $table->string('name');
            // اسم الشريك

            $table->string('phone')->nullable();
            // هاتف الشريك

            $table->string('email')->nullable();
            // بريد الشريك

            $table->string('address')->nullable();
            // عنوان الشريك

            $table->decimal('capital_amount', 15, 2)->default(0);
            // قيمة رأس المال الذي أدخله الشريك

            $table->decimal('ownership_percentage', 5, 2)->default(0);
            // نسبة الملكية - مثل 25.00 أو 50.00

            $table->unsignedBigInteger('capital_account_id')->nullable();
            // حساب رأس مال هذا الشريك في شجرة الحسابات
            // نربطه لاحقًا بعد إنشاء جدول accounts

            $table->unsignedBigInteger('current_account_id')->nullable();
            // الحساب الجاري / مسحوبات الشريك
            // نربطه لاحقًا بعد إنشاء جدول accounts

            $table->date('start_date')->nullable();
            // تاريخ بدء الشراكة

            $table->boolean('is_active')->default(true);
            // هل الشريك فعال

            $table->text('notes')->nullable();
            // ملاحظات إضافية

            $table->timestamps();
            // created_at و updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partners');
    }
};
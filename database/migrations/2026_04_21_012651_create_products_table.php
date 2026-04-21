<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            // التصنيف

            $table->string('name');
            // اسم المنتج

            $table->string('product_code')->unique();
            // كود المنتج الداخلي

            $table->string('barcode')->nullable()->unique();
            // الباركود - اختياري

            $table->string('unit_name')->default('قطعة');
            // اسم الوحدة

            $table->string('image_path')->nullable();
            // مسار صورة المنتج

            $table->decimal('current_price', 15, 2)->default(0);
            // سعر البيع الحالي

            $table->decimal('last_purchase_price', 15, 2)->default(0);
            // آخر سعر شراء

            $table->decimal('minimum_stock', 15, 2)->default(0);
            // الحد الأدنى للتنبيه

            $table->boolean('is_service')->default(false);
            // هل هذا العنصر خدمة

            $table->boolean('is_active')->default(true);
            // هل المنتج فعال

            $table->boolean('is_deleted')->default(false);
            // حذف منطقي

            $table->text('notes')->nullable();
            // ملاحظات

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
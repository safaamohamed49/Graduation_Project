<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_batches', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            // المنتج

            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            // المخزن

            $table->foreignId('purchase_invoice_id')->nullable()->constrained('purchase_invoices')->nullOnDelete();
            // فاتورة الشراء التي أدخلت هذه الدفعة

            $table->decimal('quantity', 15, 2);
            // الكمية الأصلية

            $table->decimal('remaining_quantity', 15, 2);
            // الكمية المتبقية

            $table->decimal('base_price', 15, 2)->default(0);
            // سعر التكلفة الأساسي

            $table->decimal('purchase_price', 15, 2)->default(0);
            // سعر الشراء

            $table->dateTime('entry_date');
            // تاريخ إدخال الدفعة

            $table->date('expiry_date')->nullable();
            // تاريخ الانتهاء إن وجد

            $table->text('notes')->nullable();
            // ملاحظات

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_batches');
    }
};
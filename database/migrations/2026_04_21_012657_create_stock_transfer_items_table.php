<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_transfer_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('stock_transfer_id')->constrained('stock_transfers')->cascadeOnDelete();
            // رأس التحويل

            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            // المنتج

            $table->decimal('quantity', 15, 2);
            // الكمية المحولة

            $table->decimal('unit_cost', 15, 2)->default(0);
            // تكلفة الوحدة

            $table->decimal('total_cost', 15, 2)->default(0);
            // إجمالي التكلفة

            $table->text('notes')->nullable();
            // ملاحظات

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfer_items');
    }
};
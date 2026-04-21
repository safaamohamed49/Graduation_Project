<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_item_batches', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_item_id')->constrained('order_items')->cascadeOnDelete();
            // بند البيع

            $table->foreignId('batch_id')->constrained('inventory_batches')->cascadeOnDelete();
            // دفعة المخزون

            $table->decimal('quantity_used', 15, 2);
            // الكمية المسحوبة من الدفعة

            $table->decimal('unit_cost', 15, 2)->default(0);
            // تكلفة الوحدة وقت السحب

            $table->decimal('total_cost', 15, 2)->default(0);
            // إجمالي التكلفة = quantity_used × unit_cost

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_item_batches');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_invoice_id')->constrained('return_invoices')->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->integer('quantity');
            $table->decimal('refund_amount', 15, 2);
            $table->decimal('unit_refund_price', 15, 2);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_invoice_items');
    }
};
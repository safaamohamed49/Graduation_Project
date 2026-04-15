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
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('purchase_invoices')->nullOnDelete();
            $table->integer('quantity');
            $table->decimal('base_price', 15, 2);
            $table->decimal('purchase_price', 15, 2);
            $table->integer('remaining_quantity');
            $table->dateTime('entry_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_batches');
    }
};
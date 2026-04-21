<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_price_histories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            // المنتج

            $table->decimal('price', 15, 2);
            // السعر

            $table->dateTime('start_date');
            // بداية العمل بهذا السعر

            $table->dateTime('end_date')->nullable();
            // نهاية العمل بهذا السعر

            $table->text('notes')->nullable();
            // ملاحظات

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_price_histories');
    }
};
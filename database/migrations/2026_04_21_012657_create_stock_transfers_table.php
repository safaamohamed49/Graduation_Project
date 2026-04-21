<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();

            $table->string('transfer_number')->unique();
            // رقم التحويل

            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            // الفرع المسؤول

            $table->foreignId('from_warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            // المخزن المصدر

            $table->foreignId('to_warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            // المخزن الوجهة

            $table->dateTime('transfer_date');
            // تاريخ التحويل

            $table->string('status')->default('draft');
            // الحالة: draft / completed / cancelled

            $table->unsignedBigInteger('user_id')->nullable();
            // المستخدم الذي أنشأ العملية - نربطه لاحقًا بـ users

            $table->text('notes')->nullable();
            // ملاحظات

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfers');
    }
};
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
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            // المعرف الأساسي للمخزن

            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            // الفرع التابع له المخزن - اختياري
            // لو كان المخزن رئيسي عام، يكون null

            $table->string('name');
            // اسم المخزن

            $table->string('code')->unique();
            // رمز المخزن

            $table->string('type')->default('branch');
            // نوع المخزن: رئيسي / فرعي / مؤقت

            $table->string('address')->nullable();
            // عنوان المخزن

            $table->string('phone')->nullable();
            // هاتف المخزن

            $table->boolean('is_active')->default(true);
            // هل المخزن فعال

            $table->timestamps();
            // created_at و updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouses');
    }
};
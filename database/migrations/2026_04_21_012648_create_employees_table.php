<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            // المعرف الأساسي للموظف

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            // ربط الموظف بحساب مستخدم (لو عنده حساب)

            $table->foreignId('branch_id')
                ->nullable()
                ->constrained('branches')
                ->nullOnDelete();
            // الفرع الذي يعمل فيه الموظف

            $table->string('name');
            // اسم الموظف

            $table->string('phone')->nullable();
            // هاتف الموظف

            $table->string('email')->nullable();
            // بريد الموظف

            $table->string('address')->nullable();
            // عنوان الموظف

            $table->decimal('salary', 15, 2)->default(0);
            // الراتب الأساسي

            $table->unsignedBigInteger('account_id')->nullable();
            // الحساب المحاسبي (نربطه لاحقًا)

            $table->date('hire_date')->nullable();
            // تاريخ التوظيف

            $table->boolean('is_active')->default(true);
            // هل الموظف نشط

            $table->text('notes')->nullable();
            // ملاحظات

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
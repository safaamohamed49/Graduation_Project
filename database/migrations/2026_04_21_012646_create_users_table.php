<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            // الفرع

            $table->foreignId('role_id')->nullable()->constrained('roles')->nullOnDelete();
            // الدور

            $table->string('name');
            // الاسم الكامل

            $table->string('username')->unique();
            // اسم المستخدم

            $table->string('password')->nullable();
            // كلمة المرور المشفرة

            $table->string('phone')->nullable();
            // الهاتف

            $table->string('email')->nullable();
            // البريد

            $table->string('address')->nullable();
            // العنوان

            $table->decimal('salary', 15, 2)->nullable();
            // الراتب إن استُخدم

            $table->string('image_path')->nullable();
            // مسار الصورة

            // 🔥 هذا اللي كان ناقص
            $table->json('extra_permissions')->nullable();
            $table->json('denied_permissions')->nullable();

            $table->boolean('is_active')->default(true);
            // هل المستخدم فعال

            $table->boolean('is_deleted')->default(false);
            // حذف منطقي

            $table->boolean('is_locked')->default(false);
            // هل المستخدم مقفل

            $table->dateTime('last_login_at')->nullable();
            // آخر دخول

            $table->text('notes')->nullable();
            // ملاحظات

            $table->rememberToken();
            // remember token

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
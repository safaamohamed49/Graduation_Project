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
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            // المعرف الأساسي للفرع

            $table->string('name');
            // اسم الفرع

            $table->string('code')->unique();
            // رمز الفرع مثل MAIN أو BR001

            $table->string('address')->nullable();
            // عنوان الفرع

            $table->string('phone')->nullable();
            // هاتف الفرع

            $table->unsignedBigInteger('manager_user_id')->nullable();
            // معرف المستخدم المسؤول عن إدارة الفرع
            // أضفناه الآن بدون foreign key، ونربطه لاحقًا بعد إنشاء جدول users

            $table->boolean('is_active')->default(true);
            // هل الفرع فعال أم متوقف

            $table->timestamps();
            // created_at و updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
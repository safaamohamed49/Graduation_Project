<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            // اسم الدور

            $table->string('code')->unique();
            // رمز الدور

            $table->json('permissions')->nullable();
            // الصلاحيات بصيغة JSON

            $table->boolean('is_active')->default(true);
            // هل الدور فعال

            $table->text('notes')->nullable();
            // ملاحظات

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
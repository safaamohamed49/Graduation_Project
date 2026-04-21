<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            // اسم التصنيف

            $table->string('code')->unique();
            // رمز التصنيف

            $table->text('description')->nullable();
            // وصف التصنيف

            $table->boolean('is_active')->default(true);
            // هل التصنيف فعال

            $table->text('notes')->nullable();
            // ملاحظات

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
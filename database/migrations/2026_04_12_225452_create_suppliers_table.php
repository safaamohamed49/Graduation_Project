<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone', 50)->nullable();
            $table->boolean('is_deleted')->default(false);
            $table->boolean('is_locked')->default(false);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number', 100)->unique();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->decimal('amount_paid', 15, 2);
            $table->dateTime('payment_date');
            $table->foreignId('payment_method')->constrained('payment_methods')->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->boolean('is_deleted')->default(false);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_payments');
    }
};
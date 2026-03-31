<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('razorpay_order_id', 100)->nullable();
            $table->string('razorpay_payment_id', 100)->nullable();
            $table->string('razorpay_signature', 255)->nullable();
            $table->unsignedInteger('amount'); // in paise
            $table->string('currency', 5)->default('INR');
            $table->enum('status', ['pending', 'success', 'failed', 'refunded'])->default('pending');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('razorpay_order_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};

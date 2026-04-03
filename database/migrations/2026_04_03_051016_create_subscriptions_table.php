<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('plan_id', 30); // basic, standard, premium
            $table->string('plan_name', 50);
            $table->integer('amount'); // in paise (e.g., 99900 = ₹999)
            $table->string('razorpay_order_id', 100)->nullable();
            $table->string('razorpay_payment_id', 100)->nullable();
            $table->string('razorpay_signature', 200)->nullable();
            $table->string('payment_status', 20)->default('pending'); // pending, paid, failed
            $table->date('starts_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};

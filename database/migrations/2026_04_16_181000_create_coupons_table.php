<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('description', 200)->nullable();
            $table->enum('discount_type', ['percentage', 'fixed'])->default('percentage');
            $table->unsignedInteger('discount_value'); // % or paise
            $table->unsignedInteger('max_discount_cap')->nullable(); // Max discount in paise (for percentage coupons)
            $table->unsignedInteger('min_purchase_amount')->nullable(); // Minimum order in paise
            $table->json('applicable_plan_ids')->nullable(); // null = all plans
            $table->unsignedInteger('usage_limit_total')->nullable(); // null = unlimited
            $table->unsignedInteger('usage_limit_per_user')->default(1);
            $table->unsignedInteger('times_used')->default(0);
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('coupon_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('discount_amount'); // Actual discount in paise
            $table->timestamps();
        });

        // Add coupon columns to subscriptions table
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->foreignId('coupon_id')->nullable()->after('plan_name')->constrained()->nullOnDelete();
            $table->string('coupon_code', 50)->nullable()->after('coupon_id');
            $table->unsignedInteger('discount_amount')->default(0)->after('coupon_code'); // in paise
            $table->unsignedInteger('original_amount')->default(0)->after('discount_amount'); // in paise
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('coupon_id');
            $table->dropColumn(['coupon_code', 'discount_amount', 'original_amount']);
        });

        Schema::dropIfExists('coupon_usages');
        Schema::dropIfExists('coupons');
    }
};

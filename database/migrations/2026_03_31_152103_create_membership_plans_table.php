<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membership_plans', function (Blueprint $table) {
            $table->id();
            $table->string('plan_name', 50);
            $table->string('slug', 50)->unique();
            $table->unsignedInteger('duration_months'); // 0 = lifetime (free plan)
            $table->unsignedInteger('price_inr'); // in rupees (0 for free)
            $table->unsignedInteger('strike_price_inr')->nullable(); // original price for discount display
            $table->json('features')->nullable();
            $table->unsignedInteger('daily_interest_limit')->default(5);
            $table->boolean('can_view_contact')->default(false);
            $table->boolean('is_highlighted')->default(false);
            $table->tinyInteger('sort_order')->unsigned()->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membership_plans');
    }
};

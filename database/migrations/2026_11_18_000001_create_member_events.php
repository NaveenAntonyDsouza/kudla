<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Member events (18 Nov 2026, owner: "build everything" for payment and membership
 * metrics). One small row per step a member takes towards paying, and per time a free
 * member is stopped by a paid feature:
 *
 *   plans_viewed, plan_chosen, coupon_tried, checkout_started, checkout_closed,
 *   payment_failed, paywall (subject = contact / chat_message / personal_message /
 *   interest_limit)
 *
 * Recorded on the server, so every site has them without PostHog (only Kudla has
 * PostHog). No names, emails or phone numbers: who is user_id, the rest is the step.
 * Written by App\Support\MemberEvents; read by Money → Payment Funnel and the weekly review.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('member_events')) {
            return;
        }

        Schema::create('member_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('event', 40);
            $table->string('subject', 60)->nullable();
            $table->json('data')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['event', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_events');
    }
};

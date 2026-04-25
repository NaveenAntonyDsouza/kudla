<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Webhook idempotency tracking for payment gateways.
 *
 * Razorpay (and Stripe / PayPal / etc.) retry webhooks up to 24 hours
 * after first delivery if they don't get a 2xx response — even when
 * the original delivery succeeded but the response timed out. Without
 * a dedupe table, retried events would re-process subscriptions
 * multiple times (double UserMembership rows, double coupon usage, etc.).
 *
 * Each row represents one webhook event we've seen. The
 * (gateway, event_id) unique constraint is what gives us atomic
 * dedupe — INSERT throws on duplicate; the gateway service catches
 * that and returns 200 OK without re-processing.
 *
 * Reference:
 *   docs/mobile-app/phase-2a-api/week-04-interests-payment-push/step-05-razorpay-webhook.md
 *   https://razorpay.com/docs/webhooks/  (retry behaviour)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();

            // Which gateway sent this event. Lets us reuse the table
            // across Razorpay, Stripe, PayPal, etc.
            $table->string('gateway', 30);

            // Gateway's own event identifier. Razorpay calls it
            // "event.id" in the payload; Stripe uses "id"; PayPal
            // uses "event_id". Each gateway owns extracting it from
            // its event payload.
            $table->string('event_id', 100);

            // For analytics / debugging: which event type fired
            // (payment.captured / payment.failed / refund.processed /
            // checkout.session.completed / etc.).
            $table->string('event_type', 80);

            // Outcome of processing this event:
            //   processed  — we acted on it
            //   duplicate  — we'd seen it before, no-op
            //   ignored    — known-but-uninteresting event type (no-op)
            //   failed     — handler threw; row kept for debugging
            $table->string('status', 20)->default('processed');

            // Raw event payload — for debugging + future replay if a
            // bug in our handler missed something. JSON for easy diff.
            $table->json('payload')->nullable();

            $table->timestamps();

            // Unique on (gateway, event_id). Race-safe dedupe relies
            // on the DB unique constraint, not application-level checks.
            $table->unique(['gateway', 'event_id']);

            // Lookup-by-gateway for admin debugging.
            $table->index('gateway');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
    }
};

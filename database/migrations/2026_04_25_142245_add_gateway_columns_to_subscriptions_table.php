<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Multi-gateway support for subscriptions.
 *
 * Adds two columns:
 *   - `gateway` (string slug, default 'razorpay'). Discriminates which
 *     gateway processed the payment. Existing rows get 'razorpay' so
 *     web's existing checkout flow keeps working unchanged.
 *
 *   - `gateway_metadata` (JSON nullable). Flexible storage for
 *     gateway-specific identifiers (e.g. stripe_payment_intent_id,
 *     paypal_capture_id) that don't deserve their own columns. Razorpay
 *     continues to use the dedicated razorpay_order_id /
 *     razorpay_payment_id / razorpay_signature columns for backwards
 *     compatibility with the web's existing flow.
 *
 * Each new gateway can choose which approach to use:
 *   - Dedicated columns: more typed, requires per-gateway migration
 *   - JSON metadata: zero-migration, less typed, fine for opaque IDs
 *
 * Reference: docs/mobile-app/phase-2a-api/week-04-interests-payment-push/step-04-razorpay-order-verify.md
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('gateway', 30)
                ->default('razorpay')
                ->after('plan_name')
                ->comment('Payment gateway slug: razorpay | stripe | paypal | paytm | phonepe');

            $table->json('gateway_metadata')
                ->nullable()
                ->after('razorpay_signature')
                ->comment('Gateway-specific IDs / metadata (used for non-Razorpay gateways).');

            $table->index('gateway');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropIndex(['gateway']);
            $table->dropColumn(['gateway', 'gateway_metadata']);
        });
    }
};

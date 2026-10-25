<?php

namespace App\Support;

use App\Services\Payment\PaymentGatewayManager;

/**
 * Paid plans are "coming soon" when the site isn't in Free Membership mode but
 * has no working payment method yet (Oct 2026: Crasta showed prices and
 * "Upgrade", and checkout then said "No payment method is currently
 * available"). Turns back to normal by itself once a gateway is set up.
 */
class PaidPlans
{
    public static function comingSoon(): bool
    {
        try {
            return empty(app(PaymentGatewayManager::class)->getConfigured());
        } catch (\Throwable) {
            return false;
        }
    }
}

<?php

namespace App\Services;

use App\Models\MembershipPlan;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserMembership;

class PaymentService
{
    /**
     * Create a Razorpay order.
     *
     * @return array{id: string, amount: int, currency: string}
     */
    public function createOrder(int $amountPaise, string $receipt): array
    {
        // TODO: Implement in Phase 5
        throw new \RuntimeException('PaymentService::createOrder() not yet implemented.');
    }

    /**
     * Verify Razorpay payment signature.
     *
     * @return bool Whether the payment signature is valid
     */
    public function verifyPayment(string $orderId, string $paymentId, string $signature): bool
    {
        // TODO: Implement in Phase 5
        throw new \RuntimeException('PaymentService::verifyPayment() not yet implemented.');
    }

    /**
     * Activate a membership plan for the user after successful payment.
     */
    public function activateMembership(User $user, MembershipPlan $plan, Transaction $transaction): UserMembership
    {
        // TODO: Implement in Phase 5
        throw new \RuntimeException('PaymentService::activateMembership() not yet implemented.');
    }
}

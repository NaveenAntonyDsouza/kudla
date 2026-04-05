<?php

namespace App\Http\Controllers;

use App\Models\MembershipPlan;
use App\Models\Subscription;
use App\Models\UserMembership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MembershipController extends Controller
{
    public function index()
    {
        $plans = MembershipPlan::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $user = auth()->user();
        $activeMembership = $user->activeMembership();
        $activePlanId = $activeMembership?->plan_id;

        return view('membership.index', compact('plans', 'activeMembership', 'activePlanId'));
    }

    /**
     * Create Razorpay order and redirect to checkout.
     */
    public function checkout(Request $request)
    {
        $request->validate(['plan_id' => 'required|exists:membership_plans,id']);

        $plan = MembershipPlan::findOrFail($request->plan_id);

        if ($plan->price_inr <= 0) {
            return back()->withErrors(['payment' => 'This is a free plan.']);
        }

        $amountInPaise = $plan->price_inr * 100;

        // Create Razorpay order
        $response = Http::withoutVerifying()
            ->withBasicAuth(config('services.razorpay.key'), config('services.razorpay.secret'))
            ->post('https://api.razorpay.com/v1/orders', [
                'amount' => $amountInPaise,
                'currency' => 'INR',
                'receipt' => 'sub_' . auth()->id() . '_' . time(),
                'notes' => [
                    'user_id' => auth()->id(),
                    'plan_id' => $plan->id,
                    'plan_name' => $plan->plan_name,
                ],
            ]);

        if (!$response->ok()) {
            return back()->withErrors(['payment' => 'Unable to create payment order. Please try again.']);
        }

        $order = $response->json();

        // Save pending subscription (payment audit trail)
        $subscription = Subscription::create([
            'user_id' => auth()->id(),
            'plan_id' => (string) $plan->id,
            'plan_name' => $plan->plan_name,
            'amount' => $amountInPaise,
            'razorpay_order_id' => $order['id'],
            'payment_status' => 'pending',
        ]);

        return view('membership.checkout', [
            'order' => $order,
            'plan' => [
                'id' => $plan->id,
                'name' => $plan->plan_name,
                'price' => $plan->price_inr,
                'duration_months' => $plan->duration_months,
            ],
            'subscription' => $subscription,
            'razorpayKey' => config('services.razorpay.key'),
            'user' => auth()->user(),
        ]);
    }

    /**
     * Verify Razorpay payment after checkout.
     */
    public function verify(Request $request)
    {
        $request->validate([
            'razorpay_order_id' => 'required|string',
            'razorpay_payment_id' => 'required|string',
            'razorpay_signature' => 'required|string',
        ]);

        $subscription = Subscription::where('razorpay_order_id', $request->razorpay_order_id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        // Verify signature
        $expectedSignature = hash_hmac('sha256',
            $request->razorpay_order_id . '|' . $request->razorpay_payment_id,
            config('services.razorpay.secret')
        );

        if ($expectedSignature !== $request->razorpay_signature) {
            $subscription->update(['payment_status' => 'failed']);
            return redirect()->route('membership.index')->withErrors(['payment' => 'Payment verification failed.']);
        }

        $plan = MembershipPlan::find($subscription->plan_id);
        $durationMonths = $plan?->duration_months ?? 1;

        // Update subscription record (payment audit)
        $subscription->update([
            'razorpay_payment_id' => $request->razorpay_payment_id,
            'razorpay_signature' => $request->razorpay_signature,
            'payment_status' => 'paid',
            'starts_at' => today(),
            'expires_at' => today()->addMonths($durationMonths),
            'is_active' => true,
        ]);

        // Deactivate previous subscriptions
        Subscription::where('user_id', auth()->id())
            ->where('id', '!=', $subscription->id)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        // Create UserMembership (this is what isPremium() checks)
        UserMembership::where('user_id', auth()->id())
            ->where('is_active', true)
            ->update(['is_active' => false]);

        UserMembership::create([
            'user_id' => auth()->id(),
            'plan_id' => $plan->id,
            'transaction_id' => null,
            'starts_at' => today(),
            'ends_at' => today()->addMonths($durationMonths),
            'is_active' => true,
        ]);

        return redirect()->route('membership.index')
            ->with('success', 'Payment successful! Your ' . ($plan->plan_name ?? 'Premium') . ' plan is now active.');
    }
}

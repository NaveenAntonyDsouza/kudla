<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MembershipController extends Controller
{
    public function index()
    {
        $plans = config('plans');
        $activeSub = Subscription::where('user_id', auth()->id())
            ->where('is_active', true)
            ->where('expires_at', '>=', today())
            ->first();

        return view('membership.index', compact('plans', 'activeSub'));
    }

    /**
     * Create Razorpay order and redirect to checkout.
     */
    public function checkout(Request $request)
    {
        $request->validate(['plan' => 'required|string|in:basic,standard,premium']);

        $plan = config("plans.{$request->plan}");
        if (! $plan) abort(404);

        $amountInPaise = $plan['price'] * 100;

        // Create Razorpay order
        $response = Http::withoutVerifying()
            ->withBasicAuth(config('services.razorpay.key'), config('services.razorpay.secret'))
            ->post('https://api.razorpay.com/v1/orders', [
                'amount' => $amountInPaise,
                'currency' => 'INR',
                'receipt' => 'sub_' . auth()->id() . '_' . time(),
                'notes' => [
                    'user_id' => auth()->id(),
                    'plan_id' => $plan['id'],
                ],
            ]);

        if (! $response->ok()) {
            return back()->withErrors(['payment' => 'Unable to create payment order. Please try again.']);
        }

        $order = $response->json();

        // Save pending subscription
        $subscription = Subscription::create([
            'user_id' => auth()->id(),
            'plan_id' => $plan['id'],
            'plan_name' => $plan['name'],
            'amount' => $amountInPaise,
            'razorpay_order_id' => $order['id'],
            'payment_status' => 'pending',
        ]);

        return view('membership.checkout', [
            'order' => $order,
            'plan' => $plan,
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

        // Activate subscription
        $plan = config("plans.{$subscription->plan_id}");
        $subscription->update([
            'razorpay_payment_id' => $request->razorpay_payment_id,
            'razorpay_signature' => $request->razorpay_signature,
            'payment_status' => 'paid',
            'starts_at' => today(),
            'expires_at' => today()->addMonths($plan['duration_months']),
            'is_active' => true,
        ]);

        // Deactivate any previous subscriptions
        Subscription::where('user_id', auth()->id())
            ->where('id', '!=', $subscription->id)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        return redirect()->route('membership.index')->with('success', 'Payment successful! Your ' . $subscription->plan_name . ' plan is now active.');
    }
}

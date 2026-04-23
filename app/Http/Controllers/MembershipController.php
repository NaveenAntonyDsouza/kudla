<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
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
        $activeMembership = $user?->activeMembership();
        $activePlanId = $activeMembership?->plan_id;

        return view('membership.index', compact('plans', 'activeMembership', 'activePlanId'));
    }

    /**
     * Validate coupon via AJAX (called from membership page).
     */
    public function applyCoupon(Request $request)
    {
        $request->validate([
            'coupon_code' => 'required|string|max:50',
            'plan_id' => 'required|exists:membership_plans,id',
        ]);

        $coupon = Coupon::where('code', strtoupper(trim($request->coupon_code)))->first();

        if (!$coupon) {
            return response()->json(['valid' => false, 'message' => 'Invalid coupon code.']);
        }

        $plan = MembershipPlan::findOrFail($request->plan_id);
        $priceInPaise = $plan->price_inr * 100;

        $result = $coupon->validateFor($plan->id, $priceInPaise, auth()->id());

        if ($result['valid']) {
            $discountInPaise = $result['discount'];
            $finalPrice = $priceInPaise - $discountInPaise;

            return response()->json([
                'valid' => true,
                'discount' => $discountInPaise / 100, // in rupees for display
                'final_price' => $finalPrice / 100,    // in rupees for display
                'original_price' => $plan->price_inr,
                'coupon_code' => $coupon->code,
                'formatted_discount' => $coupon->formatted_discount,
            ]);
        }

        return response()->json($result);
    }

    /**
     * Create Razorpay order and redirect to checkout.
     */
    public function checkout(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:membership_plans,id',
            'coupon_code' => 'nullable|string|max:50',
        ]);

        $plan = MembershipPlan::findOrFail($request->plan_id);

        if ($plan->price_inr <= 0) {
            return back()->withErrors(['payment' => 'This is a free plan.']);
        }

        $originalAmountInPaise = $plan->price_inr * 100;
        $discountInPaise = 0;
        $coupon = null;

        // Validate and apply coupon if provided
        if ($request->filled('coupon_code')) {
            $coupon = Coupon::where('code', strtoupper(trim($request->coupon_code)))->first();

            if ($coupon) {
                $validation = $coupon->validateFor($plan->id, $originalAmountInPaise, auth()->id());
                if ($validation['valid']) {
                    $discountInPaise = $validation['discount'];
                } else {
                    return back()->withErrors(['coupon' => $validation['message']]);
                }
            } else {
                return back()->withErrors(['coupon' => 'Invalid coupon code.']);
            }
        }

        $amountInPaise = $originalAmountInPaise - $discountInPaise;

        // If discount covers full amount, activate directly without payment
        if ($amountInPaise <= 0) {
            return $this->activateFreePlan($plan, $coupon, $originalAmountInPaise, $discountInPaise);
        }

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
                    'coupon_code' => $coupon?->code,
                    'discount' => $discountInPaise,
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
            'coupon_id' => $coupon?->id,
            'coupon_code' => $coupon?->code,
            'discount_amount' => $discountInPaise,
            'original_amount' => $originalAmountInPaise,
            'amount' => $amountInPaise,
            'razorpay_order_id' => $order['id'],
            'payment_status' => 'pending',
        ]);

        return view('membership.checkout', [
            'order' => $order,
            'plan' => [
                'id' => $plan->id,
                'name' => $plan->plan_name,
                'price' => $amountInPaise / 100,
                'original_price' => $plan->price_inr,
                'duration_months' => $plan->duration_months,
            ],
            'subscription' => $subscription,
            'razorpayKey' => config('services.razorpay.key'),
            'user' => auth()->user(),
            'coupon' => $coupon,
            'discount' => $discountInPaise / 100,
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

        // Record coupon usage if applicable
        if ($subscription->coupon_id && $subscription->discount_amount > 0) {
            $coupon = Coupon::find($subscription->coupon_id);
            $coupon?->recordUsage(auth()->id(), $subscription->id, $subscription->discount_amount);
        }

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

    /**
     * Handle 100% coupon discount — activate without Razorpay.
     */
    protected function activateFreePlan(MembershipPlan $plan, Coupon $coupon, int $originalAmountInPaise, int $discountInPaise)
    {
        $durationMonths = $plan->duration_months;

        $subscription = Subscription::create([
            'user_id' => auth()->id(),
            'plan_id' => (string) $plan->id,
            'plan_name' => $plan->plan_name,
            'coupon_id' => $coupon->id,
            'coupon_code' => $coupon->code,
            'discount_amount' => $discountInPaise,
            'original_amount' => $originalAmountInPaise,
            'amount' => 0,
            'payment_status' => 'paid',
            'starts_at' => today(),
            'expires_at' => today()->addMonths($durationMonths),
            'is_active' => true,
        ]);

        // Record coupon usage
        $coupon->recordUsage(auth()->id(), $subscription->id, $discountInPaise);

        // Deactivate previous
        Subscription::where('user_id', auth()->id())
            ->where('id', '!=', $subscription->id)
            ->where('is_active', true)
            ->update(['is_active' => false]);

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
            ->with('success', 'Coupon applied! Your ' . $plan->plan_name . ' plan is now active (100% discount).');
    }
}

<?php

namespace App\Http\Controllers;

use App\Support\MemberEvents;

use App\Models\Coupon;
use App\Models\MembershipPlan;
use App\Models\Subscription;
use App\Models\UserMembership;
use App\Services\Payment\PaymentGatewayInterface;
use App\Services\Payment\PaymentGatewayManager;
use App\Support\Analytics;
use Illuminate\Http\Request;

class MembershipController extends Controller
{
    /**
     * @param  PaymentGatewayManager  $gateways  Registry of all gateway
     *         services. Bound as a singleton in AppServiceProvider; the
     *         configured (admin-toggle-on + credentials-present) subset
     *         drives the picker / auto-pick logic in checkout().
     */
    public function __construct(private PaymentGatewayManager $gateways) {}

    public function index()
    {
        // Free-membership mode: no pricing/checkout — everyone already has full
        // access, so show a simple notice instead of the plan tiers.
        if (\App\Models\User::freeMembershipEnabled()) {
            return view('membership.free-notice');
        }

        $plans = MembershipPlan::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $user = auth()->user();
        $activeMembership = $user?->activeMembership();
        $activePlanId = $activeMembership?->plan_id;

        // payment funnel (18 Nov 2026): once a day per member, or per visitor's session
        // ?from=popup: opened from the plans pop-up (DailyPopup), so its effect can be counted
        $viewData = array_filter(['has_plan' => $activePlanId ? true : null, 'from' => request('from') === 'popup' ? 'popup' : null]);
        MemberEvents::record(MemberEvents::PLANS_VIEWED, $user ? 'member' : 'visitor', $viewData, null, 'page');

        // a code the member already applied this visit shows again on every plan it fits
        $applied = [];
        if ($user && ($remembered = $this->rememberedCoupon())) {
            foreach ($plans as $plan) {
                if ($plan->price_inr > 0) {
                    $check = $remembered->validateFor($plan->id, $plan->price_inr * 100, $user->id);
                    if ($check['valid']) {
                        $applied[$plan->id] = ['code' => $remembered->code, 'discount' => $check['discount'] / 100, 'final' => ($plan->price_inr * 100 - $check['discount']) / 100];
                    }
                }
            }
        }

        return view('membership.index', compact('plans', 'activeMembership', 'activePlanId', 'applied'));
    }

    /**
     * Coupon codes are remembered for the visit once accepted (19 Nov 2026: a member
     * applied KUDLA500 on a plan card, then paid full price — the code lived only in
     * that card, so the comparison table's UPGRADE buttons, a reload, going back from
     * the payment page, or a retry after a failed payment all dropped it).
     */
    public const COUPON_SESSION_KEY = 'membership_coupon';

    private function rememberedCoupon(): ?Coupon
    {
        $code = session(self::COUPON_SESSION_KEY);

        return $code ? Coupon::where('code', $code)->first() : null;
    }

    /**
     * Validate coupon via AJAX (called from membership page).
     */
    public function applyCoupon(Request $request)
    {
        // "Remove" on a plan card: forget the remembered code too
        if ($request->boolean('remove')) {
            session()->forget(self::COUPON_SESSION_KEY);

            return response()->json(['removed' => true]);
        }

        $request->validate([
            'coupon_code' => 'required|string|max:50',
            'plan_id' => 'required|exists:membership_plans,id',
        ]);

        $coupon = Coupon::where('code', strtoupper(trim($request->coupon_code)))->first();
        $typed = strtoupper(trim($request->coupon_code));

        if (!$coupon) {
            MemberEvents::record(MemberEvents::COUPON_TRIED, $typed, ['valid' => false, 'reason' => 'no such code', 'plan_id' => (int) $request->plan_id]);
            return response()->json(['valid' => false, 'message' => 'Invalid coupon code.']);
        }

        $plan = MembershipPlan::findOrFail($request->plan_id);
        $priceInPaise = $plan->price_inr * 100;

        $result = $coupon->validateFor($plan->id, $priceInPaise, auth()->id());
        MemberEvents::record(MemberEvents::COUPON_TRIED, $coupon->code, array_filter([
            'valid' => (bool) $result['valid'],
            'reason' => $result['valid'] ? null : ($result['message'] ?? null),
            'plan_id' => $plan->id,
        ], fn ($v) => $v !== null));

        if ($result['valid']) {
            session()->put(self::COUPON_SESSION_KEY, $coupon->code);
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
     * Checkout entry point. Three branches:
     *
     *   • Free (coupon covers 100%) → activate directly, skip payment.
     *   • One gateway enabled OR user picked from the picker → create
     *     a pending Subscription, call the gateway's createOrder(), and
     *     render the gateway's web flow (Razorpay JS / PhonePe redirect).
     *   • Two-plus gateways enabled AND no `gateway` field in the form
     *     → render the picker, which posts back here with the chosen slug.
     *
     * Validates the chosen gateway against PaymentGatewayManager::getConfigured()
     * (admin toggle on + credentials present), so a toggled-off gateway
     * can never be coerced into use via a hand-crafted POST.
     */
    public function checkout(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:membership_plans,id',
            'coupon_code' => 'nullable|string|max:50',
            // Optional. Set by the gateway-picker form. Must match an
            // entry in PaymentGatewayManager::getConfigured() if present.
            'gateway' => 'nullable|string|max:30',
        ]);

        $plan = MembershipPlan::findOrFail($request->plan_id);

        if ($plan->price_inr <= 0) {
            return back()->withErrors(['payment' => 'This is a free plan.']);
        }

        // payment funnel: a plan button pressed (once a day per member and plan, so the
        // gateway picker's second post doesn't count twice)
        MemberEvents::record(MemberEvents::PLAN_CHOSEN, $plan->plan_name, ['plan_id' => $plan->id, 'with_coupon' => $request->filled('coupon_code')], null, 'plan-'.$plan->id);

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
                    MemberEvents::record(MemberEvents::COUPON_TRIED, $coupon->code, ['valid' => false, 'reason' => $validation['message'] ?? null, 'plan_id' => $plan->id, 'at' => 'checkout']);
                    return back()->withErrors(['coupon' => $validation['message']]);
                }
            } else {
                MemberEvents::record(MemberEvents::COUPON_TRIED, strtoupper(trim($request->coupon_code)), ['valid' => false, 'reason' => 'no such code', 'plan_id' => $plan->id, 'at' => 'checkout']);
                return back()->withErrors(['coupon' => 'Invalid coupon code.']);
            }
        } elseif ($remembered = $this->rememberedCoupon()) {
            // no code on this button, but one was accepted earlier this visit: use it if it
            // fits this plan; if not (used up, other plan only), quietly charge full price
            $validation = $remembered->validateFor($plan->id, $originalAmountInPaise, auth()->id());
            if ($validation['valid']) {
                $coupon = $remembered;
                $discountInPaise = $validation['discount'];
            }
        }

        $amountInPaise = $originalAmountInPaise - $discountInPaise;

        // If discount covers full amount, activate directly without payment
        if ($amountInPaise <= 0) {
            return $this->activateFreePlan($plan, $coupon, $originalAmountInPaise, $discountInPaise);
        }

        // ── Pick a gateway ────────────────────────────────────────────
        $available = $this->gateways->getConfigured();

        // Inside the Kudla Matrimony Android WebView app, drop any gateway an
        // admin has switched off for the app via GatewaySettings → "Show in
        // mobile app". PhonePe defaults off there because its hosted checkout
        // only offers a cross-device UPI QR in a WebView (no on-device UPI
        // intent). Web browsers and the API/SDK flow keep every gateway. The
        // fallback guarantees app users are never stranded with zero options
        // if an admin happens to hide them all.
        if ($this->isWebViewApp($request)) {
            $appVisible = array_filter(
                $available,
                fn (PaymentGatewayInterface $gateway) => config('services.'.$gateway->getSlug().'.show_in_app', true) !== false,
            );
            if (! empty($appVisible)) {
                $available = $appVisible;
            }
        }

        if (empty($available)) {
            return back()->withErrors([
                'payment' => 'No payment method is currently available. Please contact support.',
            ]);
        }

        $requestedSlug = $request->input('gateway');

        if ($requestedSlug !== null && $requestedSlug !== '') {
            // User came back from the picker. Validate they picked a real,
            // currently-enabled gateway — protects against a stale form
            // posted after an admin disabled the gateway, and against
            // hand-crafted POSTs trying to coerce a non-enabled gateway.
            if (! isset($available[$requestedSlug])) {
                return back()->withErrors(['payment' => 'The selected payment method is no longer available.']);
            }
            $gateway = $available[$requestedSlug];
        } elseif (count($available) === 1) {
            // Auto-pick when there's only one option — saves a click.
            $gateway = reset($available);
        } else {
            // 2+ gateways enabled and the user hasn't chosen yet → show
            // the picker. Carries plan_id + coupon_code through so the
            // user doesn't lose their coupon when they come back here.
            return view('membership.gateway-picker', [
                'plan' => $plan,
                'amount' => $amountInPaise / 100,
                'coupon' => $coupon,
                'discount' => $discountInPaise / 100,
                'gateways' => $available,
            ]);
        }

        return $this->dispatchGatewayCheckout(
            gateway: $gateway,
            plan: $plan,
            coupon: $coupon,
            originalAmountInPaise: $originalAmountInPaise,
            amountInPaise: $amountInPaise,
            discountInPaise: $discountInPaise,
        );
    }

    /**
     * True when the request comes from the Kudla Matrimony Android app's
     * WebView, which appends "KudlaMatrimonyApp/<version>" to its User-Agent.
     * Scopes app-only payment behaviour (see checkout()).
     */
    private function isWebViewApp(Request $request): bool
    {
        return str_contains((string) $request->userAgent(), 'KudlaMatrimonyApp');
    }

    /**
     * Create the gateway order, persist the pending Subscription, and
     * render the gateway's web flow. One method per gateway slug — Razorpay
     * needs a view (JS SDK), PhonePe needs a redirect, etc.
     */
    protected function dispatchGatewayCheckout(
        PaymentGatewayInterface $gateway,
        MembershipPlan $plan,
        ?Coupon $coupon,
        int $originalAmountInPaise,
        int $amountInPaise,
        int $discountInPaise,
    ) {
        // Persist a pending Subscription FIRST so the gateway's order id
        // can be tied back to a known row when the user returns / the
        // webhook fires. gateway slug is stamped immediately so a 404
        // verify or a webhook-without-known-order can still route correctly.
        $subscription = Subscription::create([
            'user_id' => auth()->id(),
            'plan_id' => (string) $plan->id,
            'plan_name' => $plan->plan_name,
            'coupon_id' => $coupon?->id,
            'coupon_code' => $coupon?->code,
            'discount_amount' => $discountInPaise,
            'original_amount' => $originalAmountInPaise,
            'amount' => $amountInPaise,
            'gateway' => $gateway->getSlug(),
            'payment_status' => 'pending',
        ]);

        try {
            $orderResponse = $gateway->createOrder($amountInPaise, [
                'user_id' => auth()->id(),
                'plan_id' => $plan->id,
                'plan_name' => $plan->plan_name,
                'subscription_id' => $subscription->id,
                'coupon_code' => $coupon?->code,
                'discount' => $discountInPaise,
                'receipt' => 'sub_'.auth()->id().'_'.$subscription->id,
                // PhonePe consumes this as the merchantUrls.redirectUrl
                // so the buyer lands at /membership-plans/return/phonepe
                // after paying. Razorpay etc. ignore it (they're inline-JS
                // flows that POST directly back to /verify).
                'redirect_url' => route('membership.return.phonepe', ['s' => $subscription->id]),
            ]);
        } catch (\Throwable $e) {
            // Roll back the pending subscription so we don't leak rows
            // for orders that never made it past the gateway.
            $subscription->delete();
            report($e);
            return back()->withErrors(['payment' => 'Unable to create payment order. Please try again.']);
        }

        // Persist gateway-specific IDs (razorpay_order_id, phonepe_merchant_order_id, etc.)
        $gateway->applyOrderIdsToSubscription($subscription, $orderResponse);

        MemberEvents::record(MemberEvents::CHECKOUT_STARTED, $gateway->getSlug(), [
            'subscription_id' => $subscription->id,
            'plan' => $plan->plan_name,
            'amount' => (int) round($amountInPaise / 100),
            'coupon' => $coupon?->code,
        ]);

        // Render the gateway's web flow. Each slug has its own surface:
        //   razorpay → inline JS SDK (membership.checkout view)
        //   phonepe  → redirect to hosted PhonePe checkout
        return match ($gateway->getSlug()) {
            'razorpay' => $this->renderRazorpayCheckout($plan, $subscription, $orderResponse, $coupon, $amountInPaise, $discountInPaise),
            'phonepe' => $this->renderPhonePeCheckout($orderResponse),
            default => back()->withErrors([
                'payment' => 'Web checkout for '.$gateway->getName().' is not yet implemented. Please pick a different method.',
            ]),
        };
    }

    /**
     * Render the Razorpay browser-side JS SDK with the prepared order.
     * The view auto-opens checkout.razorpay.com and POSTs the verify
     * payload back to /membership-plans/verify on success.
     */
    protected function renderRazorpayCheckout(
        MembershipPlan $plan,
        Subscription $subscription,
        array $orderResponse,
        ?Coupon $coupon,
        int $amountInPaise,
        int $discountInPaise,
    ) {
        return view('membership.checkout', [
            'order' => [
                // Old shape kept for backwards compat with the existing
                // Razorpay JS view (which uses $order['id'] / $order['amount']).
                'id' => $orderResponse['order_id'],
                'amount' => $orderResponse['amount'],
            ],
            'plan' => [
                'id' => $plan->id,
                'name' => $plan->plan_name,
                'price' => $amountInPaise / 100,
                'original_price' => $plan->price_inr,
                'duration_months' => $plan->duration_months,
            ],
            'subscription' => $subscription,
            'razorpayKey' => $orderResponse['key_id'],
            'user' => auth()->user(),
            'coupon' => $coupon,
            'discount' => $discountInPaise / 100,
        ]);
    }

    /**
     * PhonePe is a redirect-based flow. PhonePe returns a hosted-checkout
     * `redirect_url` from its /pay API; we send the buyer there and they
     * come back to /membership-plans/return/phonepe?s={subscription_id}
     * after paying. That handler verifies via the order-status API and
     * activates.
     */
    protected function renderPhonePeCheckout(array $orderResponse)
    {
        $redirectUrl = $orderResponse['redirect_url'] ?? null;

        if (! is_string($redirectUrl) || $redirectUrl === '') {
            // Defensive — PhonePeService::createOrder would normally have
            // thrown a RuntimeException already if the URL was missing.
            return redirect()->route('membership.index')
                ->withErrors(['payment' => 'PhonePe did not return a checkout URL. Please try again.']);
        }

        return redirect()->away($redirectUrl);
    }

    /**
     * Handle the user's return from PhonePe's hosted checkout. Two truths
     * race here:
     *
     *   • This synchronous return → calls verifyPayment() which polls
     *     PhonePe's /status API and confirms the order is COMPLETED.
     *   • The PhonePe webhook (POST /api/v1/webhooks/phonepe — see the
     *     `webhooks/{gateway}` route in routes/api.php) → fires
     *     `checkout.order.completed` async; PhonePeService::handleWebhook
     *     activates the subscription.
     *
     * Whichever wins activates; the other becomes a no-op because
     * SubscriptionActivator::activate is idempotent. If the buyer returns
     * before PhonePe has settled (rare but possible — UPI is async on
     * PhonePe's side too), they see "Payment is still being confirmed"
     * and a link back to the dashboard. The webhook will activate later
     * and the dashboard will reflect it on next page load.
     */
    public function returnFromPhonePe(Request $request)
    {
        $subscriptionId = (int) $request->query('s');
        $subscription = Subscription::where('id', $subscriptionId)
            ->where('user_id', auth()->id())
            ->where('gateway', 'phonepe')
            ->first();

        if (! $subscription) {
            return redirect()->route('membership.index')
                ->withErrors(['payment' => 'We could not find your payment. If you were charged, please contact support with your bank reference.']);
        }

        // Already activated by the webhook? Land on the success page.
        if ($subscription->payment_status === 'paid') {
            $planName = MembershipPlan::find($subscription->plan_id)?->plan_name ?? 'Premium';
            $this->trackPurchase($subscription);
            return redirect()->route('membership.index')
                ->with('success', 'Payment successful! Your '.$planName.' plan is now active.');
        }

        $merchantOrderId = $subscription->gateway_metadata['phonepe_merchant_order_id'] ?? null;

        if (! $merchantOrderId) {
            return redirect()->route('membership.index')
                ->withErrors(['payment' => 'Payment record is missing the PhonePe reference. Please contact support.']);
        }

        $phonepe = $this->gateways->forSlug('phonepe');
        if (! $phonepe) {
            return redirect()->route('membership.index')
                ->withErrors(['payment' => 'PhonePe is currently unavailable.']);
        }

        $verified = $phonepe->verifyPayment(
            ['phonepe_merchant_order_id' => $merchantOrderId],
            $subscription,
        );

        if (! $verified) {
            // Could be: payment in progress (UPI lag), user cancelled, or
            // genuine failure. Don't mark failed here — the webhook is the
            // source of truth for that. Just tell the buyer to wait.
            return redirect()->route('membership.index')
                ->with('info', 'Your payment is still being confirmed. This page will reflect the change once the bank settles — usually within a few minutes.');
        }

        // Verified successful — record gateway IDs and activate. The
        // activator is idempotent so a concurrent webhook firing is fine.
        $phonepe->applyVerifiedIdsToSubscription($subscription, [
            'phonepe_merchant_order_id' => $merchantOrderId,
        ]);

        $activator = app(\App\Services\Payment\SubscriptionActivator::class);
        $activator->activate($subscription);

        $planName = MembershipPlan::find($subscription->plan_id)?->plan_name ?? 'Premium';

        $this->trackPurchase($subscription);
        return redirect()->route('membership.index')
            ->with('success', 'Payment successful! Your '.$planName.' plan is now active.');
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

        // Already activated — the Razorpay webhook (SubscriptionActivator) got
        // here first, or the member re-submitted / refreshed this page. Don't
        // create a second membership, count the coupon twice, or re-send the
        // "plan active" email.
        if ($subscription->payment_status === 'paid') {
            $this->trackPurchase($subscription);
            return redirect()->route('membership.index')
                ->with('success', 'Payment successful! Your ' . ($plan->plan_name ?? 'Premium') . ' plan is now active.');
        }

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

        $this->trackPurchase($subscription);
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

        $this->trackPurchase($subscription);
        return redirect()->route('membership.index')
            ->with('success', 'Coupon applied! Your ' . $plan->plan_name . ' plan is now active (100% discount).');
    }

    /**
     * Record the purchase key moment (GA4 'purchase' / Meta 'Purchase') on
     * the page where the member sees "Payment successful". The order id is
     * the transaction id, so a refresh or a second success path is counted
     * once by GA4. Amounts are stored in paise; plans are priced in INR.
     */
    /**
     * Payment-window steps the server cannot see (18 Nov 2026): the member closed
     * Razorpay's window, or Razorpay told the page why a payment failed. Sent by
     * membership.checkout with navigator.sendBeacon. Only for the member's own
     * unpaid payment; never changes its status (the webhook stays the judge).
     */
    public function event(Request $request)
    {
        $data = $request->validate([
            'event' => 'required|in:checkout_closed,payment_failed',
            's' => 'required|integer',
            'code' => 'nullable|string|max:60',
            'reason' => 'nullable|string|max:160',
            'step' => 'nullable|string|max:60',
        ]);

        $subscription = Subscription::where('id', $data['s'])->where('user_id', auth()->id())->first();
        if (! $subscription || $subscription->payment_status === 'paid') {
            return response()->noContent();
        }

        if ($data['event'] === 'payment_failed') {
            $reason = trim(implode(' — ', array_filter([$data['reason'] ?? null, $data['code'] ?? null])));
            $subscription->update(['gateway_metadata' => array_merge((array) ($subscription->gateway_metadata ?? []), ['failure_reason' => $reason ?: 'failed in the payment window'])]);
            MemberEvents::record(MemberEvents::PAYMENT_FAILED, $subscription->gateway, ['subscription_id' => $subscription->id, 'reason' => $reason, 'step' => $data['step'] ?? null, 'from' => 'browser']);
        } else {
            MemberEvents::record(MemberEvents::CHECKOUT_CLOSED, $subscription->gateway, ['subscription_id' => $subscription->id]);
        }

        return response()->noContent();
    }

    private function trackPurchase(?Subscription $subscription): void
    {
        if (! $subscription) {
            return;
        }
        $plan = MembershipPlan::find($subscription->plan_id);
        $value = round(((int) $subscription->amount) / 100, 2);

        Analytics::track('purchase', [
            'transaction_id' => (string) $subscription->id,
            'value' => $value,
            'currency' => 'INR',
            'coupon' => (string) ($subscription->coupon_code ?? ''),
            'items' => [[
                'item_id' => 'plan-' . ($plan?->id ?? $subscription->plan_id),
                'item_name' => $plan?->plan_name ?? ($subscription->plan_name ?? 'Membership'),
                'price' => $value,
                'quantity' => 1,
            ]],
        ], 'Purchase');
    }
}

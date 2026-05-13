{{--
    Gateway picker — shown when MORE THAN ONE payment gateway is enabled
    in admin → Settings → Email, SMS & Payment. If only one gateway is
    enabled, MembershipController::checkout skips this view and dispatches
    straight into that gateway's flow.

    Each card is its own POST to /membership-plans/checkout with a hidden
    `gateway` field. plan_id + coupon_code are carried over from the
    original Buy submission so the user doesn't lose their coupon.

    Required variables:
      $plan        — MembershipPlan eloquent model
      $amount      — final amount in rupees (after coupon discount)
      $coupon      — Coupon model or null
      $discount    — rupees off (0 when no coupon applied)
      $gateways    — array of PaymentGatewayInterface keyed by slug
                     (only the configured + enabled ones)
--}}
<x-layouts.app title="Choose Payment Method">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <div class="text-center mb-8">
            <h1 class="text-2xl font-serif font-bold text-gray-900">Choose a payment method</h1>
            <p class="text-sm text-gray-500 mt-2">Pick how you'd like to complete the payment for your {{ $plan->plan_name }} plan.</p>
        </div>

        {{-- Order summary --}}
        <div class="bg-white rounded-lg border border-gray-200 shadow-xs p-5 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-semibold text-gray-900">{{ $plan->plan_name }} Plan</p>
                    <p class="text-xs text-gray-500 mt-0.5">{{ $plan->duration_months }} {{ $plan->duration_months === 1 ? 'month' : 'months' }} membership</p>
                </div>
                <div class="text-right">
                    @if($discount > 0)
                        <p class="text-xs text-gray-400 line-through">₹{{ number_format($plan->price_inr) }}</p>
                    @endif
                    <p class="text-lg font-bold text-(--color-primary)">₹{{ number_format($amount) }}</p>
                </div>
            </div>
            @if($coupon)
                <div class="mt-3 pt-3 border-t border-gray-100 flex items-center gap-2 text-xs text-green-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span>Coupon <strong>{{ $coupon->code }}</strong> applied — you save ₹{{ number_format($discount) }}.</span>
                </div>
            @endif
        </div>

        @if ($errors->any())
            <div class="mb-6 p-3 bg-red-50 border border-red-200 rounded-lg">
                @foreach ($errors->all() as $error)
                    <p class="text-sm text-red-600 font-medium">{{ $error }}</p>
                @endforeach
            </div>
        @endif

        {{-- Gateway cards. Each is its own form so the user just clicks
             the "Pay with X" button — no second confirmation step. --}}
        <div class="space-y-3">
            @foreach($gateways as $slug => $gateway)
                <form method="POST" action="{{ route('membership.checkout') }}">
                    @csrf
                    <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                    @if($coupon)
                        <input type="hidden" name="coupon_code" value="{{ $coupon->code }}">
                    @endif
                    <input type="hidden" name="gateway" value="{{ $slug }}">

                    <button type="submit"
                        class="w-full bg-white hover:bg-gray-50 border border-gray-200 hover:border-(--color-primary) rounded-lg p-4 flex items-center justify-between transition-colors group">
                        <div class="flex items-center gap-4">
                            {{-- Gateway logo placeholder — uniform circle with
                                 the first letter of the gateway name. Avoids
                                 third-party logo licensing headaches; if you
                                 want real logos later, drop SVGs in
                                 resources/svg/gateways/{$slug}.svg and read
                                 here. --}}
                            <div class="w-12 h-12 rounded-full bg-(--color-primary-light) text-(--color-primary) flex items-center justify-center text-lg font-bold shrink-0">
                                {{ strtoupper(substr($gateway->getName(), 0, 1)) }}
                            </div>
                            <div class="text-left">
                                <p class="text-base font-semibold text-gray-900">Pay with {{ $gateway->getName() }}</p>
                                <p class="text-xs text-gray-500">
                                    @switch($slug)
                                        @case('razorpay') Cards · UPI · Net banking · Wallets @break
                                        @case('phonepe') UPI · Cards · Wallets @break
                                        @case('stripe') International cards · Wallets @break
                                        @case('paypal') PayPal balance · International cards @break
                                        @case('paytm') Paytm wallet · UPI · Cards @break
                                        @default Secure online payment
                                    @endswitch
                                </p>
                            </div>
                        </div>
                        <svg class="w-5 h-5 text-gray-400 group-hover:text-(--color-primary) transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                </form>
            @endforeach
        </div>

        <div class="mt-6 text-center">
            <a href="{{ route('membership.index') }}" class="text-sm text-gray-500 hover:text-(--color-primary) underline underline-offset-2">
                ← Back to plans
            </a>
        </div>

        <div class="mt-8 p-3 bg-gray-50 border border-gray-200 rounded-lg flex gap-2">
            <svg class="w-4 h-4 text-gray-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
            <p class="text-xs text-gray-600">Your payment is processed by the chosen provider. We don't see or store your card / UPI details.</p>
        </div>

    </div>
</x-layouts.app>

{{--
    Payment Details modal — rendered by the "Details" row action on
    /admin/payment-histories. The middle section switches on
    $payment->gateway so PhonePe rows show their own IDs (merchantOrderId,
    PhonePe orderId, transactionId, state, payment mode) instead of empty
    "Razorpay Order ID" placeholders.

    Razorpay IDs live in dedicated columns on the subscriptions table
    (razorpay_order_id / razorpay_payment_id / razorpay_signature). Every
    other gateway packs its identifiers into gateway_metadata JSON.
--}}
@php
    $gatewayLabels = [
        'razorpay' => 'Razorpay',
        'phonepe' => 'PhonePe',
        'stripe' => 'Stripe',
        'paypal' => 'PayPal',
        'paytm' => 'Paytm',
    ];
    $gatewayLabel = $gatewayLabels[$payment->gateway] ?? ($payment->gateway ? ucfirst($payment->gateway) : '—');
    $meta = $payment->gateway_metadata ?? [];
@endphp
<div class="space-y-4 text-sm">
    <div class="grid grid-cols-2 gap-4">
        <div>
            <p class="text-gray-500 text-xs">User</p>
            <p class="font-medium">{{ $payment->user?->name ?? '—' }}</p>
            <p class="text-xs text-gray-400">{{ $payment->user?->email }}</p>
        </div>
        <div>
            <p class="text-gray-500 text-xs">Plan</p>
            <p class="font-medium">{{ $payment->plan_name }}</p>
        </div>
    </div>

    <div class="grid grid-cols-3 gap-4">
        <div>
            <p class="text-gray-500 text-xs">Amount</p>
            <p class="font-medium text-lg">₹{{ number_format($payment->amount / 100, 2) }}</p>
        </div>
        <div>
            <p class="text-gray-500 text-xs">Status</p>
            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold
                {{ $payment->payment_status === 'paid' ? 'bg-green-100 text-green-800' : '' }}
                {{ $payment->payment_status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                {{ $payment->payment_status === 'failed' ? 'bg-red-100 text-red-800' : '' }}">
                {{ ucfirst($payment->payment_status) }}
            </span>
        </div>
        <div>
            <p class="text-gray-500 text-xs">Gateway</p>
            <p class="font-medium">{{ $gatewayLabel }}</p>
        </div>
    </div>

    @if($payment->coupon_code)
        <div class="bg-amber-50 border border-amber-200 rounded p-3">
            <p class="text-amber-800 text-xs font-semibold">Coupon applied</p>
            <p class="text-xs mt-1">
                <span class="font-mono font-medium">{{ $payment->coupon_code }}</span>
                @if($payment->discount_amount)
                    — saved ₹{{ number_format($payment->discount_amount / 100, 2) }}
                    of ₹{{ number_format($payment->original_amount / 100, 2) }}
                @endif
            </p>
        </div>
    @endif

    <hr class="border-gray-200">

    {{-- Gateway-specific identifiers. Each gateway has different
         persistence: Razorpay uses dedicated columns; everyone else
         packs IDs into gateway_metadata JSON. --}}
    <div class="space-y-3">
        @switch($payment->gateway)
            @case('razorpay')
                <div>
                    <p class="text-gray-500 text-xs">Razorpay Order ID</p>
                    <p class="font-mono text-xs">{{ $payment->razorpay_order_id ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-gray-500 text-xs">Razorpay Payment ID</p>
                    <p class="font-mono text-xs">{{ $payment->razorpay_payment_id ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-gray-500 text-xs">Razorpay Signature</p>
                    <p class="font-mono text-xs break-all">{{ $payment->razorpay_signature ?? '—' }}</p>
                </div>
                @break

            @case('phonepe')
                <div>
                    <p class="text-gray-500 text-xs">Merchant Order ID</p>
                    <p class="font-mono text-xs">{{ $meta['phonepe_merchant_order_id'] ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-gray-500 text-xs">PhonePe Order ID</p>
                    <p class="font-mono text-xs">{{ $meta['phonepe_order_id'] ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-gray-500 text-xs">Transaction ID</p>
                    <p class="font-mono text-xs">{{ $meta['phonepe_transaction_id'] ?? '—' }}</p>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-gray-500 text-xs">Payment Mode</p>
                        <p class="text-xs">{{ $meta['phonepe_payment_mode'] ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 text-xs">State</p>
                        <p class="text-xs">{{ $meta['phonepe_state'] ?? '—' }}</p>
                    </div>
                </div>
                @break

            @default
                {{-- Legacy rows (created before the `gateway` column was
                     stamped) almost certainly used Razorpay — fall back
                     to those columns. Also dump gateway_metadata for any
                     future gateway not yet special-cased above so admins
                     can still see the raw IDs without going into tinker. --}}
                @if($payment->razorpay_order_id || $payment->razorpay_payment_id)
                    <div>
                        <p class="text-gray-500 text-xs">Razorpay Order ID</p>
                        <p class="font-mono text-xs">{{ $payment->razorpay_order_id ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 text-xs">Razorpay Payment ID</p>
                        <p class="font-mono text-xs">{{ $payment->razorpay_payment_id ?? '—' }}</p>
                    </div>
                @endif
                @if(!empty($meta))
                    <div>
                        <p class="text-gray-500 text-xs">Gateway Metadata</p>
                        <pre class="font-mono text-xs bg-gray-50 p-2 rounded overflow-auto">{{ json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                    </div>
                @endif
                @if(! $payment->razorpay_order_id && ! $payment->razorpay_payment_id && empty($meta))
                    <p class="text-gray-500 text-xs italic">No gateway identifiers recorded for this payment.</p>
                @endif
        @endswitch
    </div>

    <hr class="border-gray-200">

    <div class="grid grid-cols-3 gap-4">
        <div>
            <p class="text-gray-500 text-xs">Start Date</p>
            <p class="font-medium">{{ $payment->starts_at?->format('d M Y') ?? '—' }}</p>
        </div>
        <div>
            <p class="text-gray-500 text-xs">Expiry Date</p>
            <p class="font-medium {{ $payment->expires_at && $payment->expires_at < now() ? 'text-red-600' : '' }}">
                {{ $payment->expires_at?->format('d M Y') ?? '—' }}
            </p>
        </div>
        <div>
            <p class="text-gray-500 text-xs">Payment Date</p>
            <p class="font-medium">{{ $payment->created_at?->displayTz()->format('d M Y H:i') }}</p>
        </div>
    </div>
</div>

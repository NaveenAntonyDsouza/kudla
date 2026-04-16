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

    <div class="grid grid-cols-2 gap-4">
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
    </div>

    <hr class="border-gray-200">

    <div class="space-y-3">
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
            <p class="font-medium">{{ $payment->created_at?->format('d M Y H:i') }}</p>
        </div>
    </div>
</div>

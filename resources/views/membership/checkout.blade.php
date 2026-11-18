<!DOCTYPE html>
<html>
<head>
    <title>Processing Payment - {{ config('app.name') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
</head>
<body style="display:flex; align-items:center; justify-content:center; min-height:100vh; background:#f9fafb; font-family:system-ui, sans-serif;">
    <div style="text-align:center; padding:2rem;">
        <p style="font-size:1.125rem; color:#374151; margin-bottom:1rem;">Redirecting to payment...</p>
        <p style="font-size:0.875rem; color:#6b7280;">If nothing happens, <a href="#" onclick="openRazorpay()" style="color:#8B1D91; text-decoration:underline;">click here</a></p>
    </div>

    <script>
        function openRazorpay() {
            var options = {
                key: "{{ $razorpayKey }}",
                amount: {{ $order['amount'] }},
                currency: "INR",
                name: "{{ config('app.name') }}",
                description: "{{ $plan['name'] }} Plan - {{ $plan['duration_months'] }} Months",
                order_id: "{{ $order['id'] }}",
                handler: function (response) {
                    // Submit payment details to server
                    var form = document.createElement('form');
                    form.method = 'POST';
                    form.action = "{{ route('membership.verify') }}";

                    var fields = {
                        '_token': "{{ csrf_token() }}",
                        'razorpay_order_id': response.razorpay_order_id,
                        'razorpay_payment_id': response.razorpay_payment_id,
                        'razorpay_signature': response.razorpay_signature,
                    };

                    for (var key in fields) {
                        var input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = key;
                        input.value = fields[key];
                        form.appendChild(input);
                    }

                    document.body.appendChild(form);
                    form.submit();
                },
                prefill: {
                    name: "{{ $user->name }}",
                    email: "{{ $user->email }}",
                    contact: "{{ $user->phone }}"
                },
                theme: {
                    color: "#8B1D91"
                },
                modal: {
                    ondismiss: function() {
                        sendPaymentEvent({ event: 'checkout_closed' });
                        window.location.href = "{{ route('membership.index') }}";
                    }
                }
            };

            // Enable Razorpay's UPI Intent flow when the checkout is opened
            // inside the Kudla Matrimony Android app's WebView. The app appends
            // "KudlaMatrimonyApp/<version>" to its WebView User-Agent. Regular
            // browser visitors do not match, so their checkout is unchanged.
            // The app's WebViewClient handles the resulting upi:// / intent://
            // URLs to launch GPay / PhonePe / Paytm etc.
            if (/KudlaMatrimonyApp/i.test(navigator.userAgent)) {
                options.webview_intent = true;
            }

            var rzp = new Razorpay(options);
            // why a payment failed, as Razorpay tells the page (payment funnel, 18 Nov 2026)
            rzp.on('payment.failed', function (response) {
                var e = (response && response.error) || {};
                sendPaymentEvent({ event: 'payment_failed', code: e.code || '', reason: e.description || e.reason || '', step: e.step || '' });
            });
            rzp.open();
        }

        // payment funnel: tell the server about steps it cannot see; never blocks the page
        function sendPaymentEvent(fields) {
            try {
                var f = new FormData();
                f.append('_token', "{{ csrf_token() }}");
                f.append('s', "{{ $subscription->id }}");
                for (var k in fields) { f.append(k, String(fields[k]).slice(0, 150)); }
                if (navigator.sendBeacon) { navigator.sendBeacon("{{ route('membership.event') }}", f); }
                else { fetch("{{ route('membership.event') }}", { method: 'POST', body: f, keepalive: true }); }
            } catch (err) {}
        }

        // Auto-open on page load
        document.addEventListener('DOMContentLoaded', openRazorpay);
    </script>
</body>
</html>

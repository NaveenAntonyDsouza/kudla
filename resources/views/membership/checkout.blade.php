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
                        window.location.href = "{{ route('membership.index') }}";
                    }
                }
            };

            var rzp = new Razorpay(options);
            rzp.open();
        }

        // Auto-open on page load
        document.addEventListener('DOMContentLoaded', openRazorpay);
    </script>
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>Unsubscribe — {{ \App\Models\SiteSetting::getValue('site_name', 'Matrimony') }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 2rem 1rem; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f9fafb; color: #111827; }
        .card { max-width: 480px; margin: 2rem auto; background: white; border-radius: 12px; padding: 2.5rem 2rem; box-shadow: 0 4px 12px rgba(0,0,0,0.05); text-align: center; }
        h1 { font-size: 1.5rem; margin: 0 0 0.5rem; }
        p.sub { color: #6b7280; margin: 0 0 1.75rem; line-height: 1.5; }
        button { width: 100%; padding: 0.85rem 1rem; font-size: 1rem; font-weight: 600; color: white; background: #8b1d91; border: 0; border-radius: 8px; cursor: pointer; }
        button:hover { background: #6b1571; }
        .keep { display: inline-block; margin-top: 1rem; color: #6b7280; font-size: 0.875rem; text-decoration: none; }
        .keep:hover { text-decoration: underline; }
        .footer { margin-top: 2rem; font-size: 0.75rem; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="card">
        @if ($alreadyOff)
            <h1>You're already unsubscribed</h1>
            <p class="sub">We don't send you <strong>{{ $preferenceLabel }}</strong>. Nothing more to do.</p>
            <a class="keep" href="{{ url('/') }}">Go to the website</a>
        @else
            <h1>Unsubscribe?</h1>
            <p class="sub">
                You'll stop getting <strong>{{ $preferenceLabel }}</strong> by email.
                Your other email settings stay as they are.
            </p>
            <form method="POST" action="{{ $action }}">
                @csrf
                <button type="submit">Yes, unsubscribe me</button>
            </form>
            <a class="keep" href="{{ url('/') }}">No, keep sending them</a>
        @endif

        <div class="footer">{{ \App\Models\SiteSetting::getValue('site_name', 'Matrimony') }}</div>
    </div>
</body>
</html>

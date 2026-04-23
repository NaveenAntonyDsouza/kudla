<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unsubscribed — {{ \App\Models\SiteSetting::getValue('site_name', 'Matrimony') }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 2rem 1rem; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f9fafb; color: #111827; }
        .card { max-width: 480px; margin: 2rem auto; background: white; border-radius: 12px; padding: 2.5rem 2rem; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .checkmark { width: 64px; height: 64px; margin: 0 auto 1rem; background: #10b981; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 2rem; }
        h1 { text-align: center; font-size: 1.5rem; margin: 0 0 0.5rem; }
        p.sub { text-align: center; color: #6b7280; margin: 0 0 1.5rem; }
        .resubscribe { text-align: center; padding: 1rem; background: #f3f4f6; border-radius: 6px; font-size: 0.875rem; }
        .resubscribe a { color: #8b1d91; font-weight: 600; text-decoration: none; }
        .resubscribe a:hover { text-decoration: underline; }
        .footer { text-align: center; margin-top: 2rem; font-size: 0.75rem; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="card">
        <div class="checkmark">✓</div>
        <h1>You've been unsubscribed</h1>
        <p class="sub">
            We've stopped sending you <strong>{{ $preferenceLabel }}</strong>.
            Your other email preferences remain unchanged.
        </p>

        <div class="resubscribe">
            Changed your mind?
            <a href="{{ route('unsubscribe.resubscribe', ['user' => $user->id, 'preference' => $preference]) }}">
                Re-subscribe with one click
            </a>
        </div>

        <div class="footer">
            Sent with care by {{ \App\Models\SiteSetting::getValue('site_name', 'Matrimony') }}
        </div>
    </div>
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>Confirm your email — {{ \App\Models\SiteSetting::getValue('site_name', 'Matrimony') }}</title>
    @include('components.partials.tracking-head')
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 2rem 1rem; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f9fafb; color: #111827; }
        .card { max-width: 480px; margin: 2rem auto; background: white; border-radius: 12px; padding: 2.5rem 2rem; box-shadow: 0 4px 12px rgba(0,0,0,0.05); text-align: center; }
        .tick { width: 64px; height: 64px; margin: 0 auto 1rem; background: #10b981; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 2rem; }
        h1 { font-size: 1.5rem; margin: 0 0 0.5rem; }
        p.sub { color: #6b7280; margin: 0 0 1.75rem; line-height: 1.5; word-break: break-word; }
        .button { display: block; width: 100%; padding: 0.85rem 1rem; font-size: 1rem; font-weight: 600; color: white; background: #8b1d91; border: 0; border-radius: 8px; cursor: pointer; text-decoration: none; }
        .button:hover { background: #6b1571; }
        .footer { margin-top: 2rem; font-size: 0.75rem; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="card">
        @if ($status === 'ready')
            <h1>Confirm your email</h1>
            <p class="sub">Is <strong>{{ $email }}</strong> your email address? Confirming it means you won't miss messages from members who are interested in you.</p>
            <form method="POST" action="{{ $action }}">
                @csrf
                <button type="submit" class="button">Yes, this is my email</button>
            </form>
        @elseif ($status === 'confirmed' || $status === 'already')
            <div class="tick">✓</div>
            <h1>{{ $status === 'confirmed' ? 'Thank you — your email is confirmed' : 'Your email is already confirmed' }}</h1>
            <p class="sub">
                @if ($finished)
                    Your profile is ready. Log in to see your matches.
                @else
                    Log in to finish your profile — you'll continue right where you left off.
                @endif
            </p>
            <a class="button" href="{{ $continueUrl }}">{{ $finished ? 'Log in' : 'Continue my profile' }}</a>
        @else
            <h1>This link has expired</h1>
            <p class="sub">The email address on this profile has been changed since this link was sent. Log in to check your details.</p>
            <a class="button" href="{{ $continueUrl }}">Log in</a>
        @endif

        <div class="footer">{{ \App\Models\SiteSetting::getValue('site_name', 'Matrimony') }}</div>
    </div>
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>Reply to an interest — {{ \App\Models\SiteSetting::getValue('site_name', 'Matrimony') }}</title>
    @include('components.partials.tracking-head')
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 2rem 1rem; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f9fafb; color: #111827; }
        .card { max-width: 480px; margin: 2rem auto; background: white; border-radius: 12px; padding: 2.5rem 2rem; box-shadow: 0 4px 12px rgba(0,0,0,0.05); text-align: center; }
        .tick { width: 64px; height: 64px; margin: 0 auto 1rem; background: #10b981; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 2rem; }
        h1 { font-size: 1.5rem; margin: 0 0 0.5rem; }
        p.sub { color: #6b7280; margin: 0 0 1.5rem; line-height: 1.5; word-break: break-word; }
        .who { margin: 0 0 1.5rem; padding: 1rem; border: 1px solid #e5e7eb; border-radius: 10px; background: #fafafa; }
        .who strong { display: block; font-size: 1.1rem; color: #8b1d91; }
        .who span { display: block; margin-top: 4px; font-size: 0.9rem; color: #4b5563; }
        .buttons { display: flex; gap: 12px; }
        .buttons form { flex: 1; }
        .button { display: block; width: 100%; padding: 0.85rem 1rem; font-size: 1rem; font-weight: 600; border-radius: 8px; cursor: pointer; text-decoration: none; border: 2px solid transparent; }
        .accept { color: white; background: #15803d; }
        .accept:hover { background: #166534; }
        .decline { color: #b91c1c; background: white; border-color: #fecaca; }
        .decline:hover { background: #fef2f2; }
        .primary { color: white; background: #8b1d91; }
        .primary:hover { background: #6b1571; }
        .chosen { box-shadow: 0 0 0 3px rgba(139,29,145,0.25); }
        .note { margin: 1rem 0 0; font-size: 0.85rem; color: #6b7280; }
        .note a { color: #8b1d91; }
        .footer { margin-top: 2rem; font-size: 0.75rem; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="card">
        @if ($status === 'ready')
            <h1>Reply to this interest</h1>
            <div class="who">
                <strong>{{ $senderId }}</strong>
                @if ($summary !== '')<span>{{ $summary }}</span>@endif
            </div>
            <p class="sub">Accept to connect: you can chat, call and WhatsApp each other.</p>
            <div class="buttons">
                <form method="POST" action="{{ $action }}">
                    @csrf
                    <input type="hidden" name="choice" value="accept">
                    <button type="submit" class="button accept {{ $chosen === 'accept' ? 'chosen' : '' }}">Accept</button>
                </form>
                <form method="POST" action="{{ $action }}">
                    @csrf
                    <input type="hidden" name="choice" value="decline">
                    <button type="submit" class="button decline {{ $chosen === 'decline' ? 'chosen' : '' }}">Decline</button>
                </form>
            </div>
            <p class="note"><a href="{{ $pageUrl }}">Log in to see the full profile first</a></p>
        @elseif ($status === 'answered')
            @if ($done === 'accept' || ($done === null && $answer === 'accepted'))
                <div class="tick">✓</div>
                <h1>{{ $done ? 'You are connected' : 'You have already accepted this interest' }}</h1>
                <p class="sub">{{ $senderId }} will be told. Log in to chat, call or WhatsApp each other.</p>
            @else
                <h1>{{ $done ? 'Interest declined' : 'You have already declined this interest' }}</h1>
                <p class="sub">{{ $senderId }} will be told.</p>
            @endif
            <a class="button primary" href="{{ $pageUrl }}">Log in</a>
        @elseif ($status === 'gone')
            <h1>This member is no longer on the site</h1>
            <p class="sub">There is nothing to answer.</p>
            <a class="button primary" href="{{ route('login') }}">Log in</a>
        @else
            <h1>This link has expired</h1>
            <p class="sub">Log in to see your interests and reply.</p>
            <a class="button primary" href="{{ route('login') }}">Log in</a>
        @endif

        <div class="footer">{{ \App\Models\SiteSetting::getValue('site_name', 'Matrimony') }}</div>
    </div>
</body>
</html>

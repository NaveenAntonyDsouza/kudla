@php
    $site = \App\Models\SiteSetting::getValue('site_name', 'Matrimony');
    $copy = [
        'looking' => [
            'ask' => 'Still looking?', 'confirm' => 'Yes, I\'m still looking',
            'askText' => 'We\'ll keep your profile visible to members looking for a match.',
            'done' => 'Great — your profile stays visible', 'doneText' => 'Log in now and then to see new matches and answer interests; active profiles are shown first.',
        ],
        'found' => [
            'ask' => 'Found your match?', 'confirm' => 'Yes, I found my match',
            'askText' => 'We\'ll hide your profile so no one sends you new interests. You can show it again any time from Settings.',
            'done' => 'Congratulations!', 'doneText' => 'Your profile is now hidden. We\'d love to hear your story — it gives hope to others who are still looking.',
        ],
        'hide' => [
            'ask' => 'Hide your profile?', 'confirm' => 'Hide my profile',
            'askText' => 'Members won\'t see your profile or be able to send you interests. You can show it again any time from Settings.',
            'done' => 'Your profile is hidden', 'doneText' => 'Log in and go to Settings whenever you want to show it again.',
        ],
    ][$answer];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>{{ $done ? $copy['done'] : $copy['ask'] }} — {{ $site }}</title>
    @include('components.partials.tracking-head')
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 2rem 1rem; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f9fafb; color: #111827; }
        .card { max-width: 480px; margin: 2rem auto; background: white; border-radius: 12px; padding: 2.5rem 2rem; box-shadow: 0 4px 12px rgba(0,0,0,0.05); text-align: center; }
        h1 { font-size: 1.5rem; margin: 0 0 0.5rem; }
        p.sub { color: #6b7280; margin: 0 0 1.75rem; line-height: 1.5; }
        .button { display: block; width: 100%; padding: 0.85rem 1rem; font-size: 1rem; font-weight: 600; color: white; background: #8b1d91; border: 0; border-radius: 8px; cursor: pointer; text-decoration: none; }
        .button:hover { background: #6b1571; }
        .link { display: inline-block; margin-top: 1rem; color: #6b7280; font-size: 0.875rem; }
        .footer { margin-top: 2rem; font-size: 0.75rem; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="card">
        @if (! $done)
            <h1>{{ $name ? $name.', ' : '' }}{{ lcfirst($copy['ask']) }}</h1>
            <p class="sub">{{ $copy['askText'] }}</p>
            <form method="POST" action="{{ $action }}">
                @csrf
                <button type="submit" class="button">{{ $copy['confirm'] }}</button>
            </form>
            <a class="link" href="{{ url('/login') }}">Log in instead</a>
        @else
            <h1>{{ $copy['done'] }}</h1>
            <p class="sub">{{ $copy['doneText'] }}</p>
            @if ($answer === 'found')
                <a class="button" href="{{ route('success-stories.create') }}">Share our story</a>
            @else
                <a class="button" href="{{ url('/login') }}">Log in</a>
            @endif
        @endif
        <div class="footer">{{ $site }}</div>
    </div>
</body>
</html>

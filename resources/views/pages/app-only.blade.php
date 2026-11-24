{{-- The whole website of an "app only" site (Site Settings → Mobile App → App only, 24 Nov 2026):
     the app download buttons and the information pages Google Play asks for. Inline styles:
     the live CSS build is not rebuilt on deploy. --}}
@php
    $theme = \App\Models\ThemeSetting::getTheme();
    $siteName = \App\Models\SiteSetting::getValue('site_name', config('app.name'));
    $tagline = \App\Models\SiteSetting::getValue('tagline', '');
    $play = \App\Models\SiteSetting::getValue('app_play_store_url', '');
    $apple = \App\Models\SiteSetting::getValue('app_apple_store_url', '');
    $primary = $theme->primary_color ?? '#64026A';
    $favicon = $theme->favicon_url ?? null;
    // Google Play asks for a web page where members can ask to delete their account (admin: Static Pages → "delete-account")
    $deletePage = rescue(fn () => \App\Models\StaticPage::where('slug', 'delete-account')->where('is_active', true)->exists(), false, false);
    $links = array_filter([
        'Privacy Policy' => url('/privacy-policy'),
        'Terms of Use' => url('/terms-condition'),
        'About Us' => url('/about-us'),
        'Child Safety' => url('/child-safety'),
        'Delete my account' => $deletePage ? url('/page/delete-account') : null,
        'Contact Us' => url('/contact-us'),
    ]);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $siteName }}{{ $tagline ? ' — '.$tagline : '' }}</title>
    <meta name="description" content="{{ $tagline ?: $siteName }}">
    @if($favicon)<link rel="icon" href="{{ $favicon }}">@endif
    @include('components.partials.tracking-head')
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; color: #1f2937; background: #fbfaf8; }
        .wrap { min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 40px 16px; text-align: center; }
        .logo { max-width: 240px; max-height: 120px; margin-bottom: 20px; }
        h1 { margin: 0 0 8px; font-size: 28px; color: {{ $primary }}; }
        p.tag { margin: 0 0 28px; font-size: 17px; color: #4b5563; max-width: 30rem; }
        .stores { display: flex; flex-wrap: wrap; gap: 12px; justify-content: center; }
        .store { display: inline-flex; align-items: center; gap: 10px; padding: 12px 22px; border-radius: 12px; background: #111827; color: #fff; text-decoration: none; font-weight: 600; }
        .store small { display: block; font-size: 11px; font-weight: 400; opacity: .8; text-transform: uppercase; letter-spacing: .05em; }
        .soon { color: #6b7280; font-size: 15px; }
        footer { padding: 18px 16px 28px; text-align: center; font-size: 13px; color: #6b7280; }
        footer a { color: {{ $primary }}; text-decoration: none; margin: 0 8px; white-space: nowrap; }
    </style>
</head>
<body>
    <main class="wrap">
        @if($theme->logo_url ?? false)
            <img class="logo" src="{{ $theme->logo_url }}" alt="{{ $siteName }}">
        @else
            <h1>{{ $siteName }}</h1>
        @endif
        @if($tagline)<p class="tag">{{ $tagline }}</p>@endif

        <div class="stores">
            @if($play)
                <a class="store" href="{{ $play }}" rel="noopener"><span><small>Get it on</small>Google Play</span></a>
            @endif
            @if($apple)
                <a class="store" href="{{ $apple }}" rel="noopener"><span><small>Download on the</small>App Store</span></a>
            @endif
            @if(! $play && ! $apple)
                <p class="soon">The {{ $siteName }} app is coming soon.</p>
            @endif
        </div>
    </main>
    <footer>
        @foreach($links as $label => $href)<a href="{{ $href }}">{{ $label }}</a>@endforeach
        <div style="margin-top:10px;">&copy; {{ date('Y') }} {{ $siteName }}</div>
    </footer>
</body>
</html>

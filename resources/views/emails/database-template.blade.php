<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        // Phase 2.6D: Theme-aware email wrapper.
        // These variables are injected by DatabaseMailable::themeVariables().
        // Fallbacks ensure emails still render if variables are somehow missing.
        $primaryColor = $primaryColor ?? '#8B1D91';
        $primaryHover = $primaryHover ?? '#6B1571';
        $primaryLight = $primaryLight ?? '#F3E8F7';
        $secondaryColor = $secondaryColor ?? '#00BCD4';
        $logoUrl = $logoUrl ?? '';
        $siteName = $siteName ?? config('app.name');
        $tagline = $tagline ?? '';
    @endphp
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f7; }
        .wrapper { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { text-align: center; padding: 20px 0 16px; border-bottom: 3px solid {{ $primaryColor }}; margin-bottom: 24px; }
        .header img { max-height: 48px; }
        .header-text { font-size: 20px; font-weight: 700; color: {{ $primaryColor }}; margin: 0; }
        .header-tagline { font-size: 12px; color: #666; margin: 4px 0 0; }
        .content { background: #fff; border-radius: 8px; padding: 32px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .content h1 { font-size: 22px; color: #1a1a1a; margin-top: 0; }
        .content p { margin: 12px 0; color: #4a4a4a; }
        .content a[style] { display: inline-block; margin: 16px 0; }
        .content ul { padding-left: 20px; color: #4a4a4a; }
        .content li { margin: 6px 0; }
        .footer { text-align: center; padding: 20px; font-size: 12px; color: #999; }
        .footer a { color: {{ $primaryColor }}; text-decoration: none; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            @if($logoUrl)
                <img src="{{ $logoUrl }}" alt="{{ $siteName }}">
            @else
                <div class="header-text">{{ $siteName }}</div>
                @if($tagline)
                    <div class="header-tagline">{{ $tagline }}</div>
                @endif
            @endif
        </div>
        <div class="content">
            {!! $body !!}
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} {{ $siteName }}. All rights reserved.
        </div>
    </div>
</body>
</html>

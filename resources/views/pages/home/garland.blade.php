{{--
    "Garland" and "Harmony" homepage designs (Oct 2026, Kudla in purple and
    marigold). Harmony (pages/home/harmony) is this page with $variant =
    'harmony': abstract ribbons instead of the garlands, for a culture- and
    religion-neutral homepage; everything else is shared.

    "Garland" homepage design (Oct 2026, first for Kudla in purple and marigold).
    A twilight sky in the site's theme colours hung with marigold-and-jasmine
    garlands, a floating search card, warm ivory sections, numbered steps and
    community chips. Same content and admin switches as Classic (hero text,
    stats, why-choose-us, testimonials, featured profiles, communities, FAQ,
    app links, CTA); the registration and search forms are Classic's, unchanged.
    All styling is in this file (the live CSS build is not rebuilt on deploy),
    so it only uses Tailwind classes Classic already uses.
--}}
<x-layouts.app
    :title="$siteTitle"
    :metaDescription="$siteMetaDesc">
    @php
        $variant = in_array($variant ?? null, ['garland', 'harmony'], true) ? $variant : 'garland';
        $divider = 'home-art.' . $variant . '-divider';
        $section = fn (string $key, string $default = '1') => \App\Models\SiteSetting::getValue($key, $default) === '1';
        $siteName = \App\Models\SiteSetting::getValue('site_name', 'Matrimony');
        $siteTagline = \App\Models\SiteSetting::getValue('tagline', 'Find Your Life Partner');
        $heroHeading = \App\Models\SiteSetting::getValue('hero_heading', 'Find Your Life Partner');
        $heroSubheading = \App\Models\SiteSetting::getValue('hero_subheading', '');

        // Colours come from the site theme; the shades below are mixed from it.
        $theme = \App\Models\ThemeSetting::getTheme();
        $hex = fn ($c, $default) => preg_match('/^#[0-9a-f]{6}$/i', (string) $c) ? strtolower($c) : $default;
        $primary = $hex($theme->primary_color ?? null, '#8b1d91');
        $primaryHover = $hex($theme->primary_hover ?? null, '#6b1571');
        $accent = $hex($theme->secondary_color ?? null, '#e37404');
        $mix = function (string $a, string $b, float $share) {
            $x = sscanf($a, '#%02x%02x%02x');
            $y = sscanf($b, '#%02x%02x%02x');
            return vsprintf('#%02x%02x%02x', array_map(fn ($i) => (int) round($x[$i] * $share + $y[$i] * (1 - $share)), [0, 1, 2]));
        };
        $rgb = fn (string $c) => implode(',', sscanf($c, '#%02x%02x%02x'));
        $g = [
            'p' => $primary,
            'ph' => $primaryHover,
            'a' => $accent,
            'a-rgb' => $rgb($accent),
            'deep' => $mix($primary, '#12031a', 0.38),     // night sky at the top of the hero
            'plum' => $mix($primary, '#120318', 0.62),     // headings
            'a-ink' => $mix($accent, '#000000', 0.72),     // the accent, dark enough for text
            'lav' => $mix($primary, '#ffffff', 0.05),
            'line' => $mix($primary, '#ffffff', 0.14),
            'tint' => $mix($primary, '#ffffff', 0.09),
        ];

        $whyChooseUsJson = \App\Models\SiteSetting::getValue('why_choose_us', '');
        $whyChooseUs = $whyChooseUsJson ? json_decode($whyChooseUsJson, true) : [];
        if (empty($whyChooseUs)) {
            $whyChooseUs = [
                ['title' => 'Verified Profiles', 'description' => 'Every profile is verified to ensure authenticity and safety for our members.', 'icon' => 'check'],
                ['title' => '100% Privacy', 'description' => 'Your personal information is protected. You control who sees your contact details.', 'icon' => 'lock'],
                ['title' => 'Community Focused', 'description' => 'Designed for families seeking meaningful connections within their community and values.', 'icon' => 'heart'],
                ['title' => 'Easy to Use', 'description' => 'Simple registration, powerful search, and instant messaging from your phone or computer.', 'icon' => 'star'],
            ];
        }
        // Same icon keys as Classic, drawn as line icons
        $icons = [
            'check' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
            'lock' => 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z',
            'heart' => 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z',
            'star' => 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z',
            'shield' => 'M20.618 5.984A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
            'users' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
            'phone' => 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z',
            'globe' => 'M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        ];
        $whyCols = count($whyChooseUs) <= 3 ? 3 : 4;
    @endphp

    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif+Kannada:wght@600;700&display=swap" rel="stylesheet">
    <style>
        .home-garland {
            --g-p: {{ $g['p'] }}; --g-ph: {{ $g['ph'] }}; --g-a: {{ $g['a'] }}; --g-a-rgb: {{ $g['a-rgb'] }};
            --g-deep: {{ $g['deep'] }}; --g-plum: {{ $g['plum'] }}; --g-a-ink: {{ $g['a-ink'] }};
            --g-lav: {{ $g['lav'] }}; --g-line: {{ $g['line'] }}; --g-tint: {{ $g['tint'] }};
            --g-ivory: #fff9f1; --g-ink: #2b2230; --g-muted: #6b6070;
            color: var(--g-ink);
        }
        .home-garland .g-wrap { max-width: 80rem; margin: 0 auto; padding: 0 16px; }
        @media (min-width: 640px) { .home-garland .g-wrap { padding: 0 24px; } }
        @media (min-width: 1024px) { .home-garland .g-wrap { padding: 0 32px; } }
        .home-garland .g-section { padding: 72px 0; }
        @media (max-width: 639px) { .home-garland .g-section { padding: 56px 0; } }
        .home-garland .g-ivory { background: var(--g-ivory); }
        .home-garland .g-white { background: #fff; }
        .home-garland .g-lav { background: var(--g-lav); }
        .home-garland .g-h2 { margin: 0; text-align: center; font-family: var(--brand-font-serif, Georgia, serif); font-weight: 700; color: var(--g-plum); font-size: clamp(1.6rem, 2.8vw, 2.25rem); line-height: 1.2; text-wrap: balance; }
        .home-garland .g-lead { margin: 12px auto 0; max-width: 40rem; text-align: center; color: var(--g-muted); font-size: 1rem; line-height: 1.6; }
        .home-garland .g-link { color: var(--g-p); font-weight: 600; font-size: 0.9rem; text-decoration: none; }
        .home-garland .g-link:hover { text-decoration: underline; }
        .home-garland .g-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; border-radius: 10px; font-weight: 700; font-size: 0.9rem; padding: 12px 22px; transition: background-color .15s, color .15s, border-color .15s, transform .15s; text-decoration: none; }
        .home-garland .g-btn-primary { background: var(--g-p); color: #fff; border: 0; }
        .home-garland .g-btn-primary:hover { background: var(--g-ph); }
        .home-garland .g-btn-outline { background: #fff; color: var(--g-p); border: 1.5px solid var(--g-p); }
        .home-garland .g-btn-outline:hover { background: var(--g-tint); }
        .home-garland .g-btn-marigold { background: linear-gradient(180deg, #f7b51e, var(--g-a)); color: var(--g-deep); border: 0; box-shadow: 0 10px 24px -10px rgba(0,0,0,.45); }
        .home-garland .g-btn-marigold:hover { transform: translateY(-1px); }
        .home-garland a:focus-visible, .home-garland button:focus-visible, .home-garland select:focus-visible { outline: 3px solid rgba(var(--g-a-rgb), .55); outline-offset: 2px; }

        /* 1. Hero: twilight sky in the theme colours, garlands across the top */
        .home-garland .g-hero {
            position: relative; overflow: hidden; color: #fff;
            background:
                radial-gradient(ellipse 55% 70% at 8% 100%, rgba(var(--g-a-rgb), .34) 0%, rgba(var(--g-a-rgb), 0) 70%),
                radial-gradient(ellipse 35% 45% at 92% 12%, rgba(255,255,255,.08) 0%, rgba(255,255,255,0) 70%),
                linear-gradient(165deg, var(--g-deep) 0%, var(--g-ph) 55%, var(--g-p) 100%);
            padding: clamp(132px, calc(10.8vw + 24px), 236px) 0 128px;
        }
        .home-garland .g-art { position: absolute; top: 0; left: 0; width: 100%; height: auto; pointer-events: none; }
        /* Harmony: ribbons fill the sky on wide screens, a slim band above the headline on phones */
        .home-garland .g-hero-harmony { padding: 92px 0 128px; }
        .home-garland .h-art { position: absolute; pointer-events: none; }
        .home-garland .h-art-wide { inset: 0; width: 100%; height: 100%; }
        .home-garland .h-art-narrow { display: none; top: 0; left: 0; width: 100%; height: auto; }
        .home-garland .g-art-narrow { display: none; }
        @media (max-width: 767px) {
            .home-garland .g-hero { padding: calc(25vw + 18px) 0 96px; }
            .home-garland .g-art-wide { display: none; }
            .home-garland .g-art-narrow { display: block; }
            .home-garland .g-hero-harmony { padding: calc(42vw + 6px) 0 96px; }
            .home-garland .h-art-wide { display: none; }
            .home-garland .h-art-narrow { display: block; }
        }
        .home-garland .g-hero-grid { position: relative; display: flex; flex-direction: column; align-items: center; gap: 32px; }
        .home-garland .g-hero-copy { text-align: center; }
        @media (min-width: 768px) {
            .home-garland .g-hero-grid { flex-direction: row; gap: 56px; }
            .home-garland .g-hero-copy { flex: 1; text-align: left; }
        }
        .home-garland .g-h1 { margin: 0; font-family: "Noto Serif Kannada", var(--brand-font-serif, Georgia, serif); font-weight: 700; font-size: clamp(2rem, 4.6vw, 3.5rem); line-height: 1.25; text-shadow: 0 2px 18px rgba(0,0,0,.28); text-wrap: balance; }
        .home-garland .g-bar { display: block; width: 72px; height: 4px; border-radius: 4px; margin: 18px auto 0; background: linear-gradient(90deg, var(--g-a), #f7b51e); }
        @media (min-width: 768px) { .home-garland .g-bar { margin-left: 0; } }
        .home-garland .g-hero-sub { margin: 16px auto 0; max-width: 34rem; font-size: clamp(1.05rem, 1.6vw, 1.3rem); line-height: 1.55; color: rgba(255,255,255,.92); }
        @media (min-width: 768px) { .home-garland .g-hero-sub { margin-left: 0; } }
        .home-garland .g-trust { display: none; margin-top: 30px; }
        @media (min-width: 768px) { .home-garland .g-trust { display: grid; gap: 14px; } }
        .home-garland .g-trust-item { display: flex; align-items: center; gap: 12px; }
        .home-garland .g-trust-icon { width: 42px; height: 42px; flex: none; border-radius: 999px; display: flex; align-items: center; justify-content: center; background: rgba(var(--g-a-rgb), .18); border: 1px solid rgba(var(--g-a-rgb), .4); color: #ffc27a; }
        .home-garland .g-trust-item b { display: block; font-size: 0.95rem; color: #fff; }
        .home-garland .g-trust-item small { display: block; font-size: 0.8rem; color: rgba(255,255,255,.72); }

        .home-garland .g-card { position: relative; width: 100%; max-width: 400px; flex: none; background: #fff; color: var(--g-ink); border-radius: 18px; padding: 24px 20px 20px; box-shadow: 0 30px 60px -22px rgba(12,2,18,.6), 0 0 0 1px rgba(255,255,255,.06); }
        @media (min-width: 768px) { .home-garland .g-card { width: 360px; min-width: 360px; padding: 26px 24px 22px; } }
        .home-garland .g-card::before { content: ""; position: absolute; left: 22px; right: 22px; top: 0; height: 4px; border-radius: 0 0 4px 4px; background: linear-gradient(90deg, var(--g-a), #f7b51e); }
        .home-garland .g-card-title { margin: 0; font-family: var(--brand-font-serif, Georgia, serif); font-size: 1.3rem; font-weight: 700; color: var(--g-plum); }
        .home-garland .g-card-sub { margin: 2px 0 16px; font-size: 0.78rem; color: var(--g-muted); }
        .home-garland .g-submit { background: var(--g-p); }
        .home-garland .g-submit:hover { background: var(--g-ph); }
        .home-garland .g-hero-curve { position: absolute; left: 0; bottom: -1px; width: 100%; height: 64px; display: block; }

        /* 1b. Search: a card floating over the bottom of the hero */
        .home-garland .g-search-sec { background: var(--g-ivory); }
        .home-garland .g-search { position: relative; z-index: 3; max-width: 64rem; margin: -72px auto 0; background: #fff; border: 1px solid var(--g-line); border-radius: 18px; padding: 20px; box-shadow: 0 22px 44px -22px rgba(40,6,50,.4); }
        @media (min-width: 640px) { .home-garland .g-search { padding: 22px 26px 24px; } }
        .home-garland .g-search-title { margin: 0 0 14px; display: flex; align-items: center; gap: 10px; font-family: var(--brand-font-serif, Georgia, serif); font-size: 1.15rem; font-weight: 700; color: var(--g-plum); }
        .home-garland .g-search-title::before { content: ""; width: 10px; height: 10px; border-radius: 999px; background: var(--g-a); box-shadow: 0 0 0 4px rgba(var(--g-a-rgb), .18); }
        .home-garland .g-search-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; align-items: end; }
        @media (min-width: 640px) { .home-garland .g-search-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
        @media (min-width: 1024px) { .home-garland .g-search-grid { grid-template-columns: repeat(5, minmax(0, 1fr)); } }
        .home-garland .g-label { display: block; margin-bottom: 5px; font-size: 0.75rem; font-weight: 600; color: var(--g-muted); }
        .home-garland .g-select { width: 100%; border: 1px solid #e3dce6; border-radius: 10px; background: #fff; color: var(--g-ink); font-size: 0.875rem; padding: 9px 10px; }
        .home-garland .g-select:focus { border-color: var(--g-p); outline: none; box-shadow: 0 0 0 3px var(--g-tint); }
        .home-garland .g-search .g-btn { width: 100%; padding: 10px 16px; }

        /* 2. Stats */
        .home-garland .g-stats { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; padding-top: 44px; }
        .home-garland .g-stat { text-align: center; }
        .home-garland .g-stat-num { font-family: var(--brand-font-serif, Georgia, serif); font-size: clamp(1.7rem, 4vw, 2.8rem); font-weight: 700; color: var(--g-plum); line-height: 1.1; font-variant-numeric: lining-nums tabular-nums; }
        .home-garland .g-stat-num span.plus { color: var(--g-a-ink); }
        .home-garland .g-stat-label { margin-top: 6px; font-size: clamp(0.66rem, 1.6vw, 0.78rem); font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: var(--g-muted); }

        /* 3. How it works: three numbered steps joined by a bead line */
        .home-garland .g-steps { position: relative; display: grid; grid-template-columns: 1fr; gap: 36px; margin-top: 44px; }
        @media (min-width: 768px) {
            .home-garland .g-steps { grid-template-columns: repeat(3, 1fr); gap: 32px; }
            .home-garland .g-steps::before { content: ""; position: absolute; top: 27px; left: 18%; right: 18%; height: 6px; background-image: radial-gradient(circle, rgba(var(--g-a-rgb), .55) 2px, transparent 2.6px); background-size: 14px 6px; }
        }
        .home-garland .g-step { position: relative; text-align: center; }
        .home-garland .g-step-num { width: 56px; height: 56px; margin: 0 auto 16px; border-radius: 999px; display: flex; align-items: center; justify-content: center; font-family: var(--brand-font-serif, Georgia, serif); font-size: 1.5rem; font-weight: 700; font-variant-numeric: lining-nums; color: #fff; background: radial-gradient(circle at 35% 30%, #f7b51e, var(--g-a) 70%); box-shadow: 0 0 0 6px var(--g-ivory), 0 10px 22px -10px rgba(var(--g-a-rgb), .9); }
        .home-garland .g-step h3 { margin: 0 0 8px; font-size: 1.15rem; font-weight: 700; color: var(--g-plum); }
        .home-garland .g-step p { margin: 0 auto; max-width: 18rem; font-size: 0.92rem; line-height: 1.6; color: var(--g-muted); }

        /* 4. Why choose us */
        .home-garland .g-why { display: grid; grid-template-columns: 1fr; gap: 18px; margin-top: 44px; }
        @media (min-width: 640px) { .home-garland .g-why { grid-template-columns: repeat(2, 1fr); } }
        @media (min-width: 1024px) { .home-garland .g-why.cols-3 { grid-template-columns: repeat(3, 1fr); } .home-garland .g-why.cols-4 { grid-template-columns: repeat(4, 1fr); } }
        .home-garland .g-why-card { background: #fff; border: 1px solid var(--g-line); border-radius: 16px; padding: 24px; transition: transform .2s, box-shadow .2s, border-color .2s; }
        .home-garland .g-why-card:hover { transform: translateY(-3px); border-color: rgba(var(--g-a-rgb), .45); box-shadow: 0 16px 32px -18px rgba(40,6,50,.35); }
        .home-garland .g-why-icon { width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; background: var(--g-tint); color: var(--g-p); margin-bottom: 16px; }
        .home-garland .g-why-card h3 { margin: 0 0 6px; font-size: 1rem; font-weight: 700; color: var(--g-ink); }
        .home-garland .g-why-card p { margin: 0; font-size: 0.9rem; line-height: 1.6; color: var(--g-muted); }

        /* 5. Success stories */
        .home-garland .carousel-card { width: 100%; }
        @media (min-width: 768px) { .home-garland .carousel-card { width: 33.333%; } }
        .home-garland .g-story { background: #fff; border: 1px solid var(--g-line); border-radius: 16px; overflow: hidden; height: 100%; }
        .home-garland .g-story-photo { aspect-ratio: 4 / 3; background: var(--g-tint); overflow: hidden; }
        .home-garland .g-story-photo img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .home-garland .g-story-body { position: relative; padding: 22px 20px 20px; }
        .home-garland .g-quote { position: absolute; top: -26px; left: 18px; width: 44px; height: 44px; border-radius: 999px; background: var(--g-a); color: #fff; display: flex; align-items: center; justify-content: center; font-family: Georgia, serif; font-size: 2rem; line-height: 1; padding-top: 10px; box-shadow: 0 0 0 4px #fff; }
        .home-garland .g-story h3 { margin: 6px 0 0; font-size: 1rem; font-weight: 700; color: var(--g-plum); }
        .home-garland .g-story .where { margin-top: 2px; font-size: 0.78rem; color: var(--g-muted); }
        .home-garland .g-story .text { margin-top: 10px; font-size: 0.9rem; line-height: 1.6; color: var(--g-ink); display: -webkit-box; -webkit-line-clamp: 4; -webkit-box-orient: vertical; overflow: hidden; }
        .home-garland .g-car-btn { position: absolute; top: 40%; z-index: 10; width: 42px; height: 42px; border-radius: 999px; background: #fff; border: 1px solid var(--g-line); box-shadow: 0 6px 16px -8px rgba(40,6,50,.4); display: flex; align-items: center; justify-content: center; color: var(--g-plum); }
        .home-garland .g-car-btn:hover { background: var(--g-tint); }
        .home-garland .g-dot { height: 8px; width: 8px; border-radius: 999px; background: #d9cfdc; transition: width .3s, background-color .3s; }
        .home-garland .g-dot.on { width: 24px; background: var(--g-p); }

        /* 6. Communities as chips */
        .home-garland .g-community { margin-top: 36px; }
        .home-garland .g-community h3 { margin: 0 0 14px; display: flex; align-items: center; gap: 10px; font-size: 1.05rem; font-weight: 700; color: var(--g-plum); }
        .home-garland .g-community h3::after { content: ""; flex: 1; height: 1px; background: linear-gradient(90deg, rgba(var(--g-a-rgb), .45), transparent); }
        .home-garland .g-community h3 a { color: inherit; text-decoration: none; }
        .home-garland .g-community h3 a:hover { color: var(--g-p); }
        .home-garland .g-chips { display: flex; flex-wrap: wrap; gap: 10px; }
        .home-garland .g-chip { display: inline-block; padding: 8px 16px; border-radius: 999px; background: #fff; border: 1px solid var(--g-line); color: var(--g-ink); font-size: 0.875rem; font-weight: 500; text-decoration: none; transition: border-color .15s, color .15s, background-color .15s; }
        .home-garland .g-chip:hover { border-color: var(--g-p); color: var(--g-p); background: var(--g-tint); }

        /* 7. FAQ */
        .home-garland .g-faq { border: 1px solid var(--g-line); border-radius: 14px; overflow: hidden; background: #fff; transition: border-color .2s; }
        .home-garland .g-faq.open { border-color: rgba(var(--g-a-rgb), .55); }
        .home-garland .g-faq button { width: 100%; display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 16px 20px; text-align: left; background: transparent; border: 0; cursor: pointer; }
        .home-garland .g-faq button span { font-size: 0.95rem; font-weight: 600; color: var(--g-ink); }
        .home-garland .g-faq .chev { width: 28px; height: 28px; flex: none; border-radius: 999px; background: var(--g-tint); color: var(--g-p); display: flex; align-items: center; justify-content: center; transition: transform .2s; }
        .home-garland .g-faq.open .chev { transform: rotate(180deg); }
        .home-garland .g-faq .answer { padding: 0 20px 18px; font-size: 0.9rem; line-height: 1.65; color: var(--g-muted); }

        /* 9. Closing call to action */
        /* margin-bottom cancels the footer's 4rem top margin, so the band meets the footer */
        .home-garland .g-cta { position: relative; overflow: hidden; text-align: center; color: #fff; padding: 76px 0 80px; margin-bottom: -4rem;
            background: radial-gradient(ellipse 50% 80% at 90% 110%, rgba(var(--g-a-rgb), .38) 0%, rgba(var(--g-a-rgb), 0) 70%), linear-gradient(135deg, var(--g-deep) 0%, var(--g-ph) 60%, var(--g-p) 100%); }
        .home-garland .g-cta h2 { margin: 0; font-family: var(--brand-font-serif, Georgia, serif); font-weight: 700; font-size: clamp(1.7rem, 3vw, 2.4rem); text-wrap: balance; }
        .home-garland .g-cta p { margin: 14px auto 30px; max-width: 36rem; font-size: 1.05rem; line-height: 1.6; color: rgba(255,255,255,.85); }
        @media (prefers-reduced-motion: reduce) { .home-garland * { transition: none !important; } }
    </style>

    <div class="home-garland variant-{{ $variant }}">

    {{-- 1. Hero with the registration form --}}
    <section class="g-hero g-hero-{{ $variant }}">
        <x-dynamic-component :component="'home-art.' . $variant . '-hero'" />
        <div class="g-wrap">
            <div class="g-hero-grid">
                <div class="g-hero-copy">
                    <h1 class="g-h1">{{ $heroHeading }}</h1>
                    <span class="g-bar" aria-hidden="true"></span>
                    @if($heroSubheading)
                        <p class="g-hero-sub">{{ $heroSubheading }}</p>
                    @elseif($siteTagline)
                        <p class="g-hero-sub">{{ $siteTagline }}</p>
                    @endif

                    <div class="g-trust">
                        <div class="g-trust-item">
                            <span class="g-trust-icon"><svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icons['check'] }}"/></svg></span>
                            <span><b>100% Verified Profiles</b><small>Every profile is manually reviewed</small></span>
                        </div>
                        <div class="g-trust-item">
                            <span class="g-trust-icon"><svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icons['lock'] }}"/></svg></span>
                            <span><b>Safe & Secure</b><small>Your privacy is our priority</small></span>
                        </div>
                        <div class="g-trust-item">
                            <span class="g-trust-icon"><svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icons['heart'] }}"/></svg></span>
                            <span><b>Smart Matchmaking</b><small>Matches ranked by your partner preferences</small></span>
                        </div>
                    </div>
                </div>

                {{-- Registration form: identical to Classic --}}
                <div class="g-card" data-hides-wa-fab>
                    <h2 class="g-card-title">Register Free</h2>
                    <p class="g-card-sub">Create your profile in 2 minutes</p>

                    <form method="POST" action="{{ route('register.store1') }}" class="space-y-3" x-data="{
                        gender: @js(old('gender', '')),
                        showPw: false,
                        submitting: false,
                        dob: @js(old('date_of_birth', '')),
                        get calculatedAge() {
                            if (!this.dob) return '';
                            const birth = new Date(this.dob);
                            const today = new Date();
                            let years = today.getFullYear() - birth.getFullYear();
                            let months = today.getMonth() - birth.getMonth();
                            if (months < 0 || (months === 0 && today.getDate() < birth.getDate())) {
                                years--;
                                months += 12;
                            }
                            if (today.getDate() < birth.getDate()) months--;
                            if (months < 0) months = 0;
                            return years >= 0 ? years + ' Yrs ' + months + ' Months' : '';
                        }
                    }" @submit="submitting = true">
                        @csrf

                        {{-- Full Name --}}
                        <div class="float-field">
                            <input type="text" name="full_name" id="hero_full_name" value="{{ old('full_name') }}" required placeholder=" "
                                class="w-full rounded-lg border border-gray-300 px-4 pt-5 pb-2 text-sm text-gray-900 focus:border-(--color-primary) focus:ring-1 focus:ring-(--color-primary) peer">
                            <label for="hero_full_name" class="absolute left-4 top-2 text-xs text-gray-500 peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-sm peer-focus:top-2 peer-focus:text-xs peer-focus:text-(--color-primary) transition-all pointer-events-none">Full Name *</label>
                        </div>

                        {{-- Gender --}}
                        <div>
                            <input type="hidden" name="gender" :value="gender">
                            <div class="grid grid-cols-2 gap-3">
                                <button type="button" @click="gender = 'male'"
                                    :class="gender === 'male' ? 'border-(--color-primary) bg-(--color-primary)/5 text-(--color-primary)' : 'border-gray-300 text-gray-600 hover:border-gray-400'"
                                    class="flex items-center justify-center gap-2 border rounded-lg px-4 py-2.5 text-sm font-medium transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                                    Male
                                </button>
                                <button type="button" @click="gender = 'female'"
                                    :class="gender === 'female' ? 'border-(--color-primary) bg-(--color-primary)/5 text-(--color-primary)' : 'border-gray-300 text-gray-600 hover:border-gray-400'"
                                    class="flex items-center justify-center gap-2 border rounded-lg px-4 py-2.5 text-sm font-medium transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                                    Female
                                </button>
                            </div>
                            @error('gender') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        {{-- Date of Birth: Day / Month / Year (age shown underneath) --}}
                        <x-dob-select id="hero_dob" />
                        @error('date_of_birth') <p class="-mt-2 text-xs text-red-500">{{ $message }}</p> @enderror

                        {{-- Primary Mobile Number (with country code) --}}
                        <div>
                            <x-phone-input name="phone" label="Primary Mobile Number" :value="old('phone', '')" :required="true" maxlength="10" />
                            <p style="margin-top: 3px; display: flex; align-items: center; gap: 3px; font-size: 10px; color: #9ca3af; white-space: nowrap;">
                                <svg style="width: 12px; height: 12px; color: var(--color-primary); flex-shrink: 0;" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd"/>
                                </svg>
                                OTP will be sent to this number for verification
                            </p>
                        </div>

                        {{-- Email --}}
                        <div class="float-field">
                            <input type="email" name="email" id="hero_email" value="{{ old('email') }}" required placeholder=" "
                                class="w-full rounded-lg border border-gray-300 px-4 pt-5 pb-2 text-sm text-gray-900 focus:border-(--color-primary) focus:ring-1 focus:ring-(--color-primary) peer">
                            <label for="hero_email" class="absolute left-4 top-2 text-xs text-gray-500 peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-sm peer-focus:top-2 peer-focus:text-xs peer-focus:text-(--color-primary) transition-all pointer-events-none">Email ID *</label>
                        </div>

                        {{-- Password --}}
                        <div class="float-field relative">
                            <input :type="showPw ? 'text' : 'password'" name="password" id="hero_password" required minlength="6" maxlength="64" placeholder=" "
                                class="w-full rounded-lg border border-gray-300 px-4 pt-5 pb-2 pr-10 text-sm text-gray-900 focus:border-(--color-primary) focus:ring-1 focus:ring-(--color-primary) peer">
                            <label for="hero_password" class="absolute left-4 top-2 text-xs text-gray-500 peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-sm peer-focus:top-2 peer-focus:text-xs peer-focus:text-(--color-primary) transition-all pointer-events-none">Create Password *</label>
                            <button type="button" @click="showPw = !showPw" class="absolute right-3 top-3.5 text-gray-400 hover:text-gray-600" tabindex="-1">
                                <svg x-show="!showPw" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                <svg x-show="showPw" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                            </button>
                            <p class="mt-1 flex items-center gap-1 text-[10px] text-gray-400">
                                <svg class="w-3 h-3 text-(--color-primary) shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd"/>
                                </svg>
                                Use 6-64 characters
                            </p>
                            @error('password') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        @if($errors->any())
                            <div class="rounded-lg bg-red-50 border border-red-200 p-3">
                                @foreach($errors->all() as $error)
                                    <p class="text-xs text-red-600">{{ $error }}</p>
                                @endforeach
                            </div>
                        @endif

                        <x-terms-consent id="hero_accept_terms" />
                        <button type="submit" :disabled="submitting" :class="submitting && 'opacity-50 cursor-not-allowed'"
                            class="g-submit w-full py-3 text-sm font-bold text-white rounded-lg transition-colors">
                            <span x-show="!submitting">REGISTER FREE</span>
                            <span x-show="submitting" x-cloak>Please wait...</span>
                        </button>

                    </form>

                    <p class="mt-4 text-center text-sm text-gray-600">Already have an account? <a href="{{ route('login') }}" class="text-(--color-primary) font-semibold hover:underline">LOGIN</a></p>
                </div>
            </div>
        </div>
        <svg class="g-hero-curve" viewBox="0 0 1440 64" preserveAspectRatio="none" aria-hidden="true" focusable="false">
            <path d="M0 34 C 240 58, 480 66, 720 50 C 960 34, 1200 14, 1440 30 L1440 64 L0 64 Z" fill="#fff9f1"/>
        </svg>
    </section>

    {{-- 1b. Search, floating over the bottom of the hero --}}
    <section class="g-search-sec">
        <div class="g-wrap">
            <div class="g-search">
                <h2 class="g-search-title">{{ \App\Models\SiteSetting::getValue('search_heading', '') ?: 'Search for Your Life Partner' }}</h2>
                <form action="/search" method="GET" class="g-search-grid">
                    <div>
                        <label class="g-label" for="g_s_gender">I'm looking for</label>
                        <select name="gender" id="g_s_gender" class="g-select">
                            <option value="female">Bride</option>
                            <option value="male">Groom</option>
                        </select>
                    </div>
                    <div>
                        <label class="g-label" for="g_s_from">Age from</label>
                        <select name="age_from" id="g_s_from" class="g-select">
                            @for($i = 18; $i <= 60; $i++)
                                <option value="{{ $i }}" {{ $i === 21 ? 'selected' : '' }}>{{ $i }} yrs</option>
                            @endfor
                        </select>
                    </div>
                    <div>
                        <label class="g-label" for="g_s_to">Age to</label>
                        <select name="age_to" id="g_s_to" class="g-select">
                            @for($i = 18; $i <= 60; $i++)
                                <option value="{{ $i }}" {{ $i === 30 ? 'selected' : '' }}>{{ $i }} yrs</option>
                            @endfor
                        </select>
                    </div>
                    <div>
                        <label class="g-label" for="g_s_community">Community</label>
                        <select name="community" id="g_s_community" class="g-select">
                            <option value="">All Communities</option>
                            @foreach($communities as $religion => $group)
                                <optgroup label="{{ $religion }}">
                                    @foreach($group as $community)
                                        <option value="{{ $community->id }}">{{ $community->community_name }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <button type="submit" class="g-btn g-btn-primary">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            Search
                        </button>
                    </div>
                </form>
            </div>

            {{-- 2. Stats (Admin → Homepage Settings → "Show the stats strip") --}}
            @if(\App\Models\SiteSetting::getValue('show_stats_bar', '1') === '1')
            <div class="g-stats" x-data="{
                started: false,
                members: 0,
                marriages: 0,
                years: 0,
                targetMembers: {{ (int) $stats['members'] }},
                targetMarriages: {{ (int) $stats['marriages'] }},
                targetYears: {{ (int) $stats['years'] }},
                animateCount(prop, target, duration = 2000) {
                    const steps = 60;
                    const increment = target / steps;
                    let current = 0;
                    const interval = duration / steps;
                    const timer = setInterval(() => {
                        current += increment;
                        if (current >= target) {
                            this[prop] = target;
                            clearInterval(timer);
                        } else {
                            this[prop] = Math.floor(current);
                        }
                    }, interval);
                },
                startAnimation() {
                    if (this.started) return;
                    this.started = true;
                    this.animateCount('members', this.targetMembers);
                    this.animateCount('marriages', this.targetMarriages);
                    this.animateCount('years', this.targetYears, 1500);
                }
            }" x-init="
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => { if (entry.isIntersecting) { startAnimation(); observer.disconnect(); } });
                }, { threshold: 0.3 });
                observer.observe($el);
            ">
                <div class="g-stat">
                    <div class="g-stat-num"><span x-text="members.toLocaleString()">{{ number_format((int) $stats['members']) }}</span><span class="plus">+</span></div>
                    <div class="g-stat-label">Members</div>
                </div>
                <div class="g-stat">
                    <div class="g-stat-num"><span x-text="marriages.toLocaleString()">{{ number_format((int) $stats['marriages']) }}</span><span class="plus">+</span></div>
                    <div class="g-stat-label">Successful Marriages</div>
                </div>
                <div class="g-stat">
                    <div class="g-stat-num"><span x-text="years">{{ (int) $stats['years'] }}</span><span class="plus">+</span></div>
                    <div class="g-stat-label">Years of Service</div>
                </div>
            </div>
            @endif
        </div>
    </section>

    {{-- 3. How it works --}}
    <section class="g-section g-ivory">
        <div class="g-wrap">
            <x-dynamic-component :component="$divider" />
            <h2 class="g-h2">How It Works</h2>
            <div class="g-steps">
                <div class="g-step">
                    <div class="g-step-num">1</div>
                    <h3>Register</h3>
                    <p>Create your profile for free with basic details, photos, and preferences.</p>
                </div>
                <div class="g-step">
                    <div class="g-step-num">2</div>
                    <h3>Search</h3>
                    <p>Browse profiles by community, education, location, and more to find the right match.</p>
                </div>
                <div class="g-step">
                    <div class="g-step-num">3</div>
                    <h3>Connect</h3>
                    <p>Send interests, communicate, and find your life partner with confidence.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- 4. Why choose us (Homepage Content → Why Choose Us) --}}
    @if($showWhyChooseUs)
    <section class="g-section g-white">
        <div class="g-wrap">
            <x-dynamic-component :component="$divider" />
            <h2 class="g-h2">Why Choose {{ $siteName }}?</h2>
            <p class="g-lead">We understand the importance of finding the right life partner. Here's what makes us different.</p>
            <div class="g-why cols-{{ $whyCols }}">
                @foreach($whyChooseUs as $item)
                    <div class="g-why-card">
                        <div class="g-why-icon"><svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $icons[$item['icon'] ?? 'check'] ?? $icons['check'] }}"/></svg></div>
                        <h3>{{ $item['title'] }}</h3>
                        <p>{{ $item['description'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- 5. Success stories (carousel, same behaviour as Classic) --}}
    @php
        $successStories = \App\Models\Testimonial::where('is_visible', true)->orderBy('display_order')->limit(8)->get();
    @endphp
    @if($showSuccessStories && $successStories->count() > 0)
    <section class="g-section g-lav">
        <div class="g-wrap">
            <x-dynamic-component :component="$divider" />
            <h2 class="g-h2">Success Stories</h2>
            <p class="g-lead">Real couples who met through {{ $siteName }}.</p>

            <div style="margin-top: 52px; position: relative;" x-data="{
                current: 0,
                total: {{ $successStories->count() }},
                autoplay: null,
                get maxIndex() {
                    const perView = window.innerWidth >= 768 ? 3 : 1;
                    return Math.max(0, this.total - perView);
                },
                next() { this.current = this.current >= this.maxIndex ? 0 : this.current + 1; },
                prev() { this.current = this.current <= 0 ? this.maxIndex : this.current - 1; },
                startAutoplay() { this.autoplay = setInterval(() => this.next(), 4000); },
                stopAutoplay() { clearInterval(this.autoplay); }
            }" x-init="startAutoplay()" @mouseenter="stopAutoplay()" @mouseleave="startAutoplay()">
                <div class="overflow-hidden" style="padding-top: 4px;">
                    <div class="flex transition-transform duration-500 ease-in-out" :style="'transform: translateX(-' + (current * (100 / (window.innerWidth >= 768 ? 3 : 1))) + '%)'">
                        @foreach($successStories as $story)
                            <div class="carousel-card flex-shrink-0 px-3">
                                <div class="g-story">
                                    <div class="g-story-photo">
                                        @if($story->photo_url)
                                            <img src="{{ Storage::disk('public')->url($story->photo_url) }}" alt="{{ $story->couple_names }}" loading="lazy">
                                        @else
                                            <div style="height: 100%; display: flex; align-items: center; justify-content: center; color: var(--g-p); opacity: .3;">
                                                <svg width="64" height="64" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $icons['heart'] }}"/></svg>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="g-story-body">
                                        <span class="g-quote" aria-hidden="true">&ldquo;</span>
                                        <h3>{{ $story->couple_names }}</h3>
                                        @if($story->location)
                                            <p class="where">{{ $story->location }}</p>
                                        @endif
                                        <p class="text">{{ $story->story }}</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                @if($successStories->count() > 3)
                    <button type="button" @click="prev()" class="g-car-btn" style="left: -6px;" aria-label="Previous story">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <button type="button" @click="next()" class="g-car-btn" style="right: -6px;" aria-label="Next story">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                @endif

                <div class="flex justify-center gap-2 mt-6">
                    @foreach($successStories as $index => $story)
                        @if($index <= $successStories->count() - 3)
                            <button type="button" @click="current = {{ $index }}" class="g-dot" :class="current === {{ $index }} && 'on'" aria-label="Show story {{ $index + 1 }}"></button>
                        @endif
                    @endforeach
                </div>
            </div>

            <div style="text-align: center; margin-top: 28px;">
                <a href="{{ route('success-stories.index') }}" class="g-link">View All Success Stories &rarr;</a>
            </div>
        </div>
    </section>
    @endif

    {{-- 5b. Featured profiles (Homepage Content → Featured Profiles) --}}
    @if($showFeatured && $featuredProfiles->count() > 0)
    <section class="g-section g-white">
        <div class="g-wrap">
            <x-dynamic-component :component="$divider" />
            <h2 class="g-h2">Featured Profiles</h2>
            <p class="g-lead">Hand-picked profiles from our community</p>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4" style="margin-top: 40px;">
                @foreach($featuredProfiles as $p)
                    <x-profile-card :profile="$p" />
                @endforeach
            </div>
            <div style="text-align: center; margin-top: 32px;">
                <a href="{{ route('search.quick') }}" class="g-btn g-btn-outline">
                    View All Profiles
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                </a>
            </div>
        </div>
    </section>
    @endif

    {{-- New sections (Oct 2026), each with its own switch: Admin → Homepage Content → Garland & Harmony sections --}}
    @if($section('show_plans_section'))
        @include('pages.home.sections.plans')
    @endif
    @if($section('show_assisted_matchmaking', '0'))
        @include('pages.home.sections.matchmaking')
    @endif
    @if($section('show_safety_section'))
        @include('pages.home.sections.safety')
    @endif
    @if($section(\App\Support\HomeBrowse::SETTING))
        @include('pages.home.sections.browse')
    @endif

    {{-- 6. Browse by community (links as in Classic) --}}
    @if($showCommunities && $communities->count() > 0)
    <section class="g-section g-ivory">
        <div class="g-wrap">
            <x-dynamic-component :component="$divider" />
            <h2 class="g-h2">Browse by Community</h2>

            @foreach($communities as $religion => $group)
                @php
                    $filterField = in_array($religion, ['Christian']) ? 'denomination' : 'caste';
                    $primaryCategory = match($religion) {
                        'Christian' => 'catholic-matrimony',
                        'Hindu' => 'hindu-matrimony',
                        'Muslim' => 'muslim-matrimony',
                        'Jain' => 'jain-matrimony',
                        default => 'community-matrimony',
                    };
                    $discoverLookup = [];
                    $categoriesToCheck = $religion === 'Christian'
                        ? ['catholic-matrimony', 'christian-matrimony']
                        : [$primaryCategory];
                    foreach ($categoriesToCheck as $catKey) {
                        $catConfig = config("discover.{$catKey}");
                        if ($catConfig) {
                            if (isset($catConfig['subcategories_source'])) {
                                $subs = app(\App\Services\DiscoverConfigService::class)->{$catConfig['subcategories_source']}();
                            } elseif (isset($catConfig['subcategories'])) {
                                $subs = is_callable($catConfig['subcategories']) ? ($catConfig['subcategories'])() : $catConfig['subcategories'];
                            } else {
                                $subs = [];
                            }
                            foreach ($subs as $sub) {
                                $discoverLookup[$sub['slug']] = $catKey;
                            }
                        }
                    }
                @endphp
                <div class="g-community">
                    <h3><a href="{{ route('discover.category', $primaryCategory) }}">{{ $religion }}</a></h3>
                    <div class="g-chips">
                        @foreach($group as $community)
                            @php
                                $slug = Str::slug($community->community_name);
                                $matchedCategory = $discoverLookup[$slug] ?? null;
                                $href = $matchedCategory
                                    ? route('discover.results', [$matchedCategory, $slug])
                                    : url('/search/quick-search?' . $filterField . '=' . urlencode($community->community_name));
                            @endphp
                            <a href="{{ $href }}" class="g-chip">{{ $community->community_name }}</a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </section>
    @endif

    {{-- 7. FAQ preview --}}
    @if($faqs->count() > 0)
    <section class="g-section g-white">
        <div class="g-wrap" style="max-width: 48rem;">
            <x-dynamic-component :component="$divider" />
            <h2 class="g-h2">Frequently Asked Questions</h2>
            <p class="g-lead">Have questions? We've got answers.</p>

            <div style="display: grid; gap: 12px; margin-top: 36px;">
                @foreach($faqs as $index => $faq)
                    <div x-data="{ open: {{ $index === 0 ? 'true' : 'false' }} }" class="g-faq" :class="open && 'open'">
                        <button type="button" @click="open = !open" :aria-expanded="open">
                            <span>{{ $faq->question }}</span>
                            <span class="chev" aria-hidden="true"><svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M19 9l-7 7-7-7"/></svg></span>
                        </button>
                        <div x-show="open" x-collapse>
                            <div class="answer">{{ $faq->answer }}</div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div style="text-align: center; margin-top: 28px;">
                <a href="/faq" class="g-link">View All FAQs &rarr;</a>
            </div>
        </div>
    </section>
    @endif

    {{-- 8. Download app (only when store links are set) --}}
    @php
        $playStoreUrl = \App\Models\SiteSetting::getValue('app_play_store_url', '');
        $appStoreUrl = \App\Models\SiteSetting::getValue('app_apple_store_url', '');
    @endphp
    @if(($playStoreUrl || $appStoreUrl) && \App\Models\SiteSetting::getValue('show_app_section', '1') === '1')
    <section class="g-section g-lav">
        <div class="g-wrap" style="text-align: center;">
            <x-dynamic-component :component="$divider" />
            <h2 class="g-h2">Get the {{ $siteName }} App</h2>
            <p class="g-lead">Search for your life partner on the go. Browse profiles, send interests, and chat — all from your mobile phone.</p>
            <div style="display: flex; flex-wrap: wrap; gap: 14px; justify-content: center; margin-top: 28px;">
                @if($playStoreUrl)
                    <a href="{{ $playStoreUrl }}" target="_blank" rel="noopener" class="inline-flex items-center gap-3 bg-gray-900 text-white rounded-xl px-6 py-3 hover:bg-gray-800 transition-colors">
                        <svg class="w-8 h-8" viewBox="0 0 24 24" fill="currentColor"><path d="M3.609 1.814L13.792 12 3.61 22.186a.996.996 0 01-.61-.92V2.734a1 1 0 01.609-.92zm10.89 10.893l2.302 2.302-10.937 6.333 8.635-8.635zm3.199-3.199l2.302 2.302a1 1 0 010 1.38l-2.302 2.302L15.396 13l2.302-2.492zM5.864 2.658L16.8 9.39l-2.302 2.302L5.864 2.658z"/></svg>
                        <div class="text-left">
                            <div class="text-[10px] uppercase tracking-wider text-gray-400">Get it on</div>
                            <div class="text-base font-semibold -mt-0.5">Google Play</div>
                        </div>
                    </a>
                @endif
                @if($appStoreUrl)
                    <a href="{{ $appStoreUrl }}" target="_blank" rel="noopener" class="inline-flex items-center gap-3 bg-gray-900 text-white rounded-xl px-6 py-3 hover:bg-gray-800 transition-colors">
                        <svg class="w-8 h-8" viewBox="0 0 24 24" fill="currentColor"><path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.8-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/></svg>
                        <div class="text-left">
                            <div class="text-[10px] uppercase tracking-wider text-gray-400">Download on the</div>
                            <div class="text-base font-semibold -mt-0.5">App Store</div>
                        </div>
                    </a>
                @endif
            </div>
        </div>
    </section>
    @endif

    @if($section('show_contact_section'))
        @include('pages.home.sections.contact')
    @endif

    {{-- 9. Closing call to action (Homepage Content → CTA) --}}
    @php
        $ctaTitle = \App\Models\SiteSetting::getValue('cta_title', 'Register Free Today');
        $ctaDesc = \App\Models\SiteSetting::getValue('cta_description', 'Join the families who are looking for the right match. Registration is free and takes just a few minutes.');
        $ctaButton = \App\Models\SiteSetting::getValue('cta_button_text', 'Create Your Profile');
    @endphp
    <section class="g-cta">
        <div class="g-wrap">
            <x-dynamic-component :component="$divider" :light="true" />
            <h2>{{ $ctaTitle }}</h2>
            <p>{{ $ctaDesc }}</p>
            <a href="/register" class="g-btn g-btn-marigold">{{ $ctaButton }}</a>
        </div>
    </section>

    </div>
</x-layouts.app>

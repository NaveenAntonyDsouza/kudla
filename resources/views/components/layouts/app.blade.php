@php
    $theme = \App\Models\ThemeSetting::getTheme();
    $siteName = \App\Models\SiteSetting::getValue('site_name', 'Matrimony');
    $siteTagline = \App\Models\SiteSetting::getValue('tagline', 'Find Your Life Partner');
    $isLoggedIn = auth()->check();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php
        // Determine current page slug for per-page SEO lookup (route name => SEO
        // Settings page; the about/contact/membership/privacy names were wrong
        // until Oct 2026, so those pages never picked up their custom SEO)
        $currentRoute = request()->route()?->getName() ?? '';
        $pageSlugMap = [
            'home' => 'home',
            'search.results.public' => 'search',
            'search.index' => 'search',
            'login' => 'login',
            'register' => 'register',
            'success-stories.index' => 'happy-stories',
            'privacy' => 'privacy-policy',
            'terms' => 'terms',
            'about-us' => 'about',
            'contact' => 'contact',
            'membership.index' => 'membership-plans',
            'faq' => 'faq',
            'demograph' => 'demograph',
        ];
        $pageSlug = $pageSlugMap[$currentRoute] ?? null;
        $pageSeo = $pageSlug ? \App\Models\PageSeoSetting::getForPage($pageSlug) : null;

        // SEO values with per-page override > passed props > global defaults
        $metaTitleSuffix = \App\Models\SiteSetting::getValue('meta_title_suffix', '');
        $defaultMetaTitle = \App\Models\SiteSetting::getValue('meta_title_default', $siteName);
        $defaultMetaDesc = \App\Models\SiteSetting::getValue('meta_description_default', $siteTagline . ' - ' . $siteName . '. Register free and find the right life partner.');

        $pageTitle = $pageSeo?->meta_title ?? ($title ?? '');
        if ($pageTitle) {
            $fullTitle = str_contains($pageTitle, $siteName) ? $pageTitle : $pageTitle . ($metaTitleSuffix ?: " | {$siteName}");
        } else {
            $fullTitle = $defaultMetaTitle ?: $siteName;
        }

        $metaDescription = $pageSeo?->meta_description ?? ($metaDescription ?? $defaultMetaDesc);
        $canonicalUrl = $canonicalUrl ?? url()->current();
        // Absolute URL, with the site's share image before the header logo
        // (link crawlers ignore relative paths; see App\Support\ShareImage)
        $ogImage = \App\Support\ShareImage::for($pageSeo?->og_image_url, $ogImage ?? null);

        $siteEmail = \App\Models\SiteSetting::getValue('email', '');
        $sitePhone = \App\Models\SiteSetting::getValue('phone', '');
        $siteAddress = \App\Models\SiteSetting::getValue('address', '');

        // Tracking IDs
        $gaId = \App\Models\SiteSetting::getValue('google_analytics_id', '');
        $gtmId = \App\Models\SiteSetting::getValue('google_tag_manager_id', '');
        $fbPixelId = \App\Models\SiteSetting::getValue('facebook_pixel_id', '');
    @endphp

    <title>{{ $fullTitle }}</title>

    {{-- SEO Meta Tags --}}
    <meta name="description" content="{{ Str::limit(strip_tags($metaDescription), 160) }}">
    <meta name="robots" content="{{ $robots ?? 'index, follow' }}">
    <link rel="canonical" href="{{ $canonicalUrl }}">

    {{-- Open Graph --}}
    <meta property="og:title" content="{{ $fullTitle }}">
    <meta property="og:description" content="{{ Str::limit(strip_tags($metaDescription), 160) }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:type" content="{{ $ogType ?? 'website' }}">
    <meta property="og:locale" content="en_IN">
    @if($ogImage)
        <meta property="og:image" content="{{ $ogImage }}">
    @endif

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="{{ \App\Support\ShareImage::twitterCard($ogImage) }}">
    <meta name="twitter:title" content="{{ $fullTitle }}">
    <meta name="twitter:description" content="{{ Str::limit(strip_tags($metaDescription), 160) }}">
    @if($ogImage)
        <meta name="twitter:image" content="{{ $ogImage }}">
    @endif

    {{-- Tracking & Analytics (GA, GTM, Facebook Pixel) --}}
    @include('components.partials.tracking-head', ['gaId' => $gaId, 'gtmId' => $gtmId, 'fbPixelId' => $fbPixelId])

    {{-- Structured Data: WebSite --}}
    {{-- NOTE: '@@context' (double-@) is the Blade escape for a literal '@context'.
         Without the escape, Laravel 11's @context Blade directive intercepts the
         JSON-LD key and compiles it to PHP, breaking SEO structured data. --}}
    <script type="application/ld+json">
    {!! json_encode([
        '@@context' => 'https://schema.org',
        '@type' => 'WebSite',
        'name' => $siteName,
        'url' => url('/'),
        'description' => $siteTagline,
        'potentialAction' => [
            '@type' => 'SearchAction',
            'target' => url('/search?q={search_term_string}'),
            'query-input' => 'required name=search_term_string',
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
    </script>

    {{-- Structured Data: Organization + LocalBusiness --}}
    <script type="application/ld+json">
    {!! json_encode(array_filter([
        '@@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => $siteName,
        'url' => url('/'),
        'logo' => \App\Support\ShareImage::logo(),
        'email' => $siteEmail ?: null,
        'telephone' => $sitePhone ?: null,
        'address' => $siteAddress ? ['@type' => 'PostalAddress', 'streetAddress' => $siteAddress] : null,
        'sameAs' => array_values(array_filter([
            \App\Models\SiteSetting::getValue('social_facebook', ''),
            \App\Models\SiteSetting::getValue('social_instagram', ''),
            \App\Models\SiteSetting::getValue('social_youtube', ''),
            \App\Models\SiteSetting::getValue('social_twitter', ''),
            \App\Models\SiteSetting::getValue('social_linkedin', ''),
        ])) ?: null,
    ]), JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
    </script>

    {{-- Structured Data a page adds itself (e.g. a blog article) --}}
    @if(! empty($jsonLd))
    <script type="application/ld+json">
    {!! json_encode(['@@context' => 'https://schema.org'] + $jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_PRETTY_PRINT) !!}
    </script>
    @endif

    {{-- Structured Data: breadcrumbs (Discover pages pass :breadcrumbs) --}}
    @if(! empty($breadcrumbs))
    <script type="application/ld+json">
    {!! json_encode([
        '@@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => collect([['name' => 'Home', 'url' => url('/')]])
            ->merge(array_values($breadcrumbs))
            ->values()
            ->map(fn ($crumb, $i) => ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $crumb['name'], 'item' => $crumb['url']])
            ->all(),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_PRETTY_PRINT) !!}
    </script>
    @endif

    @if($theme->favicon_url ?? false)
        <link rel="icon" href="{{ $theme->favicon_url }}" type="image/x-icon">
    @endif

    <!-- Google Fonts (dynamic — built from ThemeSetting heading_font + body_font) -->
    @php
        $headingFontFamily = $theme->heading_font ?? 'Playfair Display';
        $bodyFontFamily = $theme->body_font ?? 'Inter';

        // Build Google Fonts URL. Use sensible weight defaults.
        // For curated fonts we use their full weight list; for custom fonts we use a safe set.
        $curatedWeights = collect(array_merge(config('fonts.headings', []), config('fonts.body', [])))
            ->mapWithKeys(fn ($f) => [$f['family'] => $f['weights']])
            ->toArray();

        $headingWeights = $curatedWeights[$headingFontFamily] ?? '400;700';
        $bodyWeights = $curatedWeights[$bodyFontFamily] ?? '400;500;600;700';

        // URL-encode font family names (replace space with +)
        $fontsUrl = 'https://fonts.googleapis.com/css2?';
        $fontParams = [];
        $fontParams[] = 'family=' . str_replace(' ', '+', $headingFontFamily) . ':wght@' . $headingWeights;
        if ($bodyFontFamily !== $headingFontFamily) {
            $fontParams[] = 'family=' . str_replace(' ', '+', $bodyFontFamily) . ':wght@' . $bodyWeights;
        }
        $fontsUrl .= implode('&', $fontParams) . '&display=swap';
    @endphp
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="{{ $fontsUrl }}" rel="stylesheet">

    <!-- Typography variables — override app.css defaults with admin-picked fonts -->
    <style>
        :root {
            --brand-font-sans: "{{ $bodyFontFamily }}", ui-sans-serif, system-ui, -apple-system, sans-serif;
            --brand-font-serif: "{{ $headingFontFamily }}", Georgia, ui-serif, serif;
        }
    </style>
    {{-- Legacy hardcoded Google Fonts removed — now loaded dynamically from theme settings above. --}}

    <!-- White-label theme colors injected from database -->
    <style>
        :root {
            --brand-primary: {{ $theme->primary_color ?? '#8B1D91' }};
            --brand-primary-hover: {{ $theme->primary_hover ?? '#6B1571' }};
            --brand-primary-light: {{ $theme->primary_light ?? '#F3E8F7' }};
            --brand-secondary: {{ $theme->secondary_color ?? '#00BCD4' }};
            --brand-secondary-hover: {{ $theme->secondary_hover ?? '#00ACC1' }};
            --brand-secondary-light: {{ $theme->secondary_light ?? '#E0F7FA' }};
        }
    </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-[#FEFCFB] text-[#1C1917] font-sans antialiased">
    @include('components.partials.tracking-body', ['gtmId' => $gtmId])

    {{-- Announcement banner (Phase 2.6A — admin-toggleable) --}}
    @php
        $announcementEnabled = \App\Models\SiteSetting::getValue('announcement_enabled', '0') === '1';
        $announcementText = \App\Models\SiteSetting::getValue('announcement_text', '');
    @endphp
    @if($announcementEnabled && !empty($announcementText))
        <div class="bg-(--color-primary) text-white text-center text-sm py-2 px-4" role="alert">
            {{ $announcementText }}
        </div>
    @endif

    @if($isLoggedIn)
        {{-- ══ LOGGED-IN HEADER ══ --}}
        <header class="sticky top-0 z-40 bg-white border-b border-gray-200 shadow-xs">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <!-- Logo -->
                    <a href="/dashboard" class="flex items-center gap-2">
                        @if($theme->logo_url ?? false)
                            <img src="{{ $theme->logo_url }}" alt="{{ $siteName }}" style="height: {{ \App\Support\HeaderLogo::height() }}px; width: auto">
                        @else
                            <span class="text-xl font-serif font-bold text-(--color-primary)">{{ $siteName }}</span>
                        @endif
                    </a>

                    <!-- Desktop Nav with Dropdowns -->
                    <nav class="hidden md:flex items-center gap-1">
                        {{-- My Home --}}
                        <div x-data="{ open: false }" @click.outside="open = false" class="relative">
                            <button @click="open = !open" class="flex items-center gap-1 px-3 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 rounded-lg hover:bg-gray-50">
                                My Home
                                <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open" x-cloak class="absolute left-0 mt-1 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50">
                                <a href="/dashboard" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">My Home</a>
                                <a href="{{ route('profile.show') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">View & Edit Profile</a>
                                <a href="{{ route('photos.manage') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Manage Photos</a>
                                <a href="{{ route('idproof.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Submit ID Proof</a>
                                <a href="{{ route('membership.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Membership Plans</a>
                                <a href="{{ route('settings.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Profile Settings</a>
                            </div>
                        </div>

                        {{-- Search --}}
                        <div x-data="{ open: false }" @click.outside="open = false" class="relative">
                            <button @click="open = !open" class="flex items-center gap-1 px-3 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 rounded-lg hover:bg-gray-50">
                                Search
                                <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open" x-cloak class="absolute left-0 mt-1 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50">
                                <a href="{{ route('search.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Partner Search</a>
                                <a href="{{ route('search.index', ['tab' => 'keyword']) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Keyword Search</a>
                                <a href="{{ route('search.index', ['tab' => 'byid']) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Search by ID</a>
                                <div class="border-t border-gray-100 my-1"></div>
                                <a href="{{ route('discover.hub') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Discover Profiles</a>
                            </div>
                        </div>

                        {{-- Matches --}}
                        <div x-data="{ open: false }" @click.outside="open = false" class="relative">
                            <button @click="open = !open" class="flex items-center gap-1 px-3 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 rounded-lg hover:bg-gray-50">
                                Matches
                                <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open" x-cloak class="absolute left-0 mt-1 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50">
                                <a href="{{ route('matches.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">My Matches</a>
                                <a href="{{ route('matches.mutual') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Mutual Matches</a>
                            </div>
                        </div>

                        {{-- Interests (requests) and Chats (accepted conversations) — Oct 2026 --}}
                        @php
                            $navMe = auth()->user()?->profile;
                            $navPending = $navMe ? \App\Support\Chats::pendingInterestsCount($navMe) : 0;
                            $navUnread = $navMe ? \App\Support\Chats::unreadCount($navMe) : 0;
                        @endphp
                        <a href="{{ route('interests.inbox', $navPending ? ['tab' => 'received'] : []) }}" class="px-3 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 rounded-lg hover:bg-gray-50" style="display: inline-flex; align-items: center; gap: 6px;">
                            Interests
                            @if($navPending)<span class="nav-count-badge" aria-label="{{ $navPending }} waiting for your reply">{{ $navPending > 99 ? '99+' : $navPending }}</span>@endif
                        </a>
                        <a href="{{ route('chats.index') }}" class="px-3 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 rounded-lg hover:bg-gray-50" style="display: inline-flex; align-items: center; gap: 6px;">
                            Chats
                            @if($navUnread)<span class="nav-count-badge" aria-label="{{ $navUnread }} with new messages">{{ $navUnread > 99 ? '99+' : $navUnread }}</span>@endif
                        </a>
                        <style>.nav-count-badge { min-width: 18px; height: 18px; padding: 0 5px; border-radius: 9999px; background: var(--color-primary, #8B1D91); color: #fff; font-size: 11px; font-weight: 700; line-height: 18px; text-align: center; }</style>

                        {{-- Activity --}}
                        <div x-data="{ open: false }" @click.outside="open = false" class="relative">
                            <button @click="open = !open" class="flex items-center gap-1 px-3 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 rounded-lg hover:bg-gray-50">
                                Activity
                                <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open" x-cloak class="absolute left-0 mt-1 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50">
                                <a href="{{ route('views.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Profile Views</a>
                                <a href="{{ route('shortlist.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Shortlisted Profiles</a>
                                <a href="{{ route('blocked.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Blocked Profiles</a>
                            </div>
                        </div>
                    </nav>

                    <!-- Right side: Notification bell + Avatar dropdown -->
                    <div class="flex items-center gap-4">
                        <!-- Notification bell -->
                        <div x-data="{ notifOpen: false }" @click.outside="notifOpen = false" class="relative">
                            <button @click="notifOpen = !notifOpen" class="relative p-2 rounded-lg text-gray-500 hover:text-gray-700 hover:bg-gray-100">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                                @if(($unreadNotificationCount ?? 0) > 0)
                                    <span class="absolute -top-0.5 -right-0.5 flex items-center justify-center w-5 h-5 text-[10px] font-bold text-white rounded-full bg-red-500">{{ $unreadNotificationCount }}</span>
                                @endif
                            </button>

                            {{-- Notification dropdown --}}
                            {{-- On phones the bell sits mid-header, so a panel pinned to it ran off the
                                 left edge (Oct 2026): below 640px it is pinned to the screen under the
                                 header instead. Inline CSS: the live build is not rebuilt on deploy. --}}
                            <style>
                                @media (max-width: 639px) {
                                    .notif-panel { position: fixed !important; left: 12px !important; right: 12px !important; top: 68px !important; width: auto !important; max-width: none !important; margin-top: 0 !important; }
                                    .notif-panel .notif-list { max-height: calc(100vh - 190px) !important; }
                                }
                                .notif-msg { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; overflow-wrap: anywhere; }
                            </style>
                            <div x-show="notifOpen" x-cloak class="notif-panel absolute right-0 mt-2 w-[calc(100vw-2rem)] sm:w-80 md:w-96 max-w-96 bg-white rounded-lg shadow-lg border border-gray-200 z-50 overflow-hidden">
                                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                                    <h3 class="text-sm font-semibold text-gray-900">Notifications ({{ $unreadNotificationCount ?? 0 }})</h3>
                                    <div class="flex items-center gap-2">
                                        @if(($unreadNotificationCount ?? 0) > 0)
                                            <form method="POST" action="{{ route('notifications.readAll') }}">
                                                @csrf
                                                <button type="submit" class="text-xs text-(--color-primary) hover:underline font-medium">Mark all read</button>
                                            </form>
                                        @endif
                                        <button @click="notifOpen = false" class="p-1 text-gray-400 hover:text-gray-600">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </div>
                                </div>

                                <div class="notif-list max-h-96 overflow-y-auto">
                                    @php $notifs = $recentNotifications ?? ['today' => collect(), 'yesterday' => collect(), 'previous' => collect()]; @endphp
                                    @php $hasAny = $notifs['today']->count() + $notifs['yesterday']->count() + $notifs['previous']->count(); @endphp

                                    @if($hasAny > 0)
                                        @foreach(['today' => 'Today', 'yesterday' => 'Yesterday', 'previous' => 'Previous'] as $key => $label)
                                            @if($notifs[$key]->count() > 0)
                                                <p class="px-4 pt-3 pb-1 text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $label }}</p>
                                                @foreach($notifs[$key] as $notif)
                                                    <form method="POST" action="{{ route('notifications.read', $notif) }}">
                                                        @csrf
                                                        <button type="submit" class="w-full flex items-start gap-3 px-4 py-3 hover:bg-gray-50 transition-colors text-left {{ !$notif->is_read ? 'bg-(--color-primary-light)/30' : '' }}">
                                                            <div class="w-8 h-8 rounded-full shrink-0 mt-0.5 flex items-center justify-center {{ match($notif->type) { 'interest_received' => 'bg-(--color-primary-light) text-(--color-primary)', 'interest_accepted' => 'bg-green-100 text-green-600', 'interest_declined' => 'bg-red-100 text-red-500', default => 'bg-gray-100 text-gray-400' } }}">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75"/></svg>
                                                            </div>
                                                            <div class="flex-1 min-w-0">
                                                                <p class="text-sm font-medium text-gray-900 {{ !$notif->is_read ? 'font-semibold' : '' }}">{{ $notif->title }}</p>
                                                                <p class="notif-msg text-xs text-gray-600">{{ $notif->message }}</p>
                                                                <p class="text-[10px] text-gray-400 mt-0.5">{{ $notif->created_at->displayTz()->format('d/m/Y h:i A') }}</p>
                                                            </div>
                                                            @if(!$notif->is_read)
                                                                <div class="w-2 h-2 rounded-full bg-(--color-primary) shrink-0 mt-2"></div>
                                                            @endif
                                                        </button>
                                                    </form>
                                                @endforeach
                                            @endif
                                        @endforeach
                                    @else
                                        <div class="p-6 text-center">
                                            <p class="text-sm text-gray-400">No notifications yet</p>
                                        </div>
                                    @endif
                                </div>

                                <a href="{{ route('notifications.index') }}" class="block text-center text-xs text-(--color-primary) font-medium py-2.5 border-t border-gray-100 hover:bg-gray-50">
                                    View All Notifications
                                </a>
                            </div>
                        </div>

                        <!-- Avatar dropdown -->
                        <div x-data="{ open: false }" class="relative">
                            <button x-on:click="open = !open" class="flex items-center gap-2 p-1.5 rounded-lg hover:bg-gray-100">
                                <div class="w-8 h-8 rounded-full bg-(--color-primary-light) flex items-center justify-center">
                                    <svg class="w-5 h-5 text-(--color-primary)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                </div>
                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>

                            <div x-show="open" x-on:click.away="open = false" x-cloak class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-sm border border-gray-200 py-1 z-50">
                                <a href="{{ route('profile.show') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">My Profile</a>
                                <a href="{{ route('photos.manage') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Manage Photos</a>
                                <a href="{{ route('settings.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Settings</a>
                                <a href="{{ route('shortlist.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">My Shortlist</a>
                                <a href="{{ route('views.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Profile Views</a>
                                <div class="border-t border-gray-100 my-1"></div>
                                <form method="POST" action="/logout">
                                    @csrf
                                    <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Logout</button>
                                </form>
                            </div>
                        </div>

                        <!-- Mobile hamburger -->
                        <button x-data x-on:click="$dispatch('toggle-mobile-menu')" class="md:hidden p-2 rounded-lg hover:bg-gray-100" style="position: relative;"
                                @if(($navPending ?? 0) || ($navUnread ?? 0)) aria-label="Menu — new interests or chats" @endif>
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                            {{-- something new under Interests / Chats --}}
                            @if(($navPending ?? 0) || ($navUnread ?? 0))
                                <span style="position: absolute; top: 4px; right: 4px; width: 9px; height: 9px; border-radius: 9999px; background: var(--color-primary, #8B1D91); box-shadow: 0 0 0 2px #fff;"></span>
                            @endif
                        </button>
                    </div>
                </div>
            </div>

            <!-- Mobile menu (logged-in) -->
            <div x-data="{ open: false }" x-on:toggle-mobile-menu.window="open = !open" x-show="open" x-cloak class="md:hidden border-t border-gray-200 bg-white">
                <div class="px-4 py-3 space-y-1">
                    {{-- My Home --}}
                    <p class="px-3 pt-2 pb-1 text-[10px] font-bold text-gray-400 uppercase tracking-wider">My Home</p>
                    <a href="/dashboard" class="block px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100">Dashboard</a>
                    <a href="{{ route('profile.show') }}" class="block px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100">View & Edit Profile</a>
                    <a href="{{ route('photos.manage') }}" class="block px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100">Manage Photos</a>
                    <a href="{{ route('idproof.index') }}" class="block px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100">Submit ID Proof</a>
                    <a href="{{ route('membership.index') }}" class="block px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100">Membership Plans</a>
                    <a href="{{ route('settings.index') }}" class="block px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100">Profile Settings</a>

                    {{-- Search --}}
                    <div class="border-t border-gray-100 my-1"></div>
                    <p class="px-3 pt-2 pb-1 text-[10px] font-bold text-gray-400 uppercase tracking-wider">Search</p>
                    <a href="{{ route('search.index') }}" class="block px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100">Partner Search</a>
                    <a href="{{ route('search.index', ['tab' => 'keyword']) }}" class="block px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100">Keyword Search</a>
                    <a href="{{ route('search.index', ['tab' => 'byid']) }}" class="block px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100">Search by ID</a>
                    <a href="{{ route('discover.hub') }}" class="block px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100">Discover Profiles</a>

                    {{-- Matches --}}
                    <div class="border-t border-gray-100 my-1"></div>
                    <p class="px-3 pt-2 pb-1 text-[10px] font-bold text-gray-400 uppercase tracking-wider">Matches</p>
                    <a href="{{ route('matches.index') }}" class="block px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100">My Matches</a>
                    <a href="{{ route('matches.mutual') }}" class="block px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100">Mutual Matches</a>

                    {{-- Interests + Chats --}}
                    @php
                        $navMe = auth()->user()?->profile;
                        $navPending = $navMe ? \App\Support\Chats::pendingInterestsCount($navMe) : 0;
                        $navUnread = $navMe ? \App\Support\Chats::unreadCount($navMe) : 0;
                    @endphp
                    <div class="border-t border-gray-100 my-1"></div>
                    <a href="{{ route('interests.inbox', $navPending ? ['tab' => 'received'] : []) }}" class="block px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100" style="display: flex; align-items: center; justify-content: space-between;">
                        Interests
                        @if($navPending)<span class="nav-count-badge">{{ $navPending > 99 ? '99+' : $navPending }}</span>@endif
                    </a>
                    <a href="{{ route('chats.index') }}" class="block px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100" style="display: flex; align-items: center; justify-content: space-between;">
                        Chats
                        @if($navUnread)<span class="nav-count-badge">{{ $navUnread > 99 ? '99+' : $navUnread }}</span>@endif
                    </a>

                    {{-- Activity --}}
                    <div class="border-t border-gray-100 my-1"></div>
                    <p class="px-3 pt-2 pb-1 text-[10px] font-bold text-gray-400 uppercase tracking-wider">Activity</p>
                    <a href="{{ route('views.index') }}" class="block px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100">Profile Views</a>
                    <a href="{{ route('shortlist.index') }}" class="block px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100">Shortlisted Profiles</a>
                    <a href="{{ route('blocked.index') }}" class="block px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100">Blocked Profiles</a>

                    {{-- Logout --}}
                    <div class="border-t border-gray-100 my-1"></div>
                    <form method="POST" action="/logout">
                        @csrf
                        <button type="submit" class="block w-full text-left px-3 py-2 rounded-lg text-sm font-medium text-red-600 hover:bg-red-50">Logout</button>
                    </form>
                </div>
            </div>
        </header>
    @else
        {{-- ══ NON-LOGGED-IN HEADER ══ --}}
        @include('components.partials.contact-strip')
        <header class="sticky top-0 z-40 bg-white border-b border-gray-200 shadow-xs">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <!-- Logo -->
                    <a href="/" class="flex items-center gap-2">
                        @if($theme->logo_url ?? false)
                            <img src="{{ $theme->logo_url }}" alt="{{ $siteName }}" style="height: {{ \App\Support\HeaderLogo::height() }}px; width: auto">
                        @else
                            <span class="text-xl font-serif font-bold text-(--color-primary)">{{ $siteName }}</span>
                        @endif
                    </a>

                    <!-- Desktop Nav -->
                    <nav class="hidden md:flex items-center gap-6">
                        <a href="/" class="text-sm font-medium text-gray-700 hover:text-gray-900">Home</a>
                        {{-- Search Dropdown --}}
                        <div class="relative" x-data="{ open: false }" @click.away="open = false">
                            <button @click="open = !open" class="flex items-center gap-1 text-sm font-medium text-gray-700 hover:text-gray-900">
                                Search
                                <svg class="w-3.5 h-3.5 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-cloak
                                class="absolute left-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50">
                                <a href="{{ route('search.quick') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-(--color-primary)">Quick Search</a>
                                <a href="{{ route('search.advance') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-(--color-primary)">Advance Search</a>
                                <a href="{{ route('search.keyword') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-(--color-primary)">Keyword Search</a>
                                <a href="{{ route('search.byid') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-(--color-primary)">Id Search</a>
                            </div>
                        </div>
                        <a href="/membership-plans" class="text-sm font-medium text-gray-700 hover:text-gray-900">Membership</a>
                        <a href="/about-us" class="text-sm font-medium text-gray-700 hover:text-gray-900">About Us</a>
                        <a href="/contact-us" class="text-sm font-medium text-gray-700 hover:text-gray-900">Contact</a>
                        {{-- Register Free is the main action (filled); Login is secondary (outline) --}}
                        <a href="/login" class="px-4 py-2 text-sm font-semibold text-(--color-primary) border border-(--color-primary) hover:bg-(--color-primary-light) rounded-lg transition-colors">Login</a>
                        <a href="/register" class="px-5 py-2 text-sm font-semibold text-white bg-(--color-primary) hover:bg-(--color-primary-hover) rounded-lg transition-colors">Register Free</a>
                    </nav>

                    <!-- Mobile hamburger -->
                    <button x-data x-on:click="$dispatch('toggle-guest-menu')" class="md:hidden p-2 rounded-lg hover:bg-gray-100">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                </div>
            </div>

            <!-- Mobile menu (non-logged-in) -->
            <div x-data="{ open: false }" x-on:toggle-guest-menu.window="open = !open" x-show="open" x-cloak class="md:hidden border-t border-gray-200 bg-white">
                <div class="px-4 py-3 space-y-2">
                    <a href="/" class="block px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100">Home</a>
                    {{-- Search accordion --}}
                    <div x-data="{ open: false }">
                        <button @click="open = !open" class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100">
                            Search
                            <svg class="w-4 h-4 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open" x-collapse class="ml-4 space-y-1 mt-1">
                            <a href="{{ route('search.quick') }}" class="block px-3 py-1.5 rounded-lg text-sm text-gray-600 hover:bg-gray-100 hover:text-(--color-primary)">Quick Search</a>
                            <a href="{{ route('search.advance') }}" class="block px-3 py-1.5 rounded-lg text-sm text-gray-600 hover:bg-gray-100 hover:text-(--color-primary)">Advance Search</a>
                            <a href="{{ route('search.keyword') }}" class="block px-3 py-1.5 rounded-lg text-sm text-gray-600 hover:bg-gray-100 hover:text-(--color-primary)">Keyword Search</a>
                            <a href="{{ route('search.byid') }}" class="block px-3 py-1.5 rounded-lg text-sm text-gray-600 hover:bg-gray-100 hover:text-(--color-primary)">Id Search</a>
                        </div>
                    </div>
                    <a href="/membership-plans" class="block px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100">Membership</a>
                    <a href="/about-us" class="block px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100">About Us</a>
                    <a href="/contact-us" class="block px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100">Contact</a>
                    <a href="/register" class="block px-3 py-2 rounded-lg text-sm font-semibold text-white bg-(--color-primary) text-center">Register Free</a>
                    <a href="/login" class="block px-3 py-2 rounded-lg text-sm font-semibold text-(--color-primary) border border-(--color-primary) text-center">Login</a>
                </div>
            </div>
        </header>
    @endif

    <!-- Main Content -->
    <main>
        @if(request()->routeIs('home') || request()->routeIs('membership.index'))
            <x-offer-banner />
        @endif
        {{ $slot }}
    </main>

    @if($isLoggedIn)
        {{-- ══ LOGGED-IN FOOTER ══ --}}
        <footer class="bg-gray-900 text-gray-400 mt-16" style="background-color: {{ \App\Support\SiteContact::footerColour() }};">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                    <p class="text-sm">&copy; {{ date('Y') }} {{ $siteName }}. All rights reserved.</p>
                    <div class="flex items-center gap-4 text-sm">
                        <a href="/privacy-policy" class="hover:text-white transition-colors">Privacy</a>
                        <a href="/terms-condition" class="hover:text-white transition-colors">Terms</a>
                        <a href="/faq" class="hover:text-white transition-colors">Help</a>
                        @php
                            $socialFb = \App\Models\SiteSetting::getValue('social_facebook');
                            $socialIg = \App\Models\SiteSetting::getValue('social_instagram');
                            $socialYt = \App\Models\SiteSetting::getValue('social_youtube');
                            $socialTw = \App\Models\SiteSetting::getValue('social_twitter');
                            $socialLi = \App\Models\SiteSetting::getValue('social_linkedin');
                        @endphp
                        @if($socialFb || $socialIg || $socialYt || $socialTw || $socialLi)
                            <span class="text-gray-700">|</span>
                            @if($socialFb)<a href="{{ $socialFb }}" target="_blank" class="hover:text-white transition-colors"><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg></a>@endif
                            @if($socialIg)<a href="{{ $socialIg }}" target="_blank" class="hover:text-white transition-colors"><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg></a>@endif
                            @if($socialYt)<a href="{{ $socialYt }}" target="_blank" class="hover:text-white transition-colors"><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 00-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 00.502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 002.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 002.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg></a>@endif
                            @if($socialTw)<a href="{{ $socialTw }}" target="_blank" class="hover:text-white transition-colors"><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg></a>@endif
                            @if($socialLi)<a href="{{ $socialLi }}" target="_blank" aria-label="LinkedIn" class="hover:text-white transition-colors"><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.063 2.063 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg></a>@endif
                        @endif
                    </div>
                </div>
            </div>
        </footer>

        {{-- Phones: Home · Matches · Interests · Chats · Profile --}}
        <x-mobile-bottom-nav />
    @else
        {{-- ══ FOOTER AD BANNER ══ --}}
        <x-ad-slot position="footer_banner" />

        {{-- ══ NON-LOGGED-IN FOOTER ══ --}}
        <footer class="bg-gray-900 text-gray-400 mt-16" style="background-color: {{ \App\Support\SiteContact::footerColour() }};">
            {{-- Contact band (Oct 2026, like Chavara): call / WhatsApp, email, office, help — on every visitor page --}}
            @php
                $fPhone = \App\Support\SiteContact::phoneDisplay();
                $fTel = \App\Support\SiteContact::phoneHref();
                $fWa = \App\Support\SiteContact::whatsappHref();
                $fEmail = \App\Support\SiteContact::email();
                $fAddress = \App\Support\SiteContact::address();
                $fBand = [];
                if ($fTel || $fWa) {
                    $fBand[] = ['label' => $fTel && $fWa ? 'Call / WhatsApp' : ($fTel ? 'Call us' : 'WhatsApp'), 'value' => $fPhone ?: 'Chat with us', 'href' => $fTel ?: $fWa, 'wa' => $fTel ? $fWa : null,
                        'icon' => 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z'];
                }
                if ($fEmail) {
                    $fBand[] = ['label' => 'Email us', 'value' => $fEmail, 'href' => 'mailto:' . $fEmail, 'wa' => null,
                        'icon' => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'];
                }
                if ($fAddress) {
                    $fBand[] = ['label' => 'Visit our office', 'value' => $fAddress, 'href' => null, 'wa' => null, 'small' => true,
                        'icon' => 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z'];
                }
                if ($fBand) {
                    $fBand[] = ['label' => 'Need help?', 'value' => 'Help & FAQ', 'href' => '/faq', 'wa' => null,
                        'icon' => 'M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'];
                }
            @endphp
            @if($fBand)
                <style>
                    .ft-band { background: rgba(255,255,255,.06); border-bottom: 1px solid rgba(255,255,255,.08); }
                    .ft-band-grid { display: grid; grid-template-columns: 1fr; gap: 18px; padding-top: 22px; padding-bottom: 22px; }
                    @media (min-width: 640px) { .ft-band-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
                    {{-- wide screens: phone / email / help keep their width, the address takes what is left --}}
                    @media (min-width: 1024px) { .ft-band-grid { grid-template-columns: {{ collect($fBand)->map(fn ($b) => empty($b['small']) ? 'auto' : 'minmax(0, 1fr)')->implode(' ') }}; justify-content: space-between; gap: 32px; } .ft-band-value { overflow-wrap: normal; } }
                    .ft-band-item { display: flex; gap: 12px; align-items: flex-start; min-width: 0; }
                    .ft-band-icon { flex: none; width: 38px; height: 38px; border-radius: 999px; display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,.08); color: var(--color-secondary); }
                    .ft-band-label { display: block; font-size: 12px; color: rgba(255,255,255,.6); }
                    .ft-band-value { display: block; margin-top: 2px; font-size: 14px; font-weight: 600; color: #fff; overflow-wrap: anywhere; }
                    .ft-band-value.is-small { font-size: 12.5px; font-weight: 500; line-height: 1.45; color: rgba(255,255,255,.85); }
                    a.ft-band-value:hover, .ft-band-wa:hover { text-decoration: underline; }
                    .ft-band-wa { display: inline-block; margin-top: 2px; font-size: 12px; font-weight: 600; color: #4ade80; }
                </style>
                <div class="ft-band">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 ft-band-grid">
                        @foreach($fBand as $b)
                            <div class="ft-band-item">
                                <span class="ft-band-icon"><svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $b['icon'] }}"/></svg></span>
                                <span style="min-width: 0;">
                                    <span class="ft-band-label">{{ $b['label'] }}</span>
                                    @if($b['href'])
                                        <a href="{{ $b['href'] }}" class="ft-band-value{{ ! empty($b['small']) ? ' is-small' : '' }}" @if(str_starts_with($b['href'], 'https://wa.me')) target="_blank" rel="noopener" @endif>{{ $b['value'] }}</a>
                                    @else
                                        <span class="ft-band-value{{ ! empty($b['small']) ? ' is-small' : '' }}">{{ $b['value'] }}</span>
                                    @endif
                                    @if($b['wa'])
                                        <a href="{{ $b['wa'] }}" target="_blank" rel="noopener" class="ft-band-wa">Chat on WhatsApp</a>
                                    @endif
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8 mb-8">
                    <div>
                        <h3 class="text-white font-semibold text-sm mb-3">{{ $siteName }}</h3>
                        <p class="text-xs leading-relaxed" style="color: rgba(255,255,255,.6);">{{ \App\Support\SiteContact::sentence($siteTagline) }} {{ \App\Models\SiteSetting::getValue('footer_description', '') ?: 'A trusted matrimony platform for families seeking meaningful connections.' }}</p>
                        @php
                            $fPlay = \App\Models\SiteSetting::getValue('app_play_store_url', '');
                            $fApple = \App\Models\SiteSetting::getValue('app_apple_store_url', '');
                        @endphp
                        @if($fPlay || $fApple)
                            <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-top: 16px;">
                                @if($fPlay)
                                    <a href="{{ $fPlay }}" target="_blank" rel="noopener" style="display: inline-flex; align-items: center; gap: 8px; padding: 6px 12px; border: 1px solid rgba(255,255,255,.25); border-radius: 8px; color: #fff;">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M3.609 1.814L13.792 12 3.61 22.186a.996.996 0 01-.61-.92V2.734a1 1 0 01.609-.92zm10.89 10.893l2.302 2.302-10.937 6.333 8.635-8.635zm3.199-3.199l2.302 2.302a1 1 0 010 1.38l-2.302 2.302L15.396 13l2.302-2.492zM5.864 2.658L16.8 9.39l-2.302 2.302L5.864 2.658z"/></svg>
                                        <span style="line-height: 1.1;"><span style="display: block; font-size: 9px; opacity: .7; text-transform: uppercase; letter-spacing: .05em;">Get it on</span><span style="font-size: 13px; font-weight: 600;">Google Play</span></span>
                                    </a>
                                @endif
                                @if($fApple)
                                    <a href="{{ $fApple }}" target="_blank" rel="noopener" style="display: inline-flex; align-items: center; gap: 8px; padding: 6px 12px; border: 1px solid rgba(255,255,255,.25); border-radius: 8px; color: #fff;">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.8-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/></svg>
                                        <span style="line-height: 1.1;"><span style="display: block; font-size: 9px; opacity: .7; text-transform: uppercase; letter-spacing: .05em;">Download on the</span><span style="font-size: 13px; font-weight: 600;">App Store</span></span>
                                    </a>
                                @endif
                            </div>
                        @endif
                    </div>
                    <div>
                        <h4 class="text-white font-semibold text-sm mb-3">Quick Links</h4>
                        <ul class="space-y-2 text-sm">
                            <li><a href="/" class="hover:text-white transition-colors">Home</a></li>
                            <li><a href="/register" class="hover:text-white transition-colors">Register Free</a></li>
                            <li><a href="/login" class="hover:text-white transition-colors">Login</a></li>
                            @unless(collect(\App\Models\StaticPage::getFooterPages())->contains('slug', 'about-us'))
                                <li><a href="/about-us" class="hover:text-white transition-colors">About Us</a></li>
                            @endunless
                            <li><a href="/membership-plans" class="hover:text-white transition-colors">Membership Plans</a></li>
                            {{-- <li><a href="/demograph" class="hover:text-white transition-colors">Demographics</a></li> --}}
                            <li><a href="/success-stories" class="hover:text-white transition-colors">Success Stories</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-white font-semibold text-sm mb-3">Information</h4>
                        <ul class="space-y-2 text-sm">
                            @foreach(\App\Models\StaticPage::getFooterPages() as $fp)
                                <li><a href="/{{ $fp['slug'] }}" class="hover:text-white transition-colors">{{ $fp['title'] }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-white font-semibold text-sm mb-3">Reach Us</h4>
                        <ul class="space-y-2 text-sm">
                            <li><a href="/contact-us" class="hover:text-white transition-colors">Contact Us</a></li>
                            <li><a href="/faq" class="hover:text-white transition-colors">Help & FAQ</a></li>
                            @if(\App\Support\WhatsApp::channelUrl())
                                <li><a href="{{ route('whatsapp') }}" class="hover:text-white transition-colors">WhatsApp Channel</a></li>
                            @endif
                            @if(\App\Models\BlogPost::hasPublishedPosts())
                                <li><a href="{{ route('blog.index') }}" class="hover:text-white transition-colors">Blog</a></li>
                            @endif
                            <li><a href="/event" class="hover:text-white transition-colors">Events</a></li>
                            <li><a href="/add-with-us" class="hover:text-white transition-colors">Advertise With Us</a></li>
                        </ul>
                    </div>
                </div>
                <div class="pt-6 flex flex-col sm:flex-row items-center justify-between gap-4" style="border-top: 1px solid rgba(255,255,255,.1);">
                    <div class="text-xs">
                        <p>&copy; {{ date('Y') }} {{ $siteName }}. All rights reserved.</p>
                        @if(\App\Support\SiteContact::showNotDatingNote())
                            <p style="margin-top: 4px; color: rgba(255,255,255,.55);">{{ $siteName }} is for people who want to marry. It is not a dating site.</p>
                        @endif
                    </div>
                    @php
                        $sFb = \App\Models\SiteSetting::getValue('social_facebook');
                        $sIg = \App\Models\SiteSetting::getValue('social_instagram');
                        $sYt = \App\Models\SiteSetting::getValue('social_youtube');
                        $sTw = \App\Models\SiteSetting::getValue('social_twitter');
                        $sLi = \App\Models\SiteSetting::getValue('social_linkedin');
                    @endphp
                    @if($sFb || $sIg || $sYt || $sTw || $sLi)
                        <style>
                            .ft-social { width: 36px; height: 36px; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center; background: rgba(255,255,255,.08); color: rgba(255,255,255,.85); transition: background .15s, color .15s; }
                            .ft-social:hover { background: var(--color-secondary); color: #fff; }
                        </style>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            @if($sFb)<a href="{{ $sFb }}" target="_blank" rel="noopener" aria-label="Facebook" class="ft-social"><svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg></a>@endif
                            @if($sIg)<a href="{{ $sIg }}" target="_blank" rel="noopener" aria-label="Instagram" class="ft-social"><svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg></a>@endif
                            @if($sYt)<a href="{{ $sYt }}" target="_blank" rel="noopener" aria-label="YouTube" class="ft-social"><svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 00-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 00.502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 002.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 002.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg></a>@endif
                            @if($sTw)<a href="{{ $sTw }}" target="_blank" rel="noopener" aria-label="X (Twitter)" class="ft-social"><svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg></a>@endif
                            @if($sLi)<a href="{{ $sLi }}" target="_blank" rel="noopener" aria-label="LinkedIn" class="ft-social"><svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.063 2.063 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg></a>@endif
                        </div>
                    @endif
                </div>
            </div>
        </footer>
    @endif

    @livewireScripts
    <x-whatsapp-button />
</body>
</html>

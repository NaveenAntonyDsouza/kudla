@php
    $theme = \App\Models\ThemeSetting::getTheme();
    $siteName = \App\Models\SiteSetting::getValue('site_name', 'Matrimony');
    $siteTagline = \App\Models\SiteSetting::getValue('tagline', 'Find Your Perfect Match');
    $sitePhone = \App\Models\SiteSetting::getValue('phone', '');
    $siteWhatsApp = \App\Models\SiteSetting::getValue('whatsapp', '');
    $siteEmail = \App\Models\SiteSetting::getValue('email', '');
    $copyrightStart = \App\Models\SiteSetting::getValue('copyright_year_start', '2024');

    // Announcement banner (Phase 2.6A — new frontend rendering)
    $announcementEnabled = \App\Models\SiteSetting::getValue('announcement_enabled', '0') === '1';
    $announcementText = \App\Models\SiteSetting::getValue('announcement_text', '');

    // Social links (Phase 2.6A — added LinkedIn)
    $socials = [
        'facebook' => \App\Models\SiteSetting::getValue('social_facebook', ''),
        'instagram' => \App\Models\SiteSetting::getValue('social_instagram', ''),
        'twitter' => \App\Models\SiteSetting::getValue('social_twitter', ''),
        'youtube' => \App\Models\SiteSetting::getValue('social_youtube', ''),
        'linkedin' => \App\Models\SiteSetting::getValue('social_linkedin', ''),
    ];

    // Helpers — tel:/mailto:/wa.me clickable links
    $whatsappDigits = preg_replace('/\D/', '', (string) $siteWhatsApp);
    $phoneDigits = preg_replace('/\D/', '', (string) $sitePhone);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? '' }} | {{ $siteName }}</title>

    <!-- Google Fonts (dynamic — built from ThemeSetting heading_font + body_font) -->
    @php
        $headingFontFamily = $theme->heading_font ?? 'Playfair Display';
        $bodyFontFamily = $theme->body_font ?? 'Inter';

        $curatedWeights = collect(array_merge(config('fonts.headings', []), config('fonts.body', [])))
            ->mapWithKeys(fn ($f) => [$f['family'] => $f['weights']])
            ->toArray();

        $headingWeights = $curatedWeights[$headingFontFamily] ?? '400;700';
        $bodyWeights = $curatedWeights[$bodyFontFamily] ?? '400;500;600;700';

        $fontParams = ['family=' . str_replace(' ', '+', $headingFontFamily) . ':wght@' . $headingWeights];
        if ($bodyFontFamily !== $headingFontFamily) {
            $fontParams[] = 'family=' . str_replace(' ', '+', $bodyFontFamily) . ':wght@' . $bodyWeights;
        }
        $fontsUrl = 'https://fonts.googleapis.com/css2?' . implode('&', $fontParams) . '&display=swap';
    @endphp
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="{{ $fontsUrl }}" rel="stylesheet">
    <style>
        :root {
            --brand-font-sans: "{{ $bodyFontFamily }}", ui-sans-serif, system-ui, sans-serif;
            --brand-font-serif: "{{ $headingFontFamily }}", Georgia, ui-serif, serif;
        }
    </style>

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
    @include('components.partials.tracking-head')
</head>
<body class="bg-[#FEFCFB] text-[#1C1917] font-sans antialiased">
    @include('components.partials.tracking-body')

    {{-- Announcement banner (admin-toggleable via Homepage Settings) --}}
    @if($announcementEnabled && !empty($announcementText))
        <div class="bg-(--color-primary) text-white text-center text-sm py-2 px-4" role="alert">
            {{ $announcementText }}
        </div>
    @endif

    <!-- Public Header -->
    <header class="sticky top-0 z-40 bg-white border-b border-gray-200 shadow-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Logo -->
                <a href="/" class="flex items-center gap-2">
                    @if($theme->logo_url ?? false)
                        <img src="{{ $theme->logo_url }}" alt="{{ $siteName }}" class="h-10">
                    @else
                        <span class="text-xl font-serif font-bold text-(--color-primary)">{{ $siteName }}</span>
                    @endif
                </a>

                <!-- Desktop Nav -->
                <nav class="hidden md:flex items-center gap-6">
                    <a href="/" class="text-sm font-medium text-gray-700 hover:text-gray-900">Home</a>
                    <a href="/about" class="text-sm font-medium text-gray-700 hover:text-gray-900">About</a>
                    <a href="/help" class="text-sm font-medium text-gray-700 hover:text-gray-900">Help</a>
                    <a href="/login" class="text-sm font-medium px-4 py-2 border rounded-lg transition-colors border-(--color-primary) text-(--color-primary) hover:bg-(--color-primary-light)">Login</a>
                    <a href="/register" class="text-sm font-semibold text-white px-4 py-2 rounded-lg transition-colors bg-(--color-primary) hover:bg-(--color-primary-hover)">Register Free</a>
                </nav>

                <!-- Mobile hamburger -->
                <button x-data x-on:click="$dispatch('toggle-mobile-menu')" class="md:hidden p-2 rounded-lg hover:bg-gray-100">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
            </div>
        </div>

        <!-- Mobile menu -->
        <div x-data="{ open: false }" x-on:toggle-mobile-menu.window="open = !open" x-show="open" x-cloak class="md:hidden border-t border-gray-200 bg-white">
            <div class="px-4 py-3 space-y-2">
                <a href="/" class="block px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100">Home</a>
                <a href="/about" class="block px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100">About</a>
                <a href="/help" class="block px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100">Help</a>
                <a href="/login" class="block px-3 py-2 rounded-lg text-sm font-medium text-center border transition-colors border-(--color-primary) text-(--color-primary) hover:bg-(--color-primary-light)">Login</a>
                <a href="/register" class="block px-3 py-2 rounded-lg text-sm font-semibold text-center text-white transition-colors bg-(--color-primary) hover:bg-(--color-primary-hover)">Register Free</a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main>
        {{ $slot }}
    </main>

    <!-- Public Footer -->
    <footer class="bg-gray-900 text-gray-300 mt-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- Brand -->
                <div>
                    <h3 class="text-white text-lg font-serif font-bold">{{ $siteName }}</h3>
                    <p class="text-sm text-gray-400 mt-1">{{ $siteTagline }}</p>
                </div>

                <!-- Quick Links -->
                <div>
                    <h4 class="text-white text-sm font-semibold mb-3">Quick Links</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="/" class="hover:text-white transition-colors">Home</a></li>
                        <li><a href="/search" class="hover:text-white transition-colors">Search</a></li>
                        <li><a href="/plans" class="hover:text-white transition-colors">Plans</a></li>
                        <li><a href="/help" class="hover:text-white transition-colors">Help</a></li>
                    </ul>
                </div>

                <!-- About -->
                <div>
                    <h4 class="text-white text-sm font-semibold mb-3">About</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="/about" class="hover:text-white transition-colors">About Us</a></li>
                        <li><a href="/privacy-policy" class="hover:text-white transition-colors">Privacy Policy</a></li>
                        <li><a href="/terms-condition" class="hover:text-white transition-colors">Terms of Service</a></li>
                        <li><a href="/success-stories" class="hover:text-white transition-colors">Success Stories</a></li>
                    </ul>
                </div>

                <!-- Contact -->
                <div>
                    <h4 class="text-white text-sm font-semibold mb-3">Contact</h4>
                    <ul class="space-y-2 text-sm">
                        @if($sitePhone)
                            <li>
                                <a href="tel:{{ $phoneDigits }}" class="hover:text-white transition-colors inline-flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/></svg>
                                    {{ $sitePhone }}
                                </a>
                            </li>
                        @endif
                        @if($siteWhatsApp && $whatsappDigits)
                            <li>
                                <a href="https://wa.me/{{ $whatsappDigits }}" target="_blank" rel="noopener" class="hover:text-white transition-colors inline-flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.885 3.488"/></svg>
                                    Chat: {{ $siteWhatsApp }}
                                </a>
                            </li>
                        @endif
                        @if($siteEmail)
                            <li>
                                <a href="mailto:{{ $siteEmail }}" class="hover:text-white transition-colors inline-flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/><path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/></svg>
                                    {{ $siteEmail }}
                                </a>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>

            {{-- Social media row (Phase 2.6A added LinkedIn) --}}
            @if(array_filter($socials))
                <div class="border-t border-gray-700 mt-8 pt-6 flex items-center justify-center gap-4">
                    @if($socials['facebook'])
                        <a href="{{ $socials['facebook'] }}" target="_blank" rel="noopener" aria-label="Facebook" class="text-gray-400 hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        </a>
                    @endif
                    @if($socials['instagram'])
                        <a href="{{ $socials['instagram'] }}" target="_blank" rel="noopener" aria-label="Instagram" class="text-gray-400 hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98C.014 8.333 0 8.741 0 12s.014 3.668.072 4.948c.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24s3.668-.014 4.948-.072c4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                        </a>
                    @endif
                    @if($socials['twitter'])
                        <a href="{{ $socials['twitter'] }}" target="_blank" rel="noopener" aria-label="Twitter / X" class="text-gray-400 hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                        </a>
                    @endif
                    @if($socials['youtube'])
                        <a href="{{ $socials['youtube'] }}" target="_blank" rel="noopener" aria-label="YouTube" class="text-gray-400 hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 00-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 00.502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 002.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 002.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                        </a>
                    @endif
                    @if($socials['linkedin'])
                        <a href="{{ $socials['linkedin'] }}" target="_blank" rel="noopener" aria-label="LinkedIn" class="text-gray-400 hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.063 2.063 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                        </a>
                    @endif
                </div>
            @endif

            <div class="border-t border-gray-700 mt-8 pt-8 text-center text-sm text-gray-500">
                &copy; {{ $copyrightStart }}-{{ date('Y') }} {{ $siteName }}. All rights reserved.
            </div>
        </div>
    </footer>

    @livewireScripts
</body>
</html>

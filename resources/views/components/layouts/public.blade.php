@php
    $theme = \App\Models\ThemeSetting::getTheme();
    $siteName = \App\Models\SiteSetting::getValue('site_name', 'Matrimony');
    $siteTagline = \App\Models\SiteSetting::getValue('tagline', 'Find Your Perfect Match');
    $sitePhone = \App\Models\SiteSetting::getValue('phone', '');
    $siteWhatsApp = \App\Models\SiteSetting::getValue('whatsapp', '');
    $siteEmail = \App\Models\SiteSetting::getValue('email', '');
    $copyrightStart = \App\Models\SiteSetting::getValue('copyright_year_start', '2024');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? '' }} | {{ $siteName }}</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">

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
                            <li>Phone: {{ $sitePhone }}</li>
                        @endif
                        @if($siteWhatsApp)
                            <li>WhatsApp: {{ $siteWhatsApp }}</li>
                        @endif
                        @if($siteEmail)
                            <li>Email: {{ $siteEmail }}</li>
                        @endif
                    </ul>
                </div>
            </div>

            <div class="border-t border-gray-700 mt-8 pt-8 text-center text-sm text-gray-500">
                &copy; {{ $copyrightStart }}-{{ date('Y') }} {{ $siteName }}. All rights reserved.
            </div>
        </div>
    </footer>

    @livewireScripts
</body>
</html>

@php
    $theme = \App\Models\ThemeSetting::getTheme();
    $siteName = \App\Models\SiteSetting::getValue('site_name', 'Matrimony');
    $siteTagline = \App\Models\SiteSetting::getValue('tagline', 'Find Your Perfect Match');
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
</head>
<body class="bg-[#FEFCFB] text-[#1C1917] font-sans antialiased">
    <!-- Dashboard Header -->
    <header class="sticky top-0 z-40 bg-white border-b border-gray-200 shadow-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Logo -->
                <a href="/dashboard" class="flex items-center gap-2">
                    @if($theme->logo_url ?? false)
                        <img src="{{ $theme->logo_url }}" alt="{{ $siteName }}" class="h-10">
                    @else
                        <span class="text-xl font-serif font-bold text-(--color-primary)">{{ $siteName }}</span>
                    @endif
                </a>

                <!-- Desktop Nav -->
                <nav class="hidden md:flex items-center gap-6">
                    <a href="/dashboard" class="text-sm font-medium text-gray-700 hover:text-gray-900">Dashboard</a>
                    <a href="/search" class="text-sm font-medium text-gray-700 hover:text-gray-900">Search</a>
                    <a href="/interests" class="text-sm font-medium text-gray-700 hover:text-gray-900">Interests</a>
                    <a href="{{ route('profile.show') }}" class="text-sm font-medium text-gray-700 hover:text-gray-900">My Profile</a>
                </nav>

                <!-- Right side: Notification bell + Avatar dropdown -->
                <div class="flex items-center gap-4">
                    <!-- Notification bell -->
                    <button class="relative p-2 rounded-lg text-gray-500 hover:text-gray-700 hover:bg-gray-100">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                        {{-- Notification count badge placeholder --}}
                    </button>

                    <!-- Avatar dropdown -->
                    <div x-data="{ open: false }" class="relative">
                        <button x-on:click="open = !open" class="flex items-center gap-2 p-1.5 rounded-lg hover:bg-gray-100">
                            <div class="w-8 h-8 rounded-full bg-(--color-primary-light) flex items-center justify-center">
                                <svg class="w-5 h-5 text-(--color-primary)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            </div>
                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>

                        <div x-show="open" x-on:click.away="open = false" x-cloak class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-sm border border-gray-200 py-1 z-50">
                            <a href="/profile" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">My Profile</a>
                            <a href="/settings" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Settings</a>
                            <a href="/plans" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Plans</a>
                            <div class="border-t border-gray-100 my-1"></div>
                            <form method="POST" action="/logout">
                                @csrf
                                <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Logout</button>
                            </form>
                        </div>
                    </div>

                    <!-- Mobile hamburger -->
                    <button x-data x-on:click="$dispatch('toggle-mobile-menu')" class="md:hidden p-2 rounded-lg hover:bg-gray-100">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile menu -->
        <div x-data="{ open: false }" x-on:toggle-mobile-menu.window="open = !open" x-show="open" x-cloak class="md:hidden border-t border-gray-200 bg-white">
            <div class="px-4 py-3 space-y-2">
                <a href="/dashboard" class="block px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100">Dashboard</a>
                <a href="/search" class="block px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100">Search</a>
                <a href="/interests" class="block px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100">Interests</a>
                <a href="/profile" class="block px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100">My Profile</a>
                <a href="/settings" class="block px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100">Settings</a>
                <a href="/plans" class="block px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100">Plans</a>
                <div class="border-t border-gray-100 my-1"></div>
                <form method="POST" action="/logout">
                    @csrf
                    <button type="submit" class="block w-full text-left px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100">Logout</button>
                </form>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main>
        {{ $slot }}
    </main>

    <!-- Compact Footer -->
    <footer class="bg-gray-900 text-gray-400 mt-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                <p class="text-sm">&copy; {{ date('Y') }} {{ $siteName }}. All rights reserved.</p>
                <div class="flex items-center gap-4 text-sm">
                    <a href="/privacy-policy" class="hover:text-white transition-colors">Privacy</a>
                    <a href="/terms-of-service" class="hover:text-white transition-colors">Terms</a>
                    <a href="/help" class="hover:text-white transition-colors">Help</a>
                </div>
            </div>
        </div>
    </footer>

    @livewireScripts
</body>
</html>

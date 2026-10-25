{{-- title must be a prop: as a plain attribute it never reached <title>,
     so the login tab read "| Site name" --}}
@props(['maxWidth' => 'md', 'title' => null])
@php
    $theme = \App\Models\ThemeSetting::getTheme();
    $siteName = \App\Models\SiteSetting::getValue('site_name', 'Matrimony');
    $siteTagline = \App\Models\SiteSetting::getValue('tagline', 'Find Your Life Partner');
    $maxWidthClass = match($maxWidth) {
        'sm' => 'max-w-sm',
        'md' => 'max-w-md',
        'lg' => 'max-w-lg',
        'xl' => 'max-w-xl',
        '2xl' => 'max-w-2xl',
        '3xl' => 'max-w-3xl',
        default => 'max-w-md',
    };
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php
        $loginSeo = \App\Models\PageSeoSetting::getForPage('login');
        $loginTitle = $loginSeo?->meta_title ?: (($title ?? '') . ' | ' . $siteName);
    @endphp
    <title>{{ $loginTitle }}</title>
    @include('components.partials.seo-basic', ['seoPage' => 'login', 'seoTitle' => $loginTitle])

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
<body class="bg-[#FEFCFB] text-[#1C1917] font-sans antialiased min-h-screen flex flex-col">
    @include('components.partials.tracking-body')
    {{-- Header (Oct 2026): the login page gets a real header like the big portals: logo, Help, Register Free --}}
    <header class="bg-white border-b border-gray-200 shadow-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between h-16">
            <a href="/" class="inline-block">
                @if($theme->logo_url ?? false)
                    <img src="{{ $theme->logo_url }}" alt="{{ $siteName }}" style="height: {{ \App\Support\HeaderLogo::height() }}px; width: auto">
                @else
                    <span class="text-xl font-serif font-bold text-(--color-primary)">{{ $siteName }}</span>
                @endif
            </a>
            <div class="flex items-center gap-4">
                <a href="/faq" class="hidden sm:inline text-sm font-medium text-gray-700 hover:text-gray-900">Help</a>
                <span class="hidden sm:inline text-sm text-gray-500">New here?</span>
                <a href="/register" class="px-4 py-2 text-sm font-semibold text-white bg-(--color-primary) hover:bg-(--color-primary-hover) rounded-lg transition-colors">Register Free</a>
            </div>
        </div>
    </header>

    <!-- Centered Auth Content -->
    <div class="flex-1 flex flex-col items-center justify-center px-4 py-12">

        <!-- Auth Card -->
        <div class="w-full {{ $maxWidthClass }} bg-white rounded-lg border border-gray-200 shadow-xs p-6 sm:p-8">
            {{ $slot }}
        </div>

        <!-- Back to home -->
        <p class="mt-6 text-sm text-gray-500">
            <a href="/" class="text-(--color-primary) hover:underline">&larr; Back to Home</a>
        </p>
    </div>

    <footer class="bg-gray-900 text-gray-400 text-xs py-4 px-4 text-center" style="background-color: {{ \App\Support\SiteContact::footerColour() }};">
        &copy; {{ date('Y') }} {{ $siteName }}. All rights reserved. &middot;
        <a href="/privacy-policy" class="hover:text-white">Privacy</a> &middot;
        <a href="/terms-condition" class="hover:text-white">Terms</a> &middot;
        <a href="/faq" class="hover:text-white">Help</a>
    </footer>

    @livewireScripts
    <x-whatsapp-button />
</body>
</html>

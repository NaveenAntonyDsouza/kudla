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
<body class="bg-[#FEFCFB] text-[#1C1917] font-sans antialiased min-h-screen flex flex-col">
    <!-- Centered Auth Content -->
    <div class="flex-1 flex flex-col items-center justify-center px-4 py-12">
        <!-- Logo + Tagline -->
        <div class="text-center mb-8">
            <a href="/" class="inline-block">
                @if($theme->logo_url ?? false)
                    <img src="{{ $theme->logo_url }}" alt="{{ $siteName }}" class="h-12 mx-auto">
                @else
                    <h1 class="text-3xl font-serif font-bold text-(--color-primary)">{{ $siteName }}</h1>
                @endif
            </a>
            <p class="mt-2 text-sm text-gray-500">{{ $siteTagline }}</p>
        </div>

        <!-- Auth Card -->
        <div class="w-full max-w-md bg-white rounded-lg border border-gray-200 shadow-xs p-6 sm:p-8">
            {{ $slot }}
        </div>

        <!-- Back to home -->
        <p class="mt-6 text-sm text-gray-500">
            <a href="/" class="text-(--color-primary) hover:underline">&larr; Back to Home</a>
        </p>
    </div>

    @livewireScripts
</body>
</html>

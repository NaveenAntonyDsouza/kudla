@props(['title' => '', 'step' => 1, 'completionPct' => 0])
@php
    $theme = \App\Models\ThemeSetting::getTheme();
    $siteName = \App\Models\SiteSetting::getValue('site_name', 'Matrimony');
    $steps = [1 => 'Additional Info', 2 => 'More Details', 3 => 'Preferences', 4 => 'Lifestyle'];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} | {{ $siteName }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">

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

    {{-- Header --}}
    <header class="bg-white border-b border-gray-200 py-3 px-4 sm:px-6">
        <div class="max-w-6xl mx-auto flex items-center justify-between">
            <a href="/" class="inline-block">
                @if($theme->logo_url ?? false)
                    <img src="{{ $theme->logo_url }}" alt="{{ $siteName }}" class="h-10">
                @else
                    <h1 class="text-xl font-serif font-bold text-(--color-primary)">{{ $siteName }}</h1>
                @endif
            </a>
            <div class="text-sm text-gray-500">
                Need Assistance? <a href="tel:+911800123456" class="text-(--color-primary) hover:underline font-medium">Call Us</a>
            </div>
        </div>
    </header>

    {{-- Main Content --}}
    <main class="flex-1 py-8 px-4 sm:px-6">
        <div class="max-w-6xl mx-auto">
            <div class="flex flex-col lg:flex-row gap-8">

                {{-- Left Sidebar --}}
                <div class="lg:w-72 shrink-0">
                    <div class="sticky top-8">
                        {{-- Illustration --}}
                        <div class="hidden lg:block mb-6">
                            <div class="w-44 h-36 mx-auto bg-(--color-primary-light) rounded-2xl flex items-center justify-center">
                                <svg class="w-20 h-20 text-(--color-primary)/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                                </svg>
                            </div>
                        </div>

                        {{-- Profile Completion --}}
                        <div class="bg-white rounded-lg border border-gray-200 p-5 mb-4">
                            <div class="flex items-center justify-between mb-2">
                                <h3 class="text-sm font-semibold text-gray-900">Profile Completion</h3>
                                <span class="text-sm font-bold text-(--color-primary)">{{ $completionPct }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                <div class="h-2.5 rounded-full transition-all duration-500 {{ $completionPct >= 80 ? 'bg-green-500' : ($completionPct >= 50 ? 'bg-amber-500' : 'bg-red-500') }}"
                                    style="width: {{ $completionPct }}%"></div>
                            </div>
                            <p class="text-xs text-gray-500 mt-2">In order to complete your profile, please provide us with the required information.</p>
                        </div>

                        {{-- Benefits --}}
                        <div class="bg-white rounded-lg border border-gray-200 p-5">
                            <h3 class="text-base font-semibold text-gray-900 mb-1">The benefits of creating a profile on</h3>
                            <p class="text-sm font-medium text-(--color-primary) mb-4">{{ $siteName }}</p>
                            <ul class="space-y-2.5">
                                @foreach([
                                    'Widespread availability of profiles.',
                                    'Easy accessibility by app, site & branches.',
                                    'Saves time and money for finding matches.',
                                    'Profile listed according to your preference.',
                                    'You can enjoy high privacy and security.',
                                ] as $tip)
                                    <li class="flex items-start gap-2 text-sm text-gray-600">
                                        <svg class="w-4 h-4 text-(--color-primary) shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        {{ $tip }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>

                {{-- Right: Form Area --}}
                <div class="flex-1 min-w-0">
                    {{-- Step Progress --}}
                    <div class="flex items-center justify-center mb-8 px-2">
                        @foreach($steps as $num => $label)
                            <div class="flex items-center shrink-0">
                                <div class="flex flex-col items-center">
                                    <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-full flex items-center justify-center text-xs sm:text-sm font-semibold border-2 transition-colors
                                        {{ $num < $step ? 'bg-(--color-primary) border-(--color-primary) text-white' : '' }}
                                        {{ $num === $step ? 'bg-(--color-primary) border-(--color-primary) text-white ring-4 ring-(--color-primary)/20' : '' }}
                                        {{ $num > $step ? 'bg-white border-gray-300 text-gray-400' : '' }}">
                                        @if($num < $step)
                                            <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        @else
                                            {{ $num }}
                                        @endif
                                    </div>
                                    <span class="text-[10px] sm:text-xs mt-1 font-medium whitespace-nowrap {{ $num <= $step ? 'text-(--color-primary)' : 'text-gray-400' }}">{{ $label }}</span>
                                </div>
                                @if($num < count($steps))
                                    <div class="w-16 sm:w-24 h-0.5 mx-1 mt-[-18px] {{ $num < $step ? 'bg-(--color-primary)' : 'bg-gray-300' }}"></div>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    {{-- Form Card --}}
                    <div class="bg-white rounded-lg border border-gray-200 shadow-xs p-6 sm:p-8">
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>
    </main>

    {{-- Footer --}}
    <footer class="bg-gray-900 text-gray-400 text-xs py-4 px-4 text-center">
        <p>&copy; {{ date('Y') }} {{ $siteName }}. All Rights Reserved.</p>
    </footer>

    @livewireScripts
</body>
</html>

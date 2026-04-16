@props(['title' => '', 'step' => 1])
@php
    $theme = \App\Models\ThemeSetting::getTheme();
    $siteName = \App\Models\SiteSetting::getValue('site_name', 'Matrimony');
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
    @include('components.partials.tracking-head')
</head>
<body class="bg-[#FEFCFB] text-[#1C1917] font-sans antialiased min-h-screen flex flex-col">
    @include('components.partials.tracking-body')

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

                {{-- Left Sidebar: Tips --}}
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

                        {{-- Tips --}}
                        <div class="bg-white rounded-lg border border-gray-200 p-5">
                            <h3 class="text-base font-semibold text-gray-900 mb-1">Tips for writing an impressive</h3>
                            <p class="text-sm font-medium text-(--color-primary) mb-4">Matrimonial profile.</p>
                            <ul class="space-y-2.5">
                                <li class="flex items-start gap-2 text-sm text-gray-600">
                                    <svg class="w-4 h-4 text-(--color-primary) shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    To be honest with the Information Provided.
                                </li>
                                <li class="flex items-start gap-2 text-sm text-gray-600">
                                    <svg class="w-4 h-4 text-(--color-primary) shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Make your education and career stand out.
                                </li>
                                <li class="flex items-start gap-2 text-sm text-gray-600">
                                    <svg class="w-4 h-4 text-(--color-primary) shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Be specific about your partner preferences.
                                </li>
                                <li class="flex items-start gap-2 text-sm text-gray-600">
                                    <svg class="w-4 h-4 text-(--color-primary) shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Avoid spelling and grammar mistakes.
                                </li>
                                <li class="flex items-start gap-2 text-sm text-gray-600">
                                    <svg class="w-4 h-4 text-(--color-primary) shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Include sufficient information about you and your family.
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                {{-- Right: Form Area --}}
                <div class="flex-1 min-w-0">
                    {{-- Step Progress --}}
                    <x-registration-progress :current="$step" />

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

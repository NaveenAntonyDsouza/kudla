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
    <title>Registration Complete | {{ $siteName }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">

    <style>
        :root {
            --brand-primary: {{ $theme->primary_color ?? '#8B1D91' }};
            --brand-primary-hover: {{ $theme->primary_hover ?? '#6B1571' }};
            --brand-primary-light: {{ $theme->primary_light ?? '#F3E8F7' }};
            --brand-secondary: {{ $theme->secondary_color ?? '#00BCD4' }};
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

    {{-- Main --}}
    <main class="flex-1 flex items-center justify-center py-10 px-4 sm:px-6">
        <div class="max-w-4xl w-full flex flex-col lg:flex-row items-center gap-10 lg:gap-16">

            {{-- Left: Illustration --}}
            <div class="hidden lg:flex flex-col items-center lg:w-2/5">
                <div class="w-56 h-56 bg-(--color-primary-light) rounded-full flex items-center justify-center">
                    <svg class="w-28 h-28 text-(--color-primary)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.2" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>
                    </svg>
                </div>
            </div>

            {{-- Right: Congratulations --}}
            <div class="w-full lg:w-3/5 max-w-md text-center lg:text-left">
                {{-- Mobile illustration --}}
                <div class="lg:hidden mb-6 flex justify-center">
                    <div class="w-24 h-24 bg-(--color-primary-light) rounded-full flex items-center justify-center">
                        <svg class="w-12 h-12 text-(--color-primary)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>
                        </svg>
                    </div>
                </div>

                <h2 class="text-2xl sm:text-3xl font-serif font-bold text-(--color-primary) mb-3">Congratulations!</h2>

                <p class="text-gray-700 font-medium mb-1">
                    Dear {{ auth()->user()?->name ?? 'Member' }},
                </p>
                <p class="text-gray-700 font-medium mb-4">
                    ({{ $siteName }} ID : <span class="text-(--color-primary) font-bold">{{ $profile?->matri_id ?? 'N/A' }}</span>)
                </p>

                <p class="text-sm text-gray-500 mb-8 leading-relaxed">
                    You are now successfully registered with us. We will telephonically verify your submitted profile and activate it upon successful verification. You will receive an intimation mail from us on completion of activation.
                </p>

                <div class="flex flex-col sm:flex-row gap-3">
                    <a href="{{ route('onboarding.step1') }}"
                        class="inline-block w-full sm:w-auto bg-(--color-primary) text-white hover:bg-(--color-primary-hover) rounded-lg px-10 py-3.5 font-semibold text-sm uppercase tracking-wider transition-colors text-center">
                        Complete Your Profile
                    </a>
                    <a href="{{ route('dashboard') }}"
                        class="inline-block w-full sm:w-auto border border-gray-300 text-gray-600 hover:border-gray-400 hover:text-gray-800 rounded-lg px-10 py-3.5 font-semibold text-sm uppercase tracking-wider transition-colors text-center">
                        Go to Dashboard
                    </a>
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

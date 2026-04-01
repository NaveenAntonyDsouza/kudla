@php
    $theme = \App\Models\ThemeSetting::getTheme();
    $siteName = \App\Models\SiteSetting::getValue('site_name', 'Matrimony');
    $phone = auth()->user()->phone;
    $maskedPhone = substr($phone, 0, 2) . '******' . substr($phone, -2);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Verify Phone | {{ $siteName }}</title>

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
<body class="bg-gray-50 text-[#1C1917] font-sans antialiased min-h-screen flex flex-col">

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
        <div class="max-w-5xl w-full flex flex-col lg:flex-row items-center gap-8 lg:gap-0">

            {{-- Left: Illustration --}}
            <div class="hidden lg:flex flex-col items-center lg:w-5/12 pr-8">
                <div class="relative">
                    <div class="w-72 h-64 bg-(--color-primary-light)/50 rounded-[40%_60%_55%_45%/40%_45%_55%_60%] flex items-center justify-center">
                        <svg class="w-36 h-36 text-(--color-primary)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3"/>
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Right: Form Card --}}
            <div class="w-full lg:w-7/12">
                <div class="bg-white rounded-2xl shadow-lg p-8 sm:p-10">
                    <h2 class="text-xl sm:text-2xl font-serif font-bold text-(--color-primary) mb-6">Verify Your Mobile Number</h2>

                    @if (session('otp_sent') || $errors->has('otp'))
                        <p class="text-sm text-gray-600 mb-8 leading-relaxed">
                            Please enter the OTP which you have received on your mobile number
                            <strong class="text-gray-900">{{ $maskedPhone }}</strong>
                        </p>
                    @else
                        <p class="text-sm text-gray-600 mb-8 leading-relaxed">
                            We will send a 6-digit OTP to <strong class="text-gray-900">{{ $phone }}</strong> for verification.
                        </p>
                    @endif

                    @if ($errors->any())
                        <div class="mb-6 p-3 bg-red-50 border border-red-200 rounded-lg">
                            <p class="text-sm text-red-600 font-medium">{{ $errors->first() }}</p>
                        </div>
                    @endif

                    @if (!session('otp_sent') && !$errors->has('otp'))
                        <form method="POST" action="{{ route('register.sendotp') }}">
                            @csrf
                            <button type="submit"
                                class="w-full bg-(--color-primary) text-white hover:bg-(--color-primary-hover) rounded-lg px-6 py-3.5 font-semibold text-sm uppercase tracking-wider transition-colors">
                                Send OTP
                            </button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('register.verifyotp') }}" id="otpForm">
                            @csrf

                            <div class="flex justify-center gap-3 sm:gap-4 mb-8">
                                @for($i = 0; $i < 6; $i++)
                                    <input type="text" maxlength="1" inputmode="numeric" placeholder="*"
                                        class="otp-box w-12 h-14 sm:w-14 sm:h-16 border border-gray-300 rounded-xl text-center text-xl font-semibold outline-none transition-all placeholder:text-gray-300"
                                        {{ $i === 0 ? 'autofocus' : '' }}>
                                @endfor
                            </div>

                            <input type="hidden" name="otp" id="otpHidden">

                            <button type="submit"
                                class="w-full bg-(--color-primary) text-white hover:bg-(--color-primary-hover) rounded-lg px-6 py-3.5 font-semibold text-sm uppercase tracking-wider transition-colors">
                                Verify
                            </button>
                        </form>

                        <div class="mt-5 text-center">
                            <form method="POST" action="{{ route('register.sendotp') }}">
                                @csrf
                                <button type="submit" class="text-sm text-gray-600">
                                    Didn't receive OTP? <span class="text-(--color-primary) font-medium hover:underline">Resend OTP</span>
                                </button>
                            </form>
                        </div>
                    @endif

                    {{-- Skip & Back --}}
                    <div class="mt-8 pt-5 border-t border-gray-100 flex items-center justify-between">
                        <a href="{{ route('register.verifyemail') }}" class="text-sm text-gray-400 hover:text-gray-600">&larr; Back to Email Verify</a>
                        <a href="{{ route('register.complete') }}" class="text-sm text-(--color-primary) hover:underline font-medium">Skip for now &rarr;</a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    {{-- Footer --}}
    <footer class="bg-gray-900 text-gray-400 text-xs py-4 px-4 text-center">
        <p>&copy; {{ date('Y') }} {{ $siteName }}. All Rights Reserved.</p>
    </footer>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const boxes = document.querySelectorAll('.otp-box');
        const hidden = document.getElementById('otpHidden');
        const form = document.getElementById('otpForm');
        if (!boxes.length) return;

        function updateHidden() {
            hidden.value = Array.from(boxes).map(b => b.value).join('');
        }

        boxes.forEach((box, i) => {
            box.addEventListener('input', function() {
                this.value = this.value.replace(/\D/g, '').charAt(0) || '';
                updateHidden();
                if (this.value && i < 5) boxes[i + 1].focus();
            });
            box.addEventListener('keydown', function(e) {
                if (e.key === 'Backspace' && !this.value && i > 0) boxes[i - 1].focus();
            });
            box.addEventListener('paste', function(e) {
                e.preventDefault();
                const text = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
                text.split('').forEach((ch, j) => { if (boxes[j]) boxes[j].value = ch; });
                updateHidden();
                boxes[Math.min(text.length, 5)].focus();
            });
            box.addEventListener('focus', function() { this.style.borderColor = 'var(--brand-primary)'; });
            box.addEventListener('blur', function() { this.style.borderColor = '#d1d5db'; });
        });

        if (form) form.addEventListener('submit', function() { updateHidden(); });
    });
    </script>

    @livewireScripts
</body>
</html>

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
    <title>Register Free | {{ $siteName }}</title>

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
        /* Floating label CSS is in app.css */
    </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-[#FEFCFB] text-[#1C1917] font-sans antialiased min-h-screen flex flex-col">

    {{-- Header --}}
    <header class="bg-white border-b border-gray-200 py-3 px-4 sm:px-6">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            <a href="/" class="inline-block">
                @if($theme->logo_url ?? false)
                    <img src="{{ $theme->logo_url }}" alt="{{ $siteName }}" class="h-10">
                @else
                    <h1 class="text-xl font-serif font-bold text-(--color-primary)">{{ $siteName }}</h1>
                @endif
            </a>
            <div class="hidden sm:flex items-center gap-4 text-sm text-gray-500">
                <span>Need Assistance to Register?</span>
                <span class="text-(--color-primary) font-medium">Call Us</span>
            </div>
        </div>
    </header>

    {{-- Main: Two Panel Layout --}}
    <main class="flex-1">
        <div class="max-w-7xl mx-auto px-6 lg:px-8 py-10 lg:py-14 lg:flex">

            {{-- Left: Hero Image --}}
            <div class="hidden lg:block lg:w-2/5 pr-8">
                <div class="relative w-full h-full min-h-[500px] rounded-2xl overflow-hidden bg-gradient-to-br from-(--color-primary) via-(--color-primary)/80 to-(--color-secondary)">
                    <div class="absolute inset-0 bg-black/30"></div>
                    <div class="relative z-10 flex flex-col justify-end h-full p-8 text-white">
                        <h2 class="text-2xl lg:text-3xl font-normal leading-tight">Stop Waiting,</h2>
                        <h2 class="text-2xl lg:text-3xl font-bold leading-tight mb-3">Free Register Now</h2>
                        <p class="text-white/80 text-sm">Your future spouse is waiting;<br>take the first step by creating your free profile.</p>
                    </div>
                </div>
            </div>

            {{-- Right: Form --}}
            <div class="lg:w-3/5">
                @if ($errors->any())
                    <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                        <p class="text-sm text-red-600 font-medium">Please fix the errors below:</p>
                        <ul class="mt-1 text-xs text-red-500 list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('register.store1') }}" @submit="submitting = true" x-data="{
                    submitting: false,
                    dob: '{{ old('date_of_birth', $profile?->date_of_birth?->format('Y-m-d') ?? '') }}',
                    gender: '{{ old('gender', $profile->gender ?? '') }}',
                    get calculatedAge() {
                        if (!this.dob) return '';
                        const birth = new Date(this.dob);
                        const today = new Date();
                        let years = today.getFullYear() - birth.getFullYear();
                        let months = today.getMonth() - birth.getMonth();
                        if (months < 0 || (months === 0 && today.getDate() < birth.getDate())) {
                            years--;
                            months += 12;
                        }
                        if (today.getDate() < birth.getDate()) months--;
                        if (months < 0) months = 0;
                        return years >= 0 ? years + ' Yrs ' + months + ' Months' : '';
                    }
                }">
                    @csrf

                    <div class="space-y-5">
                        {{-- Full Name --}}
                        <div class="float-field">
                            <input type="text" name="full_name" id="full_name" value="{{ old('full_name', $user->name ?? '') }}" required placeholder=" "
                                class="border border-gray-300 rounded-lg w-full focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary)">
                            <label for="full_name">Full Name <span class="text-red-500">*</span></label>
                            @error('full_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        {{-- Gender (icon buttons) --}}
                        <div>
                            <input type="hidden" name="gender" :value="gender">
                            <div class="grid grid-cols-2 gap-3">
                                <button type="button" @click="gender = 'male'"
                                    :class="gender === 'male' ? 'border-(--color-primary) bg-(--color-primary)/5 text-(--color-primary)' : 'border-gray-300 text-gray-600 hover:border-gray-400'"
                                    class="flex items-center justify-center gap-2 border rounded-lg px-4 py-3 text-sm font-medium transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                                    </svg>
                                    Male
                                </button>
                                <button type="button" @click="gender = 'female'"
                                    :class="gender === 'female' ? 'border-(--color-primary) bg-(--color-primary)/5 text-(--color-primary)' : 'border-gray-300 text-gray-600 hover:border-gray-400'"
                                    class="flex items-center justify-center gap-2 border rounded-lg px-4 py-3 text-sm font-medium transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                                    </svg>
                                    Female
                                </button>
                            </div>
                            @error('gender') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        {{-- Date of Birth + Age --}}
                        <div class="grid grid-cols-1 sm:grid-cols-5 gap-3">
                            <div class="sm:col-span-3 float-field">
                                <input type="date" name="date_of_birth" id="date_of_birth" x-model="dob" required placeholder=" "
                                    max="{{ now()->subYears(18)->format('Y-m-d') }}"
                                    class="border border-gray-300 rounded-lg w-full focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary)">
                                <label for="date_of_birth">Date of Birth <span class="text-red-500">*</span></label>
                                @error('date_of_birth') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div class="sm:col-span-2">
                                <div class="border border-gray-200 rounded-lg px-3 py-2.5 bg-gray-50 h-full flex flex-col justify-center">
                                    <span class="text-[10px] text-gray-400 leading-tight">Age</span>
                                    <span x-text="calculatedAge || '0 Yrs 0 Months'" class="text-sm font-medium text-gray-700"></span>
                                </div>
                            </div>
                        </div>

                        {{-- Primary Mobile Number --}}
                        <x-phone-input name="phone" label="Primary Mobile Number" :value="$user->phone ?? ''" :required="true" maxlength="10" />
                        <p class="mt-1 flex items-center gap-1 text-xs text-gray-500">
                            <svg class="w-3.5 h-3.5 text-(--color-primary)" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd"/>
                            </svg>
                            We will send OTP to this mobile number for verification
                        </p>

                        {{-- Email ID --}}
                        <div class="float-field">
                            <input type="email" name="email" id="email" value="{{ old('email', $user->email ?? '') }}" required placeholder=" "
                                class="border border-gray-300 rounded-lg w-full focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary)">
                            <label for="email">Email ID <span class="text-red-500">*</span></label>
                            @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        {{-- Create Password --}}
                        <div class="float-field" x-data="{ show: false }">
                            <div class="relative">
                                <input :type="show ? 'text' : 'password'" name="password" id="password" {{ auth()->check() ? '' : 'required' }} minlength="6" maxlength="14" placeholder=" "
                                    class="border border-gray-300 rounded-lg w-full pr-10 focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary)">
                                <label for="password">{{ auth()->check() ? 'Change Password (optional)' : 'Create Password' }} <span class="text-red-500">{{ auth()->check() ? '' : '*' }}</span></label>
                                <button type="button" @click="show = !show" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                    <svg x-show="!show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/>
                                    </svg>
                                    <svg x-show="show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:none">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                </button>
                            </div>
                            <p class="mt-1 flex items-center gap-1 text-xs text-gray-500">
                                <svg class="w-3.5 h-3.5 text-(--color-primary)" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd"/>
                                </svg>
                                Use 6-14 characters.
                            </p>
                            @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    {{-- Continue Button --}}
                    <button type="submit" :disabled="submitting" :class="submitting && 'opacity-50 cursor-not-allowed'"
                        class="w-full mt-6 bg-(--color-primary) text-white hover:bg-(--color-primary-hover) rounded-lg px-6 py-3.5 font-semibold text-sm uppercase tracking-wider transition-colors">
                        <span x-show="!submitting">Continue</span>
                        <span x-show="submitting" x-cloak>Please wait...</span>
                    </button>

                    {{-- Privacy Policy --}}
                    <p class="mt-4 text-center text-xs text-gray-500">
                        By clicking on continue button, you are agree to our<br>
                        <a href="#" class="text-(--color-primary) hover:underline">Privacy Policy</a> &
                        <a href="#" class="text-(--color-primary) hover:underline">Terms of Use</a>
                    </p>

                    {{-- Login Link --}}
                    <p class="mt-4 text-center text-sm text-gray-600">
                        Already have an account? <a href="{{ route('login') }}" class="text-(--color-primary) font-semibold hover:underline">LOGIN</a>
                    </p>
                </form>
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

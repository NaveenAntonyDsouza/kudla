<x-layouts.app
    :title="$siteTitle"
    :metaDescription="$siteMetaDesc">
    @php
        $siteName = \App\Models\SiteSetting::getValue('site_name', 'Matrimony');
        $siteTagline = \App\Models\SiteSetting::getValue('tagline', 'Find Your Perfect Match');
        $heroHeading = \App\Models\SiteSetting::getValue('hero_heading', 'Find Your Perfect Match');
        $heroSubheading = \App\Models\SiteSetting::getValue('hero_subheading', '');
        $heroImageUrl = \App\Models\SiteSetting::getValue('hero_image_url', '');
    @endphp

    {{-- 1. Hero Banner with Registration Form --}}
    <section class="relative overflow-hidden py-10 sm:py-14 md:py-16" style="background: linear-gradient(135deg, var(--brand-primary) 0%, var(--brand-primary-hover) 50%, var(--brand-secondary) 100%);">
        @if($heroImageUrl)
            <div class="absolute inset-0">
                <img src="{{ $heroImageUrl }}" alt="" class="w-full h-full object-cover">
                <div class="absolute inset-0" style="background: linear-gradient(135deg, rgba(0,0,0,0.55) 0%, rgba(0,0,0,0.3) 50%, rgba(0,0,0,0.15) 100%);"></div>
            </div>
        @endif
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row gap-8 md:gap-12 items-center">
                {{-- Left: Heading + Tagline + Trust Signals --}}
                <div class="flex-1 text-center md:text-left text-white">
                    <h1 class="text-3xl sm:text-4xl lg:text-5xl font-serif font-bold leading-tight">
                        {{ $heroHeading }}
                    </h1>
                    @if($heroSubheading)
                        <p class="mt-4 text-lg sm:text-xl text-white/90 max-w-lg mx-auto md:mx-0">{{ $heroSubheading }}</p>
                    @elseif($siteTagline)
                        <p class="mt-4 text-lg sm:text-xl text-white/90 max-w-lg mx-auto md:mx-0">{{ $siteTagline }}</p>
                    @endif

                    {{-- Trust Signals --}}
                    <div class="mt-8 hidden md:block">
                        <div class="space-y-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-white">100% Verified Profiles</p>
                                    <p class="text-xs text-white/70">Every profile is manually reviewed</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-white">Safe & Secure</p>
                                    <p class="text-xs text-white/70">Your privacy is our priority</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-white">Smart Matchmaking</p>
                                    <p class="text-xs text-white/70">AI-powered compatibility scoring</p>
                                </div>
                            </div>
                        </div>

                        <p class="mt-8 text-white/60 text-sm">Already a member? <a href="{{ route('login') }}" class="text-white font-semibold hover:underline">Login</a></p>
                    </div>
                </div>

                {{-- Right: Registration Form --}}
                <div class="bg-white rounded-xl shadow-xl p-6 sm:p-8 w-full md:w-[420px] shrink-0">
                    <h2 class="text-xl font-bold text-gray-900 mb-1">Register Free</h2>
                    <p class="text-sm text-gray-500 mb-5">Create your profile in 2 minutes</p>

                    <form method="POST" action="{{ route('register.store1') }}" class="space-y-4" x-data="{
                        gender: '{{ old('gender', '') }}',
                        showPw: false,
                        submitting: false,
                        dob: '{{ old('date_of_birth', '') }}',
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
                    }" @submit="submitting = true">
                        @csrf

                        {{-- Full Name --}}
                        <div class="float-field">
                            <input type="text" name="full_name" id="hero_full_name" value="{{ old('full_name') }}" required placeholder=" "
                                class="w-full rounded-lg border border-gray-300 px-4 pt-5 pb-2 text-sm text-gray-900 focus:border-(--color-primary) focus:ring-1 focus:ring-(--color-primary) peer">
                            <label for="hero_full_name" class="absolute left-4 top-2 text-xs text-gray-500 peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-sm peer-focus:top-2 peer-focus:text-xs peer-focus:text-(--color-primary) transition-all pointer-events-none">Full Name *</label>
                        </div>

                        {{-- Gender --}}
                        <div>
                            <input type="hidden" name="gender" :value="gender">
                            <div class="grid grid-cols-2 gap-3">
                                <button type="button" @click="gender = 'male'"
                                    :class="gender === 'male' ? 'border-(--color-primary) bg-(--color-primary)/5 text-(--color-primary)' : 'border-gray-300 text-gray-600 hover:border-gray-400'"
                                    class="flex items-center justify-center gap-2 border rounded-lg px-4 py-2.5 text-sm font-medium transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                                    Male
                                </button>
                                <button type="button" @click="gender = 'female'"
                                    :class="gender === 'female' ? 'border-(--color-primary) bg-(--color-primary)/5 text-(--color-primary)' : 'border-gray-300 text-gray-600 hover:border-gray-400'"
                                    class="flex items-center justify-center gap-2 border rounded-lg px-4 py-2.5 text-sm font-medium transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                                    Female
                                </button>
                            </div>
                            @error('gender') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        {{-- Date of Birth + Age --}}
                        <div class="grid grid-cols-5 gap-2">
                            <div class="col-span-3 float-field">
                                <input type="date" name="date_of_birth" id="hero_dob" x-model="dob" required placeholder=" "
                                    max="{{ now()->subYears(18)->format('Y-m-d') }}"
                                    class="w-full rounded-lg border border-gray-300 px-4 pt-5 pb-2 text-sm text-gray-900 focus:border-(--color-primary) focus:ring-1 focus:ring-(--color-primary) peer">
                                <label for="hero_dob" class="absolute left-4 top-2 text-xs text-gray-500 peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-sm peer-focus:top-2 peer-focus:text-xs peer-focus:text-(--color-primary) transition-all pointer-events-none">Date of Birth *</label>
                            </div>
                            <div class="col-span-2">
                                <div class="border border-gray-200 rounded-lg px-3 py-2 bg-gray-50 h-full flex flex-col justify-center">
                                    <span class="text-[10px] text-gray-400 leading-tight">Age</span>
                                    <span x-text="calculatedAge || '0 Yrs 0 Months'" class="text-xs font-medium text-gray-700"></span>
                                </div>
                            </div>
                        </div>
                        @error('date_of_birth') <p class="-mt-2 text-xs text-red-500">{{ $message }}</p> @enderror

                        {{-- Primary Mobile Number (with country code) --}}
                        <div>
                            <x-phone-input name="phone" label="Primary Mobile Number" :value="old('phone', '')" :required="true" maxlength="10" />
                            <p class="mt-1 flex items-center gap-1 text-xs text-gray-500">
                                <svg class="w-3.5 h-3.5 text-(--color-primary) shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd"/>
                                </svg>
                                We will send OTP to this mobile number for verification
                            </p>
                        </div>

                        {{-- Email --}}
                        <div class="float-field">
                            <input type="email" name="email" id="hero_email" value="{{ old('email') }}" required placeholder=" "
                                class="w-full rounded-lg border border-gray-300 px-4 pt-5 pb-2 text-sm text-gray-900 focus:border-(--color-primary) focus:ring-1 focus:ring-(--color-primary) peer">
                            <label for="hero_email" class="absolute left-4 top-2 text-xs text-gray-500 peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-sm peer-focus:top-2 peer-focus:text-xs peer-focus:text-(--color-primary) transition-all pointer-events-none">Email ID *</label>
                        </div>

                        {{-- Password --}}
                        <div class="float-field relative">
                            <input :type="showPw ? 'text' : 'password'" name="password" id="hero_password" required minlength="6" maxlength="14" placeholder=" "
                                class="w-full rounded-lg border border-gray-300 px-4 pt-5 pb-2 pr-10 text-sm text-gray-900 focus:border-(--color-primary) focus:ring-1 focus:ring-(--color-primary) peer">
                            <label for="hero_password" class="absolute left-4 top-2 text-xs text-gray-500 peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-sm peer-focus:top-2 peer-focus:text-xs peer-focus:text-(--color-primary) transition-all pointer-events-none">Create Password *</label>
                            <button type="button" @click="showPw = !showPw" class="absolute right-3 top-3.5 text-gray-400 hover:text-gray-600" tabindex="-1">
                                <svg x-show="!showPw" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                <svg x-show="showPw" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                            </button>
                            <p class="mt-1 flex items-center gap-1 text-[10px] text-gray-400">
                                <svg class="w-3 h-3 text-(--color-primary) shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd"/>
                                </svg>
                                Use 6-14 characters
                            </p>
                            @error('password') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        @if($errors->any())
                            <div class="rounded-lg bg-red-50 border border-red-200 p-3">
                                @foreach($errors->all() as $error)
                                    <p class="text-xs text-red-600">{{ $error }}</p>
                                @endforeach
                            </div>
                        @endif

                        <button type="submit" :disabled="submitting" :class="submitting && 'opacity-50 cursor-not-allowed'"
                            class="w-full py-3 text-sm font-bold text-white rounded-lg transition-colors" style="background: var(--brand-primary);">
                            <span x-show="!submitting">REGISTER FREE</span>
                            <span x-show="submitting" x-cloak>Please wait...</span>
                        </button>

                        <p class="text-xs text-gray-400 text-center">By registering, you agree to our <a href="/terms-condition" class="text-(--color-primary) hover:underline">Terms</a> & <a href="/privacy-policy" class="text-(--color-primary) hover:underline">Privacy Policy</a></p>
                    </form>

                    <p class="mt-4 text-center text-sm text-gray-600">Already have an account? <a href="{{ route('login') }}" class="text-(--color-primary) font-semibold hover:underline">LOGIN</a></p>
                </div>
            </div>
        </div>
    </section>

    {{-- 1b. Search Widget (moved below hero) --}}
    <section class="py-6" style="background: linear-gradient(135deg, var(--brand-primary-hover) 0%, var(--brand-secondary) 100%);">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-white font-semibold text-center mb-4">{{ \App\Models\SiteSetting::getValue('search_heading', 'Search for Your Perfect Partner') }}</h2>
            <form action="/search" method="GET" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 items-end">
                <div class="text-left">
                    <label class="block text-xs font-medium text-white/80 mb-1">I'm looking for</label>
                    <select name="gender" class="w-full rounded-lg border-0 bg-white text-gray-900 text-sm px-3 py-2 focus:ring-2 focus:ring-white">
                        <option value="female">Bride</option>
                        <option value="male">Groom</option>
                    </select>
                </div>
                <div class="text-left">
                    <label class="block text-xs font-medium text-white/80 mb-1">Age from</label>
                    <select name="age_from" class="w-full rounded-lg border-0 bg-white text-gray-900 text-sm px-3 py-2 focus:ring-2 focus:ring-white">
                        @for($i = 18; $i <= 60; $i++)
                            <option value="{{ $i }}" {{ $i === 21 ? 'selected' : '' }}>{{ $i }} yrs</option>
                        @endfor
                    </select>
                </div>
                <div class="text-left">
                    <label class="block text-xs font-medium text-white/80 mb-1">Age to</label>
                    <select name="age_to" class="w-full rounded-lg border-0 bg-white text-gray-900 text-sm px-3 py-2 focus:ring-2 focus:ring-white">
                        @for($i = 18; $i <= 60; $i++)
                            <option value="{{ $i }}" {{ $i === 30 ? 'selected' : '' }}>{{ $i }} yrs</option>
                        @endfor
                    </select>
                </div>
                <div class="text-left">
                    <label class="block text-xs font-medium text-white/80 mb-1">Community</label>
                    <select name="community" class="w-full rounded-lg border-0 bg-white text-gray-900 text-sm px-3 py-2 focus:ring-2 focus:ring-white">
                        <option value="">All Communities</option>
                        @foreach($communities as $religion => $group)
                            <optgroup label="{{ $religion }}">
                                @foreach($group as $community)
                                    <option value="{{ $community->id }}">{{ $community->community_name }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
                <div>
                    <button type="submit" class="w-full bg-white text-gray-900 hover:bg-gray-100 rounded-lg px-4 py-2 font-semibold text-sm transition-colors">
                        Search
                    </button>
                </div>
            </form>
        </div>
    </section>

    {{-- 2. Stats Row (animated counters) --}}
    <section class="py-12 bg-white" x-data="{
        started: false,
        members: 0,
        marriages: 0,
        years: 0,
        targetMembers: {{ (int) $stats['members'] }},
        targetMarriages: {{ (int) $stats['marriages'] }},
        targetYears: {{ (int) $stats['years'] }},
        animateCount(prop, target, duration = 2000) {
            const steps = 60;
            const increment = target / steps;
            let current = 0;
            const interval = duration / steps;
            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    this[prop] = target;
                    clearInterval(timer);
                } else {
                    this[prop] = Math.floor(current);
                }
            }, interval);
        },
        startAnimation() {
            if (this.started) return;
            this.started = true;
            this.animateCount('members', this.targetMembers);
            this.animateCount('marriages', this.targetMarriages);
            this.animateCount('years', this.targetYears, 1500);
        }
    }" x-init="
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => { if (entry.isIntersecting) { startAnimation(); observer.disconnect(); } });
        }, { threshold: 0.3 });
        observer.observe($el);
    ">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                <div class="text-center p-6 bg-white rounded-lg border border-gray-200 shadow-xs">
                    <div class="text-3xl font-serif font-bold text-(--color-primary)"><span x-text="members.toLocaleString()">0</span>+</div>
                    <p class="mt-1 text-sm text-gray-600">Members</p>
                </div>
                <div class="text-center p-6 bg-white rounded-lg border border-gray-200 shadow-xs">
                    <div class="text-3xl font-serif font-bold text-(--color-primary)"><span x-text="marriages.toLocaleString()">0</span>+</div>
                    <p class="mt-1 text-sm text-gray-600">Successful Marriages</p>
                </div>
                <div class="text-center p-6 bg-white rounded-lg border border-gray-200 shadow-xs">
                    <div class="text-3xl font-serif font-bold text-(--color-primary)"><span x-text="years">0</span>+</div>
                    <p class="mt-1 text-sm text-gray-600">Years of Service</p>
                </div>
            </div>
        </div>
    </section>

    {{-- 3. How It Works --}}
    <section class="py-16 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-2xl sm:text-3xl font-serif font-bold text-gray-900 text-center mb-12">How It Works</h2>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-8">
                {{-- Step 1: Register --}}
                <div class="text-center">
                    <div class="w-16 h-16 mx-auto mb-4 bg-(--color-primary-light) rounded-full flex items-center justify-center">
                        <svg class="w-8 h-8 text-(--color-primary)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Register</h3>
                    <p class="text-sm text-gray-600">Create your profile for free with basic details, photos, and preferences.</p>
                </div>

                {{-- Step 2: Search --}}
                <div class="text-center">
                    <div class="w-16 h-16 mx-auto mb-4 bg-(--color-primary-light) rounded-full flex items-center justify-center">
                        <svg class="w-8 h-8 text-(--color-primary)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Search</h3>
                    <p class="text-sm text-gray-600">Browse profiles by community, education, location, and more to find the right match.</p>
                </div>

                {{-- Step 3: Connect --}}
                <div class="text-center">
                    <div class="w-16 h-16 mx-auto mb-4 bg-(--color-primary-light) rounded-full flex items-center justify-center">
                        <svg class="w-8 h-8 text-(--color-primary)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Connect</h3>
                    <p class="text-sm text-gray-600">Send interests, communicate, and find your life partner with confidence.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- 4. Why Choose Us (dynamic from admin settings) --}}
    @php
        $whyChooseUsJson = \App\Models\SiteSetting::getValue('why_choose_us', '');
        $whyChooseUs = $whyChooseUsJson ? json_decode($whyChooseUsJson, true) : [];
        if (empty($whyChooseUs)) {
            $whyChooseUs = [
                ['title' => 'Verified Profiles', 'description' => 'Every profile is verified to ensure authenticity and safety for our members.', 'icon' => 'check'],
                ['title' => '100% Privacy', 'description' => 'Your personal information is protected. You control who sees your contact details.', 'icon' => 'lock'],
                ['title' => 'Community Focused', 'description' => 'Designed for families seeking meaningful connections within their community and values.', 'icon' => 'heart'],
                ['title' => 'Easy to Use', 'description' => 'Simple registration, powerful search, and instant messaging from your phone or computer.', 'icon' => 'star'],
            ];
        }
        $iconMap = [
            'check' => '&#10003;',
            'lock' => '&#128274;',
            'heart' => '&#9829;',
            'star' => '&#9733;',
            'shield' => '&#128737;',
            'users' => '&#128101;',
            'phone' => '&#128222;',
            'globe' => '&#127760;',
        ];
        $colClass = count($whyChooseUs) <= 3 ? 'lg:grid-cols-3' : 'lg:grid-cols-4';
    @endphp
    <section class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-2xl sm:text-3xl font-serif font-bold text-gray-900 text-center mb-4">Why Choose {{ $siteName }}?</h2>
            <p class="text-center text-gray-500 mb-12 max-w-2xl mx-auto">We understand the importance of finding the right life partner. Here's what makes us different.</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 {{ $colClass }} gap-6">
                @foreach($whyChooseUs as $item)
                <div class="text-center p-6 rounded-xl border border-gray-100 hover:border-(--color-primary)/30 hover:shadow-sm transition-all">
                    <div class="w-12 h-12 mx-auto mb-4 bg-(--color-primary-light) rounded-full flex items-center justify-center text-(--color-primary) text-xl font-bold">{!! $iconMap[$item['icon'] ?? 'check'] ?? '&#10003;' !!}</div>
                    <h3 class="text-base font-semibold text-gray-900 mb-2">{{ $item['title'] }}</h3>
                    <p class="text-sm text-gray-500">{{ $item['description'] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- 5. Success Stories (Carousel) --}}
    @php
        $successStories = \App\Models\Testimonial::where('is_visible', true)->orderBy('display_order')->limit(8)->get();
    @endphp
    @if($successStories->count() > 0)
    <section class="py-16 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-2xl sm:text-3xl font-serif font-bold text-gray-900 text-center mb-4">Success Stories</h2>
            <p class="text-center text-gray-500 mb-12 max-w-2xl mx-auto">Real couples who found their perfect match through {{ $siteName }}.</p>

            <div x-data="{
                current: 0,
                total: {{ $successStories->count() }},
                autoplay: null,
                get maxIndex() {
                    const perView = window.innerWidth >= 768 ? 3 : 1;
                    return Math.max(0, this.total - perView);
                },
                next() { this.current = this.current >= this.maxIndex ? 0 : this.current + 1; },
                prev() { this.current = this.current <= 0 ? this.maxIndex : this.current - 1; },
                startAutoplay() { this.autoplay = setInterval(() => this.next(), 4000); },
                stopAutoplay() { clearInterval(this.autoplay); }
            }" x-init="startAutoplay()" @mouseenter="stopAutoplay()" @mouseleave="startAutoplay()" class="relative">

                {{-- Carousel Track --}}
                <div class="overflow-hidden">
                    <div class="flex transition-transform duration-500 ease-in-out" :style="'transform: translateX(-' + (current * (100 / (window.innerWidth >= 768 ? 3 : 1))) + '%)'">
                        @foreach($successStories as $story)
                            <div class="w-full md:w-1/3 flex-shrink-0 px-3">
                                <div class="bg-white rounded-xl border border-gray-200 shadow-xs overflow-hidden h-full">
                                    @if($story->photo_url)
                                        <div class="aspect-[4/3] bg-gray-100 overflow-hidden">
                                            <img src="{{ Storage::disk('public')->url($story->photo_url) }}" alt="{{ $story->couple_names }}" class="w-full h-full object-cover">
                                        </div>
                                    @else
                                        <div class="aspect-[4/3] bg-gradient-to-br from-(--color-primary-light) to-(--color-secondary-light) flex items-center justify-center">
                                            <svg class="w-16 h-16 text-(--color-primary)/30" fill="currentColor" viewBox="0 0 24 24"><path d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                                        </div>
                                    @endif
                                    <div class="p-5">
                                        <h3 class="text-base font-semibold text-gray-900">{{ $story->couple_names }}</h3>
                                        @if($story->location)
                                            <p class="text-xs text-gray-500 mt-0.5">{{ $story->location }}</p>
                                        @endif
                                        <p class="mt-2 text-sm text-gray-600 line-clamp-3">{{ $story->story }}</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Prev/Next Buttons --}}
                @if($successStories->count() > 3)
                <button @click="prev()" class="absolute left-0 top-1/2 -translate-y-1/2 -translate-x-2 w-10 h-10 bg-white rounded-full shadow-md border border-gray-200 flex items-center justify-center hover:bg-gray-50 transition-colors z-10">
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </button>
                <button @click="next()" class="absolute right-0 top-1/2 -translate-y-1/2 translate-x-2 w-10 h-10 bg-white rounded-full shadow-md border border-gray-200 flex items-center justify-center hover:bg-gray-50 transition-colors z-10">
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
                @endif

                {{-- Dot Indicators --}}
                <div class="flex justify-center gap-2 mt-6">
                    @foreach($successStories as $index => $story)
                        @if($index <= $successStories->count() - (3))
                        <button @click="current = {{ $index }}" class="w-2 h-2 rounded-full transition-all duration-300"
                            :class="current === {{ $index }} ? 'bg-(--color-primary) w-6' : 'bg-gray-300 hover:bg-gray-400'"></button>
                        @endif
                    @endforeach
                </div>
            </div>

            <div class="text-center mt-8">
                <a href="{{ route('success-stories.index') }}" class="text-sm font-medium text-(--color-primary) hover:underline">View All Success Stories &rarr;</a>
            </div>
        </div>
    </section>
    @endif

    {{-- 6. Community Browse --}}
    @if($communities->count() > 0)
    <section class="py-16 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-2xl sm:text-3xl font-serif font-bold text-gray-900 text-center mb-12">Browse by Community</h2>

            @foreach($communities as $religion => $group)
                @php
                    $filterField = in_array($religion, ['Christian']) ? 'denomination' : 'caste';
                    $primaryCategory = match($religion) {
                        'Christian' => 'catholic-matrimony',
                        'Hindu' => 'hindu-matrimony',
                        'Muslim' => 'muslim-matrimony',
                        'Jain' => 'jain-matrimony',
                        default => 'community-matrimony',
                    };
                    // For Christians, check both catholic and christian categories
                    $discoverLookup = [];
                    $categoriesToCheck = $religion === 'Christian'
                        ? ['catholic-matrimony', 'christian-matrimony']
                        : [$primaryCategory];
                    foreach ($categoriesToCheck as $catKey) {
                        $catConfig = config("discover.{$catKey}");
                        if ($catConfig) {
                            $subs = is_callable($catConfig['subcategories']) ? ($catConfig['subcategories'])() : $catConfig['subcategories'];
                            foreach ($subs as $sub) {
                                $discoverLookup[$sub['slug']] = $catKey;
                            }
                        }
                    }
                @endphp
                <div class="mb-8">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        <a href="{{ route('discover.category', $primaryCategory) }}" class="hover:text-(--color-primary)">{{ $religion }}</a>
                    </h3>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
                        @foreach($group as $community)
                            @php
                                $slug = Str::slug($community->community_name);
                                $matchedCategory = $discoverLookup[$slug] ?? null;
                                $href = $matchedCategory
                                    ? route('discover.results', [$matchedCategory, $slug])
                                    : url('/search/quick-search?' . $filterField . '=' . urlencode($community->community_name));
                            @endphp
                            <a href="{{ $href }}" class="bg-white rounded-lg border border-gray-200 shadow-xs p-4 text-center hover:border-(--color-primary) hover:shadow-sm transition-all group">
                                <p class="text-sm font-medium text-gray-900 group-hover:text-(--color-primary) transition-colors">{{ $community->community_name }}</p>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </section>
    @endif

    {{-- 7. FAQ Preview --}}
    @if($faqs->count() > 0)
    <section class="py-16 bg-white">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-2xl sm:text-3xl font-serif font-bold text-gray-900 text-center mb-4">Frequently Asked Questions</h2>
            <p class="text-center text-gray-500 mb-10 max-w-2xl mx-auto">Have questions? We've got answers.</p>

            <div class="space-y-3">
                @foreach($faqs as $index => $faq)
                    <div x-data="{ open: {{ $index === 0 ? 'true' : 'false' }} }" class="border border-gray-200 rounded-lg overflow-hidden">
                        <button @click="open = !open" class="w-full flex items-center justify-between px-5 py-4 text-left hover:bg-gray-50 transition-colors">
                            <span class="text-sm font-medium text-gray-900 pr-4">{{ $faq->question }}</span>
                            <svg class="w-5 h-5 text-gray-400 shrink-0 transition-transform duration-200" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div x-show="open" x-collapse>
                            <div class="px-5 pb-4 text-sm text-gray-600 leading-relaxed">{{ $faq->answer }}</div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="text-center mt-8">
                <a href="/faq" class="text-sm font-medium text-(--color-primary) hover:underline">View All FAQs &rarr;</a>
            </div>
        </div>
    </section>
    @endif

    {{-- 8. Download App CTA --}}
    @php
        $playStoreUrl = \App\Models\SiteSetting::getValue('app_play_store_url', '');
        $appStoreUrl = \App\Models\SiteSetting::getValue('app_apple_store_url', '');
    @endphp
    @if($playStoreUrl || $appStoreUrl)
    <section class="py-16 bg-white overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row items-center gap-10 md:gap-16">
                {{-- Left: Phone Mockup --}}
                <div class="flex-shrink-0 relative">
                    <div class="w-56 h-96 rounded-[2.5rem] border-[6px] border-gray-800 bg-gradient-to-br from-(--color-primary-light) to-white shadow-2xl relative overflow-hidden">
                        {{-- Notch --}}
                        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-24 h-6 bg-gray-800 rounded-b-2xl"></div>
                        {{-- Screen Content --}}
                        <div class="absolute inset-4 top-10 flex flex-col items-center justify-center text-center">
                            <svg class="w-14 h-14 text-(--color-primary) mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                            </svg>
                            <p class="text-sm font-bold text-(--color-primary)">{{ $siteName }}</p>
                            <p class="text-[10px] text-gray-500 mt-1">Find Your Match</p>
                            <div class="mt-4 space-y-2 w-full">
                                <div class="h-2 bg-(--color-primary)/20 rounded-full w-full"></div>
                                <div class="h-2 bg-(--color-primary)/10 rounded-full w-3/4"></div>
                                <div class="h-2 bg-(--color-primary)/15 rounded-full w-5/6"></div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Right: Content --}}
                <div class="text-center md:text-left flex-1">
                    <h2 class="text-2xl sm:text-3xl font-serif font-bold text-gray-900 mb-4">Get the {{ $siteName }} App</h2>
                    <p class="text-gray-500 mb-8 max-w-md">Find your perfect match on the go. Browse profiles, send interests, and chat — all from your mobile phone.</p>

                    <div class="flex flex-col sm:flex-row gap-4 justify-center md:justify-start">
                        @if($playStoreUrl)
                        <a href="{{ $playStoreUrl }}" target="_blank" rel="noopener" class="inline-flex items-center gap-3 bg-gray-900 text-white rounded-xl px-6 py-3 hover:bg-gray-800 transition-colors">
                            <svg class="w-8 h-8" viewBox="0 0 24 24" fill="currentColor"><path d="M3.609 1.814L13.792 12 3.61 22.186a.996.996 0 01-.61-.92V2.734a1 1 0 01.609-.92zm10.89 10.893l2.302 2.302-10.937 6.333 8.635-8.635zm3.199-3.199l2.302 2.302a1 1 0 010 1.38l-2.302 2.302L15.396 13l2.302-2.492zM5.864 2.658L16.8 9.39l-2.302 2.302L5.864 2.658z"/></svg>
                            <div class="text-left">
                                <div class="text-[10px] uppercase tracking-wider text-gray-400">Get it on</div>
                                <div class="text-base font-semibold -mt-0.5">Google Play</div>
                            </div>
                        </a>
                        @endif
                        @if($appStoreUrl)
                        <a href="{{ $appStoreUrl }}" target="_blank" rel="noopener" class="inline-flex items-center gap-3 bg-gray-900 text-white rounded-xl px-6 py-3 hover:bg-gray-800 transition-colors">
                            <svg class="w-8 h-8" viewBox="0 0 24 24" fill="currentColor"><path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.8-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/></svg>
                            <div class="text-left">
                                <div class="text-[10px] uppercase tracking-wider text-gray-400">Download on the</div>
                                <div class="text-base font-semibold -mt-0.5">App Store</div>
                            </div>
                        </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
    @endif

    {{-- 9. CTA Banner (dynamic from admin settings) --}}
    @php
        $ctaTitle = \App\Models\SiteSetting::getValue('cta_title', 'Register Free Today');
        $ctaDesc = \App\Models\SiteSetting::getValue('cta_description', 'Join thousands of members who have found their perfect match. Registration is free and takes just a few minutes.');
        $ctaButton = \App\Models\SiteSetting::getValue('cta_button_text', 'Create Your Profile');
    @endphp
    <section class="py-16" style="background: linear-gradient(135deg, var(--brand-primary) 0%, var(--brand-secondary) 100%);">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-2xl sm:text-3xl font-serif font-bold text-white mb-4">{{ $ctaTitle }}</h2>
            <p class="text-white/80 text-lg mb-8 max-w-xl mx-auto">{{ $ctaDesc }}</p>
            <a href="/register" class="inline-block bg-white text-gray-900 hover:bg-gray-100 rounded-lg px-8 py-3 font-semibold text-sm transition-colors">
                {{ $ctaButton }}
            </a>
        </div>
    </section>
</x-layouts.app>

<x-layouts.app title="Home">
    @php
        $siteName = \App\Models\SiteSetting::getValue('site_name', 'Matrimony');
        $siteTagline = \App\Models\SiteSetting::getValue('tagline', 'Find Your Perfect Match');
        $siteArea = \App\Models\SiteSetting::getValue('site_area', 'Your Community');
    @endphp

    {{-- 1. Hero Banner with Registration Form --}}
    <section class="relative overflow-hidden py-12 sm:py-16 lg:py-20" style="background: linear-gradient(135deg, var(--brand-primary) 0%, var(--brand-primary-hover) 50%, var(--brand-secondary) 100%);">
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 items-center">
                {{-- Left: Heading + Tagline --}}
                <div class="text-center lg:text-left text-white">
                    <h1 class="text-3xl sm:text-4xl lg:text-5xl font-serif font-bold leading-tight">
                        Find Your Perfect Match in {{ $siteArea }}
                    </h1>
                    <p class="mt-4 text-lg sm:text-xl text-white/90 max-w-lg">{{ $siteTagline }}</p>
                    <div class="mt-6 hidden lg:block">
                        <p class="text-white/70 text-sm">Already a member? <a href="/login" class="text-white font-semibold hover:underline">Login</a></p>
                    </div>
                </div>

                {{-- Right: Registration Form --}}
                <div class="bg-white rounded-xl shadow-xl p-6 sm:p-8 max-w-md mx-auto lg:mx-0 lg:ml-auto w-full">
                    <h2 class="text-xl font-bold text-gray-900 mb-1">Register Free</h2>
                    <p class="text-sm text-gray-500 mb-5">Create your profile in 2 minutes</p>

                    <form method="POST" action="{{ route('register.store1') }}" class="space-y-4" x-data="{ gender: '', showPw: false }">
                        @csrf

                        {{-- Full Name --}}
                        <div class="float-field">
                            <input type="text" name="full_name" id="hero_full_name" value="{{ old('full_name') }}" required placeholder=" "
                                class="w-full rounded-lg border border-gray-300 px-4 pt-5 pb-2 text-sm text-gray-900 focus:border-(--color-primary) focus:ring-1 focus:ring-(--color-primary) peer">
                            <label for="hero_full_name" class="absolute left-4 top-2 text-xs text-gray-500 peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-sm peer-focus:top-2 peer-focus:text-xs peer-focus:text-(--color-primary) transition-all pointer-events-none">Full Name *</label>
                        </div>

                        {{-- Gender --}}
                        <div>
                            <p class="text-xs text-gray-500 mb-2">Gender *</p>
                            <input type="hidden" name="gender" :value="gender">
                            <div class="grid grid-cols-2 gap-3">
                                <button type="button" @click="gender = 'male'"
                                    :class="gender === 'male' ? 'bg-(--color-primary) text-white border-(--color-primary)' : 'bg-white text-gray-700 border-gray-300 hover:border-gray-400'"
                                    class="py-2.5 rounded-lg border text-sm font-medium transition-colors">Male</button>
                                <button type="button" @click="gender = 'female'"
                                    :class="gender === 'female' ? 'bg-(--color-primary) text-white border-(--color-primary)' : 'bg-white text-gray-700 border-gray-300 hover:border-gray-400'"
                                    class="py-2.5 rounded-lg border text-sm font-medium transition-colors">Female</button>
                            </div>
                            @error('gender') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        {{-- Date of Birth --}}
                        <div class="float-field">
                            <input type="date" name="date_of_birth" id="hero_dob" value="{{ old('date_of_birth') }}" required placeholder=" "
                                max="{{ now()->subYears(18)->format('Y-m-d') }}"
                                class="w-full rounded-lg border border-gray-300 px-4 pt-5 pb-2 text-sm text-gray-900 focus:border-(--color-primary) focus:ring-1 focus:ring-(--color-primary) peer">
                            <label for="hero_dob" class="absolute left-4 top-2 text-xs text-gray-500 peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-sm peer-focus:top-2 peer-focus:text-xs peer-focus:text-(--color-primary) transition-all pointer-events-none">Date of Birth *</label>
                        </div>

                        {{-- Phone --}}
                        <div class="float-field">
                            <input type="tel" name="phone" id="hero_phone" value="{{ old('phone') }}" required maxlength="10" placeholder=" "
                                class="w-full rounded-lg border border-gray-300 px-4 pt-5 pb-2 text-sm text-gray-900 focus:border-(--color-primary) focus:ring-1 focus:ring-(--color-primary) peer">
                            <label for="hero_phone" class="absolute left-4 top-2 text-xs text-gray-500 peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-sm peer-focus:top-2 peer-focus:text-xs peer-focus:text-(--color-primary) transition-all pointer-events-none">Mobile Number *</label>
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
                            <p class="mt-1 text-[10px] text-gray-400">6-14 characters</p>
                        </div>

                        @if($errors->any())
                            <div class="rounded-lg bg-red-50 border border-red-200 p-3">
                                @foreach($errors->all() as $error)
                                    <p class="text-xs text-red-600">{{ $error }}</p>
                                @endforeach
                            </div>
                        @endif

                        <button type="submit" class="w-full py-3 text-sm font-bold text-white rounded-lg transition-colors" style="background: var(--brand-primary);">
                            REGISTER FREE
                        </button>

                        <p class="text-xs text-gray-400 text-center">By registering, you agree to our <a href="/terms-condition" class="text-(--color-primary) hover:underline">Terms</a> & <a href="/privacy-policy" class="text-(--color-primary) hover:underline">Privacy Policy</a></p>
                    </form>

                    <p class="mt-4 text-center text-sm text-gray-500 lg:hidden">Already a member? <a href="/login" class="font-semibold text-(--color-primary) hover:underline">Login</a></p>
                </div>
            </div>
        </div>
    </section>

    {{-- 1b. Search Widget (moved below hero) --}}
    <section class="py-6" style="background: linear-gradient(135deg, var(--brand-primary-hover) 0%, var(--brand-secondary) 100%);">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
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

    {{-- 2. Stats Row --}}
    <section class="py-12 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                <div class="text-center p-6 bg-white rounded-lg border border-gray-200 shadow-xs">
                    <div class="text-3xl font-serif font-bold text-(--color-primary)">{{ number_format((int) $stats['members']) }}+</div>
                    <p class="mt-1 text-sm text-gray-600">Members</p>
                </div>
                <div class="text-center p-6 bg-white rounded-lg border border-gray-200 shadow-xs">
                    <div class="text-3xl font-serif font-bold text-(--color-primary)">{{ number_format((int) $stats['marriages']) }}+</div>
                    <p class="mt-1 text-sm text-gray-600">Successful Marriages</p>
                </div>
                <div class="text-center p-6 bg-white rounded-lg border border-gray-200 shadow-xs">
                    <div class="text-3xl font-serif font-bold text-(--color-primary)">{{ $stats['years'] }}+</div>
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

    {{-- 4. Why Choose Us --}}
    <section class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-2xl sm:text-3xl font-serif font-bold text-gray-900 text-center mb-4">Why Choose {{ $siteName }}?</h2>
            <p class="text-center text-gray-500 mb-12 max-w-2xl mx-auto">We understand the importance of finding the right life partner. Here's what makes us different.</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="text-center p-6 rounded-xl border border-gray-100 hover:border-(--color-primary)/30 hover:shadow-sm transition-all">
                    <div class="w-12 h-12 mx-auto mb-4 bg-(--color-primary-light) rounded-full flex items-center justify-center text-(--color-primary) text-xl font-bold">&#10003;</div>
                    <h3 class="text-base font-semibold text-gray-900 mb-2">Verified Profiles</h3>
                    <p class="text-sm text-gray-500">Every profile is verified to ensure authenticity and safety for our members.</p>
                </div>
                <div class="text-center p-6 rounded-xl border border-gray-100 hover:border-(--color-primary)/30 hover:shadow-sm transition-all">
                    <div class="w-12 h-12 mx-auto mb-4 bg-(--color-primary-light) rounded-full flex items-center justify-center text-(--color-primary) text-xl font-bold">&#128274;</div>
                    <h3 class="text-base font-semibold text-gray-900 mb-2">100% Privacy</h3>
                    <p class="text-sm text-gray-500">Your personal information is protected. You control who sees your contact details.</p>
                </div>
                <div class="text-center p-6 rounded-xl border border-gray-100 hover:border-(--color-primary)/30 hover:shadow-sm transition-all">
                    <div class="w-12 h-12 mx-auto mb-4 bg-(--color-primary-light) rounded-full flex items-center justify-center text-(--color-primary) text-xl font-bold">&#9829;</div>
                    <h3 class="text-base font-semibold text-gray-900 mb-2">Community Focused</h3>
                    <p class="text-sm text-gray-500">Designed for families seeking meaningful connections within their community and values.</p>
                </div>
                <div class="text-center p-6 rounded-xl border border-gray-100 hover:border-(--color-primary)/30 hover:shadow-sm transition-all">
                    <div class="w-12 h-12 mx-auto mb-4 bg-(--color-primary-light) rounded-full flex items-center justify-center text-(--color-primary) text-xl font-bold">&#9733;</div>
                    <h3 class="text-base font-semibold text-gray-900 mb-2">Easy to Use</h3>
                    <p class="text-sm text-gray-500">Simple registration, powerful search, and instant messaging — all from your phone or computer.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- 5. Community Browse --}}
    @if($communities->count() > 0)
    <section class="py-16 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-2xl sm:text-3xl font-serif font-bold text-gray-900 text-center mb-12">Browse by Community</h2>

            @foreach($communities as $religion => $group)
                @php
                    // Map religion to discover category
                    $categorySlug = match($religion) {
                        'Christian' => 'catholic-matrimony',
                        'Hindu' => 'hindu-matrimony',
                        'Muslim' => 'muslim-matrimony',
                        'Jain' => 'jain-matrimony',
                        default => 'community-matrimony',
                    };
                @endphp
                <div class="mb-8">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ $religion }}</h3>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
                        @foreach($group as $community)
                            <a href="{{ route('discover.results', [$categorySlug, Str::slug($community->community_name)]) }}" class="bg-white rounded-lg border border-gray-200 shadow-xs p-4 text-center hover:border-(--color-primary) hover:shadow-sm transition-all group">
                                <p class="text-sm font-medium text-gray-900 group-hover:text-(--color-primary) transition-colors">{{ $community->community_name }}</p>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </section>
    @endif

    {{-- 6. CTA Banner --}}
    <section class="py-16" style="background: linear-gradient(135deg, var(--brand-primary) 0%, var(--brand-secondary) 100%);">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-2xl sm:text-3xl font-serif font-bold text-white mb-4">Register Free Today</h2>
            <p class="text-white/80 text-lg mb-8 max-w-xl mx-auto">Join thousands of members who have found their perfect match. Registration is free and takes just a few minutes.</p>
            <a href="/register" class="inline-block bg-white text-gray-900 hover:bg-gray-100 rounded-lg px-8 py-3 font-semibold text-sm transition-colors">
                Create Your Profile
            </a>
        </div>
    </section>
</x-layouts.app>

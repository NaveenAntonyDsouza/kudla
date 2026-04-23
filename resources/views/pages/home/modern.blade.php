<x-layouts.app
    :title="$siteTitle"
    :metaDescription="$siteMetaDesc">
    @php
        $siteName = \App\Models\SiteSetting::getValue('site_name', 'Matrimony');
        $siteTagline = \App\Models\SiteSetting::getValue('tagline', 'Find Your Perfect Match');
        $heroHeading = \App\Models\SiteSetting::getValue('hero_heading', 'Find Your Perfect Match');
        $heroSubheading = \App\Models\SiteSetting::getValue('hero_subheading', '');
        $heroImageUrl = \App\Models\SiteSetting::getValue('hero_image_url', '');
        $playStoreUrl = \App\Models\SiteSetting::getValue('app_play_store_url', '');
        $appStoreUrl = \App\Models\SiteSetting::getValue('app_apple_store_url', '');
        $whyChooseUsRaw = \App\Models\SiteSetting::getValue('why_choose_us', '[]');
        $whyChooseUs = json_decode($whyChooseUsRaw, true) ?: [];
        $ctaTitle = \App\Models\SiteSetting::getValue('cta_title', 'Ready to Find Your Soulmate?');
        $ctaDesc = \App\Models\SiteSetting::getValue('cta_description', 'Join thousands who found love through our platform.');
        $ctaButton = \App\Models\SiteSetting::getValue('cta_button_text', 'Register Free Now');
        $successStories = \App\Models\Testimonial::where('is_visible', true)->orderBy('display_order')->limit(8)->get();
    @endphp

    {{-- Modern homepage: tech-startup aesthetic, split hero, compact sections --}}
    <style>
        .m-hero-grid { display: grid; grid-template-columns: 1fr; gap: 2.5rem; align-items: center; }
        .m-profile-stack { display: none; }
        .m-stat-bar { display: grid; grid-template-columns: 1fr; gap: 1rem; }
        .m-feature-grid { display: grid; grid-template-columns: 1fr; gap: 1rem; }
        .m-feat-profile-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; }
        .m-community-tabs { display: flex; overflow-x: auto; gap: 0.5rem; padding-bottom: 0.5rem; }
        .m-community-tabs::-webkit-scrollbar { display: none; }
        @media (min-width: 768px) {
            .m-hero-grid { grid-template-columns: 1.1fr 0.9fr; gap: 3.5rem; }
            .m-profile-stack { display: block; }
            .m-stat-bar { grid-template-columns: repeat(3, 1fr); gap: 2rem; }
            .m-feature-grid { grid-template-columns: repeat(3, 1fr); gap: 1.5rem; }
            .m-feat-profile-grid { grid-template-columns: repeat(3, 1fr); gap: 1.5rem; }
        }
        @media (min-width: 1024px) {
            .m-feat-profile-grid { grid-template-columns: repeat(4, 1fr); }
        }
        .m-rotate-card-1 { transform: rotate(-4deg); }
        .m-rotate-card-2 { transform: rotate(3deg) translateY(-20px); }
        .m-rotate-card-3 { transform: rotate(-2deg) translateY(10px); }
        .m-card-hover:hover { transform: translateY(-4px); transition: transform 0.2s ease; }
    </style>

    {{-- 1. Hero: split layout with compact registration form + profile card stack --}}
    <section class="relative overflow-hidden py-12 md:py-20" style="background: linear-gradient(180deg, #ffffff 0%, var(--brand-primary-light) 100%);">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="m-hero-grid">
                {{-- Left: headline + form --}}
                <div>
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold" style="background: var(--brand-primary); color: white;">
                        <span class="w-2 h-2 bg-white rounded-full animate-pulse"></span>
                        {{ number_format($stats['members']) }}+ Members Online
                    </div>
                    <h1 class="mt-4 text-4xl md:text-5xl lg:text-6xl font-serif font-bold text-gray-900 leading-tight">
                        {{ $heroHeading }}
                    </h1>
                    @if($heroSubheading)
                        <p class="mt-4 text-base md:text-lg text-gray-600 max-w-xl">{{ $heroSubheading }}</p>
                    @elseif($siteTagline)
                        <p class="mt-4 text-base md:text-lg text-gray-600 max-w-xl">{{ $siteTagline }}</p>
                    @endif

                    {{-- Compact registration form — 3 fields --}}
                    <form action="{{ route('register') }}" method="GET" class="mt-6 bg-white rounded-2xl shadow-xl p-6 border border-gray-100">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Quick Registration</p>
                        <div class="grid grid-cols-2 gap-3 mb-3">
                            <button type="button" onclick="document.getElementById('m-gender').value='male'; this.classList.add('m-selected'); document.querySelectorAll('.m-gender-btn').forEach(b => b !== this && b.classList.remove('m-selected'));" class="m-gender-btn py-3 rounded-lg border-2 border-gray-200 text-sm font-semibold hover:border-gray-400 transition-colors" style="--active-border: var(--brand-primary);">
                                <span class="text-lg mr-1">♂</span> Male
                            </button>
                            <button type="button" onclick="document.getElementById('m-gender').value='female'; this.classList.add('m-selected'); document.querySelectorAll('.m-gender-btn').forEach(b => b !== this && b.classList.remove('m-selected'));" class="m-gender-btn py-3 rounded-lg border-2 border-gray-200 text-sm font-semibold hover:border-gray-400 transition-colors">
                                <span class="text-lg mr-1">♀</span> Female
                            </button>
                        </div>
                        <input type="hidden" id="m-gender" name="gender" value="">
                        <input type="text" name="full_name" placeholder="Full Name" required class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 text-sm focus:outline-none focus:border-gray-900 mb-3">
                        <div class="grid grid-cols-2 gap-3 mb-3">
                            <input type="email" name="email" placeholder="Email" required class="px-4 py-3 rounded-lg border-2 border-gray-200 text-sm focus:outline-none focus:border-gray-900">
                            <input type="tel" name="phone" placeholder="Phone" required class="px-4 py-3 rounded-lg border-2 border-gray-200 text-sm focus:outline-none focus:border-gray-900">
                        </div>
                        <button type="submit" class="w-full py-3.5 rounded-lg text-white font-semibold text-sm transition-colors" style="background: var(--brand-primary);" onmouseover="this.style.background='var(--brand-primary-hover)'" onmouseout="this.style.background='var(--brand-primary)'">
                            Start Your Journey — It's Free →
                        </button>
                        <p class="mt-3 text-xs text-center text-gray-500">
                            Already a member? <a href="{{ route('login') }}" class="font-semibold" style="color: var(--brand-primary);">Sign In</a>
                        </p>
                    </form>
                </div>

                {{-- Right: profile card stack (desktop only) --}}
                <div class="m-profile-stack relative h-[520px]">
                    @php $stackProfiles = $featuredProfiles->take(3); @endphp
                    @foreach($stackProfiles as $i => $profile)
                        @php $rot = ['m-rotate-card-1', 'm-rotate-card-2', 'm-rotate-card-3'][$i] ?? ''; @endphp
                        <div class="{{ $rot }} absolute bg-white rounded-2xl shadow-2xl p-4 border border-gray-100"
                             style="width: 260px; top: {{ 30 + $i * 80 }}px; {{ $i % 2 === 0 ? 'left' : 'right' }}: {{ $i * 20 }}px; z-index: {{ 10 - $i }};">
                            @if($profile->primaryPhoto && $profile->primaryPhoto->photo_url)
                                <img src="{{ $profile->primaryPhoto->photo_url }}" alt="" class="w-full h-52 object-cover rounded-xl">
                            @else
                                <div class="w-full h-52 rounded-xl flex items-center justify-center text-5xl font-serif font-bold text-white" style="background: linear-gradient(135deg, var(--brand-primary), var(--brand-secondary));">
                                    {{ strtoupper(substr($profile->full_name ?? 'A', 0, 1)) }}
                                </div>
                            @endif
                            <div class="mt-3">
                                <p class="font-semibold text-sm text-gray-900 truncate">{{ $profile->full_name }}</p>
                                <p class="text-xs text-gray-500 mt-0.5">
                                    {{ $profile->date_of_birth ? \Carbon\Carbon::parse($profile->date_of_birth)->age . ' yrs' : '' }}
                                    @if($profile->locationInfo?->native_district) · {{ $profile->locationInfo->native_district }} @endif
                                </p>
                            </div>
                        </div>
                    @endforeach

                    {{-- Floating stat bubble --}}
                    <div class="absolute bottom-0 right-0 bg-white rounded-2xl shadow-xl p-4 flex items-center gap-3 border border-gray-100" style="z-index: 20;">
                        <div class="w-12 h-12 rounded-full flex items-center justify-center" style="background: var(--brand-primary-light);">
                            <svg class="w-6 h-6" fill="none" stroke="var(--brand-primary)" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                        </div>
                        <div>
                            <p class="font-bold text-gray-900">{{ number_format($stats['marriages']) }}+</p>
                            <p class="text-xs text-gray-500">Happy Marriages</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- 2. Stats bar: horizontal strip, no counters --}}
    <section class="bg-white border-y border-gray-100 py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="m-stat-bar text-center">
                <div>
                    <p class="text-3xl md:text-4xl font-bold font-serif" style="color: var(--brand-primary);">{{ number_format($stats['members']) }}+</p>
                    <p class="mt-1 text-sm text-gray-600 font-medium">Verified Members</p>
                </div>
                <div class="md:border-x md:border-gray-200">
                    <p class="text-3xl md:text-4xl font-bold font-serif" style="color: var(--brand-primary);">{{ number_format($stats['marriages']) }}+</p>
                    <p class="mt-1 text-sm text-gray-600 font-medium">Successful Marriages</p>
                </div>
                <div>
                    <p class="text-3xl md:text-4xl font-bold font-serif" style="color: var(--brand-primary);">{{ $stats['years'] }}+</p>
                    <p class="mt-1 text-sm text-gray-600 font-medium">Years of Trust</p>
                </div>
            </div>
        </div>
    </section>

    {{-- 3. Search bar — prominent, full-width --}}
    <section class="py-12 bg-gray-50">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-2xl shadow-lg p-6 md:p-8 border border-gray-100">
                <h2 class="text-xl md:text-2xl font-serif font-bold text-gray-900 mb-1">Find your match in seconds</h2>
                <p class="text-sm text-gray-500 mb-5">Search by age, community, and more</p>
                <form action="{{ route('search.quick') }}" method="GET" class="grid grid-cols-2 md:grid-cols-5 gap-3">
                    <select name="gender" class="col-span-2 md:col-span-1 px-4 py-3 rounded-lg border-2 border-gray-200 text-sm focus:outline-none focus:border-gray-900">
                        <option value="">I'm looking for</option>
                        <option value="male">Groom</option>
                        <option value="female">Bride</option>
                    </select>
                    <input type="number" name="age_from" min="18" max="80" placeholder="Age from" class="px-4 py-3 rounded-lg border-2 border-gray-200 text-sm focus:outline-none focus:border-gray-900">
                    <input type="number" name="age_to" min="18" max="80" placeholder="Age to" class="px-4 py-3 rounded-lg border-2 border-gray-200 text-sm focus:outline-none focus:border-gray-900">
                    <select name="community" class="col-span-2 md:col-span-1 px-4 py-3 rounded-lg border-2 border-gray-200 text-sm focus:outline-none focus:border-gray-900">
                        <option value="">Any community</option>
                        @foreach($communities->flatten() as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="col-span-2 md:col-span-1 py-3 rounded-lg text-white font-semibold text-sm transition-colors" style="background: var(--brand-primary);">
                        Search →
                    </button>
                </form>
            </div>
        </div>
    </section>

    {{-- 4. Why Choose Us — modern card grid --}}
    @if(count($whyChooseUs) > 0)
    <section class="py-16 md:py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-10">
                <p class="text-xs font-bold uppercase tracking-widest mb-2" style="color: var(--brand-primary);">Why {{ $siteName }}</p>
                <h2 class="text-3xl md:text-4xl font-serif font-bold text-gray-900">Built for meaningful matches</h2>
            </div>
            <div class="m-feature-grid">
                @foreach(array_slice($whyChooseUs, 0, 6) as $item)
                    <div class="m-card-hover bg-white rounded-2xl p-6 border border-gray-100 shadow-sm">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-4" style="background: var(--brand-primary-light);">
                            <svg class="w-6 h-6" fill="none" stroke="var(--brand-primary)" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <h3 class="font-semibold text-gray-900 mb-2">{{ $item['title'] ?? '' }}</h3>
                        <p class="text-sm text-gray-600 leading-relaxed">{{ $item['description'] ?? '' }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- 5. Featured profiles — bigger cards, modern --}}
    @if($featuredProfiles->count() > 0)
    <section class="py-16 md:py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-end justify-between mb-8">
                <div>
                    <p class="text-xs font-bold uppercase tracking-widest mb-2" style="color: var(--brand-primary);">Recently Joined</p>
                    <h2 class="text-2xl md:text-3xl font-serif font-bold text-gray-900">Meet our latest members</h2>
                </div>
                <a href="{{ route('register') }}" class="hidden md:block text-sm font-semibold" style="color: var(--brand-primary);">View all →</a>
            </div>
            <div class="m-feat-profile-grid">
                @foreach($featuredProfiles as $profile)
                    <a href="{{ route('register') }}" class="m-card-hover group bg-white rounded-2xl overflow-hidden shadow-sm border border-gray-100 block">
                        <div class="relative aspect-[3/4]">
                            @if($profile->primaryPhoto && $profile->primaryPhoto->photo_url)
                                <img src="{{ $profile->primaryPhoto->photo_url }}" alt="" class="w-full h-full object-cover" style="filter: blur(4px);">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-6xl font-serif font-bold text-white" style="background: linear-gradient(135deg, var(--brand-primary), var(--brand-secondary));">
                                    {{ strtoupper(substr($profile->full_name ?? 'A', 0, 1)) }}
                                </div>
                            @endif
                            <div class="absolute inset-0" style="background: linear-gradient(180deg, transparent 40%, rgba(0,0,0,0.75) 100%);"></div>
                            @if($profile->is_vip)
                                <div class="absolute top-3 left-3 px-2 py-1 rounded-md text-xs font-bold text-white" style="background: linear-gradient(135deg, #f59e0b, #d97706);">VIP</div>
                            @elseif($profile->is_featured)
                                <div class="absolute top-3 left-3 px-2 py-1 rounded-md text-xs font-bold text-white bg-gray-900">Featured</div>
                            @endif
                            <div class="absolute bottom-0 left-0 right-0 p-4 text-white">
                                <p class="font-bold text-lg">{{ \Illuminate\Support\Str::limit($profile->full_name, 20) }}</p>
                                <p class="text-sm opacity-90 mt-0.5">
                                    {{ $profile->date_of_birth ? \Carbon\Carbon::parse($profile->date_of_birth)->age . ' yrs' : '' }}
                                    @if($profile->locationInfo?->native_district) · {{ $profile->locationInfo->native_district }} @endif
                                </p>
                                @if($profile->educationDetail?->occupation)
                                    <p class="text-xs opacity-75 mt-0.5">{{ \Illuminate\Support\Str::limit($profile->educationDetail->occupation, 25) }}</p>
                                @endif
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
            <div class="mt-8 text-center md:hidden">
                <a href="{{ route('register') }}" class="inline-block px-6 py-3 rounded-lg font-semibold text-white text-sm" style="background: var(--brand-primary);">Join to view all profiles</a>
            </div>
        </div>
    </section>
    @endif

    {{-- 6. Success Stories — card grid (not carousel) --}}
    @if($successStories->count() > 0)
    <section class="py-16 md:py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-10">
                <p class="text-xs font-bold uppercase tracking-widest mb-2" style="color: var(--brand-primary);">Success Stories</p>
                <h2 class="text-3xl md:text-4xl font-serif font-bold text-gray-900">Love stories that started here</h2>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($successStories->take(6) as $story)
                    <div class="bg-gray-50 rounded-2xl overflow-hidden border border-gray-100">
                        @if($story->photo_url)
                            <img src="{{ $story->photo_url }}" alt="{{ $story->couple_names }}" class="w-full h-56 object-cover">
                        @else
                            <div class="w-full h-56 flex items-center justify-center" style="background: linear-gradient(135deg, var(--brand-primary-light), var(--brand-primary));">
                                <svg class="w-20 h-20 text-white/70" fill="currentColor" viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                            </div>
                        @endif
                        <div class="p-6">
                            <p class="font-semibold text-gray-900">{{ $story->couple_names }}</p>
                            @if($story->location)<p class="text-xs text-gray-500 mt-1">{{ $story->location }}</p>@endif
                            <p class="mt-3 text-sm text-gray-600 leading-relaxed">{{ \Illuminate\Support\Str::limit($story->story, 140) }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- 7. Browse by community — compact tab row --}}
    @if($communities->count() > 0)
    <section class="py-16 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-2xl md:text-3xl font-serif font-bold text-gray-900 text-center mb-8">Browse by Community</h2>
            <div class="m-community-tabs">
                @foreach($communities as $religion => $items)
                    @foreach($items->take(5) as $c)
                        <a href="{{ route('discover.hub') }}"
                           class="shrink-0 px-5 py-2.5 bg-white rounded-full border border-gray-200 text-sm font-medium text-gray-700 hover:border-gray-900 transition-colors whitespace-nowrap">
                            {{ $c->name }}
                        </a>
                    @endforeach
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- 8. FAQ --}}
    @if($faqs->count() > 0)
    <section class="py-16 md:py-20 bg-white">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-10">
                <p class="text-xs font-bold uppercase tracking-widest mb-2" style="color: var(--brand-primary);">Questions</p>
                <h2 class="text-3xl md:text-4xl font-serif font-bold text-gray-900">Frequently Asked</h2>
            </div>
            <div class="space-y-3">
                @foreach($faqs as $faq)
                    <details class="group bg-gray-50 rounded-xl border border-gray-100">
                        <summary class="flex items-center justify-between gap-4 p-5 cursor-pointer list-none">
                            <span class="font-semibold text-gray-900">{{ $faq->question }}</span>
                            <svg class="w-5 h-5 text-gray-500 transition-transform group-open:rotate-45" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                        </summary>
                        <div class="px-5 pb-5 text-sm text-gray-600 leading-relaxed">{{ $faq->answer }}</div>
                    </details>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- 9. App CTA (if URLs set) --}}
    @if($playStoreUrl || $appStoreUrl)
    <section class="py-12 bg-white border-y border-gray-100">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <p class="text-xs font-bold uppercase tracking-widest mb-2" style="color: var(--brand-primary);">Get the App</p>
            <h2 class="text-2xl md:text-3xl font-serif font-bold text-gray-900 mb-6">Find love on the go</h2>
            <div class="flex flex-wrap items-center justify-center gap-3">
                @if($playStoreUrl)
                    <a href="{{ $playStoreUrl }}" target="_blank" class="inline-flex items-center gap-2 px-5 py-3 bg-gray-900 text-white rounded-xl hover:bg-gray-800">
                        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="currentColor"><path d="M3 20.5V3.5c0-.64.36-1.19.88-1.47L13.71 12l-9.83 9.97A1.61 1.61 0 0 1 3 20.5z"/></svg>
                        <span class="text-left"><span class="block text-[10px] opacity-75">GET IT ON</span><span class="block text-sm font-semibold">Google Play</span></span>
                    </a>
                @endif
                @if($appStoreUrl)
                    <a href="{{ $appStoreUrl }}" target="_blank" class="inline-flex items-center gap-2 px-5 py-3 bg-gray-900 text-white rounded-xl hover:bg-gray-800">
                        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="currentColor"><path d="M17.05 20.28c-.98.95-2.05.8-3.08.35-1.09-.46-2.09-.48-3.24 0-1.44.62-2.2.44-3.06-.35C2.79 15.25 3.51 7.59 9.05 7.31c1.35.07 2.29.74 3.08.8 1.18-.24 2.31-.93 3.57-.84 1.51.12 2.65.72 3.4 1.8-3.12 1.87-2.38 5.98.48 7.13-.57 1.5-1.31 2.99-2.54 4.09zM12.03 7.25c-.15-2.23 1.66-4.07 3.74-4.25.29 2.58-2.34 4.5-3.74 4.25z"/></svg>
                        <span class="text-left"><span class="block text-[10px] opacity-75">Download on the</span><span class="block text-sm font-semibold">App Store</span></span>
                    </a>
                @endif
            </div>
        </div>
    </section>
    @endif

    {{-- 10. Final CTA strip --}}
    <section class="py-14 md:py-20" style="background: linear-gradient(135deg, var(--brand-primary) 0%, var(--brand-secondary) 100%);">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-white">
            <h2 class="text-3xl md:text-5xl font-serif font-bold">{{ $ctaTitle }}</h2>
            <p class="mt-4 text-lg opacity-90 max-w-2xl mx-auto">{{ $ctaDesc }}</p>
            <a href="{{ route('register') }}" class="inline-block mt-8 px-8 py-4 bg-white rounded-xl font-bold text-base hover:bg-gray-100 transition-colors" style="color: var(--brand-primary);">
                {{ $ctaButton }}
            </a>
        </div>
    </section>

    <style>
        .m-gender-btn.m-selected { border-color: var(--brand-primary); background: var(--brand-primary-light); color: var(--brand-primary); }
    </style>
</x-layouts.app>

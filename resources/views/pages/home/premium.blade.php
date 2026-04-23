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
        $ctaTitle = \App\Models\SiteSetting::getValue('cta_title', 'Your Story Begins Here');
        $ctaDesc = \App\Models\SiteSetting::getValue('cta_description', 'Join a discerning community of genuine seekers.');
        $ctaButton = \App\Models\SiteSetting::getValue('cta_button_text', 'Create Your Profile');
        $successStories = \App\Models\Testimonial::where('is_visible', true)->orderBy('display_order')->limit(8)->get();
    @endphp

    {{-- Premium homepage: editorial / magazine aesthetic, photo-heavy, serif headlines --}}
    <style>
        .p-hero { min-height: 88vh; }
        .p-editorial-grid { display: grid; grid-template-columns: 1fr; gap: 2rem; }
        .p-masonry { display: grid; grid-template-columns: 1fr; gap: 1.25rem; }
        .p-community-tiles { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; }
        .p-story-card { display: grid; grid-template-columns: 1fr; gap: 1.5rem; }
        @media (min-width: 768px) {
            .p-editorial-grid { grid-template-columns: repeat(3, 1fr); gap: 2.5rem; }
            .p-masonry { grid-template-columns: repeat(3, 1fr); gap: 1.5rem; }
            .p-community-tiles { grid-template-columns: repeat(4, 1fr); gap: 1.25rem; }
            .p-story-card { grid-template-columns: 5fr 4fr; gap: 3rem; align-items: center; }
            .p-story-card.reverse { grid-template-columns: 4fr 5fr; }
            .p-masonry-offset { margin-top: 3rem; }
        }
        @media (min-width: 1024px) {
            .p-masonry { grid-template-columns: repeat(4, 1fr); }
        }
        .p-section-divider { position: relative; text-align: center; margin: 2.5rem 0; }
        .p-section-divider::before, .p-section-divider::after {
            content: ''; position: absolute; top: 50%; width: 40%; height: 1px; background: #e5e7eb;
        }
        .p-section-divider::before { left: 0; }
        .p-section-divider::after { right: 0; }
        .p-kicker {
            display: inline-block; font-size: 0.7rem; font-weight: 600; letter-spacing: 0.25em;
            text-transform: uppercase; color: var(--brand-primary); margin-bottom: 0.75rem;
        }
        .p-fade-in { opacity: 0; animation: pFadeIn 0.8s ease-out forwards; }
        @keyframes pFadeIn { to { opacity: 1; transform: translateY(0); } }
        .p-serif { font-family: var(--brand-font-serif, Georgia, serif); }
    </style>

    {{-- 1. Cinematic full-bleed hero --}}
    <section class="p-hero relative flex items-center" style="background: {{ $heroImageUrl ? 'url(' . $heroImageUrl . ') center/cover no-repeat' : 'linear-gradient(135deg, var(--brand-primary) 0%, var(--brand-primary-hover) 100%)' }};">
        @if($heroImageUrl)
            <div class="absolute inset-0" style="background: linear-gradient(180deg, rgba(0,0,0,0.3) 0%, rgba(0,0,0,0.5) 50%, rgba(0,0,0,0.7) 100%);"></div>
        @else
            <div class="absolute inset-0" style="background: linear-gradient(180deg, rgba(0,0,0,0.1) 0%, rgba(0,0,0,0.4) 100%);"></div>
        @endif
        <div class="relative w-full max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-20 text-center text-white">
            <p class="p-kicker text-white/80">Est. {{ now()->year - (int) $stats['years'] }}</p>
            <h1 class="p-serif text-4xl sm:text-5xl md:text-6xl lg:text-7xl font-bold leading-[1.1] tracking-tight max-w-4xl mx-auto">
                {{ $heroHeading }}
            </h1>
            @if($heroSubheading)
                <p class="mt-6 text-base md:text-xl text-white/90 max-w-2xl mx-auto leading-relaxed font-light">{{ $heroSubheading }}</p>
            @elseif($siteTagline)
                <p class="mt-6 text-base md:text-xl text-white/90 max-w-2xl mx-auto leading-relaxed font-light">{{ $siteTagline }}</p>
            @endif
            <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-3">
                <a href="#p-register" class="px-8 py-4 bg-white rounded-full font-semibold text-base hover:bg-gray-100 transition-colors shadow-xl" style="color: var(--brand-primary);">
                    Begin Your Story
                </a>
                <a href="{{ route('login') }}" class="px-8 py-4 border-2 border-white/60 text-white rounded-full font-semibold text-base hover:bg-white/10 transition-colors">
                    Sign In
                </a>
            </div>
            <div class="mt-12 flex items-center justify-center gap-6 md:gap-10 text-white/80 text-sm flex-wrap">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04"/></svg>
                    Verified Profiles
                </div>
                <div class="hidden md:block w-1 h-1 rounded-full bg-white/40"></div>
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
                    Private & Secure
                </div>
                <div class="hidden md:block w-1 h-1 rounded-full bg-white/40"></div>
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                    Personal Matchmaking
                </div>
            </div>
        </div>

        {{-- Scroll hint --}}
        <div class="absolute bottom-8 left-1/2 -translate-x-1/2 text-white/70 text-xs uppercase tracking-widest">
            <div class="flex flex-col items-center gap-2">
                <span>Scroll</span>
                <svg class="w-4 h-4 animate-bounce" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
            </div>
        </div>
    </section>

    {{-- 2. Narrative stats strip — prose style --}}
    <section class="py-16 md:py-20 bg-white">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <p class="p-kicker">Our Story</p>
            <p class="p-serif text-2xl md:text-3xl lg:text-4xl text-gray-800 leading-relaxed">
                Trusted by
                <span class="font-bold" style="color: var(--brand-primary);">{{ number_format($stats['members']) }}+ members</span>,
                we've crafted
                <span class="font-bold" style="color: var(--brand-primary);">{{ number_format($stats['marriages']) }} love stories</span>
                over
                <span class="font-bold" style="color: var(--brand-primary);">{{ $stats['years'] }} {{ $stats['years'] == 1 ? 'year' : 'years' }}</span>
                of dedicated matchmaking.
            </p>
        </div>
    </section>

    {{-- 3. Editor's Picks — featured profiles, magazine-cover style --}}
    @if($featuredProfiles->count() > 0)
    <section class="py-16 md:py-24" style="background: #fafaf8;">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <p class="p-kicker">Curated</p>
                <h2 class="p-serif text-3xl md:text-5xl font-bold text-gray-900">Editor's Picks</h2>
                <p class="mt-3 text-gray-600 max-w-xl mx-auto">Handpicked profiles that caught our attention this week.</p>
            </div>
            <div class="p-masonry">
                @foreach($featuredProfiles->take(8) as $i => $profile)
                    <div class="{{ $i % 2 == 1 ? 'p-masonry-offset' : '' }} group">
                        <a href="{{ route('register') }}" class="block relative overflow-hidden rounded-lg shadow-md hover:shadow-2xl transition-shadow">
                            <div class="aspect-[3/4] relative">
                                @if($profile->primaryPhoto && $profile->primaryPhoto->photo_url)
                                    <img src="{{ $profile->primaryPhoto->photo_url }}" alt="" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105" style="filter: blur(3px);">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-7xl p-serif font-bold text-white" style="background: linear-gradient(135deg, var(--brand-primary), var(--brand-secondary));">
                                        {{ strtoupper(substr($profile->full_name ?? 'A', 0, 1)) }}
                                    </div>
                                @endif
                                <div class="absolute inset-0" style="background: linear-gradient(180deg, transparent 50%, rgba(0,0,0,0.9) 100%);"></div>
                                @if($profile->is_vip)
                                    <div class="absolute top-4 right-4 px-3 py-1 rounded-sm text-[10px] font-bold tracking-widest uppercase text-white" style="background: rgba(0,0,0,0.7); border: 1px solid rgba(255,255,255,0.3);">✦ VIP</div>
                                @endif
                                <div class="absolute bottom-0 left-0 right-0 p-5 text-white">
                                    <p class="p-serif font-bold text-xl leading-tight">{{ \Illuminate\Support\Str::limit($profile->full_name, 18) }}</p>
                                    <p class="text-xs mt-1 opacity-90 tracking-wide uppercase">
                                        {{ $profile->date_of_birth ? \Carbon\Carbon::parse($profile->date_of_birth)->age : '' }}
                                        @if($profile->locationInfo?->native_district) · {{ $profile->locationInfo->native_district }} @endif
                                    </p>
                                </div>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- 4. Why we're different — editorial 3-col --}}
    @if(count($whyChooseUs) > 0)
    <section class="py-16 md:py-24 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <p class="p-kicker">The {{ $siteName }} Difference</p>
                <h2 class="p-serif text-3xl md:text-5xl font-bold text-gray-900 max-w-3xl mx-auto">Why discerning seekers choose us</h2>
            </div>
            <div class="p-editorial-grid">
                @foreach(array_slice($whyChooseUs, 0, 3) as $i => $item)
                    <div class="text-center md:text-left">
                        <span class="p-serif text-5xl font-bold" style="color: var(--brand-primary); opacity: 0.3;">0{{ $i + 1 }}</span>
                        <h3 class="p-serif text-xl md:text-2xl font-bold text-gray-900 mt-3 mb-3">{{ $item['title'] ?? '' }}</h3>
                        <p class="text-gray-600 leading-relaxed">{{ $item['description'] ?? '' }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- 5. Success Stories — long-form interview style --}}
    @if($successStories->count() > 0)
    <section class="py-16 md:py-24" style="background: #fafaf8;">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <p class="p-kicker">Love Stories</p>
                <h2 class="p-serif text-3xl md:text-5xl font-bold text-gray-900">Real couples. Real stories.</h2>
            </div>

            <div class="space-y-16 md:space-y-24">
                @foreach($successStories->take(3) as $i => $story)
                    <div class="p-story-card {{ $i % 2 === 1 ? 'reverse' : '' }}">
                        <div class="{{ $i % 2 === 1 ? 'md:order-2' : '' }}">
                            @if($story->photo_url)
                                <img src="{{ $story->photo_url }}" alt="{{ $story->couple_names }}" class="w-full aspect-[4/5] object-cover rounded-sm shadow-xl">
                            @else
                                <div class="w-full aspect-[4/5] rounded-sm flex items-center justify-center shadow-xl" style="background: linear-gradient(135deg, var(--brand-primary-light), var(--brand-primary));">
                                    <svg class="w-24 h-24 text-white/50" fill="currentColor" viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                                </div>
                            @endif
                        </div>
                        <div class="{{ $i % 2 === 1 ? 'md:order-1' : '' }}">
                            <p class="p-kicker">Story {{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</p>
                            <h3 class="p-serif text-2xl md:text-3xl font-bold text-gray-900 mb-2">{{ $story->couple_names }}</h3>
                            @if($story->location)<p class="text-sm text-gray-500 mb-4 tracking-wide">{{ $story->location }}@if($story->wedding_date) · {{ \Carbon\Carbon::parse($story->wedding_date)->format('F Y') }}@endif</p>@endif
                            <blockquote class="p-serif text-lg md:text-xl text-gray-700 leading-relaxed italic relative pl-6">
                                <span class="absolute left-0 top-0 text-5xl leading-none" style="color: var(--brand-primary); opacity: 0.3;">"</span>
                                {{ \Illuminate\Support\Str::limit($story->story, 280) }}
                            </blockquote>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- 6. Registration form — full-width, below the fold --}}
    <section id="p-register" class="py-20 md:py-28 relative overflow-hidden" style="background: linear-gradient(135deg, var(--brand-primary) 0%, var(--brand-primary-hover) 100%);">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 relative">
            <div class="text-center text-white mb-10">
                <p class="p-kicker text-white/80">Begin Today</p>
                <h2 class="p-serif text-3xl md:text-5xl font-bold">Your story starts with hello</h2>
                <p class="mt-4 text-lg text-white/80 max-w-xl mx-auto">Free to join. No obligations. Just possibility.</p>
            </div>
            <form action="{{ route('register') }}" method="GET" class="bg-white rounded-sm shadow-2xl p-8 md:p-10">
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <button type="button" onclick="document.getElementById('p-gender').value='male'; this.classList.add('p-selected'); document.querySelectorAll('.p-gender-btn').forEach(b => b !== this && b.classList.remove('p-selected'));" class="p-gender-btn py-4 border-2 border-gray-200 text-sm font-semibold hover:border-gray-400 transition-colors">
                        I am a Groom
                    </button>
                    <button type="button" onclick="document.getElementById('p-gender').value='female'; this.classList.add('p-selected'); document.querySelectorAll('.p-gender-btn').forEach(b => b !== this && b.classList.remove('p-selected'));" class="p-gender-btn py-4 border-2 border-gray-200 text-sm font-semibold hover:border-gray-400 transition-colors">
                        I am a Bride
                    </button>
                </div>
                <input type="hidden" id="p-gender" name="gender" value="">
                <input type="text" name="full_name" placeholder="Your full name" required class="w-full px-4 py-3 border-b-2 border-gray-200 text-base focus:outline-none focus:border-gray-900 mb-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <input type="email" name="email" placeholder="Email" required class="w-full px-4 py-3 border-b-2 border-gray-200 text-base focus:outline-none focus:border-gray-900">
                    <input type="tel" name="phone" placeholder="Phone" required class="w-full px-4 py-3 border-b-2 border-gray-200 text-base focus:outline-none focus:border-gray-900">
                </div>
                <button type="submit" class="w-full py-4 text-white font-semibold text-sm tracking-widest uppercase transition-colors rounded-sm" style="background: var(--brand-primary);">
                    Create Free Account
                </button>
                <p class="mt-6 text-xs text-center text-gray-500">
                    By continuing, you agree to our <a href="/terms" class="underline">Terms</a> and <a href="/privacy" class="underline">Privacy Policy</a>.
                </p>
            </form>
        </div>
    </section>

    {{-- 7. Browse communities — visual tiles --}}
    @if($communities->count() > 0)
    <section class="py-16 md:py-24 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <p class="p-kicker">Explore</p>
                <h2 class="p-serif text-3xl md:text-5xl font-bold text-gray-900">Browse by Community</h2>
            </div>
            <div class="p-community-tiles">
                @php
                    $communityColors = ['#8B1D91', '#00BCD4', '#E53935', '#43A047', '#FB8C00', '#3949AB', '#00897B', '#D81B60'];
                    $tileIndex = 0;
                @endphp
                @foreach($communities as $religion => $items)
                    @foreach($items->take(2) as $c)
                        @php $color = $communityColors[$tileIndex % count($communityColors)]; $tileIndex++; @endphp
                        <a href="{{ route('discover.hub') }}"
                           class="group relative aspect-square overflow-hidden rounded-sm block">
                            <div class="absolute inset-0 transition-transform duration-500 group-hover:scale-110" style="background: linear-gradient(135deg, {{ $color }}, {{ $color }}bb);"></div>
                            <div class="absolute inset-0" style="background: linear-gradient(180deg, transparent 50%, rgba(0,0,0,0.7) 100%);"></div>
                            <div class="absolute bottom-0 left-0 right-0 p-5 text-white">
                                <p class="text-[10px] uppercase tracking-widest opacity-80">{{ $religion }}</p>
                                <p class="p-serif text-xl font-bold mt-1">{{ $c->name }}</p>
                            </div>
                        </a>
                    @endforeach
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- 8. FAQ — minimalist --}}
    @if($faqs->count() > 0)
    <section class="py-16 md:py-24" style="background: #fafaf8;">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <p class="p-kicker">Before You Begin</p>
                <h2 class="p-serif text-3xl md:text-5xl font-bold text-gray-900">Frequently Asked</h2>
            </div>
            <div class="divide-y divide-gray-200">
                @foreach($faqs as $faq)
                    <details class="group py-5">
                        <summary class="flex items-center justify-between gap-4 cursor-pointer list-none">
                            <span class="p-serif text-lg font-semibold text-gray-900">{{ $faq->question }}</span>
                            <span class="flex-shrink-0 w-8 h-8 rounded-full border border-gray-300 flex items-center justify-center group-open:rotate-180 transition-transform">
                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                            </span>
                        </summary>
                        <div class="mt-4 text-gray-600 leading-relaxed">{{ $faq->answer }}</div>
                    </details>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- 9. App CTA — minimalist strip --}}
    @if($playStoreUrl || $appStoreUrl)
    <section class="py-14 bg-white border-y border-gray-200">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row items-center justify-between gap-6">
            <div>
                <p class="p-kicker">Also available</p>
                <p class="p-serif text-xl md:text-2xl font-bold text-gray-900">Take {{ $siteName }} with you</p>
            </div>
            <div class="flex items-center gap-3">
                @if($playStoreUrl)
                    <a href="{{ $playStoreUrl }}" target="_blank" class="inline-flex items-center gap-2 px-5 py-3 border border-gray-300 rounded-full hover:border-gray-900 transition-colors">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M3 20.5V3.5c0-.64.36-1.19.88-1.47L13.71 12l-9.83 9.97A1.61 1.61 0 0 1 3 20.5z"/></svg>
                        <span class="text-sm font-medium">Google Play</span>
                    </a>
                @endif
                @if($appStoreUrl)
                    <a href="{{ $appStoreUrl }}" target="_blank" class="inline-flex items-center gap-2 px-5 py-3 border border-gray-300 rounded-full hover:border-gray-900 transition-colors">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M17.05 20.28c-.98.95-2.05.8-3.08.35-1.09-.46-2.09-.48-3.24 0-1.44.62-2.2.44-3.06-.35C2.79 15.25 3.51 7.59 9.05 7.31c1.35.07 2.29.74 3.08.8 1.18-.24 2.31-.93 3.57-.84 1.51.12 2.65.72 3.4 1.8-3.12 1.87-2.38 5.98.48 7.13-.57 1.5-1.31 2.99-2.54 4.09zM12.03 7.25c-.15-2.23 1.66-4.07 3.74-4.25.29 2.58-2.34 4.5-3.74 4.25z"/></svg>
                        <span class="text-sm font-medium">App Store</span>
                    </a>
                @endif
            </div>
        </div>
    </section>
    @endif

    <style>
        .p-gender-btn.p-selected { border-color: var(--brand-primary); background: var(--brand-primary-light); color: var(--brand-primary); }
    </style>
</x-layouts.app>

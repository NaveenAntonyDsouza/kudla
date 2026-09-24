@props(['profile', 'matchScore' => null, 'matchBadge' => null])

{{-- Defensive: a caller may pass a null relation (e.g. a shortlisted profile whose
     owner has since deleted their account). Render nothing rather than crash on
     route('profile.view', null). --}}
@if($profile)
@php
    $p = $profile;
    $isGuest = !auth()->check();
    $desc = collect([
        $p->age ? $p->age . ' Yrs' : null,
        $p->height,
        $p->complexion,
        $p->marital_status,
        $p->religiousInfo?->religion,
        $p->religiousInfo?->display_denomination ?? $p->religiousInfo?->display_caste,
        $p->educationDetail?->highest_education,
        $p->educationDetail?->occupation,
        $p->locationInfo?->native_district ?? $p->locationInfo?->native_state ?? $p->locationInfo?->native_country,
    ])->filter()->implode(', ');

    // Cache shortlisted IDs to avoid N+1 queries
    static $shortlistedIds = null;
    if ($shortlistedIds === null && !$isGuest) {
        $shortlistedIds = \App\Models\Shortlist::where('profile_id', auth()->user()->profile->id)->pluck('shortlisted_profile_id')->toArray();
    }
    $isShortlisted = $shortlistedIds !== null && in_array($p->id, $shortlistedIds);
    $profileUrl = $isGuest ? route('login') : route('profile.view', $p);

    // Photo privacy — the shared rule (App\Support\PhotoVisibility), same as
    // the profile page. Applies to guests too: a hidden photo stays hidden
    // on the homepage and public search.
    $viewerProfile = $isGuest ? null : auth()->user()->profile;
    $photoState = \App\Support\PhotoVisibility::state($p, $viewerProfile);
    $hasPhoto = $photoState !== \App\Support\PhotoVisibility::NO_PHOTO;
    $showPhoto = $photoState === \App\Support\PhotoVisibility::VISIBLE;
    $isOwnCard = $viewerProfile && $viewerProfile->id === $p->id;
    $photoOverlay = match (true) {
        $showPhoto => null,
        $photoState === \App\Support\PhotoVisibility::HIDDEN => 'hidden',
        $photoState === \App\Support\PhotoVisibility::AFTER_ACCEPTANCE => 'after_acceptance',
        ! $isGuest && ! $isOwnCard => 'request_photo', // no photo yet
        default => null,
    };
@endphp

<div class="relative rounded-lg border border-gray-200 overflow-hidden hover:shadow-md hover:border-(--color-primary)/30 transition-all group bg-white">
    {{-- Shortlist heart (logged-in only) --}}
    @if(!$isGuest)
        <form method="POST" action="{{ route('shortlist.toggle', $p) }}" class="absolute top-2 right-2 z-10" @click.stop>
            @csrf
            <button type="submit" class="p-1.5 rounded-full {{ $isShortlisted ? 'text-pink-500' : 'text-white/80 hover:text-pink-400' }} transition-colors" style="background: rgba(0,0,0,0.3);">
                <svg class="w-4 h-4" fill="{{ $isShortlisted ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/>
                </svg>
            </button>
        </form>
    @endif

    <a href="{{ $profileUrl }}" class="block">
        {{-- Photo --}}
        <div class="aspect-[3/4] bg-gray-100 relative overflow-hidden">
            @if($showPhoto && $hasPhoto)
                <img src="{{ $p->primaryPhoto->full_url }}" alt="{{ $p->matri_id }}"
                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" loading="lazy">
            @elseif($photoOverlay === 'hidden' && $hasPhoto)
                {{-- Locked placeholder — the real image is never sent (a CSS
                     blur still hands anyone the photo's address). --}}
                <div class="absolute inset-0" style="background: linear-gradient(135deg, var(--color-primary-light, #F3E8F7), #e5e7eb);"></div>
                <div class="absolute inset-0 flex flex-col items-center justify-center text-center p-4">
                    <svg class="w-8 h-8 text-gray-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/></svg>
                    <p class="text-xs font-semibold text-gray-700">This photo is hidden</p>
                    @unless($isGuest)
                        <span class="mt-2 inline-block px-3 py-1 text-[10px] font-bold text-(--color-primary) bg-white rounded-full shadow-sm">SEND VIEW REQUEST</span>
                    @endunless
                </div>
            @elseif($photoOverlay === 'after_acceptance' && $hasPhoto)
                {{-- Locked placeholder — see the 'hidden' branch above. --}}
                <div class="absolute inset-0" style="background: linear-gradient(135deg, var(--color-primary-light, #F3E8F7), #e5e7eb);"></div>
                <div class="absolute inset-0 flex flex-col items-center justify-center text-center p-4">
                    <svg class="w-8 h-8 text-gray-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/></svg>
                    <p class="text-xs font-semibold text-gray-700">Visible only after acceptance</p>
                </div>
            @elseif($photoOverlay === 'request_photo')
                {{-- No photo — placeholder with "Request Photo" --}}
                <div class="w-full h-full flex flex-col items-center justify-center">
                    <svg class="w-16 h-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0"/>
                    </svg>
                    <span class="mt-2 inline-block px-3 py-1 text-[10px] font-bold text-(--color-primary) bg-(--color-primary-light) rounded-full">REQUEST PHOTO</span>
                </div>
            @else
                {{-- Default placeholder (no photo, own profile, guest) --}}
                <div class="w-full h-full flex items-center justify-center">
                    <svg class="w-16 h-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0"/>
                    </svg>
                </div>
            @endif

            {{-- Match Badge --}}
            @if($matchBadge)
                <div class="absolute bottom-2 left-2">
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold shadow-sm
                        {{ $matchBadge === 'great' ? 'bg-green-500 text-white' : '' }}
                        {{ $matchBadge === 'good' ? 'bg-yellow-500 text-white' : '' }}
                        {{ $matchBadge === 'partial' ? 'bg-gray-500 text-white' : '' }}">
                        {{ $matchScore }}% Match
                    </span>
                </div>
            @endif

            {{-- Login prompt for guests --}}
            @if($isGuest)
                <div class="absolute bottom-2 right-2">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-white/90 text-(--color-primary) shadow-sm">
                        Login to view
                    </span>
                </div>
            @endif
        </div>

        {{-- Details --}}
        <div class="p-3">
            <div class="flex items-center gap-1.5 flex-wrap">
                <p class="text-sm font-semibold text-(--color-primary) group-hover:underline">{{ $p->matri_id }}</p>
                @if($p->is_vip)
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold text-white leading-none" style="background: linear-gradient(135deg, #f59e0b, #d97706);">⭐ VIP</span>
                @endif
                @if($p->is_featured)
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold bg-pink-500 text-white leading-none">Featured</span>
                @endif
                @if(!$isGuest && $p->user?->isPremium())
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold bg-(--color-primary) text-white leading-none">Premium</span>
                @endif
            </div>
            <p class="text-xs text-gray-600 mt-1 line-clamp-3 min-h-[3rem]">{{ $desc ?: 'Profile details not available' }}</p>
            <div class="flex items-center justify-between mt-2">
                @php
                    $lastLogin = $p->user?->last_login_at;
                    if ($lastLogin) {
                        $diffHours = $lastLogin->diffInHours(now());
                        if ($diffHours < 1) {
                            $activityLabel = 'Online now';
                            $activityColor = 'bg-green-500';
                            $activityTextColor = 'text-green-700';
                        } elseif ($diffHours < 24) {
                            $activityLabel = 'Today';
                            $activityColor = 'bg-green-400';
                            $activityTextColor = 'text-green-600';
                        } elseif ($diffHours < 72) {
                            $activityLabel = (int) $lastLogin->diffInDays(now()) . 'd ago';
                            $activityColor = 'bg-yellow-400';
                            $activityTextColor = 'text-yellow-700';
                        } elseif ($diffHours < 168) {
                            $activityLabel = (int) $lastLogin->diffInDays(now()) . 'd ago';
                            $activityColor = 'bg-orange-400';
                            $activityTextColor = 'text-orange-700';
                        } else {
                            $activityLabel = (int) $lastLogin->diffInDays(now()) . 'd ago';
                            $activityColor = 'bg-gray-300';
                            $activityTextColor = 'text-gray-500';
                        }
                    } else {
                        $activityLabel = null;
                        $activityColor = '';
                        $activityTextColor = '';
                    }
                @endphp
                @if(!$isGuest && $activityLabel)
                    <span class="inline-flex items-center gap-1 text-[10px] {{ $activityTextColor }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $activityColor }}"></span>
                        {{ $activityLabel }}
                    </span>
                @else
                    <span></span>
                @endif
                <span class="text-[10px] text-gray-400">{{ $p->created_at?->format('d M Y') }}</span>
            </div>
        </div>
    </a>
</div>
@endif

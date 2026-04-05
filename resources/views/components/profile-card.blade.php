@props(['profile', 'matchScore' => null, 'matchBadge' => null])

@php
    $p = $profile;
    $isGuest = !auth()->check();
    $desc = collect([
        $p->age ? $p->age . ' Yrs' : null,
        $p->height,
        $p->complexion,
        $p->marital_status,
        $p->religiousInfo?->religion,
        $p->religiousInfo?->denomination ?? $p->religiousInfo?->caste,
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

    // Photo privacy logic
    $privacyLevel = $p->photoPrivacySetting?->privacy_level ?? 'visible_to_all';
    $hasPhoto = (bool) $p->primaryPhoto;
    $showPhoto = true; // default: show photo
    $photoOverlay = null; // overlay message

    if (!$isGuest && !($p->id === auth()->user()->profile->id)) {
        if (!$hasPhoto) {
            // No photo uploaded → show placeholder with "Request Photo"
            $showPhoto = false;
            $photoOverlay = 'request_photo';
        } elseif ($privacyLevel === 'hidden') {
            // Photo hidden → blur + "Send View Request"
            $showPhoto = false;
            $photoOverlay = 'hidden';
        } elseif ($privacyLevel === 'interest_accepted') {
            // Check if interest accepted between us
            static $acceptedPartnerIds = null;
            if ($acceptedPartnerIds === null) {
                $myId = auth()->user()->profile->id;
                $acceptedPartnerIds = \App\Models\Interest::where('status', 'accepted')
                    ->where(fn($q) => $q->where('sender_profile_id', $myId)->orWhere('receiver_profile_id', $myId))
                    ->get()
                    ->map(fn($i) => $i->sender_profile_id === $myId ? $i->receiver_profile_id : $i->sender_profile_id)
                    ->toArray();
            }
            if (!in_array($p->id, $acceptedPartnerIds)) {
                $showPhoto = false;
                $photoOverlay = 'after_acceptance';
            }
        }
    }
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
                {{-- Blurred photo with "Photo is hidden" --}}
                <img src="{{ $p->primaryPhoto->full_url }}" alt="{{ $p->matri_id }}"
                    class="w-full h-full object-cover" style="filter: blur(20px); transform: scale(1.1);" loading="lazy">
                <div class="absolute inset-0 flex flex-col items-center justify-center text-center p-4">
                    <svg class="w-8 h-8 text-gray-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/></svg>
                    <p class="text-xs font-semibold text-gray-700">This photo is hidden</p>
                    <span class="mt-2 inline-block px-3 py-1 text-[10px] font-bold text-(--color-primary) bg-white rounded-full shadow-sm">SEND VIEW REQUEST</span>
                </div>
            @elseif($photoOverlay === 'after_acceptance' && $hasPhoto)
                {{-- Blurred photo with "Visible after acceptance" --}}
                <img src="{{ $p->primaryPhoto->full_url }}" alt="{{ $p->matri_id }}"
                    class="w-full h-full object-cover" style="filter: blur(20px); transform: scale(1.1);" loading="lazy">
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
            <div class="flex items-center gap-1.5">
                <p class="text-sm font-semibold text-(--color-primary) group-hover:underline">{{ $p->matri_id }}</p>
                @if(!$isGuest && $p->user?->isPremium())
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold bg-(--color-primary) text-white leading-none">Premium</span>
                @endif
            </div>
            <p class="text-xs text-gray-600 mt-1 line-clamp-3 min-h-[3rem]">{{ $desc ?: 'Profile details not available' }}</p>
            <p class="text-[10px] text-gray-400 mt-2">Joined {{ $p->created_at?->format('d M Y') }}</p>
        </div>
    </a>
</div>

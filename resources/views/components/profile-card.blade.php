@props(['profile', 'matchScore' => null, 'matchBadge' => null])

@php
    $p = $profile;
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
    if ($shortlistedIds === null && auth()->check()) {
        $shortlistedIds = \App\Models\Shortlist::where('profile_id', auth()->user()->profile->id)->pluck('shortlisted_profile_id')->toArray();
    }
    $isShortlisted = $shortlistedIds !== null && in_array($p->id, $shortlistedIds);
@endphp

<div class="relative rounded-lg border border-gray-200 overflow-hidden hover:shadow-md hover:border-(--color-primary)/30 transition-all group bg-white">
    {{-- Shortlist heart --}}
    <form method="POST" action="{{ route('shortlist.toggle', $p) }}" class="absolute top-2 right-2 z-10" @click.stop>
        @csrf
        <button type="submit" class="p-1.5 rounded-full {{ $isShortlisted ? 'text-pink-500' : 'text-white/80 hover:text-pink-400' }} transition-colors" style="background: rgba(0,0,0,0.3);">
            <svg class="w-4 h-4" fill="{{ $isShortlisted ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/>
            </svg>
        </button>
    </form>

    <a href="{{ route('profile.view', $p) }}" class="block">
        {{-- Photo --}}
        <div class="aspect-[3/4] bg-gray-100 relative overflow-hidden">
            @if($p->primaryPhoto)
                <img src="{{ $p->primaryPhoto->full_url }}" alt="{{ $p->matri_id }}"
                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" loading="lazy">
            @else
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
        </div>

        {{-- Details --}}
        <div class="p-3">
            <p class="text-sm font-semibold text-(--color-primary) group-hover:underline">{{ $p->matri_id }}</p>
            <p class="text-xs text-gray-600 mt-1 line-clamp-3 min-h-[3rem]">{{ $desc ?: 'Profile details not available' }}</p>
            <p class="text-[10px] text-gray-400 mt-2">Joined {{ $p->created_at?->format('d M Y') }}</p>
        </div>
    </a>
</div>

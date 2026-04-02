@props(['profile'])

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
@endphp

<a href="{{ route('profile.view', $p) }}" class="block rounded-lg border border-gray-200 overflow-hidden hover:shadow-md hover:border-(--color-primary)/30 transition-all group bg-white">
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
    </div>

    {{-- Details --}}
    <div class="p-3">
        <p class="text-sm font-semibold text-(--color-primary) group-hover:underline">{{ $p->matri_id }}</p>
        <p class="text-xs text-gray-600 mt-1 line-clamp-3 min-h-[3rem]">{{ $desc ?: 'Profile details not available' }}</p>
        <p class="text-[10px] text-gray-400 mt-2">Joined {{ $p->created_at?->format('d M Y') }}</p>
    </div>
</a>

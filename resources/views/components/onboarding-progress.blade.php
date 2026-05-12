@props(['step' => 1])

@php
    // Onboarding funnel: 4 data-entry steps + 1 photo step (added 2026-04-30).
    // Step 5 ("Manage Photos") points at /manage-photos and is reached
    // automatically after Lifestyle saves (see OnboardingController::storeLifestyle).
    // The /manage-photos page renders this same progress bar when the user
    // arrives with ?from=onboarding or while the session marker is set
    // (see PhotoController::index).
    $steps = [
        1 => 'Additional Info',
        2 => 'More Details',
        3 => 'Preferences',
        4 => 'Lifestyle',
        5 => 'Manage Photos',
    ];
@endphp

<div class="flex items-center justify-center mb-8 px-2 overflow-x-auto">
    @foreach($steps as $num => $label)
        <div class="flex items-center shrink-0">
            <div class="flex flex-col items-center">
                <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-full flex items-center justify-center text-xs sm:text-sm font-semibold border-2 transition-colors
                    {{ $num < $step ? 'bg-(--color-primary) border-(--color-primary) text-white' : '' }}
                    {{ $num === $step ? 'bg-(--color-primary) border-(--color-primary) text-white ring-4 ring-(--color-primary)/20' : '' }}
                    {{ $num > $step ? 'bg-white border-gray-300 text-gray-400' : '' }}">
                    @if($num < $step)
                        <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    @else
                        {{ $num }}
                    @endif
                </div>
                <span class="text-[10px] sm:text-xs mt-1 font-medium whitespace-nowrap {{ $num <= $step ? 'text-(--color-primary)' : 'text-gray-400' }}">{{ $label }}</span>
            </div>
            @if($num < count($steps))
                <div class="w-12 sm:w-16 md:w-20 h-0.5 mx-1 mt-[-18px] {{ $num < $step ? 'bg-(--color-primary)' : 'bg-gray-300' }}"></div>
            @endif
        </div>
    @endforeach
</div>

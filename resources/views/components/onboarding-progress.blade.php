@props(['step' => 1])

@php
    // Onboarding funnel: 4 data-entry steps + 1 photo step. Order (Oct 2026):
    // everything about YOU first (About You, Contact, Lifestyle), then the
    // PARTNER you're looking for, then photos — Lifestyle used to come right
    // after Partner, so members answered diet / drinking for their partner.
    // Step 5 lives at /onboarding/photo (OnboardingController::showPhoto).
    $steps = [
        1 => 'About You',
        2 => 'Contact',
        3 => 'Lifestyle',
        4 => 'Partner',
        5 => 'Photos',
    ];
@endphp

{{-- Phones: five steps with 48px connectors didn't fit, and centring an
     overflowing row cut "About You" off on the left. Shorter connectors,
     and "safe" centring falls back to the start if it ever overflows. --}}
<style>
    .ob-progress { justify-content: safe center; }
    @media (max-width: 639px) { .ob-progress .ob-line { width: 22px; margin-left: 2px; margin-right: 2px; } }
</style>
<div class="ob-progress flex items-center justify-center mb-8 px-2 overflow-x-auto">
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
                <div class="ob-line w-12 sm:w-16 md:w-20 h-0.5 mx-1 mt-[-18px] {{ $num < $step ? 'bg-(--color-primary)' : 'bg-gray-300' }}"></div>
            @endif
        </div>
    @endforeach
</div>

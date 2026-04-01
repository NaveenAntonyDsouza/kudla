@props(['current' => 1])

@php
    $steps = [
        1 => 'Step 1',
        2 => 'Step 2',
        3 => 'Step 3',
        4 => 'Step 4',
        5 => 'Final Step',
    ];
@endphp

<div class="flex items-center justify-center mb-8 overflow-x-auto px-2">
    @foreach($steps as $num => $label)
        <div class="flex items-center shrink-0">
            {{-- Circle --}}
            <div class="flex flex-col items-center">
                <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-full flex items-center justify-center text-xs sm:text-sm font-semibold border-2 transition-colors
                    {{ $num < $current ? 'bg-(--color-primary) border-(--color-primary) text-white' : '' }}
                    {{ $num === $current ? 'bg-(--color-primary) border-(--color-primary) text-white ring-4 ring-(--color-primary)/20' : '' }}
                    {{ $num > $current ? 'bg-white border-gray-300 text-gray-400' : '' }}">
                    @if($num < $current)
                        <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    @else
                        {{ $num }}
                    @endif
                </div>
                <span class="text-[10px] sm:text-xs mt-1 font-medium whitespace-nowrap {{ $num <= $current ? 'text-(--color-primary)' : 'text-gray-400' }}">{{ $label }}</span>
            </div>

            {{-- Connecting line (not after last step) --}}
            @if($num < 5)
                <div class="w-6 sm:w-12 md:w-16 h-0.5 mx-0.5 sm:mx-1 mt-[-18px] {{ $num < $current ? 'bg-(--color-primary)' : 'bg-gray-300' }}"></div>
            @endif
        </div>
    @endforeach
</div>

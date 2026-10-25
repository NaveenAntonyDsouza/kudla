{{-- Sort menu for search results — visitors and members (Oct 2026). No age sorts: the age filter covers that, and other portals offer relevance / newest / recently active. --}}
@php
    $sortOptions = [
        'relevance' => 'Relevance',
        'newest' => 'Newest First',
        'recently_active' => 'Recently Active',
    ];
    $currentSort = array_key_exists($currentSort ?? '', $sortOptions) ? $currentSort : 'relevance';
@endphp
<div class="relative" x-data="{ open: false }" @click.away="open = false">
    <button @click="open = !open" type="button"
        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h6m0 0L6.5 4.5M9 7L6.5 9.5M21 17h-6m0 0l2.5 2.5M15 17l2.5-2.5M3 17h2m16-10h-2"/></svg>
        <span>{{ $sortOptions[$currentSort] }}</span>
        <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div x-show="open" x-cloak x-transition class="absolute right-0 mt-1 w-48 bg-white border border-gray-200 rounded-lg shadow-lg z-20 py-1">
        @foreach($sortOptions as $value => $label)
            <a href="{{ request()->fullUrlWithQuery(['sort' => $value, 'page' => 1]) }}"
                class="block px-4 py-2 text-sm {{ $currentSort === $value ? 'text-(--color-primary) bg-(--color-primary-light) font-semibold' : 'text-gray-700 hover:bg-gray-50' }} transition-colors">
                {{ $label }}
            </a>
        @endforeach
    </div>
</div>

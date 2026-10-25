{{--
    One search navigation for visitors and members (Oct 2026): the same items
    and the same addresses; each page shows the visitor or member version.
    $current: quick | advanced | keyword | byid
--}}
@php
    $searchNav = [
        'quick' => ['Quick Search', 'Quick', route('search.quick'), 'M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z'],
        'advanced' => ['Advanced Search', 'Advanced', route('search.advance'), 'M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75'],
        'keyword' => ['Keyword Search', 'Keyword', route('search.keyword'), 'M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z'],
        'byid' => ['Search by ID', 'By ID', route('search.byid'), 'M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 1.875 0 11-3.75 0 1.875 1.875 0 013.75 0zm1.294 6.336a6.721 6.721 0 01-3.17.789 6.721 6.721 0 01-3.168-.789 3.376 3.376 0 016.338 0z'],
        'saved' => ['Saved Searches', 'Saved', route('saved-searches.index'), 'M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0z'],
        'discover' => ['Discover Profiles', 'Discover', route('discover.hub'), 'M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418'],
    ];
    $current ??= 'quick';
@endphp
<style>
    .srch-nav-phone { display: flex; gap: 8px; overflow-x: auto; margin: 0 -16px 16px; padding: 0 16px 6px; }
    .srch-nav-phone a { flex: none; padding: 8px 16px; border-radius: 999px; font-size: 14px; font-weight: 500; white-space: nowrap; background: #f3f4f6; color: #374151; }
    .srch-nav-phone a.on { background: var(--color-primary, #8B1D91); color: #fff; }
    .srch-nav-side { display: none; }
    @media (min-width: 1024px) { .srch-nav-phone { display: none; } .srch-nav-side { display: block; width: 12rem; flex: none; } }
</style>
<nav class="srch-nav-phone" aria-label="Search">
    @foreach($searchNav as $key => [$label, $short, $url])
        <a href="{{ $url }}" class="{{ $key === $current ? 'on' : '' }}" @if($key === $current) aria-current="page" @endif>{{ $short }}</a>
    @endforeach
</nav>
<nav class="srch-nav-side" aria-label="Search">
    <div class="sticky top-24 space-y-1">
        <h2 class="text-base font-semibold text-gray-900 mb-3">Search</h2>
        @foreach($searchNav as $key => [$label, $short, $url, $icon])
            @if($key === 'saved')<div class="border-t border-gray-100" style="margin: 8px 0;"></div>@endif
            <a href="{{ $url }}" @if($key === $current) aria-current="page" @endif
                class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition-colors text-left {{ $key === $current ? 'bg-(--color-primary) text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}"/></svg>
                {{ $label }}
            </a>
        @endforeach
    </div>
</nav>

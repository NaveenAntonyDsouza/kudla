<x-layouts.app :title="$seo['title']" :metaDescription="$seo['description']" :robots="$chipRows->isEmpty() ? 'noindex, follow' : null">
    {{-- Discover hub (Oct 2026): every section a row of chips with its number of
         profiles. Scoped styles: the live sites don't rebuild the Tailwind file, so
         new utility classes wouldn't exist there. --}}
    <style>
        .dh-section { margin-top: 1.75rem; }
        .dh-section h2 { margin: 0 0 .6rem; font-size: .75rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: #6b7280; }
        .dh-chips { display: flex; flex-wrap: wrap; gap: .5rem; }
        .dh-chip { display: inline-flex; align-items: center; gap: .4rem; padding: .4rem .8rem; background: #fff; border: 1px solid #e5e7eb; border-radius: 9999px; font-size: .85rem; color: #1f2937; text-decoration: none; transition: border-color .15s, background-color .15s, color .15s; }
        .dh-chip:hover, .dh-chip:focus-visible { border-color: var(--color-primary); background: var(--color-primary-light); color: var(--color-primary); outline: none; }
        .dh-chip-n { font-size: .75rem; color: #6b7280; }
        .dh-chip-all { border-style: dashed; font-weight: 600; color: var(--color-primary); }
    </style>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- Breadcrumb --}}
        <p class="text-sm text-gray-500 mb-6">
            <a href="{{ route('dashboard') }}" class="hover:text-(--color-primary)">My Home</a>
            <span class="mx-1">/</span>
            <a href="{{ route('search.index') }}" class="hover:text-(--color-primary)">Search</a>
            <span class="mx-1">/</span>
            <span class="text-gray-700 font-medium">Discover Profiles</span>
        </p>

        <h1 class="text-xl font-semibold text-gray-900">{{ $seo['heading'] }}</h1>
        <p class="text-sm text-gray-600 mt-1">{{ $seo['intro'] }}</p>

        @if($chipRows->isEmpty())
            <p class="text-gray-600 py-8">Profiles will appear here as members join.</p>
        @endif

        @include('discover._hub-groups', ['chipRows' => $chipRows])
    </div>
</x-layouts.app>

<x-layouts.app title="{{ $title }}">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- Breadcrumb --}}
        <p class="text-sm text-gray-500 mb-6">
            <a href="{{ route('dashboard') }}" class="hover:text-(--color-primary)">My Home</a>
            <span class="mx-1">/</span>
            <a href="{{ route('search.index') }}" class="hover:text-(--color-primary)">Search</a>
            <span class="mx-1">/</span>
            <a href="{{ route('discover.hub') }}" class="hover:text-(--color-primary)">Discover Profiles</a>
            <span class="mx-1">/</span>
            @if($slug)
                <a href="{{ route('discover.category', $category) }}" class="hover:text-(--color-primary)">{{ $config['label'] }}</a>
                <span class="mx-1">/</span>
            @endif
            <span class="text-gray-700 font-medium">{{ $title }}</span>
        </p>

        {{-- Header --}}
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
            <h1 class="text-lg font-semibold text-gray-900">
                <span class="text-(--color-primary)">{{ $results->total() }}</span> {{ Str::plural('Profile', $results->total()) }} found
            </h1>
            <div class="flex items-center gap-3">
                @if($slug)
                    <a href="{{ route('discover.category', $category) }}"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-(--color-primary) border border-(--color-primary) rounded-lg hover:bg-(--color-primary-light) transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
                        {{ $config['label'] }}
                    </a>
                @else
                    <a href="{{ route('discover.hub') }}"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-(--color-primary) border border-(--color-primary) rounded-lg hover:bg-(--color-primary-light) transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
                        All Categories
                    </a>
                @endif
            </div>
        </div>

        {{-- Results Grid --}}
        @if($results->count() > 0)
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                @foreach($results as $p)
                    <x-profile-card :profile="$p" />
                @endforeach
            </div>

            {{-- Pagination --}}
            <div class="mt-8">
                {{ $results->links() }}
            </div>
        @else
            <div class="bg-white rounded-lg border border-gray-200 p-12 text-center">
                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.182 16.318A4.486 4.486 0 0012.016 15a4.486 4.486 0 00-3.198 1.318M21 12a9 9 0 11-18 0 9 9 0 0118 0zM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75 9.168 9 9.375 9s.375.336.375.75zm-.375 0h.008v.015h-.008V9.75zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75zm-.375 0h.008v.015h-.008V9.75z"/>
                </svg>
                <p class="text-gray-600 font-medium">No profiles found in this category</p>
                <p class="text-sm text-gray-400 mt-2">Check back later as more profiles are added.</p>
                <a href="{{ route('discover.hub') }}" class="inline-flex items-center gap-2 mt-4 px-6 py-2.5 text-sm font-semibold text-white bg-(--color-primary) hover:bg-(--color-primary-hover) rounded-lg transition-colors">
                    Browse Other Categories
                </a>
            </div>
        @endif
    </div>
</x-layouts.app>

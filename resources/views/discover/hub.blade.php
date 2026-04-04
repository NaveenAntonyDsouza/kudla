<x-layouts.app title="Discover Profiles">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- Breadcrumb --}}
        <p class="text-sm text-gray-500 mb-6">
            <a href="{{ route('dashboard') }}" class="hover:text-(--color-primary)">My Home</a>
            <span class="mx-1">/</span>
            <a href="{{ route('search.index') }}" class="hover:text-(--color-primary)">Search</a>
            <span class="mx-1">/</span>
            <span class="text-gray-700 font-medium">Discover Profiles</span>
        </p>

        <h1 class="text-xl font-semibold text-gray-900 mb-6">Discover Profiles</h1>

        {{-- Category Grid --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 max-w-2xl">
            @foreach($categories as $cat)
                <a href="{{ route($cat['has_subcategories'] ? 'discover.category' : 'discover.category', $cat['slug']) }}"
                    class="flex items-center justify-center px-6 py-3.5 text-sm font-medium text-gray-700 border border-gray-300 rounded-lg hover:border-(--color-primary) hover:text-(--color-primary) hover:bg-(--color-primary-light) transition-colors text-center">
                    {{ $cat['label'] }}
                </a>
            @endforeach
        </div>
    </div>
</x-layouts.app>

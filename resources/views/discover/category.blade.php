<x-layouts.app title="{{ $config['label'] }}">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- Breadcrumb --}}
        <p class="text-sm text-gray-500 mb-6">
            <a href="{{ route('dashboard') }}" class="hover:text-(--color-primary)">My Home</a>
            <span class="mx-1">/</span>
            <a href="{{ route('search.index') }}" class="hover:text-(--color-primary)">Search</a>
            <span class="mx-1">/</span>
            <a href="{{ route('discover.hub') }}" class="hover:text-(--color-primary)">Discover Profiles</a>
            <span class="mx-1">/</span>
            <span class="text-gray-700 font-medium">{{ $config['label'] }}</span>
        </p>

        <div class="max-w-2xl" x-data="{ search: '' }">

            {{-- Title + Search --}}
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-xl font-semibold text-gray-900">{{ $config['label'] }}</h1>
                @if($showSearch)
                    <div class="relative w-48">
                        <input type="text" x-model="search" placeholder="Search..."
                            class="w-full pl-3 pr-8 py-2 text-sm border border-gray-300 rounded-lg focus:ring-(--color-primary) focus:border-(--color-primary)">
                        <svg class="w-4 h-4 text-gray-400 absolute right-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                        </svg>
                    </div>
                @endif
            </div>

            {{-- Subcategory List --}}
            <div class="bg-white rounded-lg border border-gray-200 divide-y divide-gray-100">
                @foreach($subcategories as $sub)
                    <a href="{{ route('discover.results', [$category, $sub['slug']]) }}"
                        class="block px-5 py-3.5 text-sm text-gray-700 hover:bg-gray-50 hover:text-(--color-primary) transition-colors"
                        x-show="!search || '{{ strtolower($sub['label']) }}'.includes(search.toLowerCase())"
                        >
                        {{ $sub['label'] }}
                    </a>
                @endforeach
            </div>

            {{-- Other Matrimonial Directories --}}
            @if($otherCategories->count() > 0)
                <div class="mt-10">
                    <h2 class="text-base font-semibold text-gray-900 mb-4">Other Matrimonial Directories</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @foreach($otherCategories as $other)
                            <a href="{{ route('discover.category', $other['slug']) }}"
                                class="flex items-center justify-center px-4 py-2.5 text-sm font-medium text-gray-600 border border-gray-300 rounded-lg hover:border-(--color-primary) hover:text-(--color-primary) hover:bg-(--color-primary-light) transition-colors">
                                {{ $other['label'] }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-layouts.app>

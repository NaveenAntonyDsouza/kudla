<x-layouts.app title="Search Profiles">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <p class="text-sm text-gray-500 mb-6">
            <a href="/" class="hover:text-(--color-primary)">Home</a>
            <span class="mx-1">/</span>
            <span class="text-gray-700 font-medium">Search</span>
        </p>

        {{-- Search Type Tabs --}}
        <div class="flex items-center gap-6 border-b border-gray-200 mb-6">
            <a href="{{ route('search.quick') }}"
                class="pb-3 text-sm font-{{ $activeTab === 'partner' ? 'semibold' : 'medium' }} border-b-2 {{ $activeTab === 'partner' ? 'border-(--color-primary) text-(--color-primary)' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                Partner Search
            </a>
            <a href="{{ route('search.keyword') }}"
                class="pb-3 text-sm font-{{ $activeTab === 'keyword' ? 'semibold' : 'medium' }} border-b-2 {{ $activeTab === 'keyword' ? 'border-(--color-primary) text-(--color-primary)' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                Keyword Search
            </a>
            <a href="{{ route('search.byid') }}"
                class="pb-3 text-sm font-{{ $activeTab === 'byid' ? 'semibold' : 'medium' }} border-b-2 {{ $activeTab === 'byid' ? 'border-(--color-primary) text-(--color-primary)' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                Search by ID
            </a>
        </div>

        {{-- Login CTA --}}
        <div class="bg-(--color-primary-light) rounded-lg p-4 mb-6">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                <p class="text-sm text-gray-700"><a href="/register" class="font-semibold text-(--color-primary) hover:underline">Register free</a> or <a href="/login" class="font-semibold text-(--color-primary) hover:underline">login</a> to use advanced search filters and view full profiles.</p>
            </div>
        </div>

        {{-- Results --}}
        <h2 class="text-lg font-semibold text-gray-900 mb-4">
            <span class="text-(--color-primary)">{{ $results->total() }}</span> {{ Str::plural('Profile', $results->total()) }} found
        </h2>

        @if($results->count() > 0)
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                @foreach($results as $p)
                    <x-profile-card :profile="$p" />
                @endforeach
            </div>

            <div class="mt-8">
                {{ $results->links() }}
            </div>
        @else
            <div class="bg-white rounded-lg border border-gray-200 p-12 text-center">
                <p class="text-gray-600 font-medium">No profiles found</p>
                <p class="text-sm text-gray-400 mt-2">Register to create your profile and find matches.</p>
            </div>
        @endif
    </div>
</x-layouts.app>

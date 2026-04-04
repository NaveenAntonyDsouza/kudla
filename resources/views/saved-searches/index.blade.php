<x-layouts.app title="Saved Searches">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <p class="text-sm text-gray-500 mb-6">
            <a href="{{ route('dashboard') }}" class="hover:text-(--color-primary)">My Home</a>
            <span class="mx-1">/</span>
            <a href="{{ route('search.index') }}" class="hover:text-(--color-primary)">Search</a>
            <span class="mx-1">/</span>
            <span class="text-gray-700 font-medium">Saved Searches</span>
        </p>

        @if(session('success'))
            <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg">
                <p class="text-sm text-green-700 font-medium">{{ session('success') }}</p>
            </div>
        @endif

        <h1 class="text-lg font-semibold text-gray-900 mb-6">Saved Searches</h1>

        @if($savedSearches->count() > 0)
            <div class="space-y-3">
                @foreach($savedSearches as $search)
                    <div class="bg-white rounded-lg border border-gray-200 p-4 flex items-center justify-between">
                        <div class="min-w-0">
                            <h3 class="text-sm font-semibold text-gray-900">{{ $search->search_name }}</h3>
                            <p class="text-xs text-gray-500 mt-1">
                                @php
                                    $criteria = $search->criteria ?? [];
                                    $summary = collect([
                                        isset($criteria['age_from']) ? "Age: {$criteria['age_from']}-{$criteria['age_to']}" : null,
                                        isset($criteria['religion']) ? "Religion: " . (is_array($criteria['religion']) ? implode(', ', $criteria['religion']) : $criteria['religion']) : null,
                                        isset($criteria['mother_tongue']) ? "Tongue: " . (is_array($criteria['mother_tongue']) ? implode(', ', $criteria['mother_tongue']) : $criteria['mother_tongue']) : null,
                                    ])->filter()->implode(' | ');
                                @endphp
                                {{ $summary ?: 'Custom filters' }}
                            </p>
                            <p class="text-[10px] text-gray-400 mt-1">Saved {{ $search->created_at->format('d M Y') }}</p>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <a href="{{ route('saved-searches.load', $search) }}"
                                class="px-3 py-1.5 text-xs font-medium text-white bg-(--color-primary) hover:bg-(--color-primary-hover) rounded-lg transition-colors">
                                Load Search
                            </a>
                            <form method="POST" action="{{ route('saved-searches.destroy', $search) }}" onsubmit="return confirm('Delete this saved search?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-3 py-1.5 text-xs font-medium text-gray-500 border border-gray-300 hover:text-red-600 hover:border-red-300 rounded-lg transition-colors">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="bg-white rounded-lg border border-gray-200 p-12 text-center">
                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0z"/></svg>
                <p class="text-gray-600 font-medium">No saved searches yet</p>
                <p class="text-sm text-gray-400 mt-2">Save your search filters from the search page for quick access later.</p>
                <a href="{{ route('search.index') }}" class="inline-flex items-center gap-2 mt-4 px-6 py-2.5 text-sm font-semibold text-white bg-(--color-primary) hover:bg-(--color-primary-hover) rounded-lg transition-colors">
                    Go to Search
                </a>
            </div>
        @endif
    </div>
</x-layouts.app>

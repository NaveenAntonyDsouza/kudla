<x-layouts.app title="My Shortlist">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <p class="text-sm text-gray-500 mb-6">
            <a href="{{ route('dashboard') }}" class="hover:text-(--color-primary)">My Home</a>
            <span class="mx-1">/</span>
            <span class="text-gray-700 font-medium">My Shortlist</span>
        </p>

        @if(session('success'))
            <div class="mb-6 p-3 bg-green-50 border border-green-200 rounded-lg">
                <p class="text-sm text-green-700 font-medium">{{ session('success') }}</p>
            </div>
        @endif

        <h1 class="text-xl font-semibold text-gray-900 mb-6">
            My Shortlisted Profiles <span class="text-gray-400 font-normal">({{ $shortlisted->total() }})</span>
        </h1>

        @if($shortlisted->count() > 0)
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                @foreach($shortlisted as $item)
                    <x-profile-card :profile="$item->shortlistedProfile" />
                @endforeach
            </div>
            <div class="mt-8">{{ $shortlisted->links() }}</div>
        @else
            <div class="bg-white rounded-lg border border-gray-200 p-12 text-center">
                <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/>
                </svg>
                <p class="text-sm text-gray-500">No shortlisted profiles yet.</p>
                <p class="text-xs text-gray-400 mt-1">Tap the star on any profile to add it to your shortlist.</p>
                <a href="{{ route('search.index') }}" class="inline-block mt-4 text-sm text-(--color-primary) hover:underline font-medium">Search Profiles</a>
            </div>
        @endif
    </div>
</x-layouts.app>

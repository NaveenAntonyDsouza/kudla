<x-layouts.app title="Mutual Matches">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- Breadcrumb --}}
        <p class="text-sm text-gray-500 mb-6">
            <a href="{{ route('dashboard') }}" class="hover:text-(--color-primary)">My Home</a>
            <span class="mx-1">/</span>
            <span class="text-gray-700 font-medium">Mutual Matches</span>
        </p>

        {{-- Tabs --}}
        <div class="flex items-center gap-6 border-b border-gray-200 mb-6">
            <a href="{{ route('matches.index') }}"
                class="pb-3 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent">
                My Matches
            </a>
            <a href="{{ route('matches.mutual') }}"
                class="pb-3 text-sm font-semibold border-b-2 border-(--color-primary) text-(--color-primary)">
                Mutual Matches
            </a>
        </div>

        {{-- Subtitle --}}
        <p class="text-sm text-gray-500 mb-6">Profiles where both of you match each other's partner preferences.</p>

        @if(!$hasPreferences)
            {{-- No preferences set --}}
            <div class="bg-white rounded-lg border border-gray-200 p-12 text-center">
                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                </svg>
                <p class="text-gray-600 font-medium">Set your Partner Preferences first</p>
                <p class="text-sm text-gray-400 mt-2">We need to know what you're looking for to find mutual matches.</p>
                <a href="{{ route('onboarding.preferences') }}" class="inline-flex items-center gap-2 mt-4 px-6 py-2.5 text-sm font-semibold text-white bg-(--color-primary) hover:bg-(--color-primary-hover) rounded-lg transition-colors">
                    Set Partner Preferences
                </a>
            </div>
        @elseif($matches->count() > 0)
            {{-- Header --}}
            <h1 class="text-lg font-semibold text-gray-900 mb-6">
                <span class="text-(--color-primary)">{{ $matches->total() }}</span> Mutual {{ Str::plural('Match', $matches->total()) }}
            </h1>

            {{-- Results Grid --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                @foreach($matches as $p)
                    <x-profile-card :profile="$p" :matchScore="$p->match_score" :matchBadge="$p->match_badge" />
                @endforeach
            </div>

            {{-- Pagination --}}
            <div class="mt-8">
                {{ $matches->links() }}
            </div>
        @else
            {{-- No mutual matches --}}
            <div class="bg-white rounded-lg border border-gray-200 p-12 text-center">
                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.182 16.318A4.486 4.486 0 0012.016 15a4.486 4.486 0 00-3.198 1.318M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-gray-600 font-medium">No mutual matches yet</p>
                <p class="text-sm text-gray-400 mt-2">As more users join and set their preferences, mutual matches will appear.</p>
            </div>
        @endif
    </div>
</x-layouts.app>

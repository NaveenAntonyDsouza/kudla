{{-- Another member's profile the central check refuses (hidden, blocked, deactivated, or limited
     under Settings → "Show my profile to"). Never says which, so nobody learns they were blocked. --}}
<x-layouts.app title="Profile not available" robots="noindex, nofollow">
    <div class="max-w-xl mx-auto px-4 sm:px-6 py-16 text-center">
        <div class="mx-auto w-14 h-14 rounded-full bg-gray-100 text-gray-400 flex items-center justify-center mb-5">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.25a8.25 8.25 0 0115 0"/></svg>
        </div>
        <h1 class="text-xl font-semibold text-gray-900">This profile is not available</h1>
        <p class="mt-2 text-sm text-gray-600">It may be hidden, or no longer active.</p>
        <div class="mt-6 flex flex-wrap justify-center gap-3">
            <a href="{{ route('matches.index') }}" class="inline-flex items-center px-5 py-2.5 rounded-lg bg-(--color-primary) text-white text-sm font-semibold hover:opacity-90">See my matches</a>
            <a href="{{ route('dashboard') }}" class="inline-flex items-center px-5 py-2.5 rounded-lg border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50">My Home</a>
        </div>
    </div>
</x-layouts.app>

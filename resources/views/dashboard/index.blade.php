<x-layouts.app title="Dashboard">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <h1 class="text-2xl font-serif font-bold text-gray-900 mb-4">Welcome, {{ auth()->user()->name }}!</h1>
        <p class="text-gray-600">Your Profile ID: <strong class="text-(--color-primary)">{{ $profile->matri_id ?? 'N/A' }}</strong></p>
        <p class="text-gray-600 mt-2">Profile completion: {{ $profile->profile_completion_pct ?? 0 }}%</p>
        <div class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
            <p class="text-sm text-yellow-800">Dashboard is under construction. Full dashboard will be built in Phase 4.</p>
        </div>
    </div>
</x-layouts.app>

<x-layouts.app title="Member Demographics">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <h1 class="text-3xl font-serif font-bold text-gray-900 mb-8">Member Demographics</h1>

        @php
            $totalProfiles = \App\Models\Profile::where('is_active', true)->count();
            $maleCount = \App\Models\Profile::where('is_active', true)->where('gender', 'male')->count();
            $femaleCount = \App\Models\Profile::where('is_active', true)->where('gender', 'female')->count();

            $religionCounts = \App\Models\Profile::where('is_active', true)
                ->join('religious_info', 'profiles.id', '=', 'religious_info.profile_id')
                ->selectRaw('religious_info.religion, COUNT(*) as count')
                ->groupBy('religious_info.religion')
                ->orderByDesc('count')
                ->get();
        @endphp

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-10">
            <div class="bg-white rounded-lg border border-gray-200 p-6 text-center">
                <p class="text-3xl font-bold text-(--color-primary)">{{ number_format($totalProfiles) }}</p>
                <p class="text-sm text-gray-500 mt-1">Total Members</p>
            </div>
            <div class="bg-white rounded-lg border border-gray-200 p-6 text-center">
                <p class="text-3xl font-bold text-blue-600">{{ number_format($maleCount) }}</p>
                <p class="text-sm text-gray-500 mt-1">Male Members</p>
            </div>
            <div class="bg-white rounded-lg border border-gray-200 p-6 text-center">
                <p class="text-3xl font-bold text-pink-600">{{ number_format($femaleCount) }}</p>
                <p class="text-sm text-gray-500 mt-1">Female Members</p>
            </div>
        </div>

        @if($religionCounts->count() > 0)
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Members by Religion</h2>
            <div class="space-y-3">
                @foreach($religionCounts as $rc)
                    @php $pct = $totalProfiles > 0 ? round(($rc->count / $totalProfiles) * 100, 1) : 0; @endphp
                    <div>
                        <div class="flex items-center justify-between text-sm mb-1">
                            <span class="text-gray-700 font-medium">{{ $rc->religion ?? 'Not specified' }}</span>
                            <span class="text-gray-500">{{ $rc->count }} ({{ $pct }}%)</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2">
                            <div class="bg-(--color-primary) h-2 rounded-full" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</x-layouts.app>

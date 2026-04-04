<x-layouts.app title="Ignored Profiles">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <p class="text-sm text-gray-500 mb-6">
            <a href="{{ route('dashboard') }}" class="hover:text-(--color-primary)">My Home</a>
            <span class="mx-1">/</span>
            <span class="text-gray-700 font-medium">Ignored Profiles</span>
        </p>

        @if(session('success'))
            <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg">
                <p class="text-sm text-green-700 font-medium">{{ session('success') }}</p>
            </div>
        @endif

        <h1 class="text-lg font-semibold text-gray-900 mb-2">Ignored Profiles</h1>
        <p class="text-sm text-gray-500 mb-6">These profiles are hidden from your search results, matches, and dashboard.</p>

        @if($ignored->count() > 0)
            <div class="space-y-3">
                @foreach($ignored as $item)
                    @php $p = $item->ignoredProfile; @endphp
                    <div class="bg-white rounded-lg border border-gray-200 p-4 flex items-center gap-4">
                        <a href="{{ route('profile.view', $p) }}" class="shrink-0">
                            <div class="w-14 h-14 rounded-full overflow-hidden bg-gray-100">
                                @if($p->primaryPhoto)
                                    <img src="{{ $p->primaryPhoto->full_url }}" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center">
                                        <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0"/></svg>
                                    </div>
                                @endif
                            </div>
                        </a>
                        <div class="flex-1 min-w-0">
                            <a href="{{ route('profile.view', $p) }}" class="text-sm font-semibold text-(--color-primary) hover:underline">{{ $p->matri_id }}</a>
                            <p class="text-xs text-gray-600 truncate">{{ $p->full_name }} — {{ $p->age ? $p->age . ' Yrs' : '' }} {{ $p->religiousInfo?->religion }}</p>
                            <p class="text-[10px] text-gray-400 mt-0.5">Ignored {{ $item->created_at->format('d M Y') }}</p>
                        </div>
                        <form method="POST" action="{{ route('ignored.toggle', $p) }}">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 text-xs font-medium text-gray-600 border border-gray-300 hover:border-red-300 hover:text-red-600 rounded-lg transition-colors">
                                Remove
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
            <div class="mt-6">{{ $ignored->links() }}</div>
        @else
            <div class="bg-white rounded-lg border border-gray-200 p-12 text-center">
                <p class="text-gray-600 font-medium">No ignored profiles</p>
                <p class="text-sm text-gray-400 mt-2">Profiles you ignore will not appear in your search results.</p>
            </div>
        @endif
    </div>
</x-layouts.app>

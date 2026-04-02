<x-layouts.app title="Blocked Profiles">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <h1 class="text-xl font-semibold text-gray-900 mb-6">Blocked Profiles</h1>

        @if(session('success'))
            <div class="mb-6 p-3 bg-green-50 border border-green-200 rounded-lg">
                <p class="text-sm text-green-700 font-medium">{{ session('success') }}</p>
            </div>
        @endif

        @if($blocked->count() > 0)
            <div class="bg-white rounded-lg border border-gray-200 shadow-xs divide-y divide-gray-100">
                @foreach($blocked as $item)
                    <div class="flex items-center justify-between px-5 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-gray-100 overflow-hidden">
                                @if($item->blockedProfile?->primaryPhoto)
                                    <img src="{{ $item->blockedProfile->primaryPhoto->full_url }}" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center"><svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0"/></svg></div>
                                @endif
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900">{{ $item->blockedProfile?->matri_id }}</p>
                                <p class="text-xs text-gray-500">{{ $item->blockedProfile?->full_name }}</p>
                            </div>
                        </div>
                        <form method="POST" action="{{ route('unblock.profile', $item->blockedProfile) }}">
                            @csrf
                            <button type="submit" class="text-sm text-(--color-primary) hover:underline font-medium">Unblock</button>
                        </form>
                    </div>
                @endforeach
            </div>
            <div class="mt-6">{{ $blocked->links() }}</div>
        @else
            <div class="bg-white rounded-lg border border-gray-200 p-12 text-center">
                <p class="text-sm text-gray-500">No blocked profiles.</p>
            </div>
        @endif
    </div>
</x-layouts.app>

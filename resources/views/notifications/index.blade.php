<x-layouts.app title="Notifications">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-serif font-bold text-gray-900">Notifications</h1>
            <form method="POST" action="{{ route('notifications.readAll') }}">
                @csrf
                <button type="submit" class="text-sm text-(--color-primary) hover:underline font-medium">Mark All as Read</button>
            </form>
        </div>

        @if(session('success'))
            <div class="mb-6 p-3 bg-green-50 border border-green-200 rounded-lg">
                <p class="text-sm text-green-700 font-medium">{{ session('success') }}</p>
            </div>
        @endif

        <div class="bg-white rounded-lg border border-gray-200 shadow-xs overflow-hidden">
            @php $hasAny = $grouped['today']->count() + $grouped['yesterday']->count() + $grouped['previous']->count(); @endphp

            @if($hasAny > 0)
                @foreach(['today' => 'Today', 'yesterday' => 'Yesterday', 'previous' => 'Previous'] as $key => $label)
                    @if($grouped[$key]->count() > 0)
                        <p class="px-5 pt-4 pb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider bg-gray-50">{{ $label }}</p>
                        <div class="divide-y divide-gray-100">
                            @foreach($grouped[$key] as $notif)
                                <form method="POST" action="{{ route('notifications.read', $notif) }}">
                                    @csrf
                                    <button type="submit" class="w-full flex items-start gap-4 px-5 py-4 hover:bg-gray-50 transition-colors text-left {{ !$notif->is_read ? 'bg-(--color-primary-light)/20' : '' }}">
                                        <div class="w-10 h-10 rounded-full bg-gray-100 overflow-hidden shrink-0 mt-0.5">
                                            @if($notif->profile?->primaryPhoto)
                                                <img src="{{ $notif->profile->primaryPhoto->full_url }}" class="w-full h-full object-cover">
                                            @else
                                                <div class="w-full h-full flex items-center justify-center">
                                                    <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0"/></svg>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm {{ !$notif->is_read ? 'font-semibold text-gray-900' : 'font-medium text-gray-700' }}">{{ $notif->title }}</p>
                                            <p class="text-sm text-gray-600 mt-0.5">{{ $notif->message }}</p>
                                            <p class="text-xs text-gray-400 mt-1">{{ $notif->created_at->format('d/m/Y h:i A') }}</p>
                                        </div>
                                        @if(!$notif->is_read)
                                            <div class="w-2.5 h-2.5 rounded-full bg-(--color-primary) shrink-0 mt-2"></div>
                                        @endif
                                    </button>
                                </form>
                            @endforeach
                        </div>
                    @endif
                @endforeach
            @else
                <div class="p-12 text-center">
                    <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    <p class="text-sm text-gray-500">No notifications yet.</p>
                    <p class="text-xs text-gray-400 mt-1">When someone sends you an interest or responds, you'll see it here.</p>
                </div>
            @endif
        </div>
    </div>
</x-layouts.app>

<x-filament-panels::page>
    {{-- Overdue --}}
    @php $overdue = $this->getOverdueFollowUps(); @endphp
    <h3 class="text-lg font-semibold text-red-600 mb-3">Overdue ({{ $overdue->count() }})</h3>

    @if($overdue->isEmpty())
        <p class="text-sm text-gray-500 mb-6">No overdue follow-ups.</p>
    @else
        <div class="space-y-2 mb-6">
            @foreach($overdue as $note)
                <div class="bg-red-50 dark:bg-red-900/20 rounded-xl border border-red-200 dark:border-red-700 p-4 flex items-center gap-4">
                    <div class="shrink-0">
                        @if($note->profile?->primaryPhoto?->photo_url)
                            <img src="{{ asset('storage/' . $note->profile->primaryPhoto->photo_url) }}" class="w-10 h-10 rounded-full object-cover">
                        @else
                            <img src="{{ url('/images/default-avatar.svg') }}" class="w-10 h-10 rounded-full">
                        @endif
                    </div>
                    <div class="flex-1">
                        <a href="{{ route('filament.admin.resources.users.view', $note->profile_id) }}" class="font-bold text-primary-600 hover:underline">
                            {{ $note->profile?->full_name }} ({{ $note->profile?->matri_id }})
                        </a>
                        <div class="text-sm text-gray-700 mt-1">{{ $note->note }}</div>
                        <div class="text-xs text-gray-500 mt-1">
                            <span class="text-red-600 font-medium">Follow-up: {{ $note->follow_up_date->format('d M Y') }} ({{ $note->follow_up_date->diffForHumans() }})</span>
                            <span class="ml-3">By: {{ $note->adminUser?->name ?? 'Admin' }}</span>
                            <span class="ml-3">Phone: {{ $note->profile?->user?->phone ?? '-' }}</span>
                        </div>
                    </div>
                    @if($note->profile?->user?->phone)
                        @php $phone = preg_replace('/[^0-9]/', '', $note->profile->user->phone); if (strlen($phone) === 10) $phone = '91' . $phone; @endphp
                        <a href="https://wa.me/{{ $phone }}" target="_blank" class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded bg-green-100 text-green-700 hover:bg-green-200">WhatsApp</a>
                    @endif
                    <a href="{{ route('filament.admin.resources.users.view', $note->profile_id) }}" class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded bg-primary-50 text-primary-700 hover:bg-primary-100">View</a>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Today --}}
    @php $today = $this->getTodayFollowUps(); @endphp
    <h3 class="text-lg font-semibold text-orange-600 mb-3">Today ({{ $today->count() }})</h3>

    @if($today->isEmpty())
        <p class="text-sm text-gray-500 mb-6">No follow-ups scheduled for today.</p>
    @else
        <div class="space-y-2 mb-6">
            @foreach($today as $note)
                <div class="bg-orange-50 dark:bg-orange-900/20 rounded-xl border border-orange-200 dark:border-orange-700 p-4 flex items-center gap-4">
                    <div class="shrink-0">
                        @if($note->profile?->primaryPhoto?->photo_url)
                            <img src="{{ asset('storage/' . $note->profile->primaryPhoto->photo_url) }}" class="w-10 h-10 rounded-full object-cover">
                        @else
                            <img src="{{ url('/images/default-avatar.svg') }}" class="w-10 h-10 rounded-full">
                        @endif
                    </div>
                    <div class="flex-1">
                        <a href="{{ route('filament.admin.resources.users.view', $note->profile_id) }}" class="font-bold text-primary-600 hover:underline">
                            {{ $note->profile?->full_name }} ({{ $note->profile?->matri_id }})
                        </a>
                        <div class="text-sm text-gray-700 mt-1">{{ $note->note }}</div>
                        <div class="text-xs text-gray-500 mt-1">
                            <span class="font-medium">Follow-up: Today</span>
                            <span class="ml-3">By: {{ $note->adminUser?->name ?? 'Admin' }}</span>
                            <span class="ml-3">Phone: {{ $note->profile?->user?->phone ?? '-' }}</span>
                        </div>
                    </div>
                    @if($note->profile?->user?->phone)
                        @php $phone = preg_replace('/[^0-9]/', '', $note->profile->user->phone); if (strlen($phone) === 10) $phone = '91' . $phone; @endphp
                        <a href="https://wa.me/{{ $phone }}" target="_blank" class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded bg-green-100 text-green-700 hover:bg-green-200">WhatsApp</a>
                    @endif
                    <a href="{{ route('filament.admin.resources.users.view', $note->profile_id) }}" class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded bg-primary-50 text-primary-700 hover:bg-primary-100">View</a>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Upcoming --}}
    @php $upcoming = $this->getUpcomingFollowUps(); @endphp
    <h3 class="text-lg font-semibold text-blue-600 mb-3">Upcoming 7 Days ({{ $upcoming->count() }})</h3>

    @if($upcoming->isEmpty())
        <p class="text-sm text-gray-500">No follow-ups scheduled for the next 7 days.</p>
    @else
        <div class="space-y-2">
            @foreach($upcoming as $note)
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 flex items-center gap-4">
                    <div class="shrink-0">
                        @if($note->profile?->primaryPhoto?->photo_url)
                            <img src="{{ asset('storage/' . $note->profile->primaryPhoto->photo_url) }}" class="w-10 h-10 rounded-full object-cover">
                        @else
                            <img src="{{ url('/images/default-avatar.svg') }}" class="w-10 h-10 rounded-full">
                        @endif
                    </div>
                    <div class="flex-1">
                        <a href="{{ route('filament.admin.resources.users.view', $note->profile_id) }}" class="font-bold text-primary-600 hover:underline">
                            {{ $note->profile?->full_name }} ({{ $note->profile?->matri_id }})
                        </a>
                        <div class="text-sm text-gray-700 mt-1">{{ $note->note }}</div>
                        <div class="text-xs text-gray-500 mt-1">
                            <span>Follow-up: {{ $note->follow_up_date->format('d M Y') }} ({{ $note->follow_up_date->diffForHumans() }})</span>
                            <span class="ml-3">By: {{ $note->adminUser?->name ?? 'Admin' }}</span>
                        </div>
                    </div>
                    <a href="{{ route('filament.admin.resources.users.view', $note->profile_id) }}" class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded bg-primary-50 text-primary-700 hover:bg-primary-100">View</a>
                </div>
            @endforeach
        </div>
    @endif
</x-filament-panels::page>

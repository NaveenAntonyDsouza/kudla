<x-filament-panels::page>
    {{-- Deleted Users (soft-deleted) --}}
    <h3 class="text-lg font-semibold text-red-600 mb-3">Deleted Users (Can be restored)</h3>
    @php $deleted = $this->getDeletedProfiles(); @endphp

    @if($deleted->isEmpty())
        <p class="text-sm text-gray-500 mb-6">No deleted users.</p>
    @else
        <div class="space-y-2 mb-6">
            @foreach($deleted as $profile)
                <div class="bg-red-50 dark:bg-red-900/20 rounded-xl border border-red-200 dark:border-red-700 p-4 flex items-center gap-4">
                    <div class="flex-1">
                        <span class="font-bold">{{ $profile->full_name }} ({{ $profile->matri_id }})</span>
                        <div class="text-sm text-gray-600">
                            <span>{{ $profile->user?->phone ?? '-' }}</span>
                            <span class="ml-3">{{ $profile->user?->email ?? '-' }}</span>
                            <span class="ml-3 text-red-600">Deleted: {{ $profile->deleted_at?->format('d M Y, h:i A') }} ({{ $profile->deleted_at?->diffForHumans() }})</span>
                            @if($profile->deletion_reason)
                                <span class="ml-3">Reason: {{ $profile->deletion_reason }}</span>
                            @endif
                        </div>
                    </div>
                    <a href="{{ route('filament.admin.resources.users.index', ['activeTab' => 'deleted']) }}" class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded bg-primary-50 text-primary-700 hover:bg-primary-100">
                        Manage in Users
                    </a>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Deactivated Users --}}
    <h3 class="text-lg font-semibold text-orange-600 mb-3">Deactivated Users</h3>
    @php $deactivated = $this->getDeactivatedProfiles(); @endphp

    @if($deactivated->isEmpty())
        <p class="text-sm text-gray-500">No deactivated users.</p>
    @else
        <div class="space-y-2">
            @foreach($deactivated as $profile)
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 flex items-center gap-4">
                    <div class="flex-1">
                        <a href="{{ route('filament.admin.resources.users.view', $profile->id) }}" class="font-bold text-primary-600 hover:underline">{{ $profile->full_name }} ({{ $profile->matri_id }})</a>
                        <div class="text-sm text-gray-600">
                            <span>{{ $profile->user?->phone ?? '-' }}</span>
                            <span class="ml-3">{{ $profile->user?->email ?? '-' }}</span>
                            <span class="ml-3">{{ $profile->religiousInfo?->religion ?? '-' }}</span>
                            <span class="ml-3">Registered: {{ $profile->created_at?->format('d M Y') }}</span>
                        </div>
                    </div>
                    <a href="{{ route('filament.admin.resources.users.view', $profile->id) }}" class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded bg-primary-50 text-primary-700 hover:bg-primary-100">View</a>
                </div>
            @endforeach
        </div>
    @endif
</x-filament-panels::page>

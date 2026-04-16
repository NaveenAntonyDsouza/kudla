<x-filament-panels::page>
    <form wire:submit="assignPlan">
        {{ $this->form }}

        <div class="mt-4 flex gap-3">
            <x-filament::button type="button" wire:click="lookupUser" icon="heroicon-o-magnifying-glass" color="gray">
                Lookup User
            </x-filament::button>
        </div>
    </form>

    @if($searched)
        <div class="mt-6">
            @if($foundProfile)
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="text-lg font-semibold mb-4">User Found</h3>
                    <div class="flex gap-4 items-start">
                        <div class="shrink-0">
                            @if($foundProfile->primaryPhoto?->photo_url)
                                <img src="{{ asset('storage/' . $foundProfile->primaryPhoto->photo_url) }}" class="w-20 h-20 rounded-full object-cover">
                            @else
                                <img src="{{ url('/images/default-avatar.svg') }}" class="w-20 h-20 rounded-full">
                            @endif
                        </div>
                        <div class="flex-1">
                            <p class="text-xl font-bold">{{ $foundProfile->full_name }} ({{ $foundProfile->matri_id }})</p>
                            <div class="text-sm text-gray-600 mt-1 space-y-1">
                                <p>Phone: {{ $foundProfile->user?->phone ?? '-' }} | Email: {{ $foundProfile->user?->email ?? '-' }}</p>
                                <p>Gender: {{ ucfirst($foundProfile->gender) }} | Age: {{ $foundProfile->date_of_birth ? \Carbon\Carbon::parse($foundProfile->date_of_birth)->age . ' yrs' : '-' }}</p>
                                <p>Religion: {{ $foundProfile->religiousInfo?->religion ?? '-' }}{{ $foundProfile->religiousInfo?->denomination ? ' / ' . $foundProfile->religiousInfo->denomination : '' }}</p>

                                @php $currentMembership = $foundProfile->user?->activeMembership(); @endphp
                                <div class="mt-3 p-3 rounded-lg {{ $currentMembership ? 'bg-green-50 border border-green-200' : 'bg-gray-50 border border-gray-200' }}">
                                    <p class="font-medium">
                                        Current Plan:
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $currentMembership ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                            {{ $currentMembership?->plan?->plan_name ?? 'Free' }}
                                        </span>
                                        @if($currentMembership?->ends_at)
                                            <span class="text-gray-500 ml-2">Expires: {{ $currentMembership->ends_at->format('d M Y') }} ({{ $currentMembership->ends_at->diffForHumans() }})</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 flex justify-end">
                        <x-filament::button type="button" wire:click="assignPlan" icon="heroicon-o-check-circle" color="success">
                            Assign Plan
                        </x-filament::button>
                    </div>
                </div>
            @else
                <div class="text-center py-8 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                    <x-heroicon-o-user class="mx-auto h-12 w-12 text-gray-400" />
                    <p class="mt-2 text-gray-500">No user found with Matri ID or phone: <strong>{{ $matri_id }}</strong></p>
                </div>
            @endif
        </div>
    @endif
</x-filament-panels::page>

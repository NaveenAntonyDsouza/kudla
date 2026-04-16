<x-filament-panels::page>
    <form wire:submit="search">
        {{ $this->form }}

        <div class="mt-4 flex gap-3">
            <x-filament::button type="submit" icon="heroicon-o-magnifying-glass">
                Search
            </x-filament::button>
            <x-filament::button type="button" color="gray" wire:click="resetFilters" icon="heroicon-o-arrow-path">
                Reset
            </x-filament::button>
        </div>
    </form>

    @if($searched)
        <div class="mt-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Results: {{ $results->count() }} {{ $results->count() >= 100 ? '(showing max 100)' : '' }} profiles found
                </h3>
            </div>

            @if($results->isEmpty())
                <div class="text-center py-12 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                    <x-heroicon-o-magnifying-glass class="mx-auto h-12 w-12 text-gray-400" />
                    <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">No profiles found</h3>
                    <p class="mt-1 text-sm text-gray-500">Try adjusting your search filters.</p>
                </div>
            @else
                <div class="space-y-3">
                    @foreach($results as $profile)
                        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                            <div class="flex gap-4">
                                {{-- Photo --}}
                                <div class="shrink-0">
                                    @if($profile->primaryPhoto?->photo_url)
                                        <img src="{{ asset('storage/' . $profile->primaryPhoto->photo_url) }}" alt="" class="w-16 h-16 rounded-full object-cover">
                                    @else
                                        <img src="{{ url('/images/default-avatar.svg') }}" alt="" class="w-16 h-16 rounded-full">
                                    @endif
                                </div>

                                {{-- Info --}}
                                <div class="flex-1 min-w-0">
                                    {{-- Row 1: Name + Status --}}
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <a href="{{ route('filament.admin.resources.users.view', $profile->id) }}"
                                           class="text-lg font-bold text-primary-600 hover:underline">
                                            {{ $profile->full_name }} ({{ $profile->matri_id }})
                                        </a>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $profile->gender === 'male' ? 'bg-blue-100 text-blue-800' : 'bg-pink-100 text-pink-800' }}">
                                            {{ ucfirst($profile->gender) }}
                                        </span>
                                        @php $membership = $profile->user?->activeMembership(); @endphp
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $membership ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                            {{ $membership?->plan?->plan_name ?? 'Free' }}
                                        </span>
                                        @if($profile->is_approved)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Approved</span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">Pending</span>
                                        @endif
                                    </div>

                                    {{-- Row 2: Details --}}
                                    <div class="mt-1 text-sm text-gray-600 dark:text-gray-400 flex flex-wrap gap-x-4 gap-y-1">
                                        <span>{{ $profile->date_of_birth ? \Carbon\Carbon::parse($profile->date_of_birth)->age . ' yrs' : '-' }}</span>
                                        <span>{{ $profile->user?->phone ?? '-' }}</span>
                                        <span>{{ $profile->user?->email ?? '-' }}</span>
                                        <span>{{ $profile->religiousInfo?->religion ?? '-' }}{{ $profile->religiousInfo?->denomination ? ' / ' . $profile->religiousInfo->denomination : '' }}{{ $profile->religiousInfo?->caste ? ' / ' . $profile->religiousInfo->caste : '' }}</span>
                                        <span>{{ $profile->locationInfo?->native_district ?? '' }}{{ $profile->locationInfo?->native_state ? ', ' . $profile->locationInfo->native_state : '' }}</span>
                                    </div>

                                    {{-- Row 3: More --}}
                                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-500 flex flex-wrap gap-x-4 gap-y-1">
                                        <span>{{ $profile->mother_tongue ?? '-' }}</span>
                                        <span>{{ $profile->marital_status ?? '-' }}</span>
                                        <span>{{ $profile->educationDetail?->highest_education ?? '-' }}</span>
                                        <span>{{ $profile->educationDetail?->occupation ?? '-' }}</span>
                                        <span>Profile: {{ $profile->profile_completion_pct ?? 0 }}%</span>
                                        <span>Registered: {{ $profile->created_at?->format('d M Y') }}</span>
                                    </div>
                                </div>

                                {{-- Actions --}}
                                <div class="shrink-0 flex flex-col gap-1">
                                    <a href="{{ route('filament.admin.resources.users.view', $profile->id) }}"
                                       class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded bg-primary-50 text-primary-700 hover:bg-primary-100">
                                        View
                                    </a>
                                    <a href="{{ route('filament.admin.resources.users.edit', $profile->id) }}"
                                       class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded bg-gray-50 text-gray-700 hover:bg-gray-100">
                                        Edit
                                    </a>
                                    @if($profile->user?->phone)
                                        @php
                                            $phone = preg_replace('/[^0-9]/', '', $profile->user->phone);
                                            if (strlen($phone) === 10) $phone = '91' . $phone;
                                        @endphp
                                        <a href="https://wa.me/{{ $phone }}" target="_blank"
                                           class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded bg-green-50 text-green-700 hover:bg-green-100">
                                            WhatsApp
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif
</x-filament-panels::page>

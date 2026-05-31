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

    @if ($searched)
        <x-filament::section icon="heroicon-o-users" icon-color="primary">
            <x-slot name="heading">Results ({{ $results->count() }}{{ $results->count() >= 100 ? ', showing max 100' : '' }})</x-slot>

            @forelse ($results as $profile)
                @php $membership = $profile->user?->activeMembership(); @endphp
                <x-admin.member-row
                    :photo="$profile->primaryPhoto?->photo_url"
                    :name="$profile->full_name"
                    :subtitle="'(' . $profile->matri_id . ')'"
                    :href="route('filament.admin.resources.users.view', $profile->id)"
                    :first="$loop->first">
                    <x-admin.pill :color="$profile->gender === 'male' ? 'info' : 'pink'">{{ ucfirst($profile->gender) }}</x-admin.pill>
                    <x-admin.pill :color="$membership ? 'success' : 'gray'">{{ $membership?->plan?->plan_name ?? 'Free' }}</x-admin.pill>
                    <x-admin.pill :color="$profile->is_approved ? 'success' : 'warning'">{{ $profile->is_approved ? 'Approved' : 'Pending' }}</x-admin.pill>
                    <span>{{ $profile->date_of_birth ? \Carbon\Carbon::parse($profile->date_of_birth)->age . ' yrs' : '—' }}</span>
                    <span>{{ $profile->user?->phone ?? '—' }}</span>
                    <span>{{ $profile->user?->email ?? '—' }}</span>
                    <span>{{ $profile->religiousInfo?->religion ?? '—' }}{{ $profile->religiousInfo?->display_denomination ? ' / ' . $profile->religiousInfo->display_denomination : '' }}{{ $profile->religiousInfo?->display_caste ? ' / ' . $profile->religiousInfo->display_caste : '' }}</span>
                    <span>{{ $profile->locationInfo?->native_district ?? '' }}{{ $profile->locationInfo?->native_state ? ', ' . $profile->locationInfo->native_state : '' }}</span>
                    <span>{{ $profile->mother_tongue ?? '—' }}</span>
                    <span>{{ $profile->marital_status ?? '—' }}</span>
                    <span>{{ $profile->educationDetail?->highest_education ?? '—' }}</span>
                    <span>{{ $profile->educationDetail?->occupation ?? '—' }}</span>
                    <span>Profile {{ $profile->profile_completion_pct ?? 0 }}%</span>
                    <span>Registered {{ $profile->created_at?->format('d M Y') }}</span>

                    <x-slot name="actions">
                        <x-filament::button tag="a" size="sm" color="primary" icon="heroicon-o-eye" href="{{ route('filament.admin.resources.users.view', $profile->id) }}">View</x-filament::button>
                        <x-filament::button tag="a" size="sm" color="gray" icon="heroicon-o-pencil-square" href="{{ route('filament.admin.resources.users.edit', $profile->id) }}">Edit</x-filament::button>
                        @if ($profile->user?->phone)
                            @php $phone = preg_replace('/[^0-9]/', '', $profile->user->phone); if (strlen($phone) === 10) $phone = '91' . $phone; @endphp
                            <x-filament::button tag="a" size="sm" color="success" icon="heroicon-o-chat-bubble-left-right" href="https://wa.me/{{ $phone }}">WhatsApp</x-filament::button>
                        @endif
                    </x-slot>
                </x-admin.member-row>
            @empty
                <p style="opacity:.6;font-size:.875rem;text-align:center;padding:1.5rem 0;">No profiles found. Try adjusting your search filters.</p>
            @endforelse
        </x-filament::section>
    @endif
</x-filament-panels::page>

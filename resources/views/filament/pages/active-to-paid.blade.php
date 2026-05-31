<x-filament-panels::page>
    @php $profiles = $this->getProfiles(); @endphp

    <x-filament::section icon="heroicon-o-arrow-trending-up" icon-color="success">
        <x-slot name="heading">Free Active Users ({{ $profiles->count() }})</x-slot>
        <x-slot name="description">Free users sorted by recent activity — the most likely to convert. Reach out via WhatsApp to promote membership.</x-slot>

        @forelse ($profiles as $profile)
            <x-admin.member-row
                :photo="$profile->primaryPhoto?->photo_url"
                :name="$profile->full_name"
                :subtitle="'(' . $profile->matri_id . ')'"
                :href="route('filament.admin.resources.users.view', $profile->id)"
                :first="$loop->first">
                <x-admin.pill :color="$profile->gender === 'male' ? 'info' : 'pink'">{{ $profile->gender === 'male' ? 'Male' : 'Female' }}</x-admin.pill>
                <span>{{ $profile->date_of_birth ? \Carbon\Carbon::parse($profile->date_of_birth)->age . ' yrs' : '—' }}</span>
                <span>{{ $profile->user?->phone ?? '—' }}</span>
                <span>{{ $profile->religiousInfo?->religion ?? '—' }}</span>
                <span>{{ $profile->locationInfo?->native_state ?? '—' }}</span>
                <span>Last login: {{ $profile->user?->last_login_at ? \Carbon\Carbon::parse($profile->user->last_login_at)->diffForHumans() : 'Never' }}</span>
                <span>Profile {{ $profile->profile_completion_pct ?? 0 }}%</span>

                <x-slot name="actions">
                    @if (\App\Support\Permissions::can('edit_plan'))
                        <x-filament::button tag="a" size="sm" color="warning" icon="heroicon-o-credit-card"
                            href="{{ \App\Filament\Pages\ChangeMembershipPlan::getUrl(['matri_id' => $profile->matri_id]) }}">
                            Assign Plan
                        </x-filament::button>
                    @endif
                    @if ($profile->user?->phone)
                        @php $phone = preg_replace('/[^0-9]/', '', $profile->user->phone); if (strlen($phone) === 10) $phone = '91' . $phone; @endphp
                        <x-filament::button tag="a" size="sm" color="success" icon="heroicon-o-chat-bubble-left-right" href="https://wa.me/{{ $phone }}">WhatsApp</x-filament::button>
                    @endif
                    <x-filament::button tag="a" size="sm" color="primary" icon="heroicon-o-eye" href="{{ route('filament.admin.resources.users.view', $profile->id) }}">View</x-filament::button>
                </x-slot>
            </x-admin.member-row>
        @empty
            <p style="opacity:.6;font-size:.875rem;">No free active users found — all active users are on paid plans!</p>
        @endforelse
    </x-filament::section>
</x-filament-panels::page>

<x-filament-panels::page>
    <form wire:submit="assignPlan">
        {{ $this->form }}

        <div class="mt-4 flex gap-3">
            <x-filament::button type="button" wire:click="lookupUser" icon="heroicon-o-magnifying-glass" color="gray">
                Lookup User
            </x-filament::button>
        </div>
    </form>

    @if ($searched)
        @if ($foundProfile)
            @php $currentMembership = $foundProfile->user?->activeMembership(); @endphp
            <x-filament::section icon="heroicon-o-user-circle" icon-color="primary">
                <x-slot name="heading">User Found</x-slot>

                <div style="display:flex;gap:1.25rem;align-items:flex-start;">
                    <img src="{{ $foundProfile->primaryPhoto?->photo_url ? asset('storage/' . $foundProfile->primaryPhoto->photo_url) : url('/images/default-avatar.svg') }}"
                        alt="" style="width:4.5rem;height:4.5rem;border-radius:9999px;object-fit:cover;flex-shrink:0;background:rgba(128,128,128,0.12);">

                    <div style="flex:1;min-width:0;">
                        <div style="font-size:1.15rem;font-weight:700;">
                            {{ $foundProfile->full_name }}
                            <span style="opacity:.55;font-weight:400;font-size:.9rem;">({{ $foundProfile->matri_id }})</span>
                        </div>

                        <div style="opacity:.8;font-size:.875rem;margin-top:.4rem;display:flex;flex-direction:column;gap:.2rem;">
                            <span>Phone: {{ $foundProfile->user?->phone ?? '—' }} &nbsp;·&nbsp; Email: {{ $foundProfile->user?->email ?? '—' }}</span>
                            <span>Gender: {{ ucfirst($foundProfile->gender) }} &nbsp;·&nbsp; Age: {{ $foundProfile->date_of_birth ? \Carbon\Carbon::parse($foundProfile->date_of_birth)->age . ' yrs' : '—' }}</span>
                            <span>Religion: {{ $foundProfile->religiousInfo?->religion ?? '—' }}{{ $foundProfile->religiousInfo?->display_denomination ? ' / ' . $foundProfile->religiousInfo->display_denomination : '' }}</span>
                        </div>

                        <div style="margin-top:.85rem;display:flex;align-items:center;flex-wrap:wrap;gap:.5rem;">
                            <span style="font-weight:600;font-size:.875rem;">Current plan:</span>
                            <x-admin.pill :color="$currentMembership ? 'success' : 'gray'">{{ $currentMembership?->plan?->plan_name ?? 'Free' }}</x-admin.pill>
                            @if ($currentMembership?->ends_at)
                                <span style="opacity:.7;font-size:.8125rem;">Expires {{ $currentMembership->ends_at->format('d M Y') }} ({{ $currentMembership->ends_at->diffForHumans() }})</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div style="margin-top:1.25rem;display:flex;justify-content:flex-end;">
                    <x-filament::button type="button" wire:click="assignPlan" icon="heroicon-o-check-circle" color="success">
                        Assign Plan
                    </x-filament::button>
                </div>
            </x-filament::section>
        @else
            <x-filament::section icon="heroicon-o-user" icon-color="gray">
                <x-slot name="heading">No User Found</x-slot>
                <p style="opacity:.7;font-size:.875rem;">No user found with Matri ID or phone: <strong>{{ $matri_id }}</strong></p>
            </x-filament::section>
        @endif
    @endif
</x-filament-panels::page>

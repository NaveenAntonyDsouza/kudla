<x-filament-panels::page>
    {{-- ── Step 1: search ── --}}
    <x-filament::section icon="heroicon-o-magnifying-glass" icon-color="primary">
        <x-slot name="heading">Find Member</x-slot>
        <x-slot name="description">Search by Matri ID, phone number, name, or email.</x-slot>

        <div style="display:flex;gap:.75rem;align-items:center;flex-wrap:wrap;">
            <input type="text" wire:model="search" wire:keydown.enter.prevent="lookupUser"
                placeholder="Matri ID / Phone / Name / Email"
                style="flex:1;min-width:18rem;padding:.55rem .8rem;border:1px solid rgba(128,128,128,0.4);border-radius:.5rem;background:transparent;color:inherit;font-size:.9rem;outline:none;">
            <x-filament::button wire:click="lookupUser" icon="heroicon-o-magnifying-glass" wire:loading.attr="disabled">
                Search
            </x-filament::button>
        </div>
    </x-filament::section>

    {{-- ── Step 2a: several matches → pick one ── --}}
    @if ($matches->count() > 1)
        <x-filament::section icon="heroicon-o-users" icon-color="gray">
            <x-slot name="heading">{{ $matches->count() }} members found — select one</x-slot>
            @foreach ($matches as $m)
                <x-admin.member-row :first="$loop->first"
                    :photo="$m->primaryPhoto?->photo_url"
                    :name="$m->full_name"
                    :subtitle="'(' . $m->matri_id . ')'">
                    <span>{{ $m->user?->phone ?? '—' }}</span>
                    <span>{{ $m->user?->email ?? '—' }}</span>
                    <span>{{ $m->religiousInfo?->religion ?? '—' }}</span>

                    <x-slot name="actions">
                        <x-filament::button type="button" size="sm" color="primary" icon="heroicon-o-cursor-arrow-rays"
                            wire:click="selectProfile({{ $m->id }})">Select</x-filament::button>
                    </x-slot>
                </x-admin.member-row>
            @endforeach
        </x-filament::section>
    @endif

    {{-- ── Step 2b: found member (+ Step 3: reveal assignment) ── --}}
    @if ($foundProfile)
        @php $currentMembership = $foundProfile->user?->activeMembership(); @endphp
        <x-filament::section icon="heroicon-o-user-circle" icon-color="primary">
            <x-slot name="heading">Member Found</x-slot>

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

                @unless ($showAssignForm)
                    <x-filament::button type="button" wire:click="startAssign" icon="heroicon-o-credit-card" color="warning">
                        Assign / Change Plan
                    </x-filament::button>
                @endunless
            </div>
        </x-filament::section>

        {{-- Step 3: assignment fields, revealed on click (or via deep-link) --}}
        @if ($showAssignForm)
            <form wire:submit.prevent="assignPlan">
                {{ $this->form }}

                <div style="margin-top:1rem;display:flex;gap:.75rem;justify-content:flex-end;">
                    <x-filament::button type="button" color="gray" wire:click="cancelAssign">Cancel</x-filament::button>
                    <x-filament::button type="submit" color="success" icon="heroicon-o-check-circle">Assign Plan</x-filament::button>
                </div>
            </form>
        @endif
    @elseif ($searched && $matches->count() === 0)
        <x-filament::section icon="heroicon-o-user" icon-color="gray">
            <x-slot name="heading">No member found</x-slot>
            <p style="opacity:.7;font-size:.875rem;">Nothing matched “<strong>{{ $search }}</strong>”. Try a Matri ID, phone number, name, or email.</p>
        </x-filament::section>
    @endif
</x-filament-panels::page>

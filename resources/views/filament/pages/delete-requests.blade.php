<x-filament-panels::page>
    @php
        $deleted = $this->getDeletedProfiles();
        $deactivated = $this->getDeactivatedProfiles();
    @endphp

    {{-- ───────────── Deleted users (soft-deleted, restorable) ───────────── --}}
    <x-filament::section icon="heroicon-o-trash" icon-color="danger">
        <x-slot name="heading">Deleted Users ({{ $deleted->count() }})</x-slot>
        <x-slot name="description">Soft-deleted accounts — these can be restored from the Users list.</x-slot>

        @forelse ($deleted as $profile)
            <div style="display:flex;align-items:center;gap:1rem;padding:0.75rem 0;{{ $loop->first ? '' : 'border-top:1px solid rgba(128,128,128,0.18);' }}">
                <img src="{{ $profile->primaryPhoto?->photo_url ? asset('storage/' . $profile->primaryPhoto->photo_url) : url('/images/default-avatar.svg') }}"
                    alt="" style="width:2.5rem;height:2.5rem;border-radius:9999px;object-fit:cover;flex-shrink:0;">

                <div style="flex:1;min-width:0;">
                    <div style="font-weight:600;">
                        {{ $profile->full_name }}
                        <span style="opacity:.55;font-weight:400;">({{ $profile->matri_id }})</span>
                    </div>
                    <div style="opacity:.7;font-size:.8125rem;margin-top:.125rem;">
                        {{ $profile->user?->phone ?? '—' }} ·
                        {{ $profile->user?->email ?? '—' }} ·
                        <span style="color:#dc2626;">Deleted {{ $profile->deleted_at?->displayTz()->format('d M Y, h:i A') }} ({{ $profile->deleted_at?->diffForHumans() }})</span>
                        @if ($profile->deletion_reason)
                            · Reason: {{ $profile->deletion_reason }}
                        @endif
                    </div>
                </div>

                <x-filament::button tag="a" size="sm" color="gray" icon="heroicon-o-arrow-uturn-left"
                    href="{{ route('filament.admin.resources.users.index', ['activeTab' => 'deleted']) }}">
                    Manage in Users
                </x-filament::button>
            </div>
        @empty
            <p style="opacity:.6;font-size:.875rem;">No deleted users.</p>
        @endforelse
    </x-filament::section>

    {{-- ───────────── Deactivated users ───────────── --}}
    <x-filament::section icon="heroicon-o-pause-circle" icon-color="warning">
        <x-slot name="heading">Deactivated Users ({{ $deactivated->count() }})</x-slot>
        <x-slot name="description">Accounts hidden from members and search. Reactivate or manage them from the Users list.</x-slot>

        @forelse ($deactivated as $profile)
            <div style="display:flex;align-items:center;gap:1rem;padding:0.75rem 0;{{ $loop->first ? '' : 'border-top:1px solid rgba(128,128,128,0.18);' }}">
                <img src="{{ $profile->primaryPhoto?->photo_url ? asset('storage/' . $profile->primaryPhoto->photo_url) : url('/images/default-avatar.svg') }}"
                    alt="" style="width:2.5rem;height:2.5rem;border-radius:9999px;object-fit:cover;flex-shrink:0;">

                <div style="flex:1;min-width:0;">
                    <div style="font-weight:600;">
                        {{ $profile->full_name }}
                        <span style="opacity:.55;font-weight:400;">({{ $profile->matri_id }})</span>
                    </div>
                    <div style="opacity:.7;font-size:.8125rem;margin-top:.125rem;">
                        {{ $profile->user?->phone ?? '—' }} ·
                        {{ $profile->user?->email ?? '—' }} ·
                        {{ $profile->religiousInfo?->religion ?? '—' }} ·
                        Registered {{ $profile->created_at?->format('d M Y') }}
                    </div>
                </div>

                <x-filament::button tag="a" size="sm" color="primary" icon="heroicon-o-eye"
                    href="{{ route('filament.admin.resources.users.view', $profile->id) }}">
                    View
                </x-filament::button>
            </div>
        @empty
            <p style="opacity:.6;font-size:.875rem;">No deactivated users.</p>
        @endforelse
    </x-filament::section>
</x-filament-panels::page>

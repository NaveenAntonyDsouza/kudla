@php $l = $profile->locationInfo; @endphp
@if(!$l)
    <p class="text-sm text-gray-400">No location information added yet.</p>
@else
<div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-3">
    <div><p class="text-xs text-gray-500">Native Country</p><p class="text-sm font-medium text-gray-900">{{ $l->native_country ?? 'Not Mentioned' }}</p></div>
    <div><p class="text-xs text-gray-500">Native State</p><p class="text-sm font-medium text-gray-900">{{ $l->native_state ?? 'Not Mentioned' }}</p></div>
    <div><p class="text-xs text-gray-500">Native District</p><p class="text-sm font-medium text-gray-900">{{ $l->native_district ?? 'Not Mentioned' }}</p></div>
    <div><p class="text-xs text-gray-500">PIN / ZIP Code</p><p class="text-sm font-medium text-gray-900">{{ $l->pin_zip_code ?? 'Not Mentioned' }}</p></div>
    <div><p class="text-xs text-gray-500">Residing Country</p><p class="text-sm font-medium text-gray-900">{{ $l->residing_country ?? 'Not Mentioned' }}</p></div>
    @if($l->residing_country && $l->residing_country !== 'India')
        <div><p class="text-xs text-gray-500">Residency Status</p><p class="text-sm font-medium text-gray-900">{{ $l->residency_status ?? 'Not Mentioned' }}</p></div>
        @if($l->outstation_leave_date_from)
            <div><p class="text-xs text-gray-500">Leave Period</p><p class="text-sm font-medium text-gray-900">{{ $l->outstation_leave_date_from?->format('d/m/Y') }} - {{ $l->outstation_leave_date_to?->format('d/m/Y') }}</p></div>
        @endif
    @endif
</div>
@endif

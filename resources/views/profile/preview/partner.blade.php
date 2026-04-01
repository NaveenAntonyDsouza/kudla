@php
    $pp = $profile->partnerPreference;
    $nm = fn($v) => $v ?: 'Not Mentioned';
    $arrAny = fn($v) => $v && count($v) ? implode(', ', $v) : 'Any';
@endphp

@if(!$pp)
    <div class="bg-white rounded-lg border border-gray-200 shadow-xs p-6">
        <p class="text-sm text-gray-400">No partner preferences added yet.</p>
    </div>
@else
{{-- ── Partner Preferences ── --}}
<div class="bg-white rounded-lg border border-gray-200 shadow-xs p-6 mb-6">
    <h3 class="text-lg font-semibold text-(--color-primary) mb-4">Partner Preferences</h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4">
        <div><p class="text-xs text-gray-500">Age Preferred</p><p class="text-sm font-semibold text-gray-900">{{ $pp->age_from && $pp->age_to ? $pp->age_from . ' Yrs - ' . $pp->age_to . ' Yrs' : 'Any' }}</p></div>
        <div><p class="text-xs text-gray-500">Height Preferred</p><p class="text-sm font-semibold text-gray-900">{{ $pp->height_from_cm && $pp->height_to_cm ? $pp->height_from_cm . ' - ' . $pp->height_to_cm : 'Any' }}</p></div>
        <div><p class="text-xs text-gray-500">Denomination</p><p class="text-sm font-semibold text-gray-900">{{ $arrAny($pp->denomination) }}</p></div>
        <div><p class="text-xs text-gray-500">Complexion</p><p class="text-sm font-semibold text-gray-900">{{ $arrAny($pp->complexion) }}</p></div>
        <div><p class="text-xs text-gray-500">Body Type</p><p class="text-sm font-semibold text-gray-900">{{ $arrAny($pp->body_type) }}</p></div>
        <div><p class="text-xs text-gray-500">Marital Status</p><p class="text-sm font-semibold text-gray-900">{{ $arrAny($pp->marital_status) }}</p></div>
        <div><p class="text-xs text-gray-500">Children Preferences</p><p class="text-sm font-semibold text-gray-900">{{ $arrAny($pp->children_status) }}</p></div>
        <div><p class="text-xs text-gray-500">Physical Status</p><p class="text-sm font-semibold text-gray-900">{{ $arrAny($pp->physical_status) }}</p></div>
        <div><p class="text-xs text-gray-500">Family Status</p><p class="text-sm font-semibold text-gray-900">{{ $arrAny($pp->family_status) }}</p></div>
    </div>
</div>

{{-- ── Education & Professional Requirements ── --}}
<div class="bg-white rounded-lg border border-gray-200 shadow-xs p-6 mb-6">
    <h3 class="text-lg font-semibold text-(--color-primary) mb-4">Education & Professional Requirements</h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4">
        <div><p class="text-xs text-gray-500">Education</p><p class="text-sm font-semibold text-gray-900">{{ $arrAny($pp->education_levels) }}</p></div>
        <div><p class="text-xs text-gray-500">Occupation</p><p class="text-sm font-semibold text-gray-900">{{ $arrAny($pp->occupations) }}</p></div>
        <div><p class="text-xs text-gray-500">Employment Category</p><p class="text-sm font-semibold text-gray-900">{{ $arrAny($pp->employment_status) }}</p></div>
        <div><p class="text-xs text-gray-500">Annual Income</p><p class="text-sm font-semibold text-gray-900">{{ $arrAny($pp->income_range) }}</p></div>
        <div><p class="text-xs text-gray-500">Working Country</p><p class="text-sm font-semibold text-gray-900">{{ $arrAny($pp->working_countries) }}</p></div>
    </div>
</div>

{{-- ── Location Requirements ── --}}
<div class="bg-white rounded-lg border border-gray-200 shadow-xs p-6 mb-6">
    <h3 class="text-lg font-semibold text-(--color-primary) mb-4">Location Requirements</h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4">
        <div><p class="text-xs text-gray-500">Native Country</p><p class="text-sm font-semibold text-gray-900">{{ $arrAny($pp->native_countries) }}</p></div>
        <div><p class="text-xs text-gray-500">Mother Tongue</p><p class="text-sm font-semibold text-gray-900">{{ $arrAny($pp->mother_tongues) }}</p></div>
    </div>
</div>

@if($pp->about_partner)
<div class="bg-white rounded-lg border border-gray-200 shadow-xs p-6">
    <p class="text-xs text-gray-500 mb-1">Expectations about the partner in detail</p>
    <p class="text-sm text-gray-700">{{ $pp->about_partner }}</p>
</div>
@endif
@endif

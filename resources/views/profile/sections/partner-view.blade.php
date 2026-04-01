@php $pp = $profile->partnerPreference; @endphp
@if(!$pp)
    <p class="text-sm text-gray-400">No partner preferences added yet.</p>
@else
@php
    $arrDisplay = fn($arr) => $arr && count($arr) ? implode(', ', $arr) : 'Any';
@endphp
<div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-3">
    <div><p class="text-xs text-gray-500">Age Range</p><p class="text-sm font-medium text-gray-900">{{ $pp->age_from && $pp->age_to ? $pp->age_from . ' - ' . $pp->age_to . ' years' : 'Any' }}</p></div>
    <div><p class="text-xs text-gray-500">Height Range</p><p class="text-sm font-medium text-gray-900">{{ $pp->height_from_cm && $pp->height_to_cm ? $pp->height_from_cm . ' - ' . $pp->height_to_cm : 'Any' }}</p></div>
    <div><p class="text-xs text-gray-500">Marital Status</p><p class="text-sm font-medium text-gray-900">{{ $arrDisplay($pp->marital_status) }}</p></div>
    <div><p class="text-xs text-gray-500">Complexion</p><p class="text-sm font-medium text-gray-900">{{ $arrDisplay($pp->complexion) }}</p></div>
    <div><p class="text-xs text-gray-500">Body Type</p><p class="text-sm font-medium text-gray-900">{{ $arrDisplay($pp->body_type) }}</p></div>
    <div><p class="text-xs text-gray-500">Physical Status</p><p class="text-sm font-medium text-gray-900">{{ $arrDisplay($pp->physical_status) }}</p></div>
    <div><p class="text-xs text-gray-500">Family Status</p><p class="text-sm font-medium text-gray-900">{{ $arrDisplay($pp->family_status) }}</p></div>
    <div><p class="text-xs text-gray-500">Religion</p><p class="text-sm font-medium text-gray-900">{{ $arrDisplay($pp->religions) }}</p></div>
    @if($pp->religions && in_array('Christian', $pp->religions))
        <div><p class="text-xs text-gray-500">Denomination</p><p class="text-sm font-medium text-gray-900">{{ $arrDisplay($pp->denomination) }}</p></div>
        <div><p class="text-xs text-gray-500">Diocese</p><p class="text-sm font-medium text-gray-900">{{ $arrDisplay($pp->diocese) }}</p></div>
    @endif
    @if($pp->religions && (in_array('Hindu', $pp->religions) || in_array('Jain', $pp->religions)))
        <div><p class="text-xs text-gray-500">Caste</p><p class="text-sm font-medium text-gray-900">{{ $arrDisplay($pp->caste) }}</p></div>
    @endif
    <div><p class="text-xs text-gray-500">Mother Tongue</p><p class="text-sm font-medium text-gray-900">{{ $arrDisplay($pp->mother_tongues) }}</p></div>
    <div><p class="text-xs text-gray-500">Education</p><p class="text-sm font-medium text-gray-900">{{ $arrDisplay($pp->education_levels) }}</p></div>
    <div><p class="text-xs text-gray-500">Occupation</p><p class="text-sm font-medium text-gray-900">{{ $arrDisplay($pp->occupations) }}</p></div>
    <div><p class="text-xs text-gray-500">Working Country</p><p class="text-sm font-medium text-gray-900">{{ $arrDisplay($pp->working_countries) }}</p></div>
    <div><p class="text-xs text-gray-500">Native Country</p><p class="text-sm font-medium text-gray-900">{{ $arrDisplay($pp->native_countries) }}</p></div>
</div>
@if($pp->about_partner)
    <div class="mt-4 pt-3 border-t border-gray-100">
        <p class="text-xs text-gray-500 mb-1">About Partner</p>
        <p class="text-sm text-gray-700">{{ $pp->about_partner }}</p>
    </div>
@endif
@endif

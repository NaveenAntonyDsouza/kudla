@php $e = $profile->educationDetail; @endphp
@if(!$e)
    <p class="text-sm text-gray-400">No education information added yet.</p>
@else
<div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-3">
    <div><p class="text-xs text-gray-500">Highest Education</p><p class="text-sm font-medium text-gray-900">{{ $e->highest_education ?? 'Not Mentioned' }}</p></div>
    <div><p class="text-xs text-gray-500">Education Detail</p><p class="text-sm font-medium text-gray-900">{{ $e->education_detail ?? 'Not Mentioned' }}</p></div>
    <div><p class="text-xs text-gray-500">College / University</p><p class="text-sm font-medium text-gray-900">{{ $e->college_name ?? 'Not Mentioned' }}</p></div>
    <div><p class="text-xs text-gray-500">Occupation</p><p class="text-sm font-medium text-gray-900">{{ $e->occupation ?? 'Not Mentioned' }}</p></div>
    <div><p class="text-xs text-gray-500">Occupation Detail</p><p class="text-sm font-medium text-gray-900">{{ $e->occupation_detail ?? 'Not Mentioned' }}</p></div>
    <div><p class="text-xs text-gray-500">Employer Name</p><p class="text-sm font-medium text-gray-900">{{ $e->employer_name ?? 'Not Mentioned' }}</p></div>
    <div><p class="text-xs text-gray-500">Annual Income</p><p class="text-sm font-medium text-gray-900">{{ $e->annual_income ?? 'Not Mentioned' }}</p></div>
    <div><p class="text-xs text-gray-500">Working Country</p><p class="text-sm font-medium text-gray-900">{{ $e->working_country ?? 'Not Mentioned' }}</p></div>
    @if($e->working_state)
        <div><p class="text-xs text-gray-500">Working State</p><p class="text-sm font-medium text-gray-900">{{ $e->working_state }}</p></div>
    @endif
    @if($e->working_district)
        <div><p class="text-xs text-gray-500">Working District</p><p class="text-sm font-medium text-gray-900">{{ $e->working_district }}</p></div>
    @endif
</div>
@endif

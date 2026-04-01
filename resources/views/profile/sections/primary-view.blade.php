@php $p = $profile; $da = $profile->differentlyAbledInfo; $lang = $profile->lifestyleInfo?->languages_known; @endphp
<div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-3">
    <div><p class="text-xs text-gray-500">Full Name</p><p class="text-sm font-medium text-gray-900">{{ $p->full_name ?? 'Not Mentioned' }}</p></div>
    <div><p class="text-xs text-gray-500">Gender</p><p class="text-sm font-medium text-gray-900">{{ $p->gender ?? 'Not Mentioned' }}</p></div>
    <div><p class="text-xs text-gray-500">Date of Birth</p><p class="text-sm font-medium text-gray-900">{{ $p->date_of_birth?->format('d/m/Y') ?? 'Not Mentioned' }}</p></div>
    <div><p class="text-xs text-gray-500">Age</p><p class="text-sm font-medium text-gray-900">{{ $p->age ? $p->age . ' years' : 'Not Mentioned' }}</p></div>
    <div><p class="text-xs text-gray-500">Height</p><p class="text-sm font-medium text-gray-900">{{ $p->height ?? 'Not Mentioned' }}</p></div>
    <div><p class="text-xs text-gray-500">Weight</p><p class="text-sm font-medium text-gray-900">{{ $p->weight_kg ?? 'Not Mentioned' }}</p></div>
    <div><p class="text-xs text-gray-500">Complexion</p><p class="text-sm font-medium text-gray-900">{{ $p->complexion ?? 'Not Mentioned' }}</p></div>
    <div><p class="text-xs text-gray-500">Body Type</p><p class="text-sm font-medium text-gray-900">{{ $p->body_type ?? 'Not Mentioned' }}</p></div>
    <div><p class="text-xs text-gray-500">Blood Group</p><p class="text-sm font-medium text-gray-900">{{ $p->blood_group ?? 'Not Mentioned' }}</p></div>
    <div><p class="text-xs text-gray-500">Mother Tongue</p><p class="text-sm font-medium text-gray-900">{{ $p->mother_tongue ?? 'Not Mentioned' }}</p></div>
    <div><p class="text-xs text-gray-500">Languages Known</p><p class="text-sm font-medium text-gray-900">{{ $lang && count($lang) ? implode(', ', $lang) : 'Not Mentioned' }}</p></div>
    <div><p class="text-xs text-gray-500">Marital Status</p><p class="text-sm font-medium text-gray-900">{{ $p->marital_status ?? 'Not Mentioned' }}</p></div>
    @if($p->marital_status && $p->marital_status !== 'Unmarried')
        <div><p class="text-xs text-gray-500">Children with Me</p><p class="text-sm font-medium text-gray-900">{{ $p->children_with_me ?? 0 }}</p></div>
        <div><p class="text-xs text-gray-500">Children not with Me</p><p class="text-sm font-medium text-gray-900">{{ $p->children_not_with_me ?? 0 }}</p></div>
    @endif
    <div><p class="text-xs text-gray-500">Physical Status</p><p class="text-sm font-medium text-gray-900">{{ $p->physical_status ?? 'Normal' }}</p></div>
    @if($da)
        <div><p class="text-xs text-gray-500">DA Category</p><p class="text-sm font-medium text-gray-900">{{ $da->category ?? 'Not Mentioned' }}</p></div>
    @endif
</div>
@if($p->about_me)
    <div class="mt-4 pt-3 border-t border-gray-100">
        <p class="text-xs text-gray-500 mb-1">About the Candidate</p>
        <p class="text-sm text-gray-700">{{ $p->about_me }}</p>
    </div>
@endif

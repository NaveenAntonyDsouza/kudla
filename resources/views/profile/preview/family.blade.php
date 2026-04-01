@php
    $f = $profile->familyDetail;
    $nm = fn($v) => $v ?: 'Not Mentioned';
@endphp

@if(!$f)
    <div class="bg-white rounded-lg border border-gray-200 shadow-xs p-6">
        <p class="text-sm text-gray-400">No family information added yet.</p>
    </div>
@else
{{-- ── Family Information ── --}}
<div class="bg-white rounded-lg border border-gray-200 shadow-xs p-6 mb-6">
    <h3 class="text-lg font-semibold text-(--color-primary) mb-4">Family Information</h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4">
        <div class="sm:col-span-2"><p class="text-xs text-gray-500">Family Status</p><p class="text-sm font-semibold text-gray-900">{{ $nm($f->family_status) }}</p></div>
        <div><p class="text-xs text-gray-500">Father's Name</p><p class="text-sm font-semibold text-gray-900">{{ $nm($f->father_name) }}</p></div>
        <div><p class="text-xs text-gray-500">Mother's Name</p><p class="text-sm font-semibold text-gray-900">{{ $nm($f->mother_name) }}</p></div>
        <div><p class="text-xs text-gray-500">Father's Family Name / Surname</p><p class="text-sm font-semibold text-gray-900">{{ $nm($f->father_house_name) }}</p></div>
        <div><p class="text-xs text-gray-500">Mother's Maiden Family Name</p><p class="text-sm font-semibold text-gray-900">{{ $nm($f->mother_house_name) }}</p></div>
        <div><p class="text-xs text-gray-500">Father's Native Place</p><p class="text-sm font-semibold text-gray-900">{{ $nm($f->father_native_place) }}</p></div>
        <div><p class="text-xs text-gray-500">Mother's Native Place</p><p class="text-sm font-semibold text-gray-900">{{ $nm($f->mother_native_place) }}</p></div>
        <div><p class="text-xs text-gray-500">Father's Occupation</p><p class="text-sm font-semibold text-gray-900">{{ $nm($f->father_occupation) }}</p></div>
        <div><p class="text-xs text-gray-500">Mother's Occupation</p><p class="text-sm font-semibold text-gray-900">{{ $nm($f->mother_occupation) }}</p></div>
    </div>
</div>

{{-- ── Sibling Details ── --}}
<div class="bg-white rounded-lg border border-gray-200 shadow-xs p-6 mb-6">
    <h3 class="text-lg font-semibold text-(--color-primary) mb-4">Sibling Details</h3>
    <h4 class="text-sm font-semibold text-(--color-primary) mb-3">No. of Brothers</h4>
    <div class="grid grid-cols-3 gap-4 mb-5">
        <div><p class="text-xs text-gray-500">Married</p><p class="text-sm font-semibold text-gray-900">{{ $f->brothers_married ?? 0 }}</p></div>
        <div><p class="text-xs text-gray-500">UnMarried</p><p class="text-sm font-semibold text-gray-900">{{ $f->brothers_unmarried ?? 0 }}</p></div>
        <div><p class="text-xs text-gray-500">Priest</p><p class="text-sm font-semibold text-gray-900">{{ $f->brothers_priest ?? 0 }}</p></div>
    </div>
    <h4 class="text-sm font-semibold text-(--color-primary) mb-3">No. of Sisters</h4>
    <div class="grid grid-cols-3 gap-4 mb-5">
        <div><p class="text-xs text-gray-500">Married</p><p class="text-sm font-semibold text-gray-900">{{ $f->sisters_married ?? 0 }}</p></div>
        <div><p class="text-xs text-gray-500">UnMarried</p><p class="text-sm font-semibold text-gray-900">{{ $f->sisters_unmarried ?? 0 }}</p></div>
        <div><p class="text-xs text-gray-500">Nun</p><p class="text-sm font-semibold text-gray-900">{{ $f->sisters_nun ?? 0 }}</p></div>
    </div>
    <div class="border-t border-gray-100 pt-4 space-y-3">
        <div><p class="text-xs text-gray-500">Candidate's Asset Details</p><p class="text-sm font-semibold text-gray-900">{{ $nm($f->candidate_asset_details) }}</p></div>
        <div><p class="text-xs text-gray-500">About Candidate's Family</p><p class="text-sm font-semibold text-gray-900">{{ $nm($f->about_candidate_family) }}</p></div>
    </div>
</div>

{{-- ── Profile Created By ── --}}
<div class="bg-white rounded-lg border border-gray-200 shadow-xs p-6">
    <div><p class="text-xs text-gray-500">Profile Created By</p><p class="text-sm font-semibold text-gray-900">{{ $nm($profile->created_by) }}</p></div>
</div>
@endif

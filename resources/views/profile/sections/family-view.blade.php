@php $f = $profile->familyDetail; @endphp
@if(!$f)
    <p class="text-sm text-gray-400">No family information added yet.</p>
@else
<div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-3">
    <div><p class="text-xs text-gray-500">Family Status</p><p class="text-sm font-medium text-gray-900">{{ $f->family_status ?? 'Not Mentioned' }}</p></div>
    <div><p class="text-xs text-gray-500">Father's Name</p><p class="text-sm font-medium text-gray-900">{{ $f->father_name ?? 'Not Mentioned' }}</p></div>
    <div><p class="text-xs text-gray-500">Father's Family Name / Surname</p><p class="text-sm font-medium text-gray-900">{{ $f->father_house_name ?? 'Not Mentioned' }}</p></div>
    <div><p class="text-xs text-gray-500">Father's Native Place</p><p class="text-sm font-medium text-gray-900">{{ $f->father_native_place ?? 'Not Mentioned' }}</p></div>
    <div><p class="text-xs text-gray-500">Father's Occupation</p><p class="text-sm font-medium text-gray-900">{{ $f->father_occupation ?? 'Not Mentioned' }}</p></div>
    <div><p class="text-xs text-gray-500">Mother's Name</p><p class="text-sm font-medium text-gray-900">{{ $f->mother_name ?? 'Not Mentioned' }}</p></div>
    <div><p class="text-xs text-gray-500">Mother's Maiden Family Name</p><p class="text-sm font-medium text-gray-900">{{ $f->mother_house_name ?? 'Not Mentioned' }}</p></div>
    <div><p class="text-xs text-gray-500">Mother's Native Place</p><p class="text-sm font-medium text-gray-900">{{ $f->mother_native_place ?? 'Not Mentioned' }}</p></div>
    <div><p class="text-xs text-gray-500">Mother's Occupation</p><p class="text-sm font-medium text-gray-900">{{ $f->mother_occupation ?? 'Not Mentioned' }}</p></div>
</div>

{{-- Siblings --}}
<div class="mt-4 pt-3 border-t border-gray-100">
    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Sibling Details</p>
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
        <div>
            <p class="text-xs text-gray-500">No. of Brothers</p>
            <div class="flex gap-4 mt-1 text-sm">
                <span>Married: <strong>{{ $f->brothers_married ?? 0 }}</strong></span>
                <span>Unmarried: <strong>{{ $f->brothers_unmarried ?? 0 }}</strong></span>
                @if($f->brothers_priest)<span>Priest: <strong>{{ $f->brothers_priest }}</strong></span>@endif
            </div>
        </div>
        <div>
            <p class="text-xs text-gray-500">No. of Sisters</p>
            <div class="flex gap-4 mt-1 text-sm">
                <span>Married: <strong>{{ $f->sisters_married ?? 0 }}</strong></span>
                <span>Unmarried: <strong>{{ $f->sisters_unmarried ?? 0 }}</strong></span>
                @if($f->sisters_nun)<span>Nun: <strong>{{ $f->sisters_nun }}</strong></span>@endif
            </div>
        </div>
    </div>
</div>

@if($f->candidate_asset_details)
    <div class="mt-4 pt-3 border-t border-gray-100">
        <p class="text-xs text-gray-500 mb-1">Candidate's Asset Details</p>
        <p class="text-sm text-gray-700">{{ $f->candidate_asset_details }}</p>
    </div>
@endif
@if($f->about_candidate_family)
    <div class="mt-3">
        <p class="text-xs text-gray-500 mb-1">About Candidate's Family</p>
        <p class="text-sm text-gray-700">{{ $f->about_candidate_family }}</p>
    </div>
@endif
@endif

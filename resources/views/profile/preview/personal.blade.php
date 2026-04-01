@php
    $p = $profile;
    $r = $profile->religiousInfo;
    $e = $profile->educationDetail;
    $l = $profile->locationInfo;
    $h = $profile->lifestyleInfo;
    $da = $profile->differentlyAbledInfo;
    $nm = fn($v) => $v ?: 'Not Mentioned';
    $arrNm = fn($v) => $v && count($v) ? implode(', ', $v) : 'Not Mentioned';
@endphp

{{-- ── Primary Information ── --}}
<div class="bg-white rounded-lg border border-gray-200 shadow-xs p-6 mb-6">
    <h3 class="text-lg font-semibold text-(--color-primary) mb-4">Primary Information</h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4">
        <div><p class="text-xs text-gray-500">Full Name</p><p class="text-sm font-semibold text-gray-900">{{ $nm($p->full_name) }}</p></div>
        <div><p class="text-xs text-gray-500">Gender</p><p class="text-sm font-semibold text-gray-900">{{ $nm($p->gender) }}</p></div>
        <div><p class="text-xs text-gray-500">Date of Birth</p><p class="text-sm font-semibold text-gray-900">{{ $p->date_of_birth?->format('d/m/Y') ?? 'Not Mentioned' }}</p></div>
        <div><p class="text-xs text-gray-500">Age</p><p class="text-sm font-semibold text-gray-900">{{ $p->age ? $p->age . ' Yrs ' . ($p->date_of_birth ? floor($p->date_of_birth->diffInMonths(now()) % 12) . ' months' : '') : 'Not Mentioned' }}</p></div>
        <div><p class="text-xs text-gray-500">Height</p><p class="text-sm font-semibold text-gray-900">{{ $nm($p->height) }}</p></div>
        <div><p class="text-xs text-gray-500">Weight</p><p class="text-sm font-semibold text-gray-900">{{ $nm($p->weight_kg) }}</p></div>
        <div><p class="text-xs text-gray-500">Complexion</p><p class="text-sm font-semibold text-gray-900">{{ $nm($p->complexion) }}</p></div>
        <div><p class="text-xs text-gray-500">Body Type</p><p class="text-sm font-semibold text-gray-900">{{ $nm($p->body_type) }}</p></div>
        <div><p class="text-xs text-gray-500">Blood Group</p><p class="text-sm font-semibold text-gray-900">{{ $nm($p->blood_group) }}</p></div>
        <div><p class="text-xs text-gray-500">Mother Tongue</p><p class="text-sm font-semibold text-gray-900">{{ $nm($p->mother_tongue) }}</p></div>
        <div><p class="text-xs text-gray-500">Marital Status</p><p class="text-sm font-semibold text-gray-900">{{ $nm($p->marital_status) }}</p></div>
        <div><p class="text-xs text-gray-500">Physical Status</p><p class="text-sm font-semibold text-gray-900">{{ $nm($p->physical_status) ?? 'Normal' }}</p></div>
        @if($da)
            <div><p class="text-xs text-gray-500">DA Category</p><p class="text-sm font-semibold text-gray-900">{{ $nm($da->category) }}</p></div>
        @endif
    </div>
    @if($p->about_me)
        <div class="mt-4 pt-3 border-t border-gray-100">
            <p class="text-xs text-gray-500 mb-1">About the Candidate</p>
            <p class="text-sm text-gray-700">{{ $p->about_me }}</p>
        </div>
    @endif
</div>

{{-- ── Religious Information ── --}}
@if($r)
<div class="bg-white rounded-lg border border-gray-200 shadow-xs p-6 mb-6">
    <h3 class="text-lg font-semibold text-(--color-primary) mb-4">Religious Information</h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4">
        <div><p class="text-xs text-gray-500">Religion</p><p class="text-sm font-semibold text-gray-900">{{ $nm($r->religion) }}</p></div>
        @if($r->religion === 'Christian')
            <div><p class="text-xs text-gray-500">Denomination</p><p class="text-sm font-semibold text-gray-900">{{ $nm($r->denomination) }}</p></div>
            <div><p class="text-xs text-gray-500">Diocese Name</p><p class="text-sm font-semibold text-gray-900">{{ $nm($r->diocese_name ?? $r->diocese) }}</p></div>
            <div><p class="text-xs text-gray-500">Parish Name and Place</p><p class="text-sm font-semibold text-gray-900">{{ $nm($r->parish_name_place) }}</p></div>
        @endif
        @if(in_array($r->religion, ['Hindu', 'Jain']))
            <div><p class="text-xs text-gray-500">Caste</p><p class="text-sm font-semibold text-gray-900">{{ $nm($r->caste) }}</p></div>
            <div><p class="text-xs text-gray-500">Sub Caste</p><p class="text-sm font-semibold text-gray-900">{{ $nm($r->sub_caste) }}</p></div>
            <div><p class="text-xs text-gray-500">Gotra</p><p class="text-sm font-semibold text-gray-900">{{ $nm($r->gotra) }}</p></div>
        @endif
        @if($r->religion === 'Muslim')
            <div><p class="text-xs text-gray-500">Muslim Sect</p><p class="text-sm font-semibold text-gray-900">{{ $nm($r->muslim_sect) }}</p></div>
            <div><p class="text-xs text-gray-500">Muslim Community</p><p class="text-sm font-semibold text-gray-900">{{ $nm($r->muslim_community) }}</p></div>
        @endif
    </div>
</div>
@endif

{{-- ── Education & Profession ── --}}
@if($e)
<div class="bg-white rounded-lg border border-gray-200 shadow-xs p-6 mb-6">
    <h3 class="text-lg font-semibold text-(--color-primary) mb-4">Education & Profession</h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4">
        <div><p class="text-xs text-gray-500">Educational Qualifications</p><p class="text-sm font-semibold text-gray-900">{{ $nm($e->highest_education) }}</p></div>
        <div><p class="text-xs text-gray-500">Education in Detail</p><p class="text-sm font-semibold text-gray-900">{{ $nm($e->education_detail) }}</p></div>
        <div><p class="text-xs text-gray-500">Occupation Category</p><p class="text-sm font-semibold text-gray-900">{{ $nm($e->occupation) }}</p></div>
        <div><p class="text-xs text-gray-500">Occupation in Detail</p><p class="text-sm font-semibold text-gray-900">{{ $nm($e->occupation_detail) }}</p></div>
        <div><p class="text-xs text-gray-500">Employment Category</p><p class="text-sm font-semibold text-gray-900">{{ $nm($e->employment_category) }}</p></div>
        <div><p class="text-xs text-gray-500">Organization Name</p><p class="text-sm font-semibold text-gray-900">{{ $nm($e->employer_name) }}</p></div>
        <div><p class="text-xs text-gray-500">Working Country</p><p class="text-sm font-semibold text-gray-900">{{ $nm($e->working_country) }}</p></div>
        <div><p class="text-xs text-gray-500">Working State</p><p class="text-sm font-semibold text-gray-900">{{ $nm($e->working_state) }}</p></div>
        @if($e->working_district)
            <div><p class="text-xs text-gray-500">Working District</p><p class="text-sm font-semibold text-gray-900">{{ $e->working_district }}</p></div>
        @endif
        <div><p class="text-xs text-gray-500">Annual Income</p><p class="text-sm font-semibold text-gray-900">{{ $nm($e->annual_income) }}</p></div>
    </div>
</div>
@endif

{{-- ── Location Information ── --}}
@if($l)
<div class="bg-white rounded-lg border border-gray-200 shadow-xs p-6 mb-6">
    <h3 class="text-lg font-semibold text-(--color-primary) mb-4">Location Information</h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4">
        <div><p class="text-xs text-gray-500">Native Country</p><p class="text-sm font-semibold text-gray-900">{{ $nm($l->native_country) }}</p></div>
        <div><p class="text-xs text-gray-500">Native State</p><p class="text-sm font-semibold text-gray-900">{{ $nm($l->native_state) }}</p></div>
        <div><p class="text-xs text-gray-500">Native District</p><p class="text-sm font-semibold text-gray-900">{{ $nm($l->native_district) }}</p></div>
        <div><p class="text-xs text-gray-500">Residing Country</p><p class="text-sm font-semibold text-gray-900">{{ $nm($l->residing_country) }}</p></div>
    </div>
    @if($l->residing_country && $l->residing_country !== 'India')
        <h4 class="text-sm font-semibold text-(--color-primary) mt-4 mb-2">Outstation Candidate's Next Leave Date</h4>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4">
            <div><p class="text-xs text-gray-500">From Date</p><p class="text-sm font-semibold text-gray-900">{{ $l->outstation_leave_date_from?->format('d/m/Y') ?? 'Not Mentioned' }}</p></div>
            <div><p class="text-xs text-gray-500">To Date</p><p class="text-sm font-semibold text-gray-900">{{ $l->outstation_leave_date_to?->format('d/m/Y') ?? 'Not Mentioned' }}</p></div>
        </div>
    @endif
</div>
@endif

{{-- ── Hobbies & Interests ── --}}
@if($h)
<div class="bg-white rounded-lg border border-gray-200 shadow-xs p-6">
    <h3 class="text-lg font-semibold text-(--color-primary) mb-4">Hobbies & Interests</h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4">
        <div><p class="text-xs text-gray-500">Hobbies</p><p class="text-sm font-semibold text-gray-900">{{ $arrNm($h->hobbies) }}</p></div>
        <div><p class="text-xs text-gray-500">Favorite Music</p><p class="text-sm font-semibold text-gray-900">{{ $arrNm($h->favorite_music) }}</p></div>
        <div><p class="text-xs text-gray-500">Preferred Books</p><p class="text-sm font-semibold text-gray-900">{{ $arrNm($h->preferred_books) }}</p></div>
        <div><p class="text-xs text-gray-500">Preferred Movies</p><p class="text-sm font-semibold text-gray-900">{{ $arrNm($h->preferred_movies) }}</p></div>
        <div><p class="text-xs text-gray-500">Sports / Fitness / Games</p><p class="text-sm font-semibold text-gray-900">{{ $arrNm($h->sports_fitness_games) }}</p></div>
        <div><p class="text-xs text-gray-500">Favorite Cuisine</p><p class="text-sm font-semibold text-gray-900">{{ $arrNm($h->favorite_cuisine) }}</p></div>
        <div><p class="text-xs text-gray-500">Spoken Languages</p><p class="text-sm font-semibold text-gray-900">{{ $arrNm($h->languages_known) }}</p></div>
        <div><p class="text-xs text-gray-500">Cultural Background</p><p class="text-sm font-semibold text-gray-900">{{ $nm($h->cultural_background) }}</p></div>
        <div><p class="text-xs text-gray-500">Eating Habits</p><p class="text-sm font-semibold text-gray-900">{{ $nm($h->diet) }}</p></div>
        <div><p class="text-xs text-gray-500">Drinking Habits</p><p class="text-sm font-semibold text-gray-900">{{ $nm($h->drinking) }}</p></div>
        <div><p class="text-xs text-gray-500">Smoking Habits</p><p class="text-sm font-semibold text-gray-900">{{ $nm($h->smoking) }}</p></div>
    </div>
</div>
@endif

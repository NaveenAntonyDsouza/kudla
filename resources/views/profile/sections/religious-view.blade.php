@php $r = $profile->religiousInfo; @endphp
@if(!$r)
    <p class="text-sm text-gray-400">No religious information added yet.</p>
@else
<div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-3">
    <div><p class="text-xs text-gray-500">Religion</p><p class="text-sm font-medium text-gray-900">{{ $r->religion ?? 'Not Mentioned' }}</p></div>
    @if($r->religion === 'Christian')
        <div><p class="text-xs text-gray-500">Denomination</p><p class="text-sm font-medium text-gray-900">{{ $r->denomination ?? 'Not Mentioned' }}</p></div>
        <div><p class="text-xs text-gray-500">Diocese</p><p class="text-sm font-medium text-gray-900">{{ $r->diocese_name ?? $r->diocese ?? 'Not Mentioned' }}</p></div>
        <div><p class="text-xs text-gray-500">Parish Name & Place</p><p class="text-sm font-medium text-gray-900">{{ $r->parish_name_place ?? 'Not Mentioned' }}</p></div>
    @endif
    @if(in_array($r->religion, ['Hindu', 'Jain']))
        <div><p class="text-xs text-gray-500">Caste</p><p class="text-sm font-medium text-gray-900">{{ $r->caste ?? 'Not Mentioned' }}</p></div>
        <div><p class="text-xs text-gray-500">Sub Caste</p><p class="text-sm font-medium text-gray-900">{{ $r->sub_caste ?? 'Not Mentioned' }}</p></div>
        <div><p class="text-xs text-gray-500">Gotra</p><p class="text-sm font-medium text-gray-900">{{ $r->gotra ?? 'Not Mentioned' }}</p></div>
        <div><p class="text-xs text-gray-500">Nakshatra</p><p class="text-sm font-medium text-gray-900">{{ $r->nakshatra ?? 'Not Mentioned' }}</p></div>
        <div><p class="text-xs text-gray-500">Rashi</p><p class="text-sm font-medium text-gray-900">{{ $r->rashi ?? 'Not Mentioned' }}</p></div>
        <div><p class="text-xs text-gray-500">Manglik / Chovva Dosham</p><p class="text-sm font-medium text-gray-900">{{ $r->dosh ?? 'Not Mentioned' }}</p></div>
    @endif
    @if($r->religion === 'Muslim')
        <div><p class="text-xs text-gray-500">Muslim Sect</p><p class="text-sm font-medium text-gray-900">{{ $r->muslim_sect ?? 'Not Mentioned' }}</p></div>
        <div><p class="text-xs text-gray-500">Muslim Community</p><p class="text-sm font-medium text-gray-900">{{ $r->muslim_community ?? 'Not Mentioned' }}</p></div>
    @endif
    @if($r->religion === 'Jain')
        <div><p class="text-xs text-gray-500">Jain Sect</p><p class="text-sm font-medium text-gray-900">{{ $r->jain_sect ?? 'Not Mentioned' }}</p></div>
    @endif
    <div><p class="text-xs text-gray-500">Time of Birth</p><p class="text-sm font-medium text-gray-900">{{ $r->time_of_birth ?? 'Not Mentioned' }}</p></div>
    <div><p class="text-xs text-gray-500">Place of Birth</p><p class="text-sm font-medium text-gray-900">{{ $r->place_of_birth ?? 'Not Mentioned' }}</p></div>
    @if($r->jathakam_upload_url)
        <div>
            <p class="text-xs text-gray-500">Jathakam / Horoscope</p>
            <a href="{{ Storage::disk('public')->url($r->jathakam_upload_url) }}" target="_blank" class="text-sm text-(--color-primary) hover:underline font-medium">View Uploaded</a>
        </div>
    @endif
</div>
@endif

{{-- "Share my profile" (Nov 2026, idea from Chavara's "Forward profile"): a member sends
     THEIR OWN profile to parents or relatives — on WhatsApp as a short summary they see
     before sending, or as the printable page (save as PDF). Only the member's own profile:
     never another member's details off the site. No phone or email in the text. --}}
@props(['profile'])
@php
    $sp = $profile;
    $spSite = \App\Models\SiteSetting::getValue('site_name', config('app.name'));
    $spRel = $sp->religiousInfo;
    $spEdu = $sp->educationDetail;
    $spLoc = $sp->locationInfo;
    $spText = collect([
        trim($sp->full_name . ' — ' . $spSite . ' profile ' . $sp->matri_id),
        collect([$sp->age ? $sp->age . ' years' : null, $sp->marital_status])->filter()->implode(', '),
        collect([$spRel?->display_denomination ?? $spRel?->display_caste ?? $spRel?->religion, $sp->mother_tongue])->filter()->implode(', '),
        collect([$spEdu?->highest_education, $spEdu?->occupation])->filter()->implode(', '),
        collect([$spLoc?->native_district ? 'From ' . $spLoc->native_district : null, $spEdu?->working_city ? 'works in ' . $spEdu->working_city : null])->filter()->implode(', '),
    ])->filter(fn ($l) => $l !== null && $l !== '')->implode("\n")
        . "\n\nSee the full profile on " . url('/') . ' (ID ' . $sp->matri_id . ')';
@endphp
<div {{ $attributes->merge(['class' => 'space-y-1']) }}>
    <a href="https://wa.me/?text={{ rawurlencode($spText) }}" target="_blank" rel="noopener"
       class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7.217 10.907a2.25 2.25 0 100 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186l9.566-5.314m-9.566 7.5l9.566 5.314m0 0a2.25 2.25 0 103.935 2.186 2.25 2.25 0 00-3.935-2.186zm0-12.814a2.25 2.25 0 103.933-2.185 2.25 2.25 0 00-3.933 2.185z"/></svg>
        Share my profile on WhatsApp
    </a>
    <a href="{{ route('profile.print', $sp) }}" target="_blank" rel="noopener"
       class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z"/></svg>
        Print or save as PDF
    </a>
</div>

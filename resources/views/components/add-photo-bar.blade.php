{{-- "Add your photo" bar for members with no photo (weekly review, 9 Nov 2026: 483 live
     profiles had none, photo emails brought back 10 of 229 recipients, while 126 members
     without a photo were on the site that month). Shown only to the member themselves;
     a photo waiting for approval counts as having one. Inline styles: no CSS build. --}}
@php
    $apProfile = auth()->user()?->profile;
    $apShow = $apProfile && ! $apProfile->profilePhotos()->whereIn('approval_status', ['approved', 'pending'])->exists();
@endphp
@if($apShow)
    <a href="{{ route('photos.manage') }}" {{ $attributes->merge(['style' => 'display:flex;align-items:center;gap:12px;padding:14px 18px;border-radius:8px;background:var(--color-primary-light,#F3E8F7);border:1px solid color-mix(in srgb,var(--color-primary,#7B1FA2) 30%,transparent);text-decoration:none;margin-bottom:20px']) }}>
        <span style="display:flex;align-items:center;justify-content:center;width:36px;height:36px;flex:none;border-radius:9999px;background:var(--color-primary,#7B1FA2);color:#fff">
            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        </span>
        <span style="flex:1;min-width:0">
            <span style="display:block;font-size:14px;font-weight:600;color:var(--color-primary,#7B1FA2)">Add your photo</span>
            <span style="display:block;font-size:12px;color:#4b5563;margin-top:2px">A photo helps families notice your profile. You choose who can see it.</span>
        </span>
        <span style="font-size:13px;font-weight:600;color:var(--color-primary,#7B1FA2);white-space:nowrap">Add photo &rarr;</span>
    </a>
@endif

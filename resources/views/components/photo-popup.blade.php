{{-- "Add your photo" pop-up (16 Nov 2026): once a day for members with no photo.
     Every rule is in App\Support\PhotoPrompt::popupDue(). Inline styles: no CSS build. --}}
@if(\App\Support\PhotoPrompt::popupDue())
    <div x-data="{ open: true }" x-show="open" x-cloak @keydown.escape.window="open = false"
         role="dialog" aria-modal="true" aria-labelledby="photo-popup-title" data-photo-popup
         style="position:fixed;inset:0;z-index:70">
        {{-- x-show sets and clears display on the outer box, so the centring lives on this one --}}
        <div style="display:flex;align-items:center;justify-content:center;width:100%;height:100%;padding:16px;box-sizing:border-box;background:rgba(17,24,39,.55)">
        <div @click.outside="open = false"
             style="width:100%;max-width:380px;background:#fff;border-radius:14px;padding:26px 22px 18px;text-align:center;box-shadow:0 20px 50px rgba(0,0,0,.25)">
            <span style="display:inline-flex;align-items:center;justify-content:center;width:56px;height:56px;border-radius:9999px;background:var(--color-primary-light,#F3E8F7);color:var(--color-primary,#7B1FA2)">
                <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </span>
            <h2 id="photo-popup-title" style="margin:14px 0 6px;font-size:18px;font-weight:700;color:#111827">Add your photo</h2>
            <p style="margin:0;font-size:14px;line-height:1.5;color:#4b5563">A photo helps families notice your profile. You choose who can see it.</p>
            <a href="{{ route('photos.manage') }}"
               style="display:block;margin-top:20px;padding:11px 16px;border-radius:9999px;background:var(--color-primary,#7B1FA2);color:#fff;font-size:15px;font-weight:600;text-decoration:none">Add photo</a>
            <button type="button" @click="open = false"
                    style="display:block;width:100%;margin-top:8px;padding:9px;border:0;background:transparent;color:#6b7280;font-size:14px;cursor:pointer">Not now</button>
        </div>
        </div>
    </div>
@endif

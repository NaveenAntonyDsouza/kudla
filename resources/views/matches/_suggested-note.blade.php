{{-- Shown when matches come from App\Support\SuggestedPreferences: the member never
     saved partner preferences, so these are based on their own profile (17 Nov 2026).
     Inline styles: no CSS build. --}}
<div data-suggested-preferences style="display:flex;flex-wrap:wrap;align-items:center;gap:10px 16px;padding:12px 16px;border-radius:8px;background:#FFFBEB;border:1px solid #FDE68A;margin-bottom:{{ $gap ?? '20px' }}">
    <span style="flex:1;min-width:220px;font-size:13px;line-height:1.5;color:#92400E">
        These matches are based on your own profile: age, religion and height.
        Set your partner preferences to see the matches you are looking for.
    </span>
    <a href="{{ route('onboarding.preferences') }}" style="flex:none;font-size:13px;font-weight:600;color:var(--color-primary,#7B1FA2);text-decoration:none;white-space:nowrap">Set Partner Preferences &rarr;</a>
</div>

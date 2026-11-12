{{-- Staff "Login as member" (App\Support\LoginAs): the bar on every member-site
     page of a visit. Inline styles only: the site's CSS is prebuilt, new utility
     classes would not exist in it. --}}
@php
    $loginAsVisit = request()->attributes->get('login_as.visit');
    $loginAsEnds = $loginAsVisit?->expires_at?->copy()->timezone(config('app.display_timezone', 'Asia/Kolkata'))->format('g:i A');
@endphp
@if($loginAsVisit)
<style>
    body { padding-top: 46px !important; }
    header.sticky { top: 46px !important; }
    #login-as-bar { position: fixed; top: 0; left: 0; right: 0; z-index: 2147483000; min-height: 46px; box-sizing: border-box;
        display: flex; align-items: center; justify-content: center; gap: 12px; flex-wrap: wrap; padding: 6px 12px;
        background: #7c2d12; color: #fff; font: 600 13px/1.3 system-ui, -apple-system, 'Segoe UI', sans-serif; text-align: center; }
    #login-as-bar .la-note { font-weight: 400; opacity: .9; }
    #login-as-bar .la-alert { background: #fff; color: #7c2d12; padding: 2px 8px; border-radius: 4px; }
    #login-as-bar button { background: #fff; color: #7c2d12; border: 0; border-radius: 4px; padding: 5px 14px; font: inherit; cursor: pointer; }
    @media print { #login-as-bar { display: none; } body { padding-top: 0 !important; } }
</style>
<div id="login-as-bar" role="status">
    <span>Staff view of {{ $loginAsVisit->matri_id ?: 'member' }} &middot; view only</span>
    @if(session('login_as_notice'))
        <span class="la-alert">{{ session('login_as_notice') }}</span>
    @else
        <span class="la-note">Nothing can be changed or sent. Recorded in the Audit Trail. Ends {{ $loginAsEnds }}.</span>
    @endif
    <form method="POST" action="{{ route('login-as.exit') }}" style="margin:0">
        @csrf
        <button type="submit">Exit</button>
    </form>
</div>
@endif

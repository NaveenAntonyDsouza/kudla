{{-- Google Tag Manager (noscript) - include right after <body> --}}
{{-- Staff viewing the site as a member ("Login as member"): no tracking at all,
     and the view-only bar instead (this partial opens every page's <body>) --}}
@if(\App\Support\LoginAs::active())
    @include('components.partials.login-as-bar')
@else
@php
    $gtmId = $gtmId ?? \App\Models\SiteSetting::getValue('google_tag_manager_id', '');
@endphp
@if($gtmId)
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $gtmId }}" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
@endif
@endif

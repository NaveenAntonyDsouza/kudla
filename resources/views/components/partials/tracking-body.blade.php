{{-- Google Tag Manager (noscript) - include right after <body> --}}
@php
    $gtmId = $gtmId ?? \App\Models\SiteSetting::getValue('google_tag_manager_id', '');
@endphp
@if($gtmId)
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $gtmId }}" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
@endif

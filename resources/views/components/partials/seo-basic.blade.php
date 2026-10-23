{{-- Description, robots and canonical for the login and registration pages,
     which use their own layouts (until Oct 2026 they had none of these).
     $seoPage = SEO Settings page slug; $robots optional. --}}
@php
    $seoSettings = \App\Models\PageSeoSetting::getForPage($seoPage);
    $seoSiteName = \App\Models\SiteSetting::getValue('site_name', 'Matrimony');
    $seoDescription = $seoSettings?->meta_description
        ?: \App\Models\SiteSetting::getValue('meta_description_default', '')
        ?: \App\Models\SiteSetting::getValue('tagline', 'Find Your Perfect Match') . ' - ' . $seoSiteName . '. Register free and find your perfect life partner.';
    $seoImage = \App\Support\ShareImage::for($seoSettings?->og_image_url);
@endphp
<meta name="description" content="{{ Str::limit(strip_tags($seoDescription), 160) }}">
<meta name="robots" content="{{ $robots ?? 'index, follow' }}">
<link rel="canonical" href="{{ url()->current() }}">
<meta property="og:title" content="{{ $seoTitle }}">
<meta property="og:description" content="{{ Str::limit(strip_tags($seoDescription), 160) }}">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:site_name" content="{{ $seoSiteName }}">
<meta property="og:type" content="website">
@if($seoImage)
<meta property="og:image" content="{{ $seoImage }}">
<meta name="twitter:card" content="{{ \App\Support\ShareImage::twitterCard($seoImage) }}">
<meta name="twitter:image" content="{{ $seoImage }}">
@endif

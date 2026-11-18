<?php

use App\Support\DeviceDetector;

/*
| DeviceDetector classifies a User-Agent into the coarse bucket used by
| profiles.registration_source + LoginHistory.device_type. Native-app
| and admin registrations bypass this (set 'App' / 'Admin' directly).
*/

it('classifies desktop user agents', function () {
    $chrome = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36';
    expect(DeviceDetector::type($chrome))->toBe('Desktop');
    expect(DeviceDetector::type('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)'))->toBe('Desktop');
});

it('classifies mobile user agents', function () {
    $androidChrome = 'Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148 Mobile Safari/537.36';
    $iphone = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148';
    expect(DeviceDetector::type($androidChrome))->toBe('Mobile');
    expect(DeviceDetector::type($iphone))->toBe('Mobile');
});

it('classifies tablets (incl. iPad) before mobile', function () {
    $ipad = 'Mozilla/5.0 (iPad; CPU OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148';
    expect(DeviceDetector::type($ipad))->toBe('Tablet');
    expect(DeviceDetector::type('Mozilla/5.0 (Android 14; Tablet)'))->toBe('Tablet');
});

it('falls back to Desktop for empty/unknown user agents', function () {
    expect(DeviceDetector::type(null))->toBe('Desktop');
    expect(DeviceDetector::type(''))->toBe('Desktop');
    expect(DeviceDetector::type('curl/8.0'))->toBe('Desktop');
});

it('counts our Android apps (a WebView of the site) as App, but not other apps\' in-app browsers', function () {
    // real user agents seen on kannadadating.com from its Play app, Nov 2026
    $app = 'Mozilla/5.0 (Linux; Android 14; OPD2305 Build/UP1A; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/156.0.8078.47 Safari/537.36';
    $appPhone = 'Mozilla/5.0 (Linux; Android 13; M2103K19I Build/TP1A; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/156.0.8078.50 Mobile Safari/537.36';
    expect(DeviceDetector::type($app))->toBe('App')
        ->and(DeviceDetector::type($appPhone))->toBe('App');

    $facebook = 'Mozilla/5.0 (Linux; Android 14; SM-A146B Build/UP1A; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/130.0 Mobile Safari/537.36 [FB_IAB/FB4A;FBAV/480.0.0.0;]';
    $instagram = 'Mozilla/5.0 (Linux; Android 13; RMX3630 Build/TP1A; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/130.0 Mobile Safari/537.36 Instagram 350.0.0.0 Android';
    $googleApp = 'Mozilla/5.0 (Linux; Android 14; Pixel 8 Build/AP2A; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/130.0 Mobile Safari/537.36 GSA/15.40.30';
    expect(DeviceDetector::type($facebook))->toBe('Mobile')
        ->and(DeviceDetector::type($instagram))->toBe('Mobile')
        ->and(DeviceDetector::type($googleApp))->toBe('Mobile');

    // ordinary Chrome on the same phone stays Mobile
    expect(DeviceDetector::type('Mozilla/5.0 (Linux; Android 14; OPD2305) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0 Safari/537.36'))->toBe('Mobile');
});

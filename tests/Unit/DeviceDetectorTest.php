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

<?php

use Illuminate\Support\Facades\Cache;

use function Pest\Laravel\getJson;

/*
|--------------------------------------------------------------------------
| GET /api/v1/site/settings
|--------------------------------------------------------------------------
|
| The flagship public endpoint. Flutter calls this on every launch.
| Contract: envelope-shaped response with 8 top-level data sections.
|
| Design reference: docs/mobile-app/design/09-engagement-api.md §9.9
|
| These tests pre-seed the top-level cache key (api:v1:site-settings) so
| the endpoint skips the database entirely. That lets us test the envelope
| + shape contract without needing a MySQL test DB setup. A fuller test
| that exercises SiteSetting::getValue() from a real DB lands in Week 2
| once we wire the MySQL test connection.
*/

beforeEach(function () {
    // Seed the top-level cache with a realistic payload so the endpoint
    // returns it verbatim without hitting SiteSetting::getValue() (which
    // would require a DB with site_settings table).
    Cache::put('api:v1:site-settings', [
        'site' => [
            'name' => 'Test Matrimony',
            'tagline' => 'Find Your Match',
            'logo_url' => 'https://example.com/storage/branding/logo.png',
            'favicon_url' => null,
            'support_email' => 'support@test.com',
            'support_phone' => '+91-1234567890',
            'support_whatsapp' => '+91-9876543210',
            'address' => 'Bangalore, India',
        ],
        'theme' => [
            'primary_color' => '#dc2626',
            'secondary_color' => '#fbbf24',
            'heading_font' => 'Playfair Display',
            'body_font' => 'Inter',
        ],
        'features' => [
            'email_otp_login_enabled' => false,
            'mobile_otp_login_enabled' => true,
            'email_verification_required' => true,
            'phone_verification_required' => false,
            'horoscope_enabled' => false,
            'realtime_chat_enabled' => false,
            'auto_approve_profiles' => true,
        ],
        'registration' => [
            'min_age' => 18,
            'password_min_length' => 6,
            'password_max_length' => 14,
            'id_prefix' => 'AM',
        ],
        'membership' => [
            'razorpay_key' => 'rzp_test_abc',
            'currency' => 'INR',
        ],
        'app' => [
            'minimum_supported_version' => '1.0.0',
            'latest_version' => '1.0.0',
            'force_upgrade_below' => '1.0.0',
            'play_store_url' => '',
            'app_store_url' => '',
        ],
        'social_links' => [
            'facebook' => '',
            'instagram' => '',
            'youtube' => '',
            'linkedin' => '',
        ],
        'policies' => [
            'privacy_policy_url' => 'https://example.com/privacy-policy',
            'terms_url' => 'https://example.com/terms-condition',
            'refund_policy_url' => 'https://example.com/refund-policy',
            'child_safety_url' => 'https://example.com/child-safety',
        ],
    ], now()->addMinutes(5));
});

afterEach(function () {
    Cache::forget('api:v1:site-settings');
});

it('returns envelope-shaped site settings with all 8 sections', function () {
    $response = getJson('/api/v1/site/settings');

    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'data' => [
                'site' => ['name', 'tagline', 'logo_url', 'favicon_url', 'support_email', 'support_phone', 'support_whatsapp', 'address'],
                'theme' => ['primary_color', 'secondary_color', 'heading_font', 'body_font'],
                'features' => [
                    'email_otp_login_enabled',
                    'mobile_otp_login_enabled',
                    'email_verification_required',
                    'phone_verification_required',
                    'horoscope_enabled',
                    'realtime_chat_enabled',
                    'auto_approve_profiles',
                ],
                'registration' => ['min_age', 'password_min_length', 'password_max_length', 'id_prefix'],
                'membership' => ['razorpay_key', 'currency'],
                'app' => ['minimum_supported_version', 'latest_version', 'force_upgrade_below', 'play_store_url', 'app_store_url'],
                'social_links' => ['facebook', 'instagram', 'youtube', 'linkedin'],
                'policies' => ['privacy_policy_url', 'terms_url', 'refund_policy_url', 'child_safety_url'],
            ],
        ])
        ->assertJson(['success' => true]);
});

it('returns feature toggles as booleans', function () {
    $response = getJson('/api/v1/site/settings');

    $features = $response->json('data.features');

    expect($features['mobile_otp_login_enabled'])->toBeBool();
    expect($features['email_otp_login_enabled'])->toBeBool();
    expect($features['email_verification_required'])->toBeBool();
    expect($features['auto_approve_profiles'])->toBeBool();
});

it('always returns realtime_chat_enabled as false in v1', function () {
    $response = getJson('/api/v1/site/settings');

    $response->assertJsonPath('data.features.realtime_chat_enabled', false);
});

it('returns registration rules with correct types', function () {
    $response = getJson('/api/v1/site/settings');

    $reg = $response->json('data.registration');

    expect($reg['min_age'])->toBeInt();
    expect($reg['password_min_length'])->toBeInt();
    expect($reg['password_max_length'])->toBeInt();
    expect($reg['id_prefix'])->toBeString();
});

it('returns absolute URLs for policy links', function () {
    $response = getJson('/api/v1/site/settings');

    $policies = $response->json('data.policies');

    foreach ($policies as $url) {
        expect($url)->toStartWith('http');
    }
});

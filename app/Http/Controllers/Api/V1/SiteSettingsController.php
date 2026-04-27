<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Responses\ApiResponse;
use App\Models\SiteSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * GET /api/v1/site/settings
 *
 * Public endpoint. Flutter app calls this on every launch + on each
 * foreground resume after 1h idle. Used for:
 *
 * - Theme colors + fonts (primary_color, heading_font, …)
 * - Branding (site_name, logo_url, favicon_url, support_email, …)
 * - Feature toggles (email_otp_login_enabled, mobile_otp_login_enabled, …)
 * - Registration rules (min_age, password length limits, id_prefix)
 * - Razorpay public key
 * - App version gates (minimum_supported_version, force_upgrade_below)
 * - Social links + policy URLs
 *
 * Response is cached server-side for 5 minutes.
 *
 * Design reference: docs/mobile-app/design/09-engagement-api.md §9.9
 */
class SiteSettingsController extends BaseApiController
{
    /**
     * Get site configuration
     *
     * Returns site branding, theme colors, feature toggles, registration rules,
     * Razorpay public key, support contact info, mobile-app version gates,
     * social links, and policy URLs. The Flutter app calls this on every
     * launch to hydrate its theme + feature-flag state.
     *
     * Cached server-side for 5 minutes.
     *
     * @unauthenticated
     * @group Configuration
     *
     * @response 200 scenario="success" {
     *   "success": true,
     *   "data": {
     *     "site": {
     *       "name": "Kudla Matrimony",
     *       "tagline": "Find Your Perfect Match",
     *       "logo_url": "https://kudlamatrimony.com/storage/branding/logo.png",
     *       "support_email": "support@kudlamatrimony.com"
     *     },
     *     "theme": { "primary_color": "#dc2626", "heading_font": "Playfair Display" },
     *     "features": { "mobile_otp_login_enabled": true, "realtime_chat_enabled": false },
     *     "registration": { "min_age": 18, "password_min_length": 6, "id_prefix": "AM" },
     *     "membership": { "razorpay_key": "rzp_live_abc", "currency": "INR" },
     *     "app": { "minimum_supported_version": "1.0.0" },
     *     "social_links": { "facebook": "..." },
     *     "policies": { "privacy_policy_url": "https://..." }
     *   }
     * }
     */
    public function show(): JsonResponse
    {
        $data = Cache::remember('api:v1:site-settings', now()->addMinutes(5), function () {
            return [
                'site' => [
                    'name'             => SiteSetting::getValue('site_name', config('app.name')),
                    'tagline'          => SiteSetting::getValue('tagline', ''),
                    'logo_url'         => self::absUrl(SiteSetting::getValue('logo_url')),
                    'favicon_url'      => self::absUrl(SiteSetting::getValue('favicon_url')),
                    'support_email'    => SiteSetting::getValue('support_email', ''),
                    'support_phone'    => SiteSetting::getValue('support_phone', ''),
                    'support_whatsapp' => SiteSetting::getValue('support_whatsapp', ''),
                    'address'          => SiteSetting::getValue('address', ''),
                ],

                'theme' => [
                    'primary_color'   => SiteSetting::getValue('primary_color', '#dc2626'),
                    'secondary_color' => SiteSetting::getValue('secondary_color', '#fbbf24'),
                    'heading_font'    => SiteSetting::getValue('heading_font', 'Playfair Display'),
                    'body_font'       => SiteSetting::getValue('body_font', 'Inter'),
                ],

                'features' => [
                    'email_otp_login_enabled'     => self::bool(SiteSetting::getValue('email_otp_login_enabled', '0')),
                    'mobile_otp_login_enabled'    => self::bool(SiteSetting::getValue('mobile_otp_login_enabled', '1')),
                    'email_verification_required' => self::bool(SiteSetting::getValue('email_verification_enabled', '1')),
                    'phone_verification_required' => self::bool(SiteSetting::getValue('phone_verification_enabled', '0')),
                    'horoscope_enabled'           => self::bool(SiteSetting::getValue('horoscope_enabled', '0')),
                    'realtime_chat_enabled'       => false,  // v1 uses polling; toggled to true when Reverb lands in Phase 3
                    'auto_approve_profiles'       => self::bool(SiteSetting::getValue('auto_approve_profiles', '1')),
                ],

                'registration' => [
                    'min_age'             => (int) config('matrimony.registration_min_age', 18),
                    'password_min_length' => (int) config('matrimony.password_min_length', 6),
                    'password_max_length' => (int) config('matrimony.password_max_length', 14),
                    'id_prefix'           => config('matrimony.id_prefix', 'AM'),
                ],

                'membership' => [
                    'razorpay_key' => SiteSetting::getValue('razorpay_key_id', ''),
                    'currency'     => 'INR',
                ],

                'app' => [
                    'minimum_supported_version' => SiteSetting::getValue('app_min_version', '1.0.0'),
                    'latest_version'            => SiteSetting::getValue('app_latest_version', '1.0.0'),
                    'force_upgrade_below'       => SiteSetting::getValue('app_force_upgrade_below', '1.0.0'),
                    'play_store_url'            => SiteSetting::getValue('play_store_url', ''),
                    'app_store_url'             => SiteSetting::getValue('app_store_url', ''),
                ],

                'social_links' => [
                    'facebook'  => SiteSetting::getValue('facebook_url', ''),
                    'instagram' => SiteSetting::getValue('instagram_url', ''),
                    'youtube'   => SiteSetting::getValue('youtube_url', ''),
                    'linkedin'  => SiteSetting::getValue('linkedin_url', ''),
                ],

                'policies' => [
                    'privacy_policy_url' => url('/privacy-policy'),
                    'terms_url'          => url('/terms-condition'),
                    'refund_policy_url'  => url('/refund-policy'),
                    'child_safety_url'   => url('/child-safety'),
                ],
            ];
        });

        return ApiResponse::ok($data);
    }

    /**
     * SiteSetting::getValue() returns strings. Convert truthy strings to booleans.
     */
    private static function bool(?string $value): bool
    {
        return $value === '1' || $value === 'true';
    }

    /**
     * Resolve a storage path or full URL into an absolute URL.
     * Accepts three input shapes:
     *   - null / empty        -> null
     *   - 'http(s)://…'       -> returned as-is
     *   - 'branding/logo.png' -> prepended with storage/ + url() helper
     *   - '/storage/logo.png' -> prepended with url() helper (stripping leading slash)
     */
    private static function absUrl(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $path = ltrim($path, '/');

        if (! str_starts_with($path, 'storage/')) {
            $path = 'storage/' . $path;
        }

        return url($path);
    }
}

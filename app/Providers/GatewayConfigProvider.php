<?php

namespace App\Providers;

use App\Models\SiteSetting;
use Illuminate\Support\ServiceProvider;

/**
 * Overrides SMTP, SMS, and Payment gateway config values
 * from the site_settings DB table (set via admin panel).
 * Falls back to .env values if no DB value is set.
 */
class GatewayConfigProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Only override if the site_settings table exists (guards against fresh install/migration)
        try {
            $settings = SiteSetting::pluck('value', 'key')->toArray();
        } catch (\Throwable $e) {
            return; // Table doesn't exist yet, skip
        }

        $this->overrideMailConfig($settings);
        $this->overrideSmsConfig($settings);
        $this->overrideRazorpayConfig($settings);
        $this->overrideStripeConfig($settings);
        $this->overridePayPalConfig($settings);
        $this->overridePaytmConfig($settings);
        $this->overridePhonePeConfig($settings);
        $this->overrideReferenceData($settings);
    }

    /**
     * Apply admin overrides to reference_data option lists.
     *
     * Background: Admin → Content Management → Reference Data
     * (App\Filament\Pages\ReferenceDataEditor) lets admins edit
     * dropdown option lists — Mother Tongues, Eating Habits,
     * Education Qualifications, etc. — by writing JSON into
     * site_settings keyed `ref_data_{config_key}`. Before this
     * method existed, those edits saved cleanly but nothing read
     * them — every view called `config('reference_data.X_list')`
     * which hit the static PHP file, not the override.
     *
     * Mirroring the same boot-time override pattern used for SMTP +
     * payment gateway credentials, we now hydrate the config tree
     * from those JSON overrides during the boot phase. Result:
     * every existing `config('reference_data.…')` callsite (views,
     * Filament forms, API responses) automatically sees the admin's
     * overrides. No callsite changes needed.
     *
     * Resolution per category:
     *   1. site_settings.ref_data_{key} exists + decodes to an array
     *      → that value wins.
     *   2. Otherwise the value from config/reference_data.php
     *      remains (the system default the file ships with).
     *
     * Storage shape is whatever ReferenceDataEditor writes (flat
     * arrays for ordinary lists, grouped arrays for grouped
     * categories like educational_qualifications_list). We don't
     * inspect the shape — we just hand the decoded array back to
     * config() and let the consumer handle it the same way it
     * would handle the config-file version.
     */
    protected function overrideReferenceData(array $settings): void
    {
        foreach ($settings as $key => $value) {
            if (! is_string($key) || ! str_starts_with($key, 'ref_data_')) {
                continue;
            }
            if (! is_string($value) || $value === '') {
                continue;
            }

            $decoded = json_decode($value, true);
            if (! is_array($decoded) || empty($decoded)) {
                continue;
            }

            $listKey = substr($key, strlen('ref_data_'));
            config(['reference_data.'.$listKey => $decoded]);
        }
    }

    protected function overrideMailConfig(array $settings): void
    {
        if (!empty($settings['mail_host'])) {
            config([
                'mail.default' => $settings['mail_driver'] ?? config('mail.default'),
                'mail.mailers.smtp.host' => $settings['mail_host'],
                'mail.mailers.smtp.port' => (int) ($settings['mail_port'] ?? config('mail.mailers.smtp.port')),
                'mail.mailers.smtp.encryption' => $settings['mail_encryption'] ?? config('mail.mailers.smtp.encryption'),
            ]);
        }

        if (!empty($settings['mail_username'])) {
            config(['mail.mailers.smtp.username' => $settings['mail_username']]);
        }

        if (!empty($settings['mail_password'])) {
            config(['mail.mailers.smtp.password' => $settings['mail_password']]);
        }

        if (!empty($settings['mail_from_address'])) {
            config(['mail.from.address' => $settings['mail_from_address']]);
        }

        if (!empty($settings['mail_from_name'])) {
            config(['mail.from.name' => $settings['mail_from_name']]);
        }
    }

    protected function overrideSmsConfig(array $settings): void
    {
        if (!empty($settings['sms_api_key'])) {
            config(['services.fast2sms.api_key' => $settings['sms_api_key']]);
        }

        if (!empty($settings['sms_sender_id'])) {
            config(['services.fast2sms.sender_id' => $settings['sms_sender_id']]);
        }
    }

    protected function overrideRazorpayConfig(array $settings): void
    {
        if (!empty($settings['razorpay_key_id'])) {
            config(['services.razorpay.key' => $settings['razorpay_key_id']]);
        }

        if (!empty($settings['razorpay_key_secret'])) {
            config(['services.razorpay.secret' => $settings['razorpay_key_secret']]);
        }

        if (!empty($settings['razorpay_webhook_secret'])) {
            config(['services.razorpay.webhook_secret' => $settings['razorpay_webhook_secret']]);
        }

        // Admin enable/disable toggle. Stored as '1' / '0' string. Unset
        // means "use config default" (true) — preserves pre-toggle behaviour
        // for fresh installs and tests that haven't seeded the SiteSetting.
        if (array_key_exists('razorpay_enabled', $settings)) {
            config(['services.razorpay.enabled' => $settings['razorpay_enabled'] === '1']);
        }
    }

    protected function overrideStripeConfig(array $settings): void
    {
        if (!empty($settings['stripe_key'])) {
            config(['services.stripe.key' => $settings['stripe_key']]);
        }

        if (!empty($settings['stripe_secret'])) {
            config(['services.stripe.secret' => $settings['stripe_secret']]);
        }

        if (!empty($settings['stripe_webhook_secret'])) {
            config(['services.stripe.webhook_secret' => $settings['stripe_webhook_secret']]);
        }

        if (array_key_exists('stripe_enabled', $settings)) {
            config(['services.stripe.enabled' => $settings['stripe_enabled'] === '1']);
        }
    }

    protected function overridePayPalConfig(array $settings): void
    {
        if (!empty($settings['paypal_client_id'])) {
            config(['services.paypal.client_id' => $settings['paypal_client_id']]);
        }

        if (!empty($settings['paypal_secret'])) {
            config(['services.paypal.secret' => $settings['paypal_secret']]);
        }

        if (!empty($settings['paypal_mode'])) {
            config(['services.paypal.mode' => $settings['paypal_mode']]);
        }

        if (!empty($settings['paypal_webhook_id'])) {
            config(['services.paypal.webhook_id' => $settings['paypal_webhook_id']]);
        }

        if (!empty($settings['paypal_currency'])) {
            config(['services.paypal.currency' => $settings['paypal_currency']]);
        }

        if (array_key_exists('paypal_enabled', $settings)) {
            config(['services.paypal.enabled' => $settings['paypal_enabled'] === '1']);
        }
    }

    protected function overridePaytmConfig(array $settings): void
    {
        if (!empty($settings['paytm_mid'])) {
            config(['services.paytm.mid' => $settings['paytm_mid']]);
        }

        if (!empty($settings['paytm_key'])) {
            config(['services.paytm.key' => $settings['paytm_key']]);
        }

        if (!empty($settings['paytm_mode'])) {
            config(['services.paytm.mode' => $settings['paytm_mode']]);
        }

        if (!empty($settings['paytm_website'])) {
            config(['services.paytm.website' => $settings['paytm_website']]);
        }

        if (!empty($settings['paytm_industry_type'])) {
            config(['services.paytm.industry_type' => $settings['paytm_industry_type']]);
        }

        if (!empty($settings['paytm_channel_id'])) {
            config(['services.paytm.channel_id' => $settings['paytm_channel_id']]);
        }

        if (array_key_exists('paytm_enabled', $settings)) {
            config(['services.paytm.enabled' => $settings['paytm_enabled'] === '1']);
        }
    }

    protected function overridePhonePeConfig(array $settings): void
    {
        if (!empty($settings['phonepe_client_id'])) {
            config(['services.phonepe.client_id' => $settings['phonepe_client_id']]);
        }

        if (!empty($settings['phonepe_client_secret'])) {
            config(['services.phonepe.client_secret' => $settings['phonepe_client_secret']]);
        }

        if (!empty($settings['phonepe_client_version'])) {
            config(['services.phonepe.client_version' => $settings['phonepe_client_version']]);
        }

        if (!empty($settings['phonepe_mode'])) {
            config(['services.phonepe.mode' => $settings['phonepe_mode']]);
        }

        if (!empty($settings['phonepe_webhook_username'])) {
            config(['services.phonepe.webhook_username' => $settings['phonepe_webhook_username']]);
        }

        if (!empty($settings['phonepe_webhook_password'])) {
            config(['services.phonepe.webhook_password' => $settings['phonepe_webhook_password']]);
        }

        if (array_key_exists('phonepe_enabled', $settings)) {
            config(['services.phonepe.enabled' => $settings['phonepe_enabled'] === '1']);
        }
    }
}

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
    }
}

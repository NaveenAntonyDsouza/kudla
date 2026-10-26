<?php

use App\Support\SecretSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Payment gateway and SMS secrets in site_settings were plain text (Oct 2026):
 * encrypt them in place with this site's app key, as the mailbox password.
 */
return new class extends Migration
{
    private const KEYS = [
        'sms_api_key',
        'razorpay_key_secret', 'razorpay_webhook_secret',
        'stripe_secret', 'stripe_webhook_secret',
        'paypal_secret', 'paytm_key',
        'phonepe_client_secret', 'phonepe_webhook_password',
    ];

    public function up(): void
    {
        foreach (DB::table('site_settings')->whereIn('key', self::KEYS)->get() as $row) {
            if ((string) $row->value !== '' && ! SecretSetting::isSealed($row->value)) {
                DB::table('site_settings')->where('id', $row->id)->update(['value' => SecretSetting::seal((string) $row->value), 'updated_at' => now()]);
                Cache::forget("site_setting.{$row->key}");
            }
        }
        Cache::forget('site_settings.all');
    }

    public function down(): void
    {
        foreach (DB::table('site_settings')->whereIn('key', self::KEYS)->get() as $row) {
            if (SecretSetting::isSealed($row->value)) {
                DB::table('site_settings')->where('id', $row->id)->update(['value' => SecretSetting::reveal($row->value), 'updated_at' => now()]);
                Cache::forget("site_setting.{$row->key}");
            }
        }
        Cache::forget('site_settings.all');
    }
};

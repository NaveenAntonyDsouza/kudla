<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Seed `{slug}_enabled` SiteSetting flags for each payment gateway, so the
 * admin's "Enable this gateway" toggle has an explicit value to read.
 *
 * Until now the gateways were implicitly enabled-by-credentials — i.e.
 * isConfigured() returned true iff key_id + key_secret were non-empty.
 * From this commit forward, isConfigured() ANDs an explicit enable flag
 * with the credentials check, so admins can keep credentials saved but
 * temporarily flip a gateway off without wiping the secrets.
 *
 * Strategy:
 *   - Initialise each flag to '1' when the gateway's credentials are
 *     already filled in (preserving today's effective behaviour) and to
 *     '0' otherwise. This keeps the deploy a no-op for the user: anything
 *     that works today still works after migration; anything that wasn't
 *     working stays off.
 *   - Forget Cache for each affected key so the new value is read
 *     immediately (SiteSetting::getValue caches for 1h).
 */
return new class extends Migration
{
    public function up(): void
    {
        $gateways = [
            // slug => [ list of credential keys that, when all non-empty,
            //          mean the gateway is "configured" today ]
            'razorpay' => ['razorpay_key_id', 'razorpay_key_secret'],
            'stripe' => ['stripe_key', 'stripe_secret'],
            'paypal' => ['paypal_client_id', 'paypal_secret'],
            'paytm' => ['paytm_mid', 'paytm_key'],
            'phonepe' => ['phonepe_client_id', 'phonepe_client_secret'],
        ];

        foreach ($gateways as $slug => $credentialKeys) {
            $hasCredentials = true;
            foreach ($credentialKeys as $credKey) {
                $value = DB::table('site_settings')->where('key', $credKey)->value('value');
                if (empty($value)) {
                    $hasCredentials = false;
                    break;
                }
            }

            $enabledKey = $slug.'_enabled';
            $enabledValue = $hasCredentials ? '1' : '0';

            // Idempotent: only insert if the key doesn't exist yet. Skip
            // updates so a re-run doesn't clobber an admin's manual choice.
            $existing = DB::table('site_settings')->where('key', $enabledKey)->first();
            if (! $existing) {
                DB::table('site_settings')->insert([
                    'key' => $enabledKey,
                    'value' => $enabledValue,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Bust the 1-hour cache so the next isConfigured() call reads fresh.
            Cache::forget('site_setting.'.$enabledKey);
        }

        Cache::forget('site_settings.all');
    }

    public function down(): void
    {
        // Remove the toggle rows. Note: this DOESN'T re-enable gateways
        // implicitly — the application code at this point in history will
        // still AND the toggle with the credential check, so the gateways
        // will effectively be off until either the code is also rolled
        // back or the rows are re-inserted with '1'.
        foreach (['razorpay', 'stripe', 'paypal', 'paytm', 'phonepe'] as $slug) {
            $key = $slug.'_enabled';
            DB::table('site_settings')->where('key', $key)->delete();
            Cache::forget('site_setting.'.$key);
        }
        Cache::forget('site_settings.all');
    }
};

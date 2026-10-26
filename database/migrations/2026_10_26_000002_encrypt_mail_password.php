<?php

use App\Support\SecretSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * The mailbox password in site_settings was plain text (weekly review,
 * Oct 2026): encrypt it in place with this site's app key.
 */
return new class extends Migration
{
    public function up(): void
    {
        $row = DB::table('site_settings')->where('key', 'mail_password')->first();
        if ($row && (string) $row->value !== '' && ! SecretSetting::isSealed($row->value)) {
            DB::table('site_settings')->where('id', $row->id)->update(['value' => SecretSetting::seal((string) $row->value), 'updated_at' => now()]);
            Cache::forget('site_setting.mail_password');
            Cache::forget('site_settings.all');
        }
    }

    public function down(): void
    {
        $row = DB::table('site_settings')->where('key', 'mail_password')->first();
        if ($row && SecretSetting::isSealed($row->value)) {
            DB::table('site_settings')->where('id', $row->id)->update(['value' => SecretSetting::reveal($row->value), 'updated_at' => now()]);
            Cache::forget('site_setting.mail_password');
            Cache::forget('site_settings.all');
        }
    }
};

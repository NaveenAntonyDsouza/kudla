<?php

namespace App\Support;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Crypt;

/**
 * Passwords kept in site_settings are stored encrypted with the app key
 * (weekly review, Oct 2026: the mailbox password sat there as plain text,
 * readable by anyone with database or admin access). Stored as "enc:…";
 * a value saved before this still reads as it is.
 */
final class SecretSetting
{
    private const PREFIX = 'enc:';

    public static function put(string $key, string $value): void
    {
        SiteSetting::setValue($key, self::seal($value));
    }

    public static function seal(string $value): string
    {
        return self::PREFIX . Crypt::encryptString($value);
    }

    /** The plain value of what is stored ('' when it can't be read). */
    public static function reveal(?string $stored): string
    {
        $stored = (string) $stored;
        if (! str_starts_with($stored, self::PREFIX)) {
            return $stored;
        }
        try {
            return Crypt::decryptString(substr($stored, strlen(self::PREFIX)));
        } catch (\Throwable) {
            return ''; // saved under a different APP_KEY
        }
    }

    public static function isSealed(?string $stored): bool
    {
        return str_starts_with((string) $stored, self::PREFIX);
    }
}

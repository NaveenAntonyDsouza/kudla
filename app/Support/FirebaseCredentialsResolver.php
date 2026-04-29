<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Normalises the FIREBASE_CREDENTIALS env value into a path that resolves
 * regardless of which Laravel context is calling the Firebase Admin SDK.
 *
 * Why this is needed:
 *   PHP-FPM / mod_php sets the working directory to the document root
 *   (public/) for web requests, while artisan and queue workers run with
 *   the project root as CWD. A relative path like
 *   "storage/app/firebase-credentials.json" therefore points at
 *   "public/storage/app/firebase-credentials.json" from web — which does
 *   not exist — and silently breaks any FCM dispatch triggered from a
 *   controller. The breakage is invisible in tests (Pest runs from the
 *   project root) which makes it easy to ship.
 *
 *   This resolver wraps relative paths in {@see base_path()} so they
 *   anchor on the project root in every context. Absolute paths
 *   (Unix /... or Windows X:\...) pass through unchanged.
 */
final class FirebaseCredentialsResolver
{
    public static function resolve(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        if (self::isAbsolute($path)) {
            return $path;
        }

        return base_path($path);
    }

    public static function isAbsolute(string $path): bool
    {
        if (str_starts_with($path, '/')) {
            return true;
        }

        // Windows: C:\... or D:/... — drive letter + colon + slash.
        return (bool) preg_match('/^[A-Za-z]:[\\\\\/]/', $path);
    }
}

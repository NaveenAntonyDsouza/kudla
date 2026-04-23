<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * PhotoStorageService — abstracts photo storage across drivers.
 *
 * Each photo record stores which driver it lives on (ProfilePhoto.storage_driver).
 * Newly uploaded photos use the CURRENT driver from SiteSetting `active_storage_driver`.
 * Existing photos continue to use their original driver (hybrid mode).
 *
 * Supported drivers:
 *   - public     (default, local filesystem, bundled with script)
 *   - cloudinary (needs CLOUDINARY_* env vars)
 *   - r2         (Cloudflare R2 — S3-compatible, needs R2_* env vars)
 *   - s3         (AWS S3, needs AWS_* env vars)
 *
 * Selection by SiteSetting:
 *   'active_storage_driver' = 'public' | 'cloudinary' | 'r2' | 's3'
 */
class PhotoStorageService
{
    public const DRIVER_LOCAL = 'public';
    public const DRIVER_CLOUDINARY = 'cloudinary';
    public const DRIVER_R2 = 'r2';
    public const DRIVER_S3 = 's3';

    public const SUPPORTED_DRIVERS = [
        self::DRIVER_LOCAL,
        self::DRIVER_CLOUDINARY,
        self::DRIVER_R2,
        self::DRIVER_S3,
    ];

    /**
     * The driver that should be used for NEW uploads.
     * Defaults to local if setting is missing or invalid.
     */
    public function getActiveDriver(): string
    {
        $driver = (string) SiteSetting::getValue('active_storage_driver', self::DRIVER_LOCAL);
        return in_array($driver, self::SUPPORTED_DRIVERS, true) ? $driver : self::DRIVER_LOCAL;
    }

    /**
     * Human-friendly label for a driver.
     */
    public function driverLabel(string $driver): string
    {
        return match ($driver) {
            self::DRIVER_LOCAL => 'Local Storage (server filesystem)',
            self::DRIVER_CLOUDINARY => 'Cloudinary (managed CDN with image optimization)',
            self::DRIVER_R2 => 'Cloudflare R2 (zero-egress object storage)',
            self::DRIVER_S3 => 'AWS S3 (Amazon Simple Storage Service)',
            default => $driver,
        };
    }

    /**
     * Is the given driver fully configured (credentials present)?
     */
    public function isDriverConfigured(string $driver): bool
    {
        return match ($driver) {
            self::DRIVER_LOCAL => true, // always available

            self::DRIVER_CLOUDINARY =>
                !empty(config('filesystems.disks.cloudinary.cloud_name'))
                && !empty(config('filesystems.disks.cloudinary.api_key'))
                && !empty(config('filesystems.disks.cloudinary.api_secret')),

            self::DRIVER_R2 =>
                !empty(config('filesystems.disks.r2.key'))
                && !empty(config('filesystems.disks.r2.secret'))
                && !empty(config('filesystems.disks.r2.bucket'))
                && !empty(config('filesystems.disks.r2.endpoint')),

            self::DRIVER_S3 =>
                !empty(config('filesystems.disks.s3.key'))
                && !empty(config('filesystems.disks.s3.secret'))
                && !empty(config('filesystems.disks.s3.region'))
                && !empty(config('filesystems.disks.s3.bucket')),

            default => false,
        };
    }

    /**
     * Try to connect to a driver's storage.
     * Used by the admin "Test Connection" button. Returns an array with status.
     */
    public function testConnection(string $driver): array
    {
        if ($driver === self::DRIVER_LOCAL) {
            return ['ok' => true, 'message' => 'Local disk is always available.'];
        }

        if (!$this->isDriverConfigured($driver)) {
            return ['ok' => false, 'message' => 'Missing credentials — check your .env file.'];
        }

        try {
            // Simple test: list files in the root (should not error if connection works)
            Storage::disk($driver)->files('/');
            return ['ok' => true, 'message' => 'Connection successful.'];
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'message' => 'Connection failed: ' . substr($e->getMessage(), 0, 200),
            ];
        }
    }

    /**
     * Put a file (string contents) on a specific disk at a given path.
     * Returns true on success.
     */
    public function put(string $driver, string $path, string $contents): bool
    {
        try {
            return (bool) Storage::disk($driver)->put($path, $contents);
        } catch (Throwable $e) {
            report($e);
            return false;
        }
    }

    /**
     * Delete a path from a specific disk. Silent on error.
     */
    public function delete(string $driver, string $path): bool
    {
        try {
            if (Storage::disk($driver)->exists($path)) {
                return Storage::disk($driver)->delete($path);
            }
            return true;
        } catch (Throwable $e) {
            report($e);
            return false;
        }
    }

    /**
     * Build a public URL for a path on a given driver.
     */
    public function url(string $driver, string $path): string
    {
        try {
            return Storage::disk($driver)->url($path);
        } catch (Throwable $e) {
            return '';
        }
    }
}

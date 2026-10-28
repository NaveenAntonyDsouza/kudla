<?php

namespace App\Support;

use App\Models\IdProof;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

/**
 * ID proof scans (Aadhaar, passport...) — Oct 2026: they were saved in the
 * public storage folder, where anyone with the link could open them, and the
 * upload failed on every site because two columns were never created (the
 * files of 4 kudla members were left behind). Scans now live on the private
 * disk and are opened only through short-lived signed links (the member's
 * own page, the admin panel, the app). Only the last 4 characters of the ID
 * number are kept; staff read the full number on the scan.
 */
final class IdProofFiles
{
    /** storage/app/private — never served by the web server. */
    public const DISK = 'local';

    public const LINK_MINUTES = 30;

    public static function store(UploadedFile $file, int $profileId): string
    {
        return $file->store('id-proofs/' . $profileId, self::DISK);
    }

    /** A link that shows the scan for a short while, or null when there is none. */
    public static function url(IdProof $proof, string $side = 'front'): ?string
    {
        if (! self::path($proof, $side)) {
            return null;
        }

        return URL::temporarySignedRoute('id-proof.file', now()->addMinutes(self::LINK_MINUTES), ['idProof' => $proof->id, 'side' => $side]);
    }

    public static function path(IdProof $proof, string $side): ?string
    {
        return $side === 'back' ? $proof->document_back_url : $proof->document_url;
    }

    /** The disk that holds this file: private, or public for a scan saved before Oct 2026. */
    public static function diskFor(?string $path): ?string
    {
        if (! $path || str_starts_with($path, 'http')) {
            return null;
        }
        foreach ([self::DISK, 'public'] as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                return $disk;
            }
        }

        return null;
    }

    public static function delete(?string $path): void
    {
        if (! $path || str_starts_with($path, 'http')) {
            return;
        }
        foreach ([self::DISK, 'public'] as $disk) {
            try {
                Storage::disk($disk)->delete($path);
            } catch (\Throwable) {
                // the row is what matters; a missing file is fine
            }
        }
    }

    /** "1234 5678 9012" → "XXXXXXXX9012": only the last 4 characters are kept. */
    public static function maskNumber(?string $number): ?string
    {
        $clean = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $number));
        if ($clean === '') {
            return null;
        }

        return str_repeat('X', max(0, strlen($clean) - 4)) . substr($clean, -4);
    }
}

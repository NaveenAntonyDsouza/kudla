<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Web rules for member uploads on the public disk (privacy check, 4 Nov 2026).
 *
 * Member photos and horoscope files are served from public storage. Google could
 * index the photos (no noindex), and each photo's untouched original (full size, no
 * watermark) loaded by swapping "full" for "original" in its address. A small
 * .htaccess in the folder fixes both; the app writes it whenever it stores a file
 * there, so a new site or a new folder is covered too.
 */
final class PublicFolderRules
{
    /** photos/: never indexed; originals never served. */
    public const PHOTOS = <<<'TXT'
# Member photos (privacy check, 4 Nov 2026): shown on the site, never indexed by search
# engines, and the untouched originals (full size, no watermark) are never served.
<IfModule mod_headers.c>
    Header set X-Robots-Tag "noindex, noimageindex"
</IfModule>
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule (^|/)original/ - [F,L]
</IfModule>

TXT;

    /** jathakam/ and other member documents: never indexed. */
    public const DOCUMENTS = <<<'TXT'
# Member documents (privacy check, 4 Nov 2026): never indexed by search engines.
<IfModule mod_headers.c>
    Header set X-Robots-Tag "noindex, noimageindex"
</IfModule>

TXT;

    /** Write {folder}/.htaccess on the public disk if it isn't there. Never breaks an upload. */
    public static function ensure(string $folder, string $rules): void
    {
        $folder = trim(explode('/', trim($folder, '/'))[0] ?? '', '/');
        if ($folder === '') {
            return;
        }

        try {
            $disk = Storage::disk('public');
            if (! $disk->exists("{$folder}/.htaccess")) {
                $disk->put("{$folder}/.htaccess", $rules);
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }
}

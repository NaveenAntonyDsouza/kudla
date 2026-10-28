<?php

namespace App\Http\Controllers;

use App\Models\IdProof;
use App\Support\IdProofFiles;
use Illuminate\Support\Facades\Storage;

/**
 * Shows an ID proof scan through a short-lived signed link
 * (App\Support\IdProofFiles::url) — the scans themselves are private.
 */
class IdProofFileController extends Controller
{
    public function show(IdProof $idProof, string $side)
    {
        $path = IdProofFiles::path($idProof, $side);
        $disk = IdProofFiles::diskFor($path);
        abort_if($disk === null, 404);

        return Storage::disk($disk)->response($path, null, [
            'Cache-Control' => 'private, no-store',
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }
}

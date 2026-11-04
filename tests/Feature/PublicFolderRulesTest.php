<?php

use App\Support\PublicFolderRules;
use Illuminate\Support\Facades\Storage;

/*
| Privacy check, 4 Nov 2026: member photos on the public disk could be indexed by
| Google, and each photo's untouched original (full size, no watermark) loaded by
| swapping "full" for "original" in its address. The app now writes a small
| .htaccess into the folder whenever it stores a photo or a horoscope file.
*/

it('writes the photo folder rules once: never indexed, originals never served', function () {
    Storage::fake('public');

    PublicFolderRules::ensure('photos/42', PublicFolderRules::PHOTOS);
    $rules = Storage::disk('public')->get('photos/.htaccess');

    expect($rules)->toContain('Header set X-Robots-Tag "noindex, noimageindex"')
        ->toContain('RewriteRule (^|/)original/ - [F,L]');

    // an owner's own edit is kept; it is only written when missing
    Storage::disk('public')->put('photos/.htaccess', $rules . "# kept\n");
    PublicFolderRules::ensure('photos/43', PublicFolderRules::PHOTOS);
    expect(Storage::disk('public')->get('photos/.htaccess'))->toEndWith("# kept\n");
});

it('writes the documents rules for horoscope files: never indexed, still served to the owner', function () {
    Storage::fake('public');

    PublicFolderRules::ensure('jathakam', PublicFolderRules::DOCUMENTS);

    expect(Storage::disk('public')->get('jathakam/.htaccess'))->toContain('noindex, noimageindex')
        ->not->toContain('RewriteRule');
});

it('is called wherever member photos and horoscope files are stored on the public disk', function () {
    expect(file_get_contents(app_path('Services/ImageProcessingService.php')))
        ->toContain('PublicFolderRules::ensure($storagePath, \App\Support\PublicFolderRules::PHOTOS)');
    foreach (['Http/Controllers/OnboardingController.php', 'Http/Controllers/ProfileController.php'] as $file) {
        expect(file_get_contents(app_path($file)))->toContain("PublicFolderRules::ensure('jathakam', \\App\\Support\\PublicFolderRules::DOCUMENTS)");
    }
});

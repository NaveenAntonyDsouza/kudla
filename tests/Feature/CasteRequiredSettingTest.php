<?php

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

/*
|--------------------------------------------------------------------------
| caste_required site setting
|--------------------------------------------------------------------------
| SiteSetting::casteRule() drives `caste` validation in web registration
| step 2, web profile edit and the API religious section. Matrimony sites
| (default ON) require a caste for Hindu members; a dating site can switch
| it OFF. Jains are never required to give one — the forms show them a
| sect field instead of caste. The setting is pre-populated in the cache
| (CACHE_STORE=array in phpunit.xml), the same way RegistrationPhotoStepTest
| sidesteps the site_settings table.
|
| The last case pins the no-table fallback: rules are also built where
| site_settings doesn't exist (fresh install, tests with a partial schema),
| and that must not throw — several API rule tests depend on it.
*/

function casteFails(array $data): bool
{
    return Validator::make($data, ['caste' => SiteSetting::casteRule()])->fails();
}

it('requires caste for Hindu members by default', function () {
    Cache::put('site_setting.caste_required', '1');

    expect(SiteSetting::casteRequired())->toBeTrue()
        ->and(casteFails(['religion' => 'Hindu']))->toBeTrue()
        ->and(casteFails(['religion' => 'Hindu', 'caste' => 'Vokkaliga']))->toBeFalse();
});

it('never requires caste for Jain members, who pick a sect instead', function () {
    Cache::put('site_setting.caste_required', '1');

    expect(casteFails(['religion' => 'Jain', 'jain_sect' => 'Digambar']))->toBeFalse();
});

it('makes caste optional when the setting is off', function () {
    Cache::put('site_setting.caste_required', '0');

    expect(SiteSetting::casteRequired())->toBeFalse()
        ->and(casteFails(['religion' => 'Hindu']))->toBeFalse()
        ->and(SiteSetting::casteRule('max:50'))->toBe('nullable|string|max:50');
});

it('falls back to required when there is no site_settings table', function () {
    Cache::forget('site_setting.caste_required');

    // No site_settings table is created for this test, so the lookup throws
    // a QueryException internally and the default must win.
    expect(SiteSetting::casteRequired())->toBeTrue()
        ->and(SiteSetting::casteRule())->toBe('nullable|required_if:religion,Hindu|string');
});

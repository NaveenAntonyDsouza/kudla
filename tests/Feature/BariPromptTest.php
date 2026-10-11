<?php

use App\Models\Profile;
use App\Models\ReligiousInfo;
use Illuminate\Support\Facades\Blade;

/*
| "Add your bari" on the dashboard (11 Oct 2026): only for members of a bari
| caste who have no bari yet. And "Delete Account" where members look for it.
*/

function bpProfile(?string $religion, ?string $caste, ?string $gotra): Profile
{
    $p = new Profile(['matri_id' => 'KM100999']);
    $p->id = 999;
    $p->setRelation('religiousInfo', new ReligiousInfo(['religion' => $religion, 'caste' => $caste, 'gotra' => $gotra]));

    return $p;
}

function bpRender(Profile $p): string
{
    return Blade::render('<x-bari-prompt :profile="$p" />', ['p' => $p]);
}

it('asks members of a bari caste with no bari to add theirs, opening the field ready to edit', function () {
    $html = bpRender(bpProfile('Hindu', 'Billava', null));

    expect($html)->toContain('Add your bari')
        ->toContain('section=religious')->toContain('edit=1')
        ->toContain("posthog.capture('bari_prompt_click')")
        ->toContain('bariPromptHiddenUntil');                                  // "Not now" hides it for 14 days

    foreach (["Don't Know", 'Other', ''] as $nothingYet) {
        expect(bpRender(bpProfile('Hindu', 'Bunts', $nothingYet)))->toContain('Add your bari');
    }
    expect(bpRender(bpProfile('Jain', 'Bunt Jain', null)))->toContain('Add your bari');
});

it('stays away from members who have a bari, or whose caste does not go by bari', function () {
    expect(bpRender(bpProfile('Hindu', 'Billava', 'Bangera')))->not->toContain('Add your bari')
        ->and(bpRender(bpProfile('Hindu', 'Brahmin', null)))->not->toContain('Add your bari')
        ->and(bpRender(bpProfile('Hindu', 'Other / Not Listed', null)))->not->toContain('Add your bari')
        ->and(bpRender(bpProfile('Christian', null, null)))->not->toContain('Add your bari')
        ->and(bpRender(new Profile()))->not->toContain('Add your bari');
});

it('opens a profile section ready to edit from a link, and shows the card on the dashboard', function () {
    expect(file_get_contents(resource_path('views/profile/show.blade.php')))->toContain("request()->boolean('edit')")
        ->and(file_get_contents(resource_path('views/dashboard/index.blade.php')))->toContain('<x-bari-prompt :profile="$profile" />');
});

it('calls it "Delete Account", as members do, and points to it from the contact page and FAQ', function () {
    $settings = file_get_contents(resource_path('views/settings/index.blade.php'));

    expect($settings)->toContain("'delete_profile' => 'Delete Account'")->not->toContain('>Delete Profile<')
        ->and(file_get_contents(resource_path('views/pages/contact.blade.php')))->toContain("route('settings.index', ['section' => 'delete_profile'])")
        ->and(file_get_contents(resource_path('views/pages/faq.blade.php')))->toContain('Settings > Delete Account');
});

it('walks through Delete Account one step at a time, like Chavara, with an "Are you sure?"', function () {
    $page = file_get_contents(resource_path('views/settings/partials/delete-account.blade.php'));

    expect(file_get_contents(resource_path('views/settings/index.blade.php')))->toContain("@include('settings.partials.delete-account')")
        ->and($page)->toContain("pickVia('this_site')")->toContain("pickVia('other_site')")->toContain("pickVia('other')")
        ->toContain('type="date" x-model="weddingDate"')
        ->toContain('maxlength="190"')
        ->toContain('Are you sure you want to delete your account?')
        ->toContain('$refs.form.submit()');
});

it('keeps the reason in one line that fits the column, with how they met', function () {
    $r = fn (array $d) => App\Http\Controllers\SettingsController::deletionReason($d);
    config(['app.name' => 'Kudla Matrimony']);

    expect($r(['reason' => 'I am Married', 'met_via' => 'this_site', 'wedding_date' => '2026-11-20', 'partner_matri_id' => ' km100123 ']))
        ->toBe('I am Married · met on Kudla Matrimony · wedding 2026-11-20 · partner KM100123')
        ->and($r(['reason' => 'My Marriage is Fixed', 'met_via' => 'other_site', 'wedding_date' => '2027-01-05', 'other_site' => 'Shaadi']))
        ->toBe('My Marriage is Fixed · met on another site (Shaadi) · wedding 2027-01-05')
        ->and($r(['reason' => 'I am Married', 'met_via' => 'other']))->toBe('I am Married · met by other means')
        ->and($r(['reason' => 'Other Reasons', 'other_reason' => 'Too many calls']))->toBe('Other: Too many calls')
        ->and($r(['reason' => 'Other Reasons']))->toBe('Other Reasons')
        ->and(mb_strlen($r(['reason' => 'Other Reasons', 'other_reason' => str_repeat('a', 400)])))->toBe(200);
});

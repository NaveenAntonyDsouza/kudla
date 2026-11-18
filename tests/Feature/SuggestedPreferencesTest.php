<?php

use App\Models\PartnerPreference;
use App\Models\Profile;
use App\Models\ReligiousInfo;
use App\Services\MatchingService;
use App\Support\AdminModules;
use App\Support\SuggestedPreferences;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
| Matches for members who never saved partner preferences (owner, 17 Nov 2026: 430 of
| Kudla's 848 live members saw an empty My Matches page). Preferences are suggested from
| the member's own profile and never saved: age (a man up to 7 years younger, a woman up
| to 7 years older), their own religion, and height (owner: a man his height and below,
| a woman her height and above).
*/

beforeEach(function () {
    Schema::dropIfExists('site_settings');
    Schema::create('site_settings', function (Blueprint $t) {
        $t->id();
        $t->string('key')->unique();
        $t->text('value')->nullable();
        $t->timestamps();
    });
    Cache::flush();
    Carbon::setTestNow('2026-11-17 10:00:00');
});

afterEach(function () {
    Carbon::setTestNow();
    Schema::dropIfExists('site_settings');
});

function suggestSwitch(string $value): void
{
    DB::table('site_settings')->updateOrInsert(['key' => AdminModules::SUGGESTED_PREFS], ['value' => $value]);
    Cache::flush();
}

function suggestOn(): void
{
    suggestSwitch('1');
}

/** An unsaved member: gender, age, height and religion are all the suggestion reads. */
function personFor(string $gender, ?int $age, ?string $height, ?string $religion): Profile
{
    $p = new Profile(['gender' => $gender, 'date_of_birth' => $age ? now()->subYears($age)->subDays(10)->toDateString() : null, 'height' => $height]);
    $p->setRelation('religiousInfo', $religion ? new ReligiousInfo(['religion' => $religion]) : null);
    $p->setRelation('partnerPreference', null);

    return $p;
}

it('suggests for a man: women up to 7 years younger, his religion, his height and below', function () {
    $prefs = SuggestedPreferences::suggest(personFor('male', 30, '170 cm - 5 ft 07 inch', 'Hindu'));

    expect($prefs->age_from)->toBe(23)->and($prefs->age_to)->toBe(30)
        ->and($prefs->religions)->toBe(['Hindu'])
        ->and((int) $prefs->height_from_cm)->toBe(134)    // the shortest height on the list
        ->and((int) $prefs->height_to_cm)->toBe(170)
        ->and($prefs->exists)->toBeFalse();
});

it('suggests for a woman: men up to 7 years older, her religion, her height and above', function () {
    $prefs = SuggestedPreferences::suggest(personFor('female', 25, '155 cm - 5 ft 01 inch', 'Christian'));

    expect($prefs->age_from)->toBe(25)->and($prefs->age_to)->toBe(32)
        ->and($prefs->religions)->toBe(['Christian'])
        ->and((int) $prefs->height_from_cm)->toBe(155)
        ->and((int) $prefs->height_to_cm)->toBe(213);    // the tallest height on the list
});

it('keeps men at 21 and over, and leaves out whatever the profile does not say', function () {
    expect(SuggestedPreferences::suggest(personFor('female', 19, null, null))->only(['age_from', 'age_to']))->toBe(['age_from' => 21, 'age_to' => 28]);

    $noHeight = SuggestedPreferences::suggest(personFor('male', 28, null, 'Hindu'));
    expect($noHeight->height_from_cm)->toBeNull()->and($noHeight->religions)->toBe(['Hindu']);

    expect(SuggestedPreferences::suggest(personFor('male', null, null, null)))->toBeNull()
        ->and(SuggestedPreferences::suggest(personFor('', 30, '170 cm', 'Hindu')))->toBeNull();
});

it('uses saved preferences first, and suggests only while the switch is on', function () {
    $man = personFor('male', 30, '170 cm', 'Hindu');
    expect(SuggestedPreferences::for($man))->not->toBeNull();     // on by default (owner, 18 Nov 2026)

    suggestSwitch('0');
    expect(SuggestedPreferences::for($man))->toBeNull();          // off: as before, no matches

    suggestOn();
    $suggested = SuggestedPreferences::for($man);
    expect($suggested)->not->toBeNull()->and(SuggestedPreferences::isSuggested($suggested))->toBeTrue();

    $saved = new PartnerPreference(['age_from' => 20, 'age_to' => 40]);
    $saved->exists = true;
    $man->setRelation('partnerPreference', $saved);
    expect(SuggestedPreferences::for($man))->toBe($saved)->and(SuggestedPreferences::isSuggested($saved))->toBeFalse();
});

it('ranks a woman who fits him at 100% and one who does not lower', function () {
    $prefs = SuggestedPreferences::suggest(personFor('male', 30, '170 cm', 'Hindu'));
    $fits = personFor('female', 26, '160 cm', 'Hindu');
    $older = personFor('female', 33, '160 cm', 'Hindu');
    $taller = personFor('female', 26, '175 cm', 'Hindu');

    $score = fn (Profile $p) => app(MatchingService::class)->calculateScore($p, $prefs)['score'];

    expect($score($fits))->toBe(100)
        ->and($score($older))->toBeLessThan(100)
        ->and($score($taller))->toBeLessThan(100);
});

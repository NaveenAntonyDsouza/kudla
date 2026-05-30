<?php

use App\Models\ContactInfo;
use App\Models\EducationDetail;
use App\Models\FamilyDetail;
use App\Models\LifestyleInfo;
use App\Models\LocationInfo;
use App\Models\PartnerPreference;
use App\Models\Profile;
use App\Models\ProfilePhoto;
use App\Models\ReligiousInfo;
use App\Services\ProfileCompletionService;
use Illuminate\Database\Eloquent\Collection;

/*
|--------------------------------------------------------------------------
| ProfileCompletionService — section status detection
|--------------------------------------------------------------------------
| DB-free: builds in-memory Eloquent instances and sets relations directly,
| so sectionDoneMap / getSectionStatuses / detectMissingSections are exercised
| without standing up the full matrimony schema. The photo check is
| DB-tolerant and reads the loaded relation here.
|
| Locks the invariant that powers BOTH the web dashboard checklist and the
| mobile-app nudge system: the 9 section done-flags, their inverse (missing),
| and the impact ordering.
*/

uses(Tests\TestCase::class);

/** A profile with NOTHING filled — only an account exists. */
function emptyProfile(): Profile
{
    $p = new Profile();
    $p->exists = true;
    $p->forceFill(['id' => 9100]);
    // Empty visible-photos relation → DB-tolerant photo check returns false.
    $p->setRelation('profilePhotos', new Collection());
    // Null relations so the ?-> chains evaluate to "missing" without a DB hit.
    $p->setRelation('religiousInfo', null);
    $p->setRelation('educationDetail', null);
    $p->setRelation('familyDetail', null);
    $p->setRelation('locationInfo', null);
    $p->setRelation('contactInfo', null);
    $p->setRelation('lifestyleInfo', null);
    $p->setRelation('partnerPreference', null);

    return $p;
}

beforeEach(function () {
    $this->svc = app(ProfileCompletionService::class);
});

it('marks every section incomplete for an empty profile', function () {
    $map = $this->svc->sectionDoneMap(emptyProfile());

    expect($map)->toHaveCount(9);
    foreach ($map as $key => $done) {
        expect($done)->toBeFalse("section {$key} should be incomplete");
    }
});

it('getSectionStatuses returns all 9 ordered by weight, photo first', function () {
    $statuses = $this->svc->getSectionStatuses(emptyProfile());

    expect($statuses)->toHaveCount(9);
    // Photo carries the highest weight (15) and must lead.
    expect($statuses[0]['key'])->toBe('photo');
    expect($statuses[0]['weight'])->toBe(15);
    // Shape contract.
    expect($statuses[0])->toHaveKeys(['key', 'label', 'weight', 'done']);
    // Weights are non-increasing (impact order).
    $weights = array_column($statuses, 'weight');
    expect($weights)->toBe(collect($weights)->sortDesc()->values()->all());
});

it('detectMissingSections is the exact inverse of done-flags', function () {
    $profile = emptyProfile();
    // Fill basic info + religion so two sections flip to done.
    $profile->forceFill(['full_name' => 'Asha R', 'gender' => 'female', 'date_of_birth' => '1996-02-02']);
    $rel = new ReligiousInfo(); $rel->religion = 'Christian';
    $profile->setRelation('religiousInfo', $rel);

    $done = $this->svc->sectionDoneMap($profile);
    $missing = $this->svc->detectMissingSections($profile);

    expect($done['basic_info'])->toBeTrue();
    expect($done['religious'])->toBeTrue();
    // basic_info + religious are done → not in missing.
    expect($missing)->not->toHaveKey('basic_info');
    expect($missing)->not->toHaveKey('religious');
    // Everything else still missing.
    expect($missing)->toHaveKey('photo');
    expect($missing)->toHaveKey('partner_preferences');
    // Inverse invariant: a key is missing IFF its done-flag is false.
    foreach ($done as $key => $isDone) {
        expect(isset($missing[$key]))->toBe(! $isDone, "missing/done mismatch for {$key}");
    }
});

it('detects a fully complete profile as all done / none missing', function () {
    $p = emptyProfile();
    $p->forceFill(['full_name' => 'Liya', 'gender' => 'female', 'date_of_birth' => '1994-07-07']);

    $rel = new ReligiousInfo(); $rel->religion = 'Hindu'; $p->setRelation('religiousInfo', $rel);
    $edu = new EducationDetail(); $edu->highest_education = 'B.E'; $p->setRelation('educationDetail', $edu);
    $fam = new FamilyDetail(); $fam->father_name = 'Ravi'; $p->setRelation('familyDetail', $fam);
    $loc = new LocationInfo(); $loc->native_country = 'India'; $p->setRelation('locationInfo', $loc);
    $con = new ContactInfo(); $con->whatsapp_number = '9999999999'; $p->setRelation('contactInfo', $con);
    $life = new LifestyleInfo(); $life->diet = 'Vegetarian'; $p->setRelation('lifestyleInfo', $life);
    $pref = new PartnerPreference(); $pref->age_from = 25; $p->setRelation('partnerPreference', $pref);

    $photo = new ProfilePhoto(); $photo->is_visible = true;
    $p->setRelation('profilePhotos', new Collection([$photo]));

    $done = $this->svc->sectionDoneMap($p);

    foreach ($done as $key => $isDone) {
        expect($isDone)->toBeTrue("section {$key} should be complete");
    }
    expect($this->svc->detectMissingSections($p))->toBe([]);
});

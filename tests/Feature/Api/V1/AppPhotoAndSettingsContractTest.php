<?php

use App\Http\Resources\V1\NotificationResource;
use App\Http\Resources\V1\ProfileResource;
use App\Models\PhotoPrivacySetting;
use App\Models\Profile;
use App\Models\ProfilePhoto;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

use function Pest\Laravel\getJson;

/*
|--------------------------------------------------------------------------
| App API contract additions (Sep 2026)
|--------------------------------------------------------------------------
| What the Flutter app relies on from these changes:
|   1. ProfileResource photos block — photo_privacy (own profile: real
|      per-type levels) and photo_access (someone else's profile: what the
|      viewer can see + whether they can send a photo request).
|   2. /site/settings — free_membership, caste_required, show_diocese,
|      gender labels, and id_prefix from the setting that actually
|      generates member IDs.
|   3. NotificationResource::target — which screen a notification opens.
*/

beforeEach(function () {
    // Read by PhotoVisibility (approved requests / accepted interests) and
    // by photo_access.request_status.
    Schema::create('photo_requests', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('requester_profile_id');
        $t->unsignedBigInteger('target_profile_id');
        $t->string('status')->default('pending');
        $t->timestamps();
    });
    Schema::create('interests', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('sender_profile_id');
        $t->unsignedBigInteger('receiver_profile_id');
        $t->string('status')->default('pending');
        $t->timestamps();
    });
});

afterEach(function () {
    Schema::dropIfExists('interests');
    Schema::dropIfExists('photo_requests');
});

function contractProfile(int $id, ?array $privacy = null, bool $withPhoto = true): Profile
{
    $user = new User();
    $user->exists = true;
    $user->forceFill(['id' => $id, 'email' => "m{$id}@example.test", 'is_active' => true]);

    $p = new Profile();
    $p->exists = true;
    $p->forceFill(['id' => $id, 'user_id' => $id, 'matri_id' => 'AM'.(100000 + $id), 'gender' => 'female', 'is_active' => true, 'is_approved' => true]);

    // Everything ProfileResource reads, stubbed (same as ProfileResourceTest).
    $p->setRelation('user', $user);
    foreach (['religiousInfo', 'educationDetail', 'familyDetail', 'locationInfo', 'contactInfo', 'lifestyleInfo', 'partnerPreference', 'socialMediaLink'] as $rel) {
        $p->setRelation($rel, null);
    }

    $pp = null;
    if ($privacy !== null) {
        $pp = new PhotoPrivacySetting();
        $pp->forceFill(array_merge(['profile_id' => $id], $privacy));
    }
    $p->setRelation('photoPrivacySetting', $pp);

    $photo = null;
    if ($withPhoto) {
        $photo = new ProfilePhoto();
        $photo->forceFill(['id' => 700 + $id, 'profile_id' => $id, 'photo_type' => 'profile', 'photo_url' => 'p/a.jpg', 'is_primary' => true]);
    }
    $p->setRelation('primaryPhoto', $photo);
    $p->setRelation('profilePhotos', collect($photo ? [$photo] : []));

    return $p;
}

/* ---------------- 1. photos block ---------------- */

it('own profile: photo_privacy carries the real per-type levels, photo_access is null', function () {
    $me = contractProfile(1, ['profile_photo_privacy' => 'hidden', 'album_photos_privacy' => 'interest_accepted']);

    $photos = (new ProfileResource($me, viewer: $me))->resolve()['photos'];

    expect($photos['photo_privacy'])->toBe(['profile' => 'hidden', 'album' => 'interest_accepted', 'family' => 'visible_to_all'])
        ->and($photos['photo_access'])->toBeNull();
});

it("someone else's profile: photo_access tells the app the photo is hidden and it can request", function () {
    $owner = contractProfile(2, ['profile_photo_privacy' => 'hidden']);
    $viewer = contractProfile(3);

    $photos = (new ProfileResource($owner, viewer: $viewer))->resolve()['photos'];

    expect($photos['photo_privacy'])->toBeNull()
        ->and($photos['photo_access']['profile'])->toBe('hidden')
        ->and($photos['photo_access']['can_request'])->toBeTrue()
        ->and($photos['profile'][0]['url'])->toBeNull()          // no image sent
        ->and($photos['profile'][0]['lock_reason'])->toBe('hidden');
});

it("someone else's profile with a visible photo: nothing to request", function () {
    $owner = contractProfile(4, ['profile_photo_privacy' => 'visible_to_all']);
    $viewer = contractProfile(5);

    $access = (new ProfileResource($owner, viewer: $viewer))->resolve()['photos']['photo_access'];

    expect($access['profile'])->toBe('visible')
        ->and($access['can_request'])->toBeFalse();
});

it('a member with no photo can be asked to add one', function () {
    $owner = contractProfile(6, null, withPhoto: false);
    $viewer = contractProfile(7);

    $access = (new ProfileResource($owner, viewer: $viewer))->resolve()['photos']['photo_access'];

    expect($access['profile'])->toBe('no_photo')
        ->and($access['can_request'])->toBeTrue();
});

/* ---------------- 2. /site/settings ---------------- */

it('site settings expose the switches the app needs, from the real settings table', function () {
    Schema::create('site_settings', function (Blueprint $t) {
        $t->id();
        $t->string('key')->unique();
        $t->text('value')->nullable();
        $t->timestamps();
    });
    foreach ([
        'free_membership_enabled' => '1',
        'caste_required' => '0',
        'show_diocese' => '0',
        'gender_label_male' => 'A Man',
        'gender_label_female' => 'A Woman',
        'profile_id_prefix' => 'KD',
    ] as $k => $v) {
        SiteSetting::create(['key' => $k, 'value' => $v]);
    }
    Cache::flush();

    $data = getJson('/api/v1/site/settings')->assertOk()->json('data');

    expect($data['features']['free_membership'])->toBeTrue()
        ->and($data['registration']['caste_required'])->toBeFalse()
        ->and($data['registration']['show_diocese'])->toBeFalse()
        ->and($data['registration']['id_prefix'])->toBe('KD')
        ->and($data['labels'])->toBe(['gender_male' => 'A Man', 'gender_female' => 'A Woman']);

    Schema::dropIfExists('site_settings');
});

it('an admin settings change reaches the app immediately (API snapshot is cleared)', function () {
    Cache::put('api:v1:site-settings', ['stale' => true]);
    Schema::create('site_settings', function (Blueprint $t) {
        $t->id();
        $t->string('key')->unique();
        $t->text('value')->nullable();
        $t->timestamps();
    });

    SiteSetting::setValue('free_membership_enabled', '1');

    expect(Cache::has('api:v1:site-settings'))->toBeFalse();
    Schema::dropIfExists('site_settings');
});

/* ---------------- 3. notification targets ---------------- */

it('maps every notification type to the screen it should open', function (string $type, array $data, ?int $from, ?array $expected) {
    expect(NotificationResource::target($type, $data, $from))->toBe($expected);
})->with([
    'interest'         => ['interest_received', ['interest_id' => 9], 5, ['screen' => 'interest', 'id' => 9, 'section' => null]],
    'photo request'    => ['photo_request', [], 5, ['screen' => 'photo_requests', 'id' => null, 'section' => null]],
    'request approved' => ['photo_request_approved', [], 5, ['screen' => 'profile', 'id' => 5, 'section' => null]],
    'photo added'      => ['photo_added', ['owner_profile_id' => 7], 7, ['screen' => 'profile', 'id' => 7, 'section' => null]],
    'photo rejected'   => ['photo_rejected', [], null, ['screen' => 'my_photos', 'id' => null, 'section' => null]],
    'changes needed'   => ['profile_changes_requested', [], null, ['screen' => 'my_profile', 'id' => null, 'section' => null]],
    'id proof'         => ['id_proof_approved', [], null, ['screen' => 'my_documents', 'id' => null, 'section' => null]],
    'plan expiring'    => ['membership_expiring', ['membership_id' => 3], null, ['screen' => 'membership', 'id' => null, 'section' => null]],
    'nudge'            => ['system', ['nudge_type' => 'photos'], null, ['screen' => 'my_profile', 'id' => null, 'section' => 'photos']],
    'broadcast'        => ['admin_broadcast', [], null, null],
]);

it('gives every known notification type a specific icon', function () {
    foreach (['photo_added' => 'photo', 'id_proof_rejected' => 'verified', 'plan_changed' => 'membership', 'profile_changes_requested' => 'profile'] as $type => $icon) {
        expect(NotificationResource::iconType($type))->toBe($icon);
    }
});

<?php

use App\Models\Interest;
use App\Models\PhotoPrivacySetting;
use App\Models\PhotoRequest;
use App\Models\Profile;
use App\Models\ProfilePhoto;
use App\Models\User;
use App\Support\PhotoVisibility as PV;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| PhotoVisibility — who may see a member's profile photo
|--------------------------------------------------------------------------
| The single rule behind search cards, the profile page, homepage, interests,
| notifications, views, photo requests and print. Regression cover for the
| bug where the privacy form saved `profile_photo_privacy` but every view
| read the legacy `privacy_level` — so photos members set to Hidden /
| After interest accepted were shown to everyone (68 members on kudla).
*/

beforeEach(function () {
    Schema::create('users', function (Blueprint $t) {
        $t->id();
        $t->string('name')->nullable();
        $t->string('email')->nullable();
        $t->string('password')->nullable();
        $t->unsignedBigInteger('branch_id')->nullable();
        $t->json('notification_preferences')->nullable();
        $t->timestamps();
    });
    Schema::create('profiles', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('user_id');
        $t->string('matri_id', 20)->unique();
        $t->string('full_name')->nullable();
        $t->boolean('is_approved')->default(true);
        $t->boolean('onboarding_completed')->default(true);
        $t->unsignedBigInteger('branch_id')->nullable();
        $t->timestamp('deleted_at')->nullable();
        $t->timestamps();
    });
    Schema::create('profile_photos', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('profile_id');
        $t->string('photo_type')->default('profile');
        $t->string('photo_url')->nullable();
        $t->string('storage_driver')->default('public');
        $t->boolean('is_primary')->default(false);
        $t->boolean('is_visible')->default(true);
        $t->string('approval_status')->default('approved');
        $t->timestamps();
    });
    Schema::create('photo_privacy_settings', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('profile_id')->unique();
        $t->string('privacy_level')->nullable();
        $t->string('profile_photo_privacy')->nullable();
        $t->string('album_photos_privacy')->nullable();
        $t->string('family_photos_privacy')->nullable();
        $t->timestamps();
    });
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

    Mail::fake(); // photo-request model hooks send email
});

afterEach(function () {
    foreach (['interests', 'photo_requests', 'photo_privacy_settings', 'profile_photos', 'profiles', 'users'] as $table) {
        Schema::dropIfExists($table);
    }
});

function pvMember(bool $withPhoto = true, array $privacy = []): Profile
{
    static $n = 0;
    $n++;
    $u = User::create(['name' => "M{$n}", 'email' => "m{$n}@example.test", 'password' => 'x']);
    $p = Profile::create(['user_id' => $u->id, 'matri_id' => 'AM' . (200000 + $n), 'full_name' => "M{$n}"]);

    if ($withPhoto) {
        ProfilePhoto::create(['profile_id' => $p->id, 'photo_type' => 'profile', 'photo_url' => "photos/{$n}.jpg", 'is_primary' => true]);
    }
    if ($privacy) {
        PhotoPrivacySetting::create(array_merge(['profile_id' => $p->id], $privacy));
    }

    return $p->fresh();
}

it('shows a visible-to-all photo to members and guests', function () {
    $owner = pvMember(privacy: ['profile_photo_privacy' => 'visible_to_all']);
    $viewer = pvMember();

    expect(PV::state($owner, $viewer))->toBe(PV::VISIBLE)
        ->and(PV::state($owner, null))->toBe(PV::VISIBLE)
        ->and(PV::url($owner, $viewer))->not->toBeNull();
});

it('hides a photo set to Hidden on the photos page (per-type field, legacy field empty)', function () {
    // Exactly what the privacy form saves: profile_photo_privacy only.
    $owner = pvMember(privacy: ['profile_photo_privacy' => 'hidden', 'privacy_level' => null]);
    $viewer = pvMember();

    expect(PV::state($owner, $viewer))->toBe(PV::HIDDEN)
        ->and(PV::state($owner, null))->toBe(PV::HIDDEN)
        ->and(PV::url($owner, $viewer))->toBeNull();
});

it('reveals a hidden photo only to the member whose request was approved', function () {
    $owner = pvMember(privacy: ['profile_photo_privacy' => 'hidden']);
    $approved = pvMember();
    $stranger = pvMember();
    PhotoRequest::create(['requester_profile_id' => $approved->id, 'target_profile_id' => $owner->id, 'status' => 'approved']);

    expect(PV::state($owner, $approved))->toBe(PV::VISIBLE)
        ->and(PV::state($owner, $stranger))->toBe(PV::HIDDEN);
});

it('shows an after-interest photo only once an interest is accepted, in either direction', function () {
    $owner = pvMember(privacy: ['profile_photo_privacy' => 'interest_accepted']);
    $accepted = pvMember();
    $pending = pvMember();
    Interest::create(['sender_profile_id' => $owner->id, 'receiver_profile_id' => $accepted->id, 'status' => 'accepted']);
    Interest::create(['sender_profile_id' => $pending->id, 'receiver_profile_id' => $owner->id, 'status' => 'pending']);

    expect(PV::state($owner, $accepted))->toBe(PV::VISIBLE)
        ->and(PV::state($owner, $pending))->toBe(PV::AFTER_ACCEPTANCE)
        ->and(PV::state($owner, null))->toBe(PV::AFTER_ACCEPTANCE);
});

it('still honours the legacy privacy_level for older rows', function () {
    $owner = pvMember(privacy: ['privacy_level' => 'hidden']);

    expect(PV::state($owner, pvMember()))->toBe(PV::HIDDEN);
});

it('always shows members their own photo', function () {
    $owner = pvMember(privacy: ['profile_photo_privacy' => 'hidden']);

    expect(PV::state($owner, $owner))->toBe(PV::VISIBLE);
});

it('reports no photo when there is no approved primary photo', function () {
    $owner = pvMember(withPhoto: false);

    expect(PV::state($owner, pvMember()))->toBe(PV::NO_PHOTO)
        ->and(PV::url($owner, null))->toBeNull();
});

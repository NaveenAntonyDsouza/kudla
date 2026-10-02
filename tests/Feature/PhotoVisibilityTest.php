<?php

use App\Models\Interest;
use App\Models\PhotoPrivacySetting;
use App\Models\PhotoRequest;
use App\Models\Profile;
use App\Models\ProfilePhoto;
use App\Models\User;
use App\Support\PhotoVisibility as PV;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        $t->string('thumbnail_url')->nullable();
        $t->string('storage_driver')->default('public');
        $t->boolean('is_primary')->default(false);
        $t->integer('display_order')->default(0);
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
    foreach (['interests', 'photo_requests', 'photo_privacy_settings', 'profile_photos', 'profiles', 'users', 'site_settings', 'user_memberships', 'membership_plans'] as $table) {
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

it('sees an approval made after an earlier check in the same request', function () {
    $owner = pvMember(privacy: ['profile_photo_privacy' => 'hidden']);
    $viewer = pvMember();
    $request = PhotoRequest::create(['requester_profile_id' => $viewer->id, 'target_profile_id' => $owner->id, 'status' => 'pending']);

    expect(PV::state($owner, $viewer))->toBe(PV::HIDDEN); // memoised here…

    $request->update(['status' => 'approved']);

    expect(PV::state($owner, $viewer))->toBe(PV::VISIBLE); // …but not stale
});

it('sees an interest accepted after an earlier check in the same request', function () {
    $owner = pvMember(privacy: ['profile_photo_privacy' => 'interest_accepted']);
    $viewer = pvMember();
    $interest = Interest::create(['sender_profile_id' => $viewer->id, 'receiver_profile_id' => $owner->id, 'status' => 'pending']);

    expect(PV::state($owner, $viewer))->toBe(PV::AFTER_ACCEPTANCE);

    $interest->update(['status' => 'accepted']);

    expect(PV::state($owner, $viewer))->toBe(PV::VISIBLE);
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

/* ---------------- Photo viewer (profile page) ---------------- */

function pvAddPhoto(Profile $p, string $type, string $file, array $attrs = []): void
{
    ProfilePhoto::create(array_merge(['profile_id' => $p->id, 'photo_type' => $type, 'photo_url' => "photos/{$file}"], $attrs));
}

function pvGallerySrcs(array $gallery): array
{
    return array_map(fn ($p) => basename($p['src']), $gallery['photos']);
}

it('lists the viewer\'s photos main first, then profile, album, family — approved and visible only', function () {
    $owner = pvMember(); // primary: photos/{n}.jpg
    pvAddPhoto($owner, 'family', 'fam.jpg');
    pvAddPhoto($owner, 'album', 'album-2.jpg', ['display_order' => 2]);
    pvAddPhoto($owner, 'album', 'album-1.jpg', ['display_order' => 1]);
    pvAddPhoto($owner, 'profile', 'side.jpg');
    pvAddPhoto($owner, 'album', 'pending.jpg', ['approval_status' => 'pending']);
    pvAddPhoto($owner, 'album', 'rejected.jpg', ['approval_status' => 'rejected']);
    pvAddPhoto($owner, 'album', 'switched-off.jpg', ['is_visible' => false]);
    $owner = $owner->fresh();

    $srcs = pvGallerySrcs(PV::gallery($owner, pvMember()));

    expect($srcs[0])->toBe(basename($owner->primaryPhoto->photo_url))
        ->and(array_slice($srcs, 1))->toBe(['side.jpg', 'album-1.jpg', 'album-2.jpg', 'fam.jpg']);
});

it('gates each photo type by its own privacy and never sends locked image addresses', function () {
    $owner = pvMember(privacy: [
        'profile_photo_privacy' => 'visible_to_all',
        'album_photos_privacy' => 'hidden',
        'family_photos_privacy' => 'interest_accepted',
    ]);
    pvAddPhoto($owner, 'album', 'secret-album.jpg');
    pvAddPhoto($owner, 'album', 'secret-album-2.jpg');
    pvAddPhoto($owner, 'family', 'secret-family.jpg');

    $gallery = PV::gallery($owner->fresh(), pvMember());

    expect(count($gallery['photos']))->toBe(1) // the main photo only
        ->and($gallery['locked'])->toBe([
            'album' => ['count' => 2, 'state' => PV::HIDDEN],
            'family' => ['count' => 1, 'state' => PV::AFTER_ACCEPTANCE],
        ])
        ->and(json_encode($gallery))->not->toContain('secret');
});

it('shows a guest nothing behind a hidden main photo', function () {
    $owner = pvMember(privacy: ['profile_photo_privacy' => 'hidden']);
    pvAddPhoto($owner, 'album', 'album.jpg');

    $gallery = PV::gallery($owner->fresh(), null);

    expect($gallery['photos'])->toBe([])
        ->and($gallery['locked']['profile']['state'])->toBe(PV::HIDDEN);
});

it('opens every hidden type once the viewer\'s photo request is approved', function () {
    $owner = pvMember(privacy: ['profile_photo_privacy' => 'hidden', 'album_photos_privacy' => 'hidden']);
    pvAddPhoto($owner, 'album', 'album.jpg');
    $viewer = pvMember();
    PhotoRequest::create(['requester_profile_id' => $viewer->id, 'target_profile_id' => $owner->id, 'status' => 'approved']);

    $gallery = PV::gallery($owner->fresh(), $viewer);

    expect(count($gallery['photos']))->toBe(2)->and($gallery['locked'])->toBe([]);
});

it('shows members all their own photos whatever their privacy', function () {
    $owner = pvMember(privacy: ['profile_photo_privacy' => 'hidden', 'album_photos_privacy' => 'hidden', 'family_photos_privacy' => 'interest_accepted']);
    pvAddPhoto($owner, 'album', 'album.jpg');
    pvAddPhoto($owner, 'family', 'fam.jpg');
    $owner = $owner->fresh();

    expect(count(PV::gallery($owner, $owner)['photos']))->toBe(3);
});

it('never makes album or family photos more visible than the main photo', function () {
    // Main photo after-interest; album left on the default "visible to all"
    $owner = pvMember(privacy: ['profile_photo_privacy' => 'interest_accepted']);
    pvAddPhoto($owner, 'album', 'album.jpg');
    $owner = $owner->fresh();
    $stranger = pvMember();
    $partner = pvMember();
    Interest::create(['sender_profile_id' => $partner->id, 'receiver_profile_id' => $owner->id, 'status' => 'accepted']);

    expect(PV::gate($owner, $stranger, 'album'))->toBe(PV::AFTER_ACCEPTANCE)
        ->and(PV::gallery($owner, $stranger)['photos'])->toBe([])
        ->and(PV::gate($owner, $partner, 'album'))->toBe(PV::VISIBLE)
        ->and(count(PV::gallery($owner, $partner)['photos']))->toBe(2);
});

/* ---------------- "Premium members only" ---------------- */

function pvPremiumSchema(): void
{
    if (! Schema::hasTable('site_settings')) {
        Schema::create('site_settings', function (Blueprint $t) {
            $t->id();
            $t->string('key')->unique();
            $t->text('value')->nullable();
            $t->timestamps();
        });
    }
    if (! Schema::hasTable('membership_plans')) {
        Schema::create('membership_plans', function (Blueprint $t) {
            $t->id();
            $t->boolean('can_view_contact')->default(true);
            $t->timestamps();
        });
        DB::table('membership_plans')->insert(['id' => 1, 'can_view_contact' => true]);
    }
    if (! Schema::hasTable('user_memberships')) {
        Schema::create('user_memberships', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->unsignedBigInteger('plan_id')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamp('starts_at')->nullable();
            $t->timestamp('ends_at')->nullable();
            $t->timestamps();
        });
    }
}

function pvMakePremium(Profile $p): void
{
    DB::table('user_memberships')->insert(['user_id' => $p->user_id, 'plan_id' => 1, 'is_active' => true, 'ends_at' => now()->addMonth()]);
}

it('premium-only: paying members see it; free members and guests get a premium placeholder', function () {
    pvPremiumSchema();
    $owner = pvMember(privacy: ['profile_photo_privacy' => 'premium_only']);
    $free = pvMember();
    $paid = pvMember();
    pvMakePremium($paid);

    expect(PV::state($owner, $paid))->toBe(PV::VISIBLE)
        ->and(PV::state($owner, $free))->toBe(PV::PREMIUM_ONLY)
        ->and(PV::state($owner, null))->toBe(PV::PREMIUM_ONLY)
        ->and(PV::url($owner, $free))->toBeNull();
});

it('premium-only: someone the owner already accepted sees it without paying', function () {
    pvPremiumSchema();
    $owner = pvMember(privacy: ['profile_photo_privacy' => 'premium_only']);
    $partner = pvMember();
    $requester = pvMember();
    Interest::create(['sender_profile_id' => $partner->id, 'receiver_profile_id' => $owner->id, 'status' => 'accepted']);
    PhotoRequest::create(['requester_profile_id' => $requester->id, 'target_profile_id' => $owner->id, 'status' => 'approved']);

    expect(PV::state($owner, $partner))->toBe(PV::VISIBLE)
        ->and(PV::state($owner, $requester))->toBe(PV::VISIBLE);
});

it('premium-only: in Free Membership mode every member sees it and the choice is not offered', function () {
    pvPremiumSchema();
    DB::table('site_settings')->insert(['key' => 'free_membership_enabled', 'value' => '1']);
    \Illuminate\Support\Facades\Cache::flush();
    $owner = pvMember(privacy: ['profile_photo_privacy' => 'premium_only']);

    expect(PV::state($owner, pvMember()))->toBe(PV::VISIBLE)
        ->and(PV::state($owner, null))->toBe(PV::PREMIUM_ONLY) // still never guests
        ->and(PhotoPrivacySetting::levelsOffered())->not->toHaveKey('premium_only');

    DB::table('site_settings')->where('key', 'free_membership_enabled')->update(['value' => '0']);
    \Illuminate\Support\Facades\Cache::flush();
    expect(PhotoPrivacySetting::levelsOffered())->toHaveKey('premium_only');
});

it('premium-only album photos stay locked for free members even when the main photo is public', function () {
    pvPremiumSchema();
    $owner = pvMember(privacy: ['profile_photo_privacy' => 'visible_to_all', 'album_photos_privacy' => 'premium_only']);
    pvAddPhoto($owner, 'album', 'album.jpg');
    $owner = $owner->fresh();

    $gallery = PV::gallery($owner, pvMember());
    expect(count($gallery['photos']))->toBe(1)
        ->and($gallery['locked']['album']['state'])->toBe(PV::PREMIUM_ONLY);
});

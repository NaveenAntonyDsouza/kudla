<?php

use App\Models\PhotoPrivacySetting;
use App\Models\Profile;
use App\Models\ProfilePhoto;
use App\Models\ThemeSetting;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Onboarding Photo Step (web) — v2
|--------------------------------------------------------------------------
| Step 5 of the onboarding funnel. The page itself (GET /onboarding/photo)
| now embeds the same rich /manage-photos grid via a shared partial; uploads
| go through PhotoController::upload (covered by its own tests). The
| onboarding-specific endpoints exercised here are:
|
|   GET  /onboarding/photo     — renders the grid with a Continue CTA
|   POST /onboarding/finish    — flips onboarding_completed=true and lands
|                                on /dashboard
|
| The old POST /onboarding/photo and POST /onboarding/photo/skip endpoints
| were removed in this refactor. The corresponding RegisterController-style
| anonymous-Profile stubbing (with its FK-derivation workaround) was deleted
| with them — the controller no longer touches profilePhotos at all.
|
| Reference: docs/mobile-app/00-decisions-and-context.md (the photo-on-
| registration + photo-on-onboarding pair agreed 2026-04-30) and the v2
| decision on 2026-05-12 to consolidate onto the /manage-photos UI.
*/

function seedThemeSettingsCacheForOnboardingTest(): void
{
    $theme = new ThemeSetting([
        'site_name' => 'Test Matrimony',
        'tagline' => 'Test',
        'primary_color' => '#dc2626',
        'primary_hover' => '#b91c1c',
        'primary_light' => '#fee2e2',
        'secondary_color' => '#fbbf24',
        'secondary_hover' => '#f59e0b',
        'secondary_light' => '#fef3c7',
        'heading_font' => 'Inter',
        'body_font' => 'Inter',
    ]);
    Cache::put('theme_settings', $theme, 3600);
    Cache::put('site_setting.google_analytics_id', '', 3600);
    Cache::put('site_setting.google_tag_manager_id', '', 3600);
    Cache::put('site_setting.facebook_pixel_id', '', 3600);
    Cache::put('site_setting.posthog_api_key', '', 3600);
    Cache::put('site_setting.posthog_host', 'https://us.i.posthog.com', 3600);
    Cache::put('site_setting.site_name', 'Test Matrimony', 3600);
}

function createMinimalSchemaForOnboardingTest(): void
{
    if (! Schema::hasTable('profile_photos')) {
        Schema::create('profile_photos', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('profile_id');
            $t->string('photo_type');
            $t->string('photo_url')->nullable();
            $t->string('thumbnail_url')->nullable();
            $t->string('medium_url')->nullable();
            $t->string('original_url')->nullable();
            $t->string('storage_driver')->default('public');
            $t->boolean('is_primary')->default(false);
            $t->boolean('is_visible')->default(true);
            $t->integer('display_order')->default(0);
            $t->string('approval_status')->default('pending');
            $t->timestamps();
            $t->index('profile_id');
        });
    }

    // photo_privacy_settings is queried via the photoPrivacySetting HasOne
    // relation during showPhoto. The table must exist even if empty.
    if (! Schema::hasTable('photo_privacy_settings')) {
        Schema::create('photo_privacy_settings', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('profile_id');
            $t->string('privacy_level')->nullable();
            $t->string('profile_photo_privacy')->nullable();
            $t->string('album_photos_privacy')->nullable();
            $t->string('family_photos_privacy')->nullable();
            $t->timestamps();
            $t->index('profile_id');
        });
    }
}

function actingAsOnboardingUser(int $userId = 7001, int $profileId = 7001): User
{
    $user = new User();
    $user->exists = true;
    $user->forceFill([
        'id' => $userId,
        'email' => "onboard-photo-{$userId}@example.com",
        'phone' => '9700000000',
        'name' => 'Onboard Photo Test',
        'is_active' => true,
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
    ]);

    // Anonymous Profile subclass — same pattern as RegistrationPhotoStepTest:
    //   calculateCompletion() — walks religious_info / education_detail /
    //     lifestyle_info / etc. tables which this inline schema doesn't
    //     bootstrap. Returns a fixed % instead.
    //   update() — would persist to a profiles table this test doesn't
    //     create. No-op; assertions look at redirect destinations, not
    //     persisted side effects.
    //   profilePhotos() — explicitly names 'profile_id' as the FK because
    //     anonymous classes break Eloquent's default snake_case-of-parent
    //     FK derivation (it would produce e.g. "onboarding_photo_step_test
    //     .php:NNN$xxx_id"). Required for showPhoto's photo queries.
    $profile = new class extends Profile
    {
        public function calculateCompletion(): int { return 50; }
        public function update(array $attributes = [], array $options = []) { return true; }
        public function profilePhotos(): \Illuminate\Database\Eloquent\Relations\HasMany
        {
            return $this->hasMany(ProfilePhoto::class, 'profile_id', 'id');
        }
        public function photoPrivacySetting(): \Illuminate\Database\Eloquent\Relations\HasOne
        {
            return $this->hasOne(PhotoPrivacySetting::class, 'profile_id', 'id');
        }
    };
    $profile->exists = true;
    $profile->forceFill([
        'id' => $profileId,
        'user_id' => $userId,
        'gender' => 'female',
        'is_active' => true,
        'is_approved' => true,
        'onboarding_step_completed' => 5,
        'onboarding_completed' => false,
    ]);

    $profile->setRelation('user', $user);
    $user->setRelation('profile', $profile);

    test()->actingAs($user);

    return $user;
}

beforeEach(function () {
    createMinimalSchemaForOnboardingTest();
    seedThemeSettingsCacheForOnboardingTest();
});

afterEach(function () {
    if (Schema::hasTable('profile_photos')) {
        \DB::table('profile_photos')->delete();
    }
    Cache::flush();
});

it('renders the photo step page with the manage-photos grid', function () {
    actingAsOnboardingUser(userId: 7002, profileId: 7002);

    $response = $this->get('/onboarding/photo');

    $response->assertOk();
    // The shared partial drives the Alpine grid; its component name is the
    // canonical signal that we're rendering the rich UI (not the old
    // standalone cropper page).
    $response->assertSee('photoManagerEditor');
    // Continue button posts to /onboarding/finish, not the old endpoints.
    $response->assertSee(route('onboarding.finish'), false);
});

it('finish endpoint redirects to dashboard', function () {
    actingAsOnboardingUser(userId: 7003, profileId: 7003);

    $this->post('/onboarding/finish')
        ->assertRedirect(route('dashboard'));
});

it('removed POST /onboarding/photo no longer routes', function () {
    actingAsOnboardingUser(userId: 7004, profileId: 7004);

    // The old POST /onboarding/photo (storePhoto) was removed in v2 — the
    // upload now goes through PhotoController::upload instead. Hitting the
    // old endpoint should 405 (method not allowed) since GET still exists.
    $this->post('/onboarding/photo', [])
        ->assertStatus(405);
});

it('removed POST /onboarding/photo/skip no longer exists', function () {
    actingAsOnboardingUser(userId: 7005, profileId: 7005);

    // The old POST /onboarding/photo/skip was removed in v2 — finish is
    // the single exit. 404 because no /onboarding/photo/skip route exists
    // for any verb.
    $this->post('/onboarding/photo/skip', [])
        ->assertStatus(404);
});

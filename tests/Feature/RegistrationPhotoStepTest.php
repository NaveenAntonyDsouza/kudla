<?php

use App\Models\Profile;
use App\Models\ProfilePhoto;
use App\Models\ThemeSetting;
use App\Models\User;
use App\Services\ImageProcessingService;
use App\Services\PhotoStorageService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Registration Photo Step (web)
|--------------------------------------------------------------------------
| Optional opt-in screen between Step 5 and email/phone verification.
| Validates: redirect detour from Step 5, valid file upload creates a
| primary ProfilePhoto, missing file → validation error, skip endpoint
| creates no row, prior photo bypasses the screen.
|
| Mirrors the ergonomics of Api/V1/PhotoControllerTest — inline SQLite
| schema for profile_photos, fake Image/Storage services bound to the
| container so uploads don't touch real disks. Pre-populates the cache
| (CACHE_STORE=array in phpunit.xml) for the SiteSetting keys the
| controller queries — sidesteps the missing site_settings table without
| reproducing the entire SiteSetting schema.
|
| The user is configured as email_verified=null + email_verification
| ENABLED so redirectAfterPhotoStep terminates at /register/verify-email
| BEFORE reaching the profile.update() branch — that branch would need a
| profiles table the test deliberately doesn't create.
|
| Reference: docs/mobile-app/00-decisions-and-context.md (the photo-on-
| registration product decision agreed 2026-04-30).
*/

function createProfilePhotosTableForRegistrationTest(): void
{
    if (Schema::hasTable('profile_photos')) {
        return;
    }

    Schema::create('profile_photos', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('profile_id');
        $t->string('photo_type');
        $t->string('photo_url')->nullable();
        $t->string('cloudinary_public_id')->nullable();
        $t->string('thumbnail_url')->nullable();
        $t->string('medium_url')->nullable();
        $t->string('original_url')->nullable();
        $t->string('storage_driver')->default('public');
        $t->boolean('is_primary')->default(false);
        $t->boolean('is_visible')->default(true);
        $t->integer('display_order')->default(0);
        $t->string('approval_status')->default('pending');
        $t->string('rejection_reason')->nullable();
        $t->unsignedBigInteger('approved_by')->nullable();
        $t->timestamp('approved_at')->nullable();
        $t->timestamps();

        $t->index('profile_id');
    });
}

function bindFakeImageProcessorForRegistrationTest(): void
{
    $fake = new class extends ImageProcessingService
    {
        public function __construct() {}

        public function processUpload($file, string $storagePath, string $disk = 'public'): array
        {
            return [
                'original' => "{$storagePath}/fake-original.jpg",
                'full' => "{$storagePath}/fake-full.webp",
                'medium' => "{$storagePath}/fake-medium.webp",
                'thumb' => "{$storagePath}/fake-thumb.webp",
                'driver' => $disk,
            ];
        }

        public function deleteVariants(array $paths, string $disk = 'public'): void {}
    };
    app()->instance(ImageProcessingService::class, $fake);
}

function bindFakeStorageServiceForRegistrationTest(): void
{
    $fake = new class extends PhotoStorageService
    {
        public function __construct() {}

        public function getActiveDriver(): string
        {
            return self::DRIVER_LOCAL;
        }

        public function isDriverConfigured(string $driver): bool
        {
            return true;
        }
    };
    app()->instance(PhotoStorageService::class, $fake);
}

/**
 * Pre-populate site setting cache keys so SiteSetting::getValue() returns
 * test values without touching the missing DB table.
 *
 * Verification flags are configured to terminate redirectAfterPhotoStep at
 * /register/verify-email (the user has no email_verified_at). This avoids
 * the trailing $profile->update() call which would otherwise need a
 * profiles table the test doesn't bootstrap.
 */
function seedSiteSettingsCacheForRegistrationTest(): void
{
    Cache::put('site_setting.email_verification_enabled', '1', 3600);
    Cache::put('site_setting.phone_verification_enabled', '0', 3600);
    Cache::put('site_setting.auto_approve_profile_photos', '1', 3600);
}

/**
 * Pre-populate the theme_settings cache so view rendering doesn't query DB.
 * Keeps view-render tests (#1) viable without creating the table.
 */
function seedThemeSettingsCacheForRegistrationTest(): void
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

    // Tracking partials read these — defaulting to empty disables those
    // tracking pixels in the rendered view.
    Cache::put('site_setting.google_analytics_id', '', 3600);
    Cache::put('site_setting.google_tag_manager_id', '', 3600);
    Cache::put('site_setting.facebook_pixel_id', '', 3600);
    Cache::put('site_setting.posthog_api_key', '', 3600);
    Cache::put('site_setting.posthog_host', 'https://us.i.posthog.com', 3600);
    Cache::put('site_setting.site_name', 'Test Matrimony', 3600);
    Cache::put('site_setting.phone', '77600 20186', 3600); // "Need Assistance? Call Us"
    Cache::put('site_setting.hide_phone_whatsapp', '0', 3600);
    // SEO tags on the registration pages (Oct 2026)
    Cache::put('site_setting.meta_description_default', 'Test Matrimony. Register free.', 3600);
    Cache::put('page_seo_v2.register', [], 3600);
    Cache::put('site_setting.share_image_url', '', 3600);
}

function actingAsRegistrationUser(int $userId = 6001, int $profileId = 6001): User
{
    $user = new User();
    $user->exists = true;
    $user->forceFill([
        'id' => $userId,
        'email' => "reg-photo-{$userId}@example.com",
        'phone' => '9700000000',
        'name' => 'Reg Photo Test',
        'is_active' => true,
        // No email_verified_at — redirectAfterPhotoStep will land at
        // /register/verify-email (avoiding the profile.update() at the
        // tail of the redirect helper, which needs a profiles table we
        // deliberately don't create here).
        'email_verified_at' => null,
        'phone_verified_at' => null,
    ]);

    $profile = new Profile();
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
    createProfilePhotosTableForRegistrationTest();
    // Only what "Skip for now" writes: the member's choice is remembered (Oct 2026)
    if (! Schema::hasTable('profiles')) {
        Schema::create('profiles', function (Blueprint $t) {
            $t->id();
            $t->timestamp('photo_step_skipped_at')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
    }
    if (! Schema::hasTable('users')) {
        // the "last active" touch that runs once a profiles table exists
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->timestamp('last_login_at')->nullable();
            $t->unsignedTinyInteger('reengagement_level')->default(0);
            $t->timestamps();
        });
    }
    foreach ([6001, 6002, 6003, 6004, 6005, 6006, 6007, 6008, 6009, 6010] as $id) {
        \DB::table('users')->insertOrIgnore(['id' => $id]);
        \DB::table('profiles')->insertOrIgnore(['id' => $id]);
    }
    bindFakeImageProcessorForRegistrationTest();
    bindFakeStorageServiceForRegistrationTest();
    seedSiteSettingsCacheForRegistrationTest();
    seedThemeSettingsCacheForRegistrationTest();
});

afterEach(function () {
    if (Schema::hasTable('profile_photos')) {
        \DB::table('profile_photos')->delete();
    }
    Cache::flush();
});

it('redirects past /register/photo to verify-email when a profile photo already exists', function () {
    $user = actingAsRegistrationUser(userId: 6002, profileId: 6002);

    \DB::table('profile_photos')->insert([
        'profile_id' => $user->profile->id,
        'photo_type' => 'profile',
        'photo_url' => 'photos/6002/existing.webp',
        'storage_driver' => 'public',
        'is_primary' => true,
        'is_visible' => true,
        'display_order' => 1,
        'approval_status' => 'approved',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->get('/register/photo')
        ->assertRedirect(route('register.verifyemail'));
});

it('uploads a photo, creates a primary ProfilePhoto row, and redirects to verify-email', function () {
    $user = actingAsRegistrationUser(userId: 6003, profileId: 6003);

    $file = UploadedFile::fake()->image('me.jpg', 800, 1000);

    $this->post('/register/photo', ['photo' => $file])
        ->assertRedirect(route('register.verifyemail'));

    $row = \DB::table('profile_photos')->where('profile_id', $user->profile->id)->first();
    expect($row)->not->toBeNull();
    expect($row->photo_type)->toBe('profile');
    expect((bool) $row->is_primary)->toBeTrue();
    expect((bool) $row->is_visible)->toBeTrue();
    expect($row->approval_status)->toBe(ProfilePhoto::STATUS_APPROVED);
    expect($row->photo_url)->toBe("photos/{$user->profile->id}/fake-full.webp");
});

it('rejects an empty photo upload with a validation error', function () {
    actingAsRegistrationUser(userId: 6004, profileId: 6004);

    $this->post('/register/photo', [])
        ->assertSessionHasErrors('photo');

    expect(\DB::table('profile_photos')->count())->toBe(0);
});

it('skip endpoint creates no photo row and redirects to verify-email', function () {
    $user = actingAsRegistrationUser(userId: 6005, profileId: 6005);

    $this->post('/register/photo/skip')
        ->assertRedirect(route('register.verifyemail'));

    expect(\DB::table('profile_photos')->where('profile_id', $user->profile->id)->count())
        ->toBe(0);
});

it('counts a skipped photo step for the weekly review', function () {
    actingAsRegistrationUser(userId: 6006, profileId: 6006);

    $this->post('/register/photo/skip');

    expect(array_column((array) session(\App\Support\Analytics::SESSION_KEY, []), 'name'))
        ->toContain('photo_skipped');
});


it('remembers "Skip for now", so logging back in does not show the photo step again', function () {
    $user = actingAsRegistrationUser(userId: 6007, profileId: 6007);

    expect(\App\Http\Controllers\Auth\RegisterController::resumeRoute($user->profile))->toBe('register.photo');

    $this->post('/register/photo/skip');

    expect(\DB::table('profiles')->where('id', 6007)->value('photo_step_skipped_at'))->not->toBeNull()
        ->and(\App\Http\Controllers\Auth\RegisterController::resumeRoute($user->profile->forceFill(['photo_step_skipped_at' => \DB::table('profiles')->where('id', 6007)->value('photo_step_skipped_at')])))
        ->toBe('register.verifyemail');
});

it('sends earlier steps back to their form, whatever the photo', function () {
    $user = actingAsRegistrationUser(userId: 6008, profileId: 6008);
    $user->profile->forceFill(['onboarding_step_completed' => 2]);

    expect(\App\Http\Controllers\Auth\RegisterController::resumeRoute($user->profile))->toBe('register.step3');
});

it('counts "sent on WhatsApp" separately from a skip', function () {
    actingAsRegistrationUser(userId: 6009, profileId: 6009);

    $this->post('/register/photo/skip?via=whatsapp')->assertRedirect(route('register.verifyemail'));

    $names = array_column((array) session(\App\Support\Analytics::SESSION_KEY, []), 'name');
    expect($names)->toContain('photo_via_whatsapp')->not->toContain('photo_skipped');
});

it('offers sending the photo on WhatsApp only where the site has it switched on', function () {
    actingAsRegistrationUser(userId: 6010, profileId: 6010);
    Cache::put('site_setting.whatsapp', '+91 77600 20186', 3600);

    Cache::put('site_setting.photo_via_whatsapp', '0', 3600);
    $this->get('/register/photo')->assertOk()->assertDontSee('Send photo on WhatsApp');

    Cache::put('site_setting.photo_via_whatsapp', '1', 3600);
    $this->get('/register/photo')->assertOk()->assertSee('Send photo on WhatsApp')->assertSee('wa.me/917760020186', false);
});

it('lets the admin switch "photo by WhatsApp" on and off in Site Settings', function () {
    $page = file_get_contents(app_path('Filament/Pages/SiteSettings.php'));

    expect($page)->toContain("Forms\Components\Toggle::make('photo_via_whatsapp')")
        ->toContain("'photo_via_whatsapp' => (\$settings['photo_via_whatsapp'] ?? '0') === '1'")
        ->toContain("'whatsapp_button_enabled', 'photo_via_whatsapp',");
});

it('lets a woman choose who sees her photo as she uploads it', function () {
    Schema::dropIfExists('photo_privacy_settings');
    Schema::create('photo_privacy_settings', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('profile_id');
        $t->string('privacy_level')->nullable();
        $t->string('profile_photo_privacy')->nullable();
        $t->timestamps();
    });
    actingAsRegistrationUser(userId: 6003, profileId: 6003);   // a woman
    Cache::put('site_setting.photo_via_whatsapp', '0', 3600);
    Cache::put('site_setting.whatsapp', '', 3600);

    $this->get('/register/photo')->assertOk()
        ->assertSee('Who can see your photo?')
        ->assertSee('Only members whose interest I accept');
    $this->post('/register/photo', ['photo' => UploadedFile::fake()->image('me.jpg', 800, 1000), 'photo_privacy' => 'interest_accepted'])
        ->assertRedirect(route('register.verifyemail'));

    expect(\DB::table('photo_privacy_settings')->where('profile_id', 6003)->value('profile_photo_privacy'))->toBe('interest_accepted');
    Schema::dropIfExists('photo_privacy_settings');
});

it('keeps the plain privacy note on a man\'s photo step', function () {
    $user = actingAsRegistrationUser(userId: 6004, profileId: 6004);
    $user->profile->forceFill(['gender' => 'male']);
    Cache::put('site_setting.photo_via_whatsapp', '0', 3600);
    Cache::put('site_setting.whatsapp', '', 3600);

    $this->get('/register/photo')->assertOk()
        ->assertDontSee('Who can see your photo?')
        ->assertSee('Fine-tune privacy any time at');
});

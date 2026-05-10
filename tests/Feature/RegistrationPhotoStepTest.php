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

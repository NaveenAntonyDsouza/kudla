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
| Onboarding Photo Step (web)
|--------------------------------------------------------------------------
| Step 5 of the onboarding funnel. Lifestyle's Save and Skip-for-now both
| land here. Uploading or skipping calls finishOnboarding() →
| onboarding_completed=true → /dashboard.
|
| Mirrors RegistrationPhotoStepTest's setup pattern (inline profile_photos
| schema, fake Image/Storage services, pre-populated cache for the
| SiteSetting keys the controller queries). The test user is configured
| with onboarding_completed=false so the photo step is meaningful.
|
| Reference: docs/mobile-app/00-decisions-and-context.md (the photo-on-
| registration + photo-on-onboarding pair agreed 2026-04-30).
*/

function createProfilePhotosTableForOnboardingTest(): void
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

function bindFakeImageProcessorForOnboardingTest(): void
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

function bindFakeStorageServiceForOnboardingTest(): void
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

function seedSiteSettingsCacheForOnboardingTest(): void
{
    Cache::put('site_setting.auto_approve_profile_photos', '1', 3600);
}

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

    // Anonymous class stubbing three Profile concerns that finishOnboarding()
    // and storePhoto() would otherwise hit:
    //   calculateCompletion() — walks religious_info / education_detail /
    //     lifestyle_info / etc. tables which the inline test schema
    //     deliberately doesn't bootstrap. Returns a fixed % instead.
    //   update() — would persist onboarding_completed=true to a missing
    //     profiles table. No-op; the test asserts the redirect destination,
    //     not the persisted side effect.
    //   profilePhotos() — must EXPLICITLY name 'profile_id' as the FK.
    //     Default hasMany derivation uses the parent-class snake_case name,
    //     which for an anonymous class becomes something like
    //     "onboarding_photo_step_test.php:151$1cb_id" → SQL: no such column.
    $profile = new class extends Profile
    {
        public function calculateCompletion(): int { return 50; }
        public function update(array $attributes = [], array $options = []) { return true; }
        public function profilePhotos(): \Illuminate\Database\Eloquent\Relations\HasMany
        {
            return $this->hasMany(ProfilePhoto::class, 'profile_id', 'id');
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
    createProfilePhotosTableForOnboardingTest();
    bindFakeImageProcessorForOnboardingTest();
    bindFakeStorageServiceForOnboardingTest();
    seedSiteSettingsCacheForOnboardingTest();
    seedThemeSettingsCacheForOnboardingTest();
});

afterEach(function () {
    if (Schema::hasTable('profile_photos')) {
        \DB::table('profile_photos')->delete();
    }
    Cache::flush();
});

it('uploads a photo, creates a primary ProfilePhoto row, and redirects to dashboard', function () {
    $user = actingAsOnboardingUser(userId: 7002, profileId: 7002);

    $file = UploadedFile::fake()->image('me.jpg', 800, 1000);

    $this->post('/onboarding/photo', ['photo' => $file])
        ->assertRedirect(route('dashboard'));

    $row = \DB::table('profile_photos')->where('profile_id', $user->profile->id)->first();
    expect($row)->not->toBeNull();
    expect($row->photo_type)->toBe('profile');
    expect((bool) $row->is_primary)->toBeTrue();
    expect((bool) $row->is_visible)->toBeTrue();
    expect($row->approval_status)->toBe(ProfilePhoto::STATUS_APPROVED);
});

it('rejects an empty photo upload with a validation error', function () {
    actingAsOnboardingUser(userId: 7003, profileId: 7003);

    $this->post('/onboarding/photo', [])
        ->assertSessionHasErrors('photo');

    expect(\DB::table('profile_photos')->count())->toBe(0);
});

it('skip endpoint creates no photo row and redirects to dashboard', function () {
    $user = actingAsOnboardingUser(userId: 7004, profileId: 7004);

    $this->post('/onboarding/photo/skip')
        ->assertRedirect(route('dashboard'));

    expect(\DB::table('profile_photos')->where('profile_id', $user->profile->id)->count())
        ->toBe(0);
});

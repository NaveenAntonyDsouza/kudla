<?php

use App\Models\PartnerPreference;
use App\Models\Profile;
use App\Models\EducationDetail;
use App\Models\SiteSetting;
use App\Services\MatchingService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
| Site audit fixes, 25 Oct 2026 (owner OK'd):
| - Free Membership mode: the homepage plans section said "Upgrade…" with
|   prices while /membership-plans said all is free; the safety section said
|   contacts are for paid plans and that every profile is checked before it
|   shows (automatic approval is on).
| - "Write your own message" was offered to members who can't send one.
| - Education preferences saved as levels (Bachelor's) matched nobody.
| - A failed photo upload archived the member's old main photo first.
| - Pending interests to removed / deactivated profiles waited forever.
| - Memberships that ended on a day the job missed stayed "active".
*/

beforeEach(function () {
    Schema::dropIfExists('site_settings');
    Schema::create('site_settings', function (Blueprint $t) {
        $t->id();
        $t->string('key')->unique();
        $t->text('value')->nullable();
        $t->timestamps();
    });
    Schema::dropIfExists('membership_plans');
    Schema::create('membership_plans', function (Blueprint $t) {
        $t->id();
        $t->string('plan_name');
        $t->integer('price_inr')->default(0);
        $t->integer('strike_price_inr')->nullable();
        $t->integer('duration_months')->default(0);
        $t->json('features')->nullable();
        $t->boolean('is_popular')->default(false);
        $t->boolean('is_active')->default(true);
        $t->integer('sort_order')->default(0);
        $t->timestamps();
    });
    DB::table('membership_plans')->insert(['plan_name' => 'Silver', 'price_inr' => 1499, 'duration_months' => 3, 'features' => json_encode(['View up to 50 contacts'])]);
    Cache::flush();
});

afterEach(function () {
    foreach (['site_settings', 'membership_plans', 'interests', 'profiles', 'notifications', 'admin_activity_log'] as $table) {
        Schema::dropIfExists($table);
    }
});

$renderSection = fn (string $section) => view("pages.home.sections.{$section}", ['divider' => 'home-art.harmony-divider'])->render();

it('says everything is free on the homepage in Free Membership mode, instead of prices', function () use ($renderSection) {
    expect($renderSection('plans'))->toContain('&#8377;1,499')->toContain('Upgrade when you are ready');

    SiteSetting::setValue('free_membership_enabled', '1');
    expect($renderSection('plans'))->toContain('Everything Is Free Right Now')
        ->toContain('Send up to 5 interests a day')
        ->not->toContain('&#8377;')->not->toContain('Choose Silver');
});

it('only claims what the site really does in the safety section', function () use ($renderSection) {
    // defaults: automatic approval on, paid plans
    expect($renderSection('safety'))->toContain('Our team removes fake or misleading profiles')
        ->not->toContain('before it appears on the site')
        ->toContain('only after an interest is accepted, and only to members on a paid plan');

    SiteSetting::setValue('auto_approve_profiles', '0');
    SiteSetting::setValue('free_membership_enabled', '1');
    expect($renderSection('safety'))->toContain('Our team checks every new profile and photo before it appears on the site.')
        ->toContain('shown only after both of you accept an interest')
        ->not->toContain('paid plan');
});

it('offers "write your own message" only to members who can send one', function () {
    $modal = file_get_contents(resource_path('views/components/send-interest-modal.blade.php'));
    $service = file_get_contents(app_path('Services/InterestService.php'));

    expect($modal)->toContain('canWritePersonalMessage(auth()->user()->profile, $profile)')
        ->toContain('Write your own message: included in')
        ->and($service)->toContain('if ($customMessage && ! $this->canWritePersonalMessage($sender, $receiver))')
        ->toContain('public function canWritePersonalMessage(Profile $sender, ?Profile $receiver = null): bool');
});

it('matches education preferences given as levels or qualifications', function () {
    $match = fn (array $levels, ?array $quals, array $ed) => (function () use ($levels, $quals, $ed) {
        $candidate = new Profile();
        $candidate->setRelation('educationDetail', (new EducationDetail())->forceFill($ed));
        $prefs = (new PartnerPreference())->forceFill(['education_levels' => $levels, 'educational_qualifications' => $quals]);

        return $this->educationMatches($candidate, $prefs);
    })->call(app(MatchingService::class));

    expect($match(["Bachelor's"], null, ['highest_education' => 'B.Sc', 'education_level' => "Bachelor's"]))->toBeTrue()   // onboarding level
        ->and($match(['B.Sc'], null, ['highest_education' => 'B.Sc', 'education_level' => "Bachelor's"]))->toBeTrue()       // profile-edit qualification
        ->and($match([], ['MBA/PGDM'], ['highest_education' => 'MBA/PGDM', 'education_level' => "Master's"]))->toBeTrue()  // onboarding's 2nd box
        ->and($match(["Master's"], null, ['highest_education' => 'B.Sc', 'education_level' => "Bachelor's"]))->toBeFalse();

    expect(file_get_contents(app_path('Http/Controllers/SearchController.php')))->toContain("->orWhereIn('education_level', \$education)")
        ->and(file_get_contents(app_path('Http/Controllers/Api/V1/SearchController.php')))->toContain("->orWhereIn('education_level', \$education)");
});

it('archives the old main photo only after the new upload is processed', function () {
    foreach (['Http/Controllers/PhotoController.php', 'Http/Controllers/Api/V1/PhotoController.php', 'Http/Controllers/Auth/RegisterController.php'] as $file) {
        $src = file_get_contents(app_path($file));
        $processed = strpos($src, '->processUpload(');
        $archived = strpos($src, "->ofType('profile')", $processed ?: 0);
        expect($processed)->not->toBeFalse()
            ->and($archived)->toBeGreaterThan($processed)
            ->and(substr_count($src, "update(['is_visible' => false, 'is_primary' => false])"))->toBeGreaterThan(0);
    }
    expect(file_get_contents(resource_path('views/photos/_grid-content.blade.php')))->toContain('Your profile card shows no photo yet.');
});

it('closes pending interests to or from a removed or deactivated profile, leaving the rest', function () {
    Schema::create('profiles', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('user_id')->nullable();
        $t->string('matri_id')->nullable();
        $t->boolean('is_active')->default(true);
        $t->softDeletes();
        $t->timestamps();
    });
    Schema::create('interests', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('sender_profile_id');
        $t->unsignedBigInteger('receiver_profile_id');
        $t->string('status')->default('pending');
        $t->timestamps();
    });
    Schema::create('admin_activity_log', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('admin_user_id')->nullable();
        $t->string('action');
        $t->string('model_type')->nullable();
        $t->unsignedBigInteger('model_id')->nullable();
        $t->json('changes')->nullable();
        $t->string('ip_address')->nullable();
        $t->timestamps();
    });
    DB::table('profiles')->insert([
        ['id' => 1, 'is_active' => true, 'deleted_at' => null],
        ['id' => 2, 'is_active' => false, 'deleted_at' => null],   // deactivated
        ['id' => 3, 'is_active' => true, 'deleted_at' => now()],   // removed
        ['id' => 4, 'is_active' => true, 'deleted_at' => null],
    ]);
    DB::table('interests')->insert([
        ['sender_profile_id' => 1, 'receiver_profile_id' => 2, 'status' => 'pending', 'created_at' => now()],
        ['sender_profile_id' => 3, 'receiver_profile_id' => 4, 'status' => 'pending', 'created_at' => now()],
        ['sender_profile_id' => 1, 'receiver_profile_id' => 4, 'status' => 'pending', 'created_at' => now()],  // both here: stays
    ]);

    $this->artisan('interests:expire', ['--dry-run' => true])->expectsOutputToContain('Pending interests to/from removed or deactivated profiles: 2.');
    expect(DB::table('interests')->where('status', 'pending')->count())->toBe(3);

    $this->artisan('interests:expire')->assertSuccessful();
    expect(DB::table('interests')->where('status', 'expired')->count())->toBe(2)
        ->and(DB::table('interests')->where('sender_profile_id', 1)->where('receiver_profile_id', 4)->value('status'))->toBe('pending');
});

it('switches off every membership that has ended, not only yesterday\'s', function () {
    $src = file_get_contents(app_path('Console/Commands/SendMembershipExpiryReminders.php'));
    expect($src)->toContain("->where('ends_at', '<', now())")
        ->toContain('$membership->ends_at->lt(now()->subDays(3))')
        ->not->toContain("whereDate('ends_at', now()->subDay()->toDateString())");
});

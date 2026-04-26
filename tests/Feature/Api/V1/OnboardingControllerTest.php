<?php

use App\Http\Controllers\Api\V1\OnboardingController;
use App\Models\Profile;
use App\Models\User;
use App\Services\OnboardingService;
use App\Services\ProfileCompletionService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| OnboardingController — 5 endpoints
|--------------------------------------------------------------------------
| Tests verify CONTROLLER orchestration (validation, profile-required
| guard, service dispatch with correct args, response shape) using a
| FakeOnboardingService. The service's per-table persistence logic is
| straightforward updateOrCreate calls and is exercised end-to-end at
| step-15 Bruno smoke time.
|
| Inline `users` + `profiles` tables (minimal columns) are needed
| because finish/lifestyle do `$profile->update(['onboarding_completed'])`
| against real Eloquent.
*/

class FakeOnboardingService extends OnboardingService
{
    /** @var array<int, array{method: string, args: array}> */
    public array $calls = [];

    public function __construct() {}

    public function updateStep1(Profile $profile, array $personal, array $professional, array $family): void
    {
        $this->calls[] = ['method' => 'updateStep1', 'args' => compact('personal', 'professional', 'family')];
    }

    public function updateStep2(Profile $profile, array $location, array $contact): void
    {
        $this->calls[] = ['method' => 'updateStep2', 'args' => compact('location', 'contact')];
    }

    public function updatePartnerPrefs(Profile $profile, array $data): void
    {
        $this->calls[] = ['method' => 'updatePartnerPrefs', 'args' => $data];
    }

    public function updateLifestyle(Profile $profile, array $lifestyle, array $social): void
    {
        $this->calls[] = ['method' => 'updateLifestyle', 'args' => compact('lifestyle', 'social')];
    }
}

function buildOnboardingController(?FakeOnboardingService $svc = null): OnboardingController
{
    $svc ??= new FakeOnboardingService();
    return new OnboardingController($svc, app(ProfileCompletionService::class));
}

function buildOnboardingUser(bool $withProfile = true): User
{
    // Don't pass id — User's $fillable doesn't include it, so mass-assignment
    // would silently drop it and auto-increment would take over. Use the
    // returned id for the profile's user_id reference.
    $u = User::create([
        'name' => 'Test User',
        'email' => 'ob'.random_int(10000, 99999).'@e.com',
        'is_active' => true,
    ]);
    $u->setRelation('userMemberships', new EloquentCollection());

    if ($withProfile) {
        Profile::create([
            'user_id' => $u->id,
            'matri_id' => 'AM'.str_pad((string) $u->id, 6, '0', STR_PAD_LEFT),
            'gender' => 'male',
            'date_of_birth' => Carbon::parse('1995-01-01'),
            'is_active' => true,
            'is_approved' => true,
            'onboarding_completed' => false,
            'profile_completion_pct' => 0,
        ]);
        $u->load('profile');
    }

    return $u;
}

function onboardingRequest(User $user, array $body, string $path): Request
{
    $r = Request::create($path, 'POST', $body);
    $r->setUserResolver(fn () => $user);
    return $r;
}

beforeEach(function () {
    if (! Schema::hasTable('users')) {
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name')->nullable();
            $t->string('email')->nullable();
            $t->string('password')->nullable();
            $t->string('phone')->nullable();
            $t->string('role')->nullable();
            $t->unsignedBigInteger('staff_role_id')->nullable();
            $t->unsignedBigInteger('branch_id')->nullable();
            $t->timestamp('phone_verified_at')->nullable();
            $t->timestamp('email_verified_at')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamp('last_login_at')->nullable();
            $t->timestamp('last_reengagement_sent_at')->nullable();
            $t->integer('reengagement_level')->default(0);
            $t->timestamp('last_weekly_match_sent_at')->nullable();
            $t->integer('nudges_sent_count')->default(0);
            $t->timestamp('last_nudge_sent_at')->nullable();
            $t->json('notification_preferences')->nullable();
            $t->timestamps();
        });
    }
    if (! Schema::hasTable('profiles')) {
        Schema::create('profiles', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->string('matri_id', 20)->unique();
            $t->string('full_name')->nullable();
            $t->string('gender', 10)->nullable();
            $t->date('date_of_birth')->nullable();
            $t->string('marital_status', 30)->nullable();
            $t->string('weight_kg', 20)->nullable();
            $t->string('blood_group', 10)->nullable();
            $t->string('mother_tongue', 50)->nullable();
            $t->text('about_me')->nullable();
            $t->integer('profile_completion_pct')->default(0);
            $t->boolean('onboarding_completed')->default(false);
            $t->boolean('is_approved')->default(true);
            $t->boolean('is_active')->default(true);
            $t->boolean('is_hidden')->default(false);
            $t->boolean('is_verified')->default(false);
            $t->boolean('is_vip')->default(false);
            $t->boolean('is_featured')->default(false);
            $t->string('suspension_status', 20)->default('active');
            $t->string('show_profile_to', 30)->default('all');
            $t->boolean('only_same_religion')->default(false);
            $t->boolean('only_same_denomination')->default(false);
            $t->boolean('only_same_mother_tongue')->default(false);
            $t->string('deletion_reason', 200)->nullable();
            $t->timestamp('deleted_at')->nullable();
            $t->timestamps();
        });
    }
});

afterEach(function () {
    Schema::dropIfExists('profiles');
    Schema::dropIfExists('users');
});

/* ==================================================================
 |  Common — 422 PROFILE_REQUIRED
 | ================================================================== */

it('every step returns 422 PROFILE_REQUIRED when viewer has no profile', function () {
    $user = buildOnboardingUser(withProfile: false);
    $controller = buildOnboardingController();

    foreach (['step1', 'step2', 'partnerPrefs', 'lifestyle', 'finish'] as $method) {
        $path = '/api/v1/onboarding/'.match ($method) {
            'step1' => 'step-1',
            'step2' => 'step-2',
            'partnerPrefs' => 'partner-preferences',
            default => $method,
        };
        $response = $controller->$method(onboardingRequest($user, [], $path));

        expect($response->getStatusCode())->toBe(422);
        expect($response->getData(true)['error']['code'])->toBe('PROFILE_REQUIRED');
    }
});

/* ==================================================================
 |  step-1 — Personal + Professional + Family
 | ================================================================== */

it('step1 dispatches the 3 nested groups to OnboardingService', function () {
    $user = buildOnboardingUser();
    $svc = new FakeOnboardingService();
    $controller = buildOnboardingController($svc);

    $response = $controller->step1(onboardingRequest($user, [
        'personal' => [
            'weight_kg' => '70 kg',
            'mother_tongue' => 'Kannada',
            'languages_known' => ['Kannada', 'English'],
            'about_me' => 'Easygoing, family-oriented.',
        ],
        'professional' => [
            'occupation_detail' => 'Software Engineer',
            'employer_name' => 'Infosys',
        ],
        'family' => [
            'father_name' => 'Ramesh',
            'brothers_married' => 1,
        ],
    ], '/api/v1/onboarding/step-1'));

    expect($response->getStatusCode())->toBe(200);
    expect($response->getData(true)['data']['next_step'])->toBe('onboarding.step-2');

    expect($svc->calls)->toHaveCount(1);
    expect($svc->calls[0]['method'])->toBe('updateStep1');
    expect($svc->calls[0]['args']['personal']['weight_kg'])->toBe('70 kg');
    expect($svc->calls[0]['args']['personal']['languages_known'])->toBe(['Kannada', 'English']);
    expect($svc->calls[0]['args']['professional']['employer_name'])->toBe('Infosys');
    expect($svc->calls[0]['args']['family']['brothers_married'])->toBe(1);
});

it('step1 rejects when about_me exceeds 5000 chars', function () {
    $user = buildOnboardingUser();

    expect(fn () => buildOnboardingController()->step1(onboardingRequest($user, [
        'personal' => ['about_me' => str_repeat('a', 5001)],
    ], '/api/v1/onboarding/step-1')))->toThrow(\Illuminate\Validation\ValidationException::class);
});

it('step1 accepts empty body (all fields optional)', function () {
    $user = buildOnboardingUser();
    $svc = new FakeOnboardingService();

    $response = buildOnboardingController($svc)->step1(
        onboardingRequest($user, [], '/api/v1/onboarding/step-1'),
    );

    expect($response->getStatusCode())->toBe(200);
    // Service still called — with empty arrays.
    expect($svc->calls[0]['args']['personal'])->toBe([]);
});

/* ==================================================================
 |  step-2 — Location + Contact
 | ================================================================== */

it('step2 dispatches location + contact groups', function () {
    $user = buildOnboardingUser();
    $svc = new FakeOnboardingService();

    $response = buildOnboardingController($svc)->step2(onboardingRequest($user, [
        'location' => [
            'residing_country' => 'India',
            'residency_status' => null,
        ],
        'contact' => [
            'residential_phone_number' => '0824-1234567',
            'alternate_email' => 'alt@example.com',
        ],
    ], '/api/v1/onboarding/step-2'));

    expect($response->getStatusCode())->toBe(200);
    expect($response->getData(true)['data']['next_step'])->toBe('onboarding.partner-preferences');
    expect($svc->calls[0]['method'])->toBe('updateStep2');
    expect($svc->calls[0]['args']['location']['residing_country'])->toBe('India');
    expect($svc->calls[0]['args']['contact']['alternate_email'])->toBe('alt@example.com');
});

it('step2 rejects malformed alternate_email', function () {
    $user = buildOnboardingUser();

    expect(fn () => buildOnboardingController()->step2(onboardingRequest($user, [
        'contact' => ['alternate_email' => 'not-an-email'],
    ], '/api/v1/onboarding/step-2')))->toThrow(\Illuminate\Validation\ValidationException::class);
});

it('step2 rejects outstation_to before outstation_from', function () {
    $user = buildOnboardingUser();

    expect(fn () => buildOnboardingController()->step2(onboardingRequest($user, [
        'location' => [
            'outstation_leave_date_from' => '2026-06-01',
            'outstation_leave_date_to' => '2026-05-01',  // before from
        ],
    ], '/api/v1/onboarding/step-2')))->toThrow(\Illuminate\Validation\ValidationException::class);
});

/* ==================================================================
 |  partner-preferences
 | ================================================================== */

it('partnerPrefs dispatches the flat partner preferences payload', function () {
    $user = buildOnboardingUser();
    $svc = new FakeOnboardingService();

    $response = buildOnboardingController($svc)->partnerPrefs(onboardingRequest($user, [
        'age_from' => 22,
        'age_to' => 30,
        'religions' => ['Hindu'],
        'caste' => ['Brahmin'],
        'mother_tongues' => ['Kannada', 'English'],
        'about_partner' => 'Looking for a caring partner.',
    ], '/api/v1/onboarding/partner-preferences'));

    expect($response->getStatusCode())->toBe(200);
    expect($response->getData(true)['data']['next_step'])->toBe('onboarding.lifestyle');
    expect($svc->calls[0]['method'])->toBe('updatePartnerPrefs');
    expect($svc->calls[0]['args']['age_from'])->toBe(22);
    expect($svc->calls[0]['args']['religions'])->toBe(['Hindu']);
});

it('partnerPrefs rejects age_to less than age_from', function () {
    $user = buildOnboardingUser();

    expect(fn () => buildOnboardingController()->partnerPrefs(onboardingRequest($user, [
        'age_from' => 30,
        'age_to' => 22,  // less than from
    ], '/api/v1/onboarding/partner-preferences')))->toThrow(\Illuminate\Validation\ValidationException::class);
});

it('partnerPrefs rejects age outside 18-70', function () {
    $user = buildOnboardingUser();

    expect(fn () => buildOnboardingController()->partnerPrefs(onboardingRequest($user, [
        'age_from' => 15,  // too young
    ], '/api/v1/onboarding/partner-preferences')))->toThrow(\Illuminate\Validation\ValidationException::class);
});

/* ==================================================================
 |  lifestyle — final onboarding step
 | ================================================================== */

it('lifestyle dispatches lifestyle + social groups + flips onboarding_completed', function () {
    $user = buildOnboardingUser();
    $svc = new FakeOnboardingService();

    $response = buildOnboardingController($svc)->lifestyle(onboardingRequest($user, [
        'lifestyle' => [
            'diet' => 'Vegetarian',
            'hobbies' => ['Reading', 'Trekking'],
        ],
        'social' => [
            'instagram_url' => 'https://instagram.com/anita',
        ],
    ], '/api/v1/onboarding/lifestyle'));
    $data = $response->getData(true)['data'];

    expect($response->getStatusCode())->toBe(200);
    expect($data['next_step'])->toBe('dashboard');
    expect($data['onboarding_finished'])->toBeTrue();
    expect($svc->calls[0]['method'])->toBe('updateLifestyle');
    expect($svc->calls[0]['args']['lifestyle']['diet'])->toBe('Vegetarian');
    expect($svc->calls[0]['args']['social']['instagram_url'])->toBe('https://instagram.com/anita');

    expect($user->profile->fresh()->onboarding_completed)->toBeTrue();
});

it('lifestyle rejects invalid social URL', function () {
    $user = buildOnboardingUser();

    expect(fn () => buildOnboardingController()->lifestyle(onboardingRequest($user, [
        'social' => ['linkedin_url' => 'not-a-url'],
    ], '/api/v1/onboarding/lifestyle')))->toThrow(\Illuminate\Validation\ValidationException::class);
});

/* ==================================================================
 |  finish — skip-to-dashboard
 | ================================================================== */

it('finish flips onboarding_completed without touching field data', function () {
    $user = buildOnboardingUser();
    $svc = new FakeOnboardingService();

    $response = buildOnboardingController($svc)->finish(
        onboardingRequest($user, [], '/api/v1/onboarding/finish'),
    );
    $data = $response->getData(true)['data'];

    expect($response->getStatusCode())->toBe(200);
    expect($data)->toMatchArray([
        'next_step' => 'dashboard',
        'onboarding_finished' => true,
    ]);

    // Service was NOT called — finish touches no field data.
    expect($svc->calls)->toBe([]);

    expect($user->profile->fresh()->onboarding_completed)->toBeTrue();
});

it('finish is idempotent — calling twice is fine', function () {
    $user = buildOnboardingUser();
    $controller = buildOnboardingController();

    $controller->finish(onboardingRequest($user, [], '/api/v1/onboarding/finish'));
    $r = $controller->finish(onboardingRequest($user, [], '/api/v1/onboarding/finish'));

    expect($r->getStatusCode())->toBe(200);
    expect($user->profile->fresh()->onboarding_completed)->toBeTrue();
});

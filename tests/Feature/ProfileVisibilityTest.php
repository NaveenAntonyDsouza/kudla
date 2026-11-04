<?php

use App\Models\Profile;
use App\Services\InterestService;
use App\Services\ProfileAccessService;
use App\Support\ProfileVisibility;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
| Privacy check, 4 Nov 2026. What members choose under Settings → "Show my
| profile to" was ignored in several places: Discover showed 77 Kudla members
| who chose "premium only", "matches only" or "same religion / caste / mother
| tongue only" to every visitor; "matches only" was checked the wrong way round;
| an empty caste counted as "the same"; "same sect only" compared denomination;
| and the web profile page checked gender only, so a hidden, blocked or
| deactivated profile opened for anyone who typed its Matri ID.
|
| One rule now (App\Support\ProfileVisibility + ProfileAccessService::check).
| Every case has members who must NOT see the profile, and the SQL lists must
| agree with the single-profile check for every viewer / target pair.
*/

const VIS_TABLES = ['site_settings', 'users', 'membership_plans', 'user_memberships', 'profiles', 'religious_info', 'partner_preferences', 'blocked_profiles', 'ignored_profiles', 'interests', 'daily_interest_usages'];

beforeEach(function () {
    Schema::create('site_settings', fn (Blueprint $t) => [$t->id(), $t->string('key')->unique(), $t->text('value')->nullable(), $t->timestamps()]);
    Schema::create('users', fn (Blueprint $t) => [$t->id(), $t->string('name')->nullable(), $t->string('email')->nullable(), $t->string('password')->nullable(), $t->string('role')->nullable(), $t->unsignedBigInteger('staff_role_id')->nullable(), $t->boolean('is_active')->default(true), $t->timestamps()]);
    Schema::create('membership_plans', fn (Blueprint $t) => [$t->id(), $t->boolean('can_view_contact')->default(true), $t->timestamps()]);
    Schema::create('user_memberships', fn (Blueprint $t) => [$t->id(), $t->unsignedBigInteger('user_id'), $t->unsignedBigInteger('plan_id'), $t->boolean('is_active')->default(true), $t->timestamp('starts_at')->nullable(), $t->timestamp('ends_at')->nullable(), $t->timestamps()]);
    Schema::create('profiles', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('user_id');
        $t->string('matri_id')->nullable();
        $t->string('gender')->nullable();
        $t->date('date_of_birth')->nullable();
        $t->string('mother_tongue')->nullable();
        $t->boolean('is_active')->default(true);
        $t->boolean('is_approved')->default(true);
        $t->boolean('is_hidden')->default(false);
        $t->string('suspension_status')->default('active');
        $t->string('show_profile_to')->default('all');
        $t->boolean('only_same_religion')->default(false);
        $t->boolean('only_same_denomination')->default(false);
        $t->boolean('only_same_mother_tongue')->default(false);
        $t->softDeletes();
        $t->timestamps();
    });
    Schema::create('religious_info', fn (Blueprint $t) => [$t->id(), $t->unsignedBigInteger('profile_id'), $t->string('religion')->nullable(), $t->string('denomination')->nullable(), $t->string('caste')->nullable(), $t->string('muslim_sect')->nullable(), $t->string('other_denomination_name')->nullable(), $t->string('other_caste_name')->nullable(), $t->string('nakshatra')->nullable(), $t->timestamps()]);
    Schema::create('partner_preferences', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('profile_id');
        foreach (['religions', 'mother_tongues', 'denomination', 'caste', 'education_levels', 'educational_qualifications', 'occupations', 'native_districts', 'native_states', 'native_countries', 'working_countries', 'working_states', 'working_districts', 'marital_status', 'diet', 'family_status'] as $c) {
            $t->json($c)->nullable();
        }
        foreach (['age_from', 'age_to', 'height_from_cm', 'height_to_cm'] as $c) {
            $t->integer($c)->nullable();
        }
        $t->timestamps();
    });
    Schema::create('blocked_profiles', fn (Blueprint $t) => [$t->id(), $t->unsignedBigInteger('profile_id'), $t->unsignedBigInteger('blocked_profile_id'), $t->timestamps()]);
    Schema::create('ignored_profiles', fn (Blueprint $t) => [$t->id(), $t->unsignedBigInteger('profile_id'), $t->unsignedBigInteger('ignored_profile_id'), $t->timestamps()]);
    Schema::create('interests', fn (Blueprint $t) => [$t->id(), $t->unsignedBigInteger('sender_profile_id'), $t->unsignedBigInteger('receiver_profile_id'), $t->string('status')->default('pending'), $t->timestamps()]);
    Schema::create('daily_interest_usages', fn (Blueprint $t) => [$t->id(), $t->unsignedBigInteger('profile_id'), $t->date('usage_date')->nullable(), $t->integer('count')->default(0), $t->timestamps()]);
    Cache::flush();
    DB::table('membership_plans')->insert(['id' => 1, 'can_view_contact' => true]);
});

afterEach(function () {
    foreach (VIS_TABLES as $table) {
        Schema::dropIfExists($table);
    }
});

/** A member: profile columns, religion details, partner preferences, premium or not. */
function visMember(string $matri, string $gender, array $profile = [], array $religion = [], ?array $prefs = null, bool $premium = false): Profile
{
    static $n = 0;
    $n++;
    $userId = DB::table('users')->insertGetId(['name' => $matri, 'email' => "{$matri}@site.test"]);
    $id = DB::table('profiles')->insertGetId(array_merge(['user_id' => $userId, 'matri_id' => $matri, 'gender' => $gender, 'date_of_birth' => '1996-01-01'], $profile));
    if ($religion) {
        DB::table('religious_info')->insert(array_merge(['profile_id' => $id], $religion));
    }
    if ($prefs !== null) {
        DB::table('partner_preferences')->insert(array_merge(['profile_id' => $id], array_map(fn ($v) => is_array($v) ? json_encode($v) : $v, $prefs)));
    }
    if ($premium) {
        DB::table('user_memberships')->insert(['user_id' => $userId, 'plan_id' => 1, 'is_active' => true]);
    }

    return Profile::find($id);
}

function visVisibleIds(Profile $viewer): array
{
    return ProfileVisibility::forMember(Profile::query(), $viewer)->orderBy('id')->pluck('matri_id')->all();
}

function visCheckIds(Profile $viewer, iterable $targets): array
{
    $access = app(ProfileAccessService::class);

    return collect($targets)->filter(fn (Profile $t) => $access->canAccess($viewer, $t->fresh()))->sortBy('id')->pluck('matri_id')->values()->all();
}

/** The women every viewer below is tested against. */
function visTargets(): array
{
    return [
        visMember('OPEN', 'female', [], ['religion' => 'Christian', 'denomination' => 'Roman Catholic']),
        visMember('PREMIUM', 'female', ['show_profile_to' => 'premium']),
        // "Only to those who match my Partner Preferences": Hindu, Tulu-speaking men
        visMember('MATCHES', 'female', ['show_profile_to' => 'matches'], ['religion' => 'Hindu', 'caste' => 'Billava'], ['religions' => ['Hindu'], 'mother_tongues' => ['Tulu']]),
        visMember('MATCHES_NOPREFS', 'female', ['show_profile_to' => 'matches']),
        visMember('SAME_RELIGION', 'female', ['only_same_religion' => true], ['religion' => 'Christian', 'denomination' => 'CSI']),
        visMember('SAME_DENOM', 'female', ['only_same_denomination' => true], ['religion' => 'Christian', 'denomination' => 'Roman Catholic']),
        visMember('SAME_CASTE', 'female', ['only_same_denomination' => true], ['religion' => 'Hindu', 'caste' => 'Billava']),
        visMember('SAME_SECT', 'female', ['only_same_denomination' => true], ['religion' => 'Muslim', 'muslim_sect' => 'Sunni']),
        visMember('SAME_TONGUE', 'female', ['only_same_mother_tongue' => true, 'mother_tongue' => 'Tulu']),
        visMember('HIDDEN', 'female', ['is_hidden' => true]),
        visMember('SUSPENDED', 'female', ['suspension_status' => 'suspended']),
        visMember('INACTIVE', 'female', ['is_active' => false]),
        visMember('UNAPPROVED', 'female', ['is_approved' => false]),
        visMember('MAN', 'male'),
    ];
}

it('shows visitors only members who set no limit, never hidden, suspended, inactive or unapproved ones', function () {
    visTargets();

    expect(ProfileVisibility::forVisitors(Profile::query())->orderBy('id')->pluck('matri_id')->all())->toBe(['OPEN', 'MAN']);
});

it('lists for each member exactly the profiles the single-profile check opens, with members on both sides of every limit', function () {
    $targets = visTargets();
    $viewers = [
        // Catholic, Konkani, premium: meets "premium", "same religion"? no (target is CSI → same religion only needs Christian: yes), "same denomination" RC
        'catholic' => visMember('V_CATHOLIC', 'male', ['mother_tongue' => 'Konkani'], ['religion' => 'Christian', 'denomination' => 'Roman Catholic'], null, true),
        // Hindu Billava, Tulu, free: meets "matches", "same caste", "same mother tongue"
        'billava' => visMember('V_BILLAVA', 'male', ['mother_tongue' => 'Tulu'], ['religion' => 'Hindu', 'caste' => 'Billava']),
        // Muslim Sunni, free
        'sunni' => visMember('V_SUNNI', 'male', ['mother_tongue' => 'Beary'], ['religion' => 'Muslim', 'muslim_sect' => 'Sunni']),
        // nothing filled in: an empty value is never "the same"
        'empty' => visMember('V_EMPTY', 'male'),
    ];
    $expected = [
        'catholic' => ['OPEN', 'PREMIUM', 'SAME_RELIGION', 'SAME_DENOM'],
        'billava' => ['OPEN', 'MATCHES', 'SAME_CASTE', 'SAME_TONGUE'],
        'sunni' => ['OPEN', 'SAME_SECT'],
        'empty' => ['OPEN'],
    ];

    foreach ($viewers as $key => $viewer) {
        expect(visVisibleIds($viewer))->toBe($expected[$key], "list for {$key}")
            ->and(visCheckIds($viewer, $targets))->toBe($expected[$key], "profile check for {$key}");
    }
});

it('checks "matches only" against HER preferences, not the viewer\'s', function () {
    // she wants a Hindu, Tulu-speaking man; he is a Catholic who would happily take her
    $her = visMember('HER', 'female', ['show_profile_to' => 'matches', 'mother_tongue' => 'Tulu'], ['religion' => 'Hindu'], ['religions' => ['Hindu'], 'mother_tongues' => ['Tulu']]);
    $him = visMember('HIM', 'male', ['mother_tongue' => 'Konkani'], ['religion' => 'Christian'], ['religions' => ['Hindu'], 'mother_tongues' => ['Tulu']]);

    expect(app(ProfileAccessService::class)->check($him, $her))->toBe(ProfileAccessService::REASON_VISIBILITY_MATCHES)
        ->and(visVisibleIds($him))->not->toContain('HER');

    // every preference "Any": everyone matches
    $anyone = visMember('ANYONE', 'female', ['show_profile_to' => 'matches'], [], []);
    expect(app(ProfileAccessService::class)->canAccess($him, $anyone))->toBeTrue();
});

it('lets an interest either way open hidden and limited profiles, but never a blocked, suspended or deactivated one', function () {
    $him = visMember('HIM', 'male');
    $hidden = visMember('HIDDEN', 'female', ['is_hidden' => true]);
    $sameTongue = visMember('TONGUE', 'female', ['only_same_mother_tongue' => true, 'mother_tongue' => 'Tulu']);
    $blocker = visMember('BLOCKER', 'female');
    $inactive = visMember('INACTIVE', 'female', ['is_active' => false]);
    $third = visMember('THIRD', 'male');
    $access = app(ProfileAccessService::class);

    // a third member's interests open nothing for him
    DB::table('interests')->insert([
        ['sender_profile_id' => $hidden->id, 'receiver_profile_id' => $third->id, 'status' => 'pending'],
        ['sender_profile_id' => $sameTongue->id, 'receiver_profile_id' => $third->id, 'status' => 'accepted'],
    ]);
    expect($access->check($him, $hidden))->toBe(ProfileAccessService::REASON_HIDDEN)
        ->and($access->check($him, $sameTongue))->toBe(ProfileAccessService::REASON_VISIBILITY_COMMUNITY);

    // she wrote to him: he may see her
    DB::table('interests')->insert([
        ['sender_profile_id' => $hidden->id, 'receiver_profile_id' => $him->id, 'status' => 'pending'],
        ['sender_profile_id' => $sameTongue->id, 'receiver_profile_id' => $him->id, 'status' => 'pending'],
        ['sender_profile_id' => $blocker->id, 'receiver_profile_id' => $him->id, 'status' => 'accepted'],
        ['sender_profile_id' => $inactive->id, 'receiver_profile_id' => $him->id, 'status' => 'accepted'],
    ]);
    DB::table('blocked_profiles')->insert(['profile_id' => $blocker->id, 'blocked_profile_id' => $him->id]);

    expect($access->canAccess($him, $hidden->fresh()))->toBeTrue()
        ->and($access->canAccess($him, $sameTongue->fresh()))->toBeTrue()
        ->and($access->check($him, $blocker))->toBe(ProfileAccessService::REASON_BLOCKED)
        ->and($access->check($him, $inactive))->toBe(ProfileAccessService::REASON_UNAVAILABLE);
});

it('refuses an interest to a profile the sender may not see, so an interest can\'t be used to open it', function () {
    $him = visMember('HIM', 'male');
    $hidden = visMember('HIDDEN', 'female', ['is_hidden' => true]);

    expect(fn () => app(InterestService::class)->send($him, $hidden, null, null))
        ->toThrow(RuntimeException::class, 'Cannot send interest to this profile.');
    expect(DB::table('interests')->count())->toBe(0);
});

it('opens another member\'s profile page and print view only through the central check, without saying why', function () {
    $controller = file_get_contents(app_path('Http/Controllers/ProfileController.php'));

    expect(substr_count($controller, '$refused = $this->refusedProfilePage($profile)'))->toBe(2)
        ->and($controller)->toContain("default => response()->view('profile.unavailable', [], 404)")
        // what the page shows (Blade comments aside) never mentions a block
        ->and(preg_replace('/\{\{--.*?--\}\}/s', '', file_get_contents(resource_path('views/profile/unavailable.blade.php'))))
        ->toContain('This profile is not available')
        ->not->toContain('block');
});

it('builds every visitor list and every member list from the one rule', function () {
    $uses = fn (string $file, string $call) => str_contains(file_get_contents(app_path($file)), $call);

    foreach (['Http/Controllers/DiscoverController.php', 'Http/Controllers/HomeController.php', 'Http/Controllers/SearchController.php', 'Http/Controllers/Api/V1/DiscoverController.php', 'Support/NriWording.php'] as $file) {
        expect($uses($file, 'ProfileVisibility::forVisitors('))->toBeTrue($file);
    }
    foreach (['Traits/ProfileQueryFilters.php', 'Services/DashboardService.php', 'Http/Controllers/ProfileViewController.php', 'Http/Controllers/ShortlistController.php', 'Http/Controllers/DashboardController.php', 'Services/ProfileViewsDigest.php', 'Http/Controllers/Api/V1/DiscoverController.php'] as $file) {
        expect($uses($file, 'ProfileVisibility::forMember('))->toBeTrue($file);
    }
    // adding needs access; removing / blocking always works
    foreach (['Http/Controllers/ShortlistController.php', 'Http/Controllers/Api/V1/ShortlistController.php', 'Http/Controllers/PhotoRequestController.php', 'Services/InterestService.php'] as $file) {
        expect($uses($file, 'canAccess('))->toBeTrue($file);
    }
});

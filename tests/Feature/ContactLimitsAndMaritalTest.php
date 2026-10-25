<?php

use App\Models\Profile;
use App\Models\User;
use App\Services\ContactViewQuota;
use App\Support\ContactAccess;
use App\Support\MaritalStatus;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
| Audit fixes (25 Oct 2026, owner OK'd):
| 1. The plans advertise contact-view limits (Silver 10 a day / 50 in total,
|    Gold 25 / 200) that were never applied. ContactViewQuota counts the first
|    time a member's number opens to the viewer and stops at the plan's limit.
| 2. "Divorced" vs "Divorcee", "Widower" vs "Widow/Widower" and bulk import's
|    "never_married" are the same status: search and match scoring treat them
|    as one (31 Kudla partner preferences matched nobody).
*/

beforeEach(function () {
    Schema::create('contact_views', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('viewer_profile_id');
        $t->unsignedBigInteger('target_profile_id');
        $t->timestamp('created_at')->nullable();
        $t->unique(['viewer_profile_id', 'target_profile_id']);
    });
    Schema::create('membership_plans', function (Blueprint $t) {
        $t->id();
        $t->string('plan_name')->nullable();
        $t->integer('view_contacts_limit')->default(0);
        $t->integer('daily_contact_views')->default(0);
        $t->timestamps();
    });
    Schema::create('user_memberships', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('user_id');
        $t->unsignedBigInteger('plan_id');
        $t->boolean('is_active')->default(true);
        $t->timestamp('starts_at')->nullable();
        $t->timestamp('ends_at')->nullable();
        $t->timestamps();
    });
});

afterEach(function () {
    foreach (['contact_views', 'membership_plans', 'user_memberships'] as $table) {
        Schema::dropIfExists($table);
    }
});

function quotaMember(int $id, ?array $plan = null): Profile
{
    $user = new User();
    $user->exists = true;
    $user->forceFill(['id' => $id]);
    if ($plan) {
        $planId = DB::table('membership_plans')->insertGetId($plan);
        DB::table('user_memberships')->insert(['user_id' => $id, 'plan_id' => $planId, 'is_active' => true, 'starts_at' => now()->subDays(10), 'ends_at' => now()->addMonths(2)]);
    }
    $profile = new Profile();
    $profile->forceFill(['id' => $id]);
    $profile->setRelation('user', $user);

    return $profile;
}

it('applies the plan\'s daily and total contact limits, never counting the same member twice', function () {
    $quota = new ContactViewQuota();
    $me = quotaMember(1, ['plan_name' => 'Silver', 'daily_contact_views' => 2, 'view_contacts_limit' => 3]);
    $other = fn (int $id) => quotaMember($id);

    expect($quota->allow($me, $other(11)))->toBeTrue()
        ->and($quota->allow($me, $other(12)))->toBeTrue()
        ->and($quota->allow($me, $other(13)))->toBeFalse()              // 2 a day
        ->and($quota->allow($me, $other(11)))->toBeTrue()               // already opened: free
        ->and($quota->reason($me))->toBe("You've opened 2 contacts today, the daily limit on your Silver plan. More open tomorrow, or upgrade for more.");

    $this->travel(1)->days();
    expect($quota->allow($me, $other(13)))->toBeTrue()                  // a new day
        ->and($quota->allow($me, $other(14)))->toBeFalse()              // 3 in total
        ->and($quota->reason($me))->toContain('all 3 included in your Silver plan');
});

it('has no limit without a plan, or on a plan with 0 (unlimited)', function () {
    $quota = new ContactViewQuota();
    $free = quotaMember(2);
    $diamond = quotaMember(3, ['plan_name' => 'Diamond', 'daily_contact_views' => 0, 'view_contacts_limit' => 0]);

    foreach (range(20, 60) as $id) {
        expect($quota->allow($free, quotaMember($id)))->toBeTrue()
            ->and($quota->allow($diamond, quotaMember($id + 100)))->toBeTrue();
    }
    expect($quota->reason($diamond))->toBeNull();
});

it('lets a contact show when counting fails (never hides a paid contact by mistake)', function () {
    Schema::dropIfExists('contact_views');
    expect((new ContactViewQuota())->allow(quotaMember(4, ['plan_name' => 'Silver', 'daily_contact_views' => 1]), quotaMember(40)))->toBeTrue();
});

it('shows the limit on every contact screen, and a list never uses up views', function () {
    expect(file_get_contents(app_path('Support/ContactAccess.php')))->toContain("public const LIMIT_REACHED = 'limit_reached';")
        ->and(file_get_contents(resource_path('views/chats/index.blade.php')))->toContain('ContactAccess::for($profile, $other, countView: false)')
        ->and(file_get_contents(resource_path('views/profile/preview/contact.blade.php')))->toContain('@case($ca::LIMIT_REACHED)')
        ->and(file_get_contents(resource_path('views/interests/show.blade.php')))->toContain('ContactAccess::LIMIT_REACHED')
        ->and(file_get_contents(app_path('Http/Controllers/Api/V1/ProfileController.php')))->toContain("'contact_limit_reached' => \$contactLimitReached");
    expect(ContactAccess::lockedReason(ContactAccess::LIMIT_REACHED))->toContain('Upgrade');
});

it('treats spellings of the same marital status as one', function () {
    expect(MaritalStatus::expand(['Widower']))->toContain('Widow/Widower')
        ->and(MaritalStatus::expand(['Divorced', 'Any']))->toContain('Divorcee')->not->toContain('Any')
        ->and(MaritalStatus::expand(['never_married']))->toContain('Unmarried')
        ->and(MaritalStatus::same('Divorcee', 'divorced'))->toBeTrue()
        ->and(MaritalStatus::same('Divorcee', 'Separated'))->toBeFalse();

    config(['reference_data.marital_status_list' => ['Unmarried', 'Divorcee', 'Widow/Widower']]);
    expect(MaritalStatus::toSiteValue('divorced'))->toBe('Divorcee')
        ->and(MaritalStatus::toSiteValue('widowed'))->toBe('Widow/Widower')
        ->and(MaritalStatus::toSiteValue('never_married'))->toBe('Unmarried');

    expect(file_get_contents(app_path('Services/MatchingService.php')))->toContain('MaritalStatus::expand($prefs->marital_status ?? [])')
        ->and(substr_count(file_get_contents(app_path('Http/Controllers/SearchController.php')), 'MaritalStatus::expand('))->toBe(2)
        ->and(file_get_contents(app_path('Http/Controllers/Api/V1/SearchController.php')))->toContain('MaritalStatus::expand(')
        ->and(file_get_contents(app_path('Services/MemberCreationService.php')))->toContain('MaritalStatus::toSiteValue(')->not->toContain("?? 'never_married'");
});

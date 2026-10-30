<?php

use App\Support\MemberMix;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
| Insights (Oct 2026): men and women, age groups, where members live, the
| 12-week trend and sign-up sources by month.
*/

const MIX_TABLES = ['users', 'profiles', 'location_info', 'interests', 'interest_replies'];

beforeEach(function () {
    foreach (MIX_TABLES as $t) {
        Schema::dropIfExists($t);
    }
    Schema::create('users', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('staff_role_id')->nullable();
        $t->timestamp('last_login_at')->nullable();
        $t->timestamps();
    });
    Schema::create('profiles', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('user_id');
        $t->string('full_name')->nullable();
        $t->string('gender')->nullable();
        $t->date('date_of_birth')->nullable();
        $t->boolean('is_active')->default(true);
        $t->string('how_did_you_hear_about_us')->nullable();
        $t->unsignedBigInteger('created_by_staff_id')->nullable();
        $t->softDeletes();
        $t->timestamps();
    });
    Schema::create('location_info', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('profile_id');
        $t->string('residing_country')->nullable();
        $t->string('native_district')->nullable();
        $t->timestamps();
    });
    Schema::create('interests', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('sender_profile_id');
        $t->unsignedBigInteger('receiver_profile_id');
        $t->string('status')->default('pending');
        $t->timestamps();
    });
    Schema::create('interest_replies', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('interest_id');
        $t->string('reply_type');
        $t->timestamps();
    });
    Carbon::setTestNow('2026-10-30 12:00:00');
});

afterEach(function () {
    Carbon::setTestNow();
    foreach (MIX_TABLES as $t) {
        Schema::dropIfExists($t);
    }
});

function mixMember(int $id, string $gender, array $profile = [], array $user = []): void
{
    DB::table('users')->insert(array_merge(['id' => $id, 'last_login_at' => now()->subDays(2)], $user));
    DB::table('profiles')->insert(array_merge(['id' => $id, 'user_id' => $id, 'full_name' => "M{$id}", 'gender' => $gender, 'date_of_birth' => '1995-01-01', 'created_at' => now()->subDays(40)], $profile));
}

it('counts men and women, live and active, leaving out staff, deleted and switched-off profiles', function () {
    mixMember(1, 'male');
    mixMember(2, 'male', [], ['last_login_at' => now()->subDays(60)]);   // live, not active
    mixMember(3, 'female');
    mixMember(4, 'female', ['deleted_at' => now()]);
    mixMember(5, 'female', ['is_active' => false]);
    mixMember(6, 'male', [], ['staff_role_id' => 5]);

    expect(MemberMix::genderBalance())->toBe([
        'live' => ['male' => 2, 'female' => 1],
        'active' => ['male' => 1, 'female' => 1],
    ]);
});

it('puts members in age groups by gender', function () {
    mixMember(1, 'male', ['date_of_birth' => '2003-05-01']);   // 23
    mixMember(2, 'male', ['date_of_birth' => '1990-05-01']);   // 36
    mixMember(3, 'female', ['date_of_birth' => '1998-12-01']); // 27
    mixMember(4, 'female', ['date_of_birth' => '1980-01-01'], ['last_login_at' => now()->subDays(90)]); // 46, not active

    $all = MemberMix::ageGroups();
    $active = MemberMix::ageGroups(activeOnly: true);

    expect($all['18–24'])->toBe(['male' => 1, 'female' => 0])
        ->and($all['25–29'])->toBe(['male' => 0, 'female' => 1])
        ->and($all['35–39'])->toBe(['male' => 1, 'female' => 0])
        ->and($all['40+'])->toBe(['male' => 0, 'female' => 1])
        ->and($active['40+'])->toBe(['male' => 0, 'female' => 0]);
});

it('lists the countries members live in, biggest first, "Not given" last', function () {
    foreach ([1 => 'India', 2 => 'India', 3 => 'UAE', 4 => '', 5 => 'Qatar', 6 => 'Oman'] as $id => $country) {
        mixMember($id, 'male');
        DB::table('location_info')->insert(['profile_id' => $id, 'residing_country' => $country, 'native_district' => $id < 4 ? 'Udupi' : null]);
    }

    expect(MemberMix::countries(top: 2))->toBe(['India' => 2, 'Oman' => 1, 'Other countries' => 2, 'Not given' => 1])
        ->and(MemberMix::homeDistricts())->toBe(['Udupi' => 3, 'Not given' => 3]);
});

it('compares interests sent by men and by women', function () {
    mixMember(1, 'male');
    mixMember(2, 'male');
    mixMember(3, 'female');
    DB::table('interests')->insert([
        ['sender_profile_id' => 1, 'receiver_profile_id' => 3, 'status' => 'declined', 'created_at' => now()->subDays(3)],
        ['sender_profile_id' => 2, 'receiver_profile_id' => 3, 'status' => 'pending', 'created_at' => now()->subDays(3)],
        ['sender_profile_id' => 1, 'receiver_profile_id' => 3, 'status' => 'cancelled', 'created_at' => now()->subDays(3)],
        ['sender_profile_id' => 3, 'receiver_profile_id' => 2, 'status' => 'accepted', 'created_at' => now()->subDays(3)],
    ]);

    $g = MemberMix::interestsByGender(90);

    expect($g['male'])->toMatchArray(['sent' => 2, 'answered_pct' => 50, 'accepted_pct' => 0])
        ->and($g['female'])->toMatchArray(['sent' => 1, 'answered_pct' => 100, 'accepted_pct' => 100])
        ->and($g['female']['received_per_active'])->toBe(2.0)    // 2 interests, 1 active woman
        ->and($g['male']['received_per_active'])->toBe(0.5);     // 1 interest, 2 active men
});

it('gives 12 weekly points of sign-ups, interests and connections', function () {
    mixMember(1, 'male', ['created_at' => now()->subDays(2)]);
    mixMember(2, 'female', ['created_at' => now()->subDays(10)]);
    DB::table('interests')->insert(['id' => 1, 'sender_profile_id' => 1, 'receiver_profile_id' => 2, 'status' => 'accepted', 'created_at' => now()->subDays(1)]);
    DB::table('interest_replies')->insert(['interest_id' => 1, 'reply_type' => 'accept', 'created_at' => now()->subHours(5)]);

    $t = MemberMix::weeklyTrend(12);

    expect($t['labels'])->toHaveCount(12)
        ->and(array_slice($t['signups'], -2))->toBe([1, 1])
        ->and(end($t['interests']))->toBe(1)
        ->and(end($t['connections']))->toBe(1)
        ->and(array_sum($t['signups']))->toBe(2);
});

it('splits sign-ups by source per month, the biggest sources by name and the rest as other answers', function () {
    $sources = ['Google Search', 'Google Search', 'Google Search', 'Instagram', 'Instagram', 'Friends', 'TV', '', null];
    foreach ($sources as $i => $source) {
        mixMember($i + 1, 'male', ['how_did_you_hear_about_us' => $source, 'created_at' => $i < 5 ? '2026-10-10 10:00:00' : '2026-09-10 10:00:00']);
    }
    mixMember(20, 'male', ['how_did_you_hear_about_us' => 'Google Search', 'created_by_staff_id' => 3, 'created_at' => '2026-10-10 10:00:00']);

    $m = MemberMix::sourcesByMonth(months: 2, top: 2);

    expect($m['labels'])->toBe(['Sep 2026', 'Oct 2026'])
        ->and(array_keys($m['series']))->toBe(['Google Search', 'Instagram', 'Other answers'])
        ->and($m['series']['Google Search'])->toBe([0, 3])      // the staff-added one left out
        ->and($m['series']['Instagram'])->toBe([0, 2])
        ->and($m['series']['Other answers'])->toBe([2, 0]);     // Friends + TV in September
});

it('builds every new Insights chart from the same numbers, in the shared colours', function () {
    mixMember(1, 'male', ['how_did_you_hear_about_us' => 'Google Search', 'created_at' => now()->subDays(3)]);
    mixMember(2, 'female', ['how_did_you_hear_about_us' => 'Instagram', 'created_at' => now()->subDays(3)]);
    DB::table('location_info')->insert(['profile_id' => 1, 'residing_country' => 'UAE', 'native_district' => 'Udupi']);

    $data = fn (object $widget) => (fn () => $this->getData())->call($widget);

    $gender = $data(new \App\Filament\Widgets\GenderBalanceChart);
    expect($gender['labels'])->toBe(['Live profiles', 'Active, last 30 days'])
        ->and($gender['datasets'][0])->toMatchArray(['label' => 'Men', 'data' => [1, 1], 'backgroundColor' => \App\Support\ChartColors::MEN])
        ->and($gender['datasets'][1])->toMatchArray(['label' => 'Women', 'data' => [1, 1], 'backgroundColor' => \App\Support\ChartColors::WOMEN]);

    $age = new \App\Filament\Widgets\AgeGroupsChart;
    $age->filter = 'live';
    expect($data($age)['labels'])->toBe(MemberMix::AGE_BANDS);

    $where = new \App\Filament\Widgets\WhereMembersLiveChart;
    expect($data($where)['labels'])->toBe(['UAE', 'Not given']);
    $where->filter = 'district';
    expect($data($where)['labels'])->toBe(['Udupi', 'Not given']);

    $trend = new \App\Filament\Widgets\WeeklyTrendChart;
    $trend->metric = 'signups';
    expect($data($trend)['datasets'][0]['data'])->toHaveCount(12)->and(array_sum($data($trend)['datasets'][0]['data']))->toBe(2)
        ->and($trend->getHeading())->toBe('Sign-ups per week');

    $sources = $data(new \App\Filament\Widgets\SourcesByMonthChart);
    expect(array_column($sources['datasets'], 'label'))->toBe(['Google Search', 'Instagram'])
        ->and(array_column($sources['datasets'], 'backgroundColor'))->toBe(array_slice(\App\Support\ChartColors::SERIES, 0, 2));
});

it('lets Staff open Insights but keeps the member CSV downloads for the owner', function () {
    Schema::dropIfExists('staff_roles');
    Schema::dropIfExists('staff_role_permissions');
    Schema::create('staff_roles', function (Blueprint $t) {
        $t->id();
        $t->string('name');
        $t->string('slug')->unique();
        $t->boolean('is_active')->default(true);
        $t->integer('sort_order')->default(0);
        $t->timestamps();
    });
    Schema::create('staff_role_permissions', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('staff_role_id');
        $t->string('permission_key', 60);
        $t->string('scope', 10)->default('no');
        $t->timestamps();
    });
    DB::table('staff_roles')->insert([['id' => 1, 'name' => 'Super Admin', 'slug' => 'super_admin'], ['id' => 5, 'name' => 'Staff', 'slug' => 'staff']]);
    DB::table('staff_role_permissions')->insert([
        ['staff_role_id' => 5, 'permission_key' => 'view_user_reports', 'scope' => 'yes'],
        ['staff_role_id' => 5, 'permission_key' => 'view_engagement_reports', 'scope' => 'yes'],
    ]);
    DB::table('users')->insert([['id' => 50, 'staff_role_id' => 1], ['id' => 51, 'staff_role_id' => 5]]);
    $can = fn () => [
        \App\Filament\Pages\Scorecard::canAccess(),
        \App\Filament\Pages\MembersOverview::canAccess(),
        \App\Filament\Pages\SignupSources::canAccess(),
        \App\Support\Permissions::can('export_member_data'),
    ];

    $this->actingAs(\App\Models\User::find(51), 'admin');
    expect($can())->toBe([true, true, true, false]);    // staff: Insights yes, CSV no

    $this->actingAs(\App\Models\User::find(50), 'admin');
    expect($can())->toBe([true, true, true, true]);     // owner: everything

    Schema::dropIfExists('staff_role_permissions');
    Schema::dropIfExists('staff_roles');
});

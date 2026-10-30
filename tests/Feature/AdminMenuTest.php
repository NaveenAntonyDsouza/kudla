<?php

use App\Models\SiteSetting;
use App\Support\WorkQueue;
use Filament\Facades\Filament;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
| Admin audit phase 2 (Oct 2026): the menu in seven groups, Today first, and
| a work queue of everything waiting for a person.
*/

it('puts every menu page in one of the seven groups', function () {
    $panel = Filament::getPanel('admin');
    $groups = collect($panel->getNavigationGroups())->map(fn ($g) => is_string($g) ? $g : $g->getLabel())->values()->all();
    expect($groups)->toBe(['Today', 'Members', 'Engagement', 'Money', 'Site & Content', 'Insights', 'Admin & Security']);

    $classes = array_merge($panel->getResources(), $panel->getPages());
    foreach ($classes as $class) {
        $group = (new ReflectionClass($class))->getStaticPropertyValue('navigationGroup', null);
        if ($group === null) {
            continue; // not in the menu (e.g. a profile page)
        }
        expect($group)->toBeIn($groups, "{$class} is in \"{$group}\"");
    }
});

it('opens on Today, with the members charts under Insights', function () {
    expect(\App\Filament\Pages\Dashboard::getNavigationLabel())->toBe('Work Queue')
        ->and((new \App\Filament\Pages\Dashboard)->getTitle())->toBe('Today')
        ->and((new \App\Filament\Pages\Dashboard)->getWidgets())->toContain(\App\Filament\Widgets\TodayQueue::class)
        ->and((new \App\Filament\Pages\MembersOverview)->getWidgets())->toContain(\App\Filament\Widgets\MembersStats::class, \App\Filament\Widgets\GenderBalanceChart::class);
});

describe('work queue', function () {
    beforeEach(function () {
        foreach (['users', 'profiles', 'contact_submissions', 'site_settings'] as $t) {
            Schema::dropIfExists($t);
        }
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name')->nullable();
            $t->string('role')->nullable();
            $t->unsignedBigInteger('staff_role_id')->nullable();
            $t->timestamp('email_undeliverable_at')->nullable();
            $t->timestamps();
        });
        Schema::create('profiles', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->unsignedBigInteger('branch_id')->nullable();
            $t->string('full_name')->nullable();
            $t->string('matri_id')->nullable();
            $t->boolean('is_approved')->default(true);
            $t->softDeletes();
            $t->timestamps();
        });
        Schema::create('contact_submissions', function (Blueprint $t) {
            $t->id();
            $t->string('status')->default('new');
            $t->timestamps();
        });
        Schema::create('site_settings', function (Blueprint $t) {
            $t->id();
            $t->string('key')->unique();
            $t->text('value')->nullable();
            $t->timestamps();
        });
        Cache::flush();
        DB::table('users')->insert(['id' => 1, 'name' => 'Owner', 'role' => 'admin']);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(\App\Models\User::find(1), 'admin');
    });

    afterEach(function () {
        foreach (['users', 'profiles', 'contact_submissions', 'site_settings'] as $t) {
            Schema::dropIfExists($t);
        }
    });

    it('counts what is waiting, shows how long the oldest has waited and puts overdue work first', function () {
        DB::table('profiles')->insert([
            ['user_id' => 1, 'full_name' => 'A', 'is_approved' => false, 'created_at' => now()->subHours(3)],
            ['user_id' => 1, 'full_name' => 'B', 'is_approved' => true, 'created_at' => now()->subDays(9)],
        ]);
        DB::table('contact_submissions')->insert([
            ['status' => 'new', 'created_at' => now()->subDays(3)],
            ['status' => 'new', 'created_at' => now()->subHour()],
            ['status' => 'replied', 'created_at' => now()->subDays(20)],
        ]);

        $items = collect(WorkQueue::items())->keyBy('key');

        expect($items['contact']['count'])->toBe(2)
            ->and($items['contact']['overdue'])->toBeTrue()               // 3 days against a 1-day target
            ->and($items['profiles']['count'])->toBe(1)
            ->and($items['profiles']['overdue'])->toBeFalse()             // 3 hours
            ->and(WorkQueue::items()[0]['key'])->toBe('contact')          // overdue first
            ->and($items['contact']['url'])->toContain('activeTab=new');
        // lines whose tables this site doesn't have are simply left out
        expect($items->has('photos'))->toBeFalse();
    });
});

describe('scorecard', function () {
    beforeEach(function () {
        foreach (['users', 'profiles', 'interests', 'interest_replies', 'site_settings'] as $t) {
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
            $t->boolean('is_active')->default(true);
            $t->boolean('is_approved')->default(true);
            $t->boolean('onboarding_completed')->default(false);
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
        Schema::create('interest_replies', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('interest_id');
            $t->string('reply_type');
            $t->timestamps();
        });
        Schema::create('site_settings', function (Blueprint $t) {
            $t->id();
            $t->string('key')->unique();
            $t->text('value')->nullable();
            $t->timestamps();
        });
        Cache::flush();
    });

    afterEach(function () {
        foreach (['users', 'profiles', 'interests', 'interest_replies', 'site_settings'] as $t) {
            Schema::dropIfExists($t);
        }
    });

    it('compares this week with last week', function () {
        DB::table('users')->insert([['id' => 1, 'last_login_at' => now()->subDay()], ['id' => 2, 'last_login_at' => now()->subDays(20)], ['id' => 3, 'last_login_at' => null]]);
        DB::table('profiles')->insert([
            ['id' => 1, 'user_id' => 1, 'full_name' => 'A', 'onboarding_completed' => true, 'created_at' => now()->subDays(2)],
            ['id' => 2, 'user_id' => 2, 'full_name' => 'B', 'onboarding_completed' => false, 'created_at' => now()->subDays(3)],
            ['id' => 3, 'user_id' => 3, 'full_name' => 'C', 'onboarding_completed' => true, 'created_at' => now()->subDays(10)],
        ]);
        DB::table('interests')->insert([
            ['sender_profile_id' => 1, 'receiver_profile_id' => 2, 'status' => 'accepted', 'created_at' => now()->subDays(1)],
            ['sender_profile_id' => 1, 'receiver_profile_id' => 3, 'status' => 'declined', 'created_at' => now()->subDays(2)],
            ['sender_profile_id' => 2, 'receiver_profile_id' => 3, 'status' => 'pending', 'created_at' => now()->subDays(2)],
            ['sender_profile_id' => 3, 'receiver_profile_id' => 1, 'status' => 'cancelled', 'created_at' => now()->subDays(2)],
            ['sender_profile_id' => 3, 'receiver_profile_id' => 2, 'status' => 'pending', 'created_at' => now()->subDays(9)],
        ]);
        DB::table('interest_replies')->insert(['interest_id' => 1, 'reply_type' => 'accept', 'created_at' => now()->subHours(5)]);

        $rows = collect(\App\Support\Scorecard::rows())->keyBy('key');

        expect([$rows['signups']['this'], $rows['signups']['last']])->toBe([2, 1])
            ->and($rows['finished']['this'])->toBe(50)
            ->and([$rows['interests']['this'], $rows['interests']['last']])->toBe([3, 1])   // the withdrawn one doesn't count
            ->and($rows['answered']['this'])->toBe(67)                                       // 2 of 3
            ->and($rows['accepted']['this'])->toBe(50)                                       // 1 of 2
            ->and($rows['connections']['this'])->toBe(1)
            ->and([$rows['active7']['this'], $rows['active30']['this'], $rows['active30']['last']])->toBe([1, 2, null]);
        // money and backlog rows need tables this test doesn't have: left out, no error
        expect($rows->has('revenue'))->toBeFalse();
    });
});

describe('document approvals label', function () {
    beforeEach(function () {
        foreach (['profiles', 'religious_info', 'communities'] as $t) {
            Schema::dropIfExists($t);
        }
        Schema::create('profiles', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id')->default(1);
            $t->softDeletes();
            $t->timestamps();
        });
        Schema::create('religious_info', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('profile_id');
            $t->string('religion')->nullable();
            $t->timestamps();
        });
        Schema::create('communities', function (Blueprint $t) {
            $t->id();
            $t->string('religion')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Cache::flush();
    });

    afterEach(function () {
        foreach (['profiles', 'religious_info', 'communities'] as $t) {
            Schema::dropIfExists($t);
        }
    });

    $label = fn () => \App\Filament\Resources\DocumentApprovalResource::getNavigationLabel();

    it('names the menu after the live members\' religions', function () use ($label) {
        DB::table('profiles')->insert([['id' => 1, 'deleted_at' => null], ['id' => 2, 'deleted_at' => now()]]);
        DB::table('religious_info')->insert([['profile_id' => 1, 'religion' => 'Christian'], ['profile_id' => 2, 'religion' => 'Muslim']]);
        expect($label())->toBe('Baptism Certificates');   // the Muslim profile was deleted

        Cache::flush();
        DB::table('profiles')->insert(['id' => 3]);
        DB::table('religious_info')->insert(['profile_id' => 3, 'religion' => 'Hindu']);
        expect($label())->toBe('Horoscope / Baptism');

        Cache::flush();
        DB::table('religious_info')->where('religion', 'Christian')->delete();
        expect($label())->toBe('Horoscopes');
    });

    it('goes by the community list on a site with no members yet', function () use ($label) {
        DB::table('communities')->insert([['religion' => 'Christian'], ['religion' => 'Christian']]);
        expect($label())->toBe('Baptism Certificates');
    });
});

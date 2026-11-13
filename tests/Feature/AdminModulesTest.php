<?php

use App\Filament\Pages\StaffPerformanceReport;
use App\Filament\Resources\BranchPayoutResource;
use App\Filament\Resources\BranchResource;
use App\Filament\Resources\LeadResource;
use App\Filament\Resources\StaffTargetResource;
use App\Filament\Widgets\MyStatsOverview;
use App\Filament\Widgets\MyTargetProgress;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\AdminModules;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
| Staff tools switches (Oct 2026): Branches, Leads & telecalling and Staff
| targets & performance are off unless switched on in Site Settings, and
| when off even the owner can't reach their pages.
*/

beforeEach(function () {
    foreach (['users', 'site_settings'] as $t) {
        Schema::dropIfExists($t);
    }
    Schema::create('users', function (Blueprint $t) {
        $t->id();
        $t->string('name')->nullable();
        $t->string('email')->nullable();
        $t->string('password')->nullable();
        $t->string('role')->nullable();
        $t->unsignedBigInteger('staff_role_id')->nullable();
        $t->unsignedBigInteger('branch_id')->nullable();
        $t->rememberToken();
        $t->timestamps();
    });
    Schema::create('site_settings', function (Blueprint $t) {
        $t->id();
        $t->string('key')->unique();
        $t->text('value')->nullable();
        $t->timestamps();
    });
    Cache::flush();

    // Legacy owner account: passes every permission check.
    DB::table('users')->insert(['id' => 1, 'name' => 'Owner', 'role' => 'admin']);
    $this->actingAs(User::find(1));
});

it('starts with all three tools off', function () {
    expect(AdminModules::branches())->toBeFalse()
        ->and(AdminModules::leads())->toBeFalse()
        ->and(AdminModules::staffTargets())->toBeFalse()
        ->and(AdminModules::hiddenRoleSlugs())->toBe(['branch_manager', 'branch_staff', 'telecaller']);
});

it('hides each tool from the owner while it is off', function () {
    expect(BranchResource::canAccess())->toBeFalse()
        ->and(BranchResource::shouldRegisterNavigation())->toBeFalse()
        ->and(BranchPayoutResource::canAccess())->toBeFalse()
        ->and(LeadResource::canAccess())->toBeFalse()
        ->and(LeadResource::shouldRegisterNavigation())->toBeFalse()
        ->and(StaffTargetResource::canAccess())->toBeFalse()
        ->and(StaffPerformanceReport::canAccess())->toBeFalse();
});

it('switches each tool on separately', function () {
    SiteSetting::setValue(AdminModules::LEADS, '1');

    expect(LeadResource::canAccess())->toBeTrue()
        ->and(BranchResource::canAccess())->toBeFalse()
        ->and(StaffTargetResource::canAccess())->toBeFalse()
        ->and(AdminModules::hiddenRoleSlugs())->toBe(['branch_manager', 'branch_staff']);

    SiteSetting::setValue(AdminModules::BRANCHES, '1');
    SiteSetting::setValue(AdminModules::STAFF_TARGETS, '1');

    expect(BranchResource::canAccess())->toBeTrue()
        ->and(BranchPayoutResource::canAccess())->toBeTrue()
        ->and(StaffTargetResource::canAccess())->toBeTrue()
        ->and(StaffPerformanceReport::canAccess())->toBeTrue()
        ->and(AdminModules::hiddenRoleSlugs())->toBe([]);
});

it('keeps the telecaller widgets off the dashboard while their tool is off', function () {
    DB::table('users')->insert(['id' => 2, 'name' => 'Caller', 'staff_role_id' => 7, 'branch_id' => 1]);
    $this->actingAs(User::find(2));

    expect(MyStatsOverview::canView())->toBeFalse()
        ->and(MyTargetProgress::canView())->toBeFalse();
});

/*
| The Nov 2026 builds got switches too (owner, 13 Nov: "can most of the features
| be turned on or off by admin?"). A tool that was already working keeps working
| until the owner switches it off, so the switch itself changed nothing live;
| only Verification calls, which is new, starts off.
*/

it('starts the November tools as they were: the new one off, the working ones on', function () {
    expect(AdminModules::verificationCalls())->toBeFalse()   // new: switch it on to start calling
        ->and(AdminModules::assisted())->toBeTrue()
        ->and(AdminModules::staffActivity())->toBeTrue()
        ->and(AdminModules::loginAsMember())->toBeTrue();
});

it('switches each November tool off on its own, and back on', function () {
    $staff = DB::table('users')->insertGetId(['name' => 'Office', 'role' => 'admin', 'created_at' => now(), 'updated_at' => now()]);
    $this->actingAs(User::find($staff));

    expect(\App\Support\LoginAs::allowed(User::find($staff)))->toBeTrue()
        ->and(\App\Filament\Pages\StaffActivity::canAccess())->toBeTrue();

    SiteSetting::setValue(AdminModules::LOGIN_AS_MEMBER, '0');
    SiteSetting::setValue(AdminModules::STAFF_ACTIVITY, '0');
    SiteSetting::setValue(AdminModules::ASSISTED, '0');

    expect(\App\Support\LoginAs::allowed(User::find($staff)))->toBeFalse()   // even for the owner
        ->and(\App\Filament\Pages\StaffActivity::canAccess())->toBeFalse()
        ->and(\App\Support\Assisted::offered())->toBeFalse()
        ->and(\App\Support\Assisted::inMenu(User::find($staff)))->toBeFalse();

    SiteSetting::setValue(AdminModules::LOGIN_AS_MEMBER, '1');
    SiteSetting::setValue(AdminModules::STAFF_ACTIVITY, '1');

    expect(\App\Support\LoginAs::allowed(User::find($staff)))->toBeTrue()
        ->and(\App\Filament\Pages\StaffActivity::canAccess())->toBeTrue();
});

<?php

use App\Filament\Resources\UserResource;
use App\Models\Profile;
use App\Models\SiteSetting;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
| Profile addresses use the Matri ID (30 Oct 2026): /profile/KM100855 on the
| site and /console/users/KM100855 in the admin panel. Old number links (and
| IDs from before a prefix rename) still find the profile.
*/

beforeEach(function () {
    foreach (['users', 'profiles', 'site_settings', 'profile_notes'] as $t) {
        Schema::dropIfExists($t);
    }
    Schema::create('profile_notes', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('profile_id');
        $t->timestamps();
    });
    Schema::create('users', function (Blueprint $t) {
        $t->id();
        $t->string('name')->nullable();
        $t->string('email')->nullable();
        $t->string('role')->nullable();
        $t->unsignedBigInteger('staff_role_id')->nullable();
        $t->unsignedBigInteger('branch_id')->nullable();
        $t->softDeletes();
        $t->timestamps();
    });
    Schema::create('profiles', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('user_id');
        $t->unsignedBigInteger('branch_id')->nullable();
        $t->string('matri_id')->nullable();
        $t->string('full_name')->nullable();
        $t->softDeletes();
        $t->timestamps();
    });
    Schema::create('site_settings', function (Blueprint $t) {
        $t->id();
        $t->string('key')->unique();
        $t->text('value')->nullable();
        $t->timestamps();
    });
    Cache::flush();
    SiteSetting::setValue('profile_id_prefix', 'CR');
    SiteSetting::setValue('matri_id_previous_prefix', 'MT');
    DB::table('users')->insert([['id' => 1, 'name' => 'Owner', 'role' => 'admin'], ['id' => 857, 'name' => 'Member', 'role' => 'user']]);
    DB::table('profiles')->insert(['id' => 855, 'user_id' => 857, 'matri_id' => 'CR100010', 'full_name' => 'Member']);
});

afterEach(function () {
    foreach (['users', 'profiles', 'site_settings', 'profile_notes'] as $t) {
        Schema::dropIfExists($t);
    }
});

it('builds profile links with the Matri ID', function () {
    $profile = Profile::find(855);

    expect(route('profile.view', $profile, false))->toBe('/profile/CR100010')
        ->and(route('profile.print', $profile, false))->toBe('/profile/CR100010/print')
        ->and(route('shortlist.toggle', $profile, false))->toBe('/shortlist/CR100010');
});

it('finds the profile by Matri ID, old number or pre-rename ID', function (string $key) {
    expect((new Profile)->resolveRouteBinding($key)?->id)->toBe(855);
})->with(['CR100010', 'cr100010', '855', 'MT100010']);

it('finds nothing for an unknown Matri ID', function () {
    expect((new Profile)->resolveRouteBinding('CR999999'))->toBeNull();
});

it('addresses admin member pages by Matri ID', function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->actingAs(User::find(1), 'admin');

    // the Users pages show Profile records
    expect(UserResource::getUrl('view', ['record' => Profile::find(855)], false))->toEndWith('/users/CR100010')
        ->and(UserResource::profileViewUrl(Profile::find(855)))->toEndWith('/users/CR100010');
    // (opening /console/users/KM… and the old /console/users/857 is checked in the browser:
    // the record query loads half the schema)
});

<?php

use App\Filament\Pages\Auth\EditAdminProfile;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

/*
|--------------------------------------------------------------------------
| Admin "My Profile" — change your own password
|--------------------------------------------------------------------------
| The panel had no way for an admin to change their own password, so four
| sites still ran on the install-time one. The page must ask for the
| current password, refuse weak ones, and log the change (never the value).
*/

beforeEach(function () {
    Schema::create('users', function (Blueprint $t) {
        $t->id();
        $t->string('name')->nullable();
        $t->string('email')->nullable();
        $t->string('phone')->nullable();
        $t->string('password')->nullable();
        $t->string('role')->nullable();
        $t->unsignedBigInteger('staff_role_id')->nullable();
        $t->boolean('is_active')->default(true);
        $t->timestamp('last_login_at')->nullable();
        $t->integer('reengagement_level')->default(0);
        $t->rememberToken();
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
    Schema::create('site_settings', function (Blueprint $t) {
        $t->id();
        $t->string('key')->unique();
        $t->text('value')->nullable();
        $t->timestamps();
    });
    Cache::flush();

    DB::table('users')->insert([
        'id' => 1, 'name' => 'Site Admin', 'email' => 'admin@site.test', 'role' => 'admin',
        'password' => Hash::make('Admin@1234'), 'created_at' => now(), 'updated_at' => now(),
    ]);
    $this->admin = User::find(1);
    $this->actingAs($this->admin);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

afterEach(function () {
    foreach (['users', 'admin_activity_log', 'site_settings'] as $t) {
        Schema::dropIfExists($t);
    }
});

it('the panel offers a My Profile page', function () {
    expect(Filament::getPanel('admin')->hasProfile())->toBeTrue()
        ->and(Filament::getPanel('admin')->getProfilePage())->toBe(EditAdminProfile::class);
});

it('changes the password when the current one is given, and logs it without the value', function () {
    Livewire::test(EditAdminProfile::class)
        ->fillForm([
            'password' => 'Kudla-Strong#2026x',
            'passwordConfirmation' => 'Kudla-Strong#2026x',
            'currentPassword' => 'Admin@1234',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $hash = DB::table('users')->where('id', 1)->value('password');
    $log = DB::table('admin_activity_log')->where('action', 'own_profile_updated')->first();

    expect(Hash::check('Kudla-Strong#2026x', $hash))->toBeTrue()
        ->and(Hash::check('Admin@1234', $hash))->toBeFalse()
        ->and(json_decode($log->changes, true)['password_changed'])->toBeTrue()
        ->and($log->changes)->not->toContain('Kudla-Strong');
});

it('refuses without the correct current password', function () {
    Livewire::test(EditAdminProfile::class)
        ->fillForm([
            'password' => 'Kudla-Strong#2026x',
            'passwordConfirmation' => 'Kudla-Strong#2026x',
            'currentPassword' => 'wrong-password',
        ])
        ->call('save')
        ->assertHasFormErrors(['currentPassword']);

    expect(Hash::check('Admin@1234', DB::table('users')->where('id', 1)->value('password')))->toBeTrue();
});

it('refuses weak new passwords', function (string $weak) {
    Livewire::test(EditAdminProfile::class)
        ->fillForm(['password' => $weak, 'passwordConfirmation' => $weak, 'currentPassword' => 'Admin@1234'])
        ->call('save')
        ->assertHasFormErrors(['password']);

    expect(Hash::check('Admin@1234', DB::table('users')->where('id', 1)->value('password')))->toBeTrue();
})->with([
    'too short' => 'Ab1#short',
    'no symbol' => 'Abcdefghijk12',
    'no capital' => 'abcdefgh#1234',
    'no number' => 'Abcdefgh#ijkl',
]);

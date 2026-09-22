<?php

use Database\Seeders\RoleSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

/*
|--------------------------------------------------------------------------
| RoleSeeder
|--------------------------------------------------------------------------
| Members are given the 'User' role on creation (MemberCreationService,
| Filament CreateUser). It was missing from the seeder, so every fresh
| site needed it created by hand — and until someone did, member creation
| threw RoleDoesNotExist and left orphan user rows (that is what broke
| sign-ups on globalcatholicmatch.com in Aug 2026).
|
| Also pins re-runnability: seeders do get re-run on live databases, and
| Permission::create / Role::create would throw on the second pass.
|
| Builds only the spatie/laravel-permission tables inline rather than
| using RefreshDatabase — the full migration set includes a MySQL
| fulltext index that SQLite cannot create. Same approach as
| RegistrationPhotoStepTest.
*/

function createPermissionTablesForSeederTest(): void
{
    foreach (['roles', 'permissions'] as $table) {
        if (! Schema::hasTable($table)) {
            Schema::create($table, function (Blueprint $t) {
                $t->id();
                $t->string('name');
                $t->string('guard_name')->default('web');
                $t->timestamps();
                $t->unique(['name', 'guard_name']);
            });
        }
    }

    if (! Schema::hasTable('role_has_permissions')) {
        Schema::create('role_has_permissions', function (Blueprint $t) {
            $t->unsignedBigInteger('permission_id');
            $t->unsignedBigInteger('role_id');
            $t->primary(['permission_id', 'role_id']);
        });
    }

    foreach (['model_has_roles' => 'role_id', 'model_has_permissions' => 'permission_id'] as $table => $fk) {
        if (! Schema::hasTable($table)) {
            Schema::create($table, function (Blueprint $t) use ($fk) {
                $t->unsignedBigInteger($fk);
                $t->string('model_type');
                $t->unsignedBigInteger('model_id');
                $t->primary([$fk, 'model_id', 'model_type']);
            });
        }
    }
}

beforeEach(function () {
    createPermissionTablesForSeederTest();
});

it('seeds all four roles, including User', function () {
    $this->seed(RoleSeeder::class);

    expect(Role::pluck('name')->sort()->values()->all())
        ->toBe(['Moderator', 'Super Admin', 'Support Agent', 'User']);
});

it('can be re-run without throwing or duplicating roles', function () {
    $this->seed(RoleSeeder::class);
    $this->seed(RoleSeeder::class);

    expect(Role::count())->toBe(4)
        ->and(Role::where('name', 'User')->count())->toBe(1);
});

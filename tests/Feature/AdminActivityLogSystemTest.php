<?php

use App\Models\AdminActivityLog;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
| System actions (scheduled jobs, members answering emails) have no admin.
| Until 29 Oct 2026 they were filed under the main admin account; now they
| are "System", and deleting a staff account keeps that person's history.
*/

beforeEach(function () {
    Schema::create('users', function (Blueprint $t) {
        $t->id();
        $t->string('name')->nullable();
        $t->string('role')->nullable();
        $t->timestamps();
    });
    // the live schema before the migration: NOT NULL, cascade on delete
    Schema::create('admin_activity_log', function (Blueprint $t) {
        $t->id();
        $t->foreignId('admin_user_id')->constrained('users')->cascadeOnDelete();
        $t->string('action');
        $t->string('model_type')->nullable();
        $t->unsignedBigInteger('model_id')->nullable();
        $t->json('changes')->nullable();
        $t->string('ip_address')->nullable();
        $t->timestamps();
    });
    DB::table('users')->insert([['name' => 'Owner', 'role' => 'admin'], ['name' => 'Staff', 'role' => null]]);
});

afterEach(function () {
    Schema::dropIfExists('admin_activity_log');
    Schema::dropIfExists('users');
});

it('moves rows recorded without an IP to System and keeps panel actions', function () {
    DB::table('admin_activity_log')->insert([
        ['admin_user_id' => 1, 'action' => 'interests_expired', 'ip_address' => null],
        ['admin_user_id' => 1, 'action' => 'settings.updated', 'ip_address' => '203.0.113.5'],
    ]);

    (require base_path('database/migrations/2026_10_29_000001_admin_activity_log_system_rows.php'))->up();

    expect(AdminActivityLog::where('action', 'interests_expired')->first()->actor_name)->toBe('System')
        ->and(AdminActivityLog::where('action', 'settings.updated')->first()->actor_name)->toBe('Owner');
});

it('records system actions with no admin, and keeps a deleted person\'s rows', function () {
    (require base_path('database/migrations/2026_10_29_000001_admin_activity_log_system_rows.php'))->up();

    $system = AdminActivityLog::create(['action' => 'interests_expired', 'changes' => ['expired' => 3]]);
    $manual = AdminActivityLog::create(['admin_user_id' => 2, 'action' => 'profile_approved', 'ip_address' => '203.0.113.5']);
    DB::table('users')->where('id', 2)->delete();

    expect($system->fresh()->admin_user_id)->toBeNull()
        ->and($manual->fresh())->not->toBeNull()
        ->and($manual->fresh()->admin_user_id)->toBeNull();
});

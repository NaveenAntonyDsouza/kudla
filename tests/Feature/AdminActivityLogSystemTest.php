<?php

use App\Models\AdminActivityLog;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
| System actions (scheduled jobs, members answering emails) have no admin,
| but admin_user_id is NOT NULL in the live schema — those audit rows failed
| on kudla (5–7 Oct 2026). They're filed under the main admin account.
*/

beforeEach(function () {
    Schema::create('users', function (Blueprint $t) {
        $t->id();
        $t->string('name')->nullable();
        $t->string('role')->nullable();
        $t->timestamps();
    });
    Schema::create('admin_activity_log', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('admin_user_id'); // NOT NULL, as on the live sites
        $t->string('action');
        $t->string('model_type')->nullable();
        $t->unsignedBigInteger('model_id')->nullable();
        $t->json('changes')->nullable();
        $t->string('ip_address')->nullable();
        $t->timestamps();
    });
});

afterEach(function () {
    Schema::dropIfExists('admin_activity_log');
    Schema::dropIfExists('users');
});

it('files system actions under the main admin account, and keeps an explicit admin', function () {
    DB::table('users')->insert([['name' => 'Member', 'role' => 'user'], ['name' => 'Owner', 'role' => 'admin'], ['name' => 'Second admin', 'role' => 'admin']]);

    $system = AdminActivityLog::create(['action' => 'interests_expired', 'changes' => ['expired' => 3]]);
    $manual = AdminActivityLog::create(['admin_user_id' => 3, 'action' => 'setting_changed']);

    expect($system->fresh()->admin_user_id)->toBe(2)
        ->and($manual->fresh()->admin_user_id)->toBe(3);
});

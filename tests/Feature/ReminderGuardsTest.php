<?php

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Reminder "already sent" guards live in the database
|--------------------------------------------------------------------------
| The finish-registration (7-day) and add-a-photo (30-day) guards were in
| the cache; a cache clear on kudla (4 Oct 2026) wiped them. They are now
| users columns, and the migration rebuilds them from the activity log,
| which records the Matri IDs each run was sent to.
*/

beforeEach(function () {
    Schema::create('users', function (Blueprint $t) {
        $t->id();
        $t->string('name')->nullable();
        $t->string('email')->nullable();
        $t->string('password')->nullable();
        $t->timestamps();
    });
    Schema::create('profiles', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('user_id');
        $t->string('matri_id')->nullable();
        $t->timestamps();
    });
    Schema::create('admin_activity_log', function (Blueprint $t) {
        $t->id();
        $t->string('action');
        $t->json('changes')->nullable();
        $t->timestamps();
    });
});

afterEach(function () {
    foreach (['admin_activity_log', 'profiles', 'users'] as $table) {
        Schema::dropIfExists($table);
    }
});

it('rebuilds the reminder guards from the activity log, and they survive a cache clear', function () {
    foreach (['KM1', 'KM2', 'KM3'] as $i => $matri) {
        $id = DB::table('users')->insertGetId(['name' => $matri, 'email' => "{$matri}@example.test", 'created_at' => now(), 'updated_at' => now()]);
        DB::table('profiles')->insert(['user_id' => $id, 'matri_id' => $matri]);
    }
    DB::table('admin_activity_log')->insert([
        ['action' => 'registration_reminders_sent', 'changes' => json_encode(['sent' => ['KM1', 'KM2'], 'failed' => []]), 'created_at' => '2026-10-02 19:00:00', 'updated_at' => now()],
        ['action' => 'photo_reminders_sent', 'changes' => json_encode(['sent' => ['KM2', 'KM3'], 'failed' => []]), 'created_at' => '2026-10-02 22:00:00', 'updated_at' => now()],
        ['action' => 'something_else', 'changes' => json_encode(['sent' => ['KM3']]), 'created_at' => now(), 'updated_at' => now()],
    ]);

    (require database_path('migrations/2026_10_04_000004_add_reminder_sent_columns_to_users_table.php'))->up();
    Cache::flush();

    $guards = DB::table('users')->join('profiles', 'profiles.user_id', '=', 'users.id')->orderBy('matri_id')
        ->get(['matri_id', 'last_registration_reminder_at', 'last_photo_reminder_at'])
        ->mapWithKeys(fn ($r) => [$r->matri_id => [$r->last_registration_reminder_at, $r->last_photo_reminder_at]])->all();

    expect($guards)->toBe([
        'KM1' => ['2026-10-02 19:00:00', null],
        'KM2' => ['2026-10-02 19:00:00', '2026-10-02 22:00:00'],
        'KM3' => [null, '2026-10-02 22:00:00'],
    ])->and(User::find(2)->last_photo_reminder_at)->toBeInstanceOf(\Carbon\CarbonInterface::class);
});

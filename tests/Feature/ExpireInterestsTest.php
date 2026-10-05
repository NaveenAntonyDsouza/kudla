<?php

use App\Models\SiteSetting;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

/*
| Interests unanswered for 30 days expire (owner's choice 2026-10-05);
| each sender gets one in-app note per run; nothing is emailed.
*/

beforeEach(function () {
    Schema::create('users', function (Blueprint $t) {
        $t->id();
        $t->string('name')->nullable();
        $t->string('email')->nullable();
        $t->json('notification_preferences')->nullable();
        $t->timestamps();
    });
    Schema::create('profiles', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('user_id');
        $t->string('matri_id')->nullable();
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
    Schema::create('notifications', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('user_id');
        $t->unsignedBigInteger('profile_id')->nullable();
        $t->string('type');
        $t->string('title');
        $t->text('message');
        $t->json('data')->nullable();
        $t->boolean('is_read')->default(false);
        $t->timestamps();
    });
    foreach (['site_settings', 'admin_activity_log', 'user_devices'] as $table) {
        Schema::create($table, function (Blueprint $t) use ($table) {
            $t->id();
            match ($table) {
                'site_settings' => [$t->string('key')->unique(), $t->text('value')->nullable()],
                'admin_activity_log' => [$t->unsignedBigInteger('admin_user_id')->nullable(), $t->string('action'), $t->string('model_type')->nullable(), $t->unsignedBigInteger('model_id')->nullable(), $t->json('changes')->nullable(), $t->string('ip_address')->nullable()],
                'user_devices' => [$t->unsignedBigInteger('user_id'), $t->string('fcm_token')->nullable(), $t->boolean('is_active')->default(true)],
            };
            $t->timestamps();
        });
    }
    Mail::fake();
});

afterEach(function () {
    foreach (['users', 'profiles', 'interests', 'notifications', 'site_settings', 'admin_activity_log', 'user_devices'] as $table) {
        Schema::dropIfExists($table);
    }
});

function exProfile(string $matri): int
{
    $userId = DB::table('users')->insertGetId(['name' => $matri, 'email' => strtolower($matri).'@site.test', 'created_at' => now(), 'updated_at' => now()]);

    return DB::table('profiles')->insertGetId(['user_id' => $userId, 'matri_id' => $matri, 'created_at' => now(), 'updated_at' => now()]);
}

function exInterest(int $from, int $to, int $daysAgo, string $status = 'pending'): void
{
    DB::table('interests')->insert(['sender_profile_id' => $from, 'receiver_profile_id' => $to, 'status' => $status, 'created_at' => now()->subDays($daysAgo), 'updated_at' => now()->subDays($daysAgo)]);
}

it('expires interests unanswered for 30 days and tells each sender once', function () {
    [$asha, $ravi, $meera, $kiran] = [exProfile('KM1'), exProfile('KM2'), exProfile('KM3'), exProfile('KM4')];
    exInterest($asha, $ravi, 40);
    exInterest($asha, $meera, 35);
    exInterest($kiran, $ravi, 31);
    exInterest($kiran, $meera, 10);              // too recent
    exInterest($ravi, $asha, 60, 'accepted');    // answered

    $this->artisan('interests:expire')->assertSuccessful();

    expect(DB::table('interests')->where('status', 'expired')->count())->toBe(3)
        ->and(DB::table('interests')->where('status', 'pending')->count())->toBe(1)
        ->and(DB::table('notifications')->where('type', 'interest_expired')->orderBy('user_id')->pluck('title')->all())
        ->toBe(['2 interests you sent have expired', 'An interest you sent has expired']);
    Mail::assertNothingOutgoing();
});

it('changes nothing on a dry run or when switched off, and runs daily', function () {
    exInterest(exProfile('KM1'), exProfile('KM2'), 40);

    $this->artisan('interests:expire', ['--dry-run' => true])->expectsOutputToContain('30+ days: 1')->assertSuccessful();
    $this->artisan('interests:expire', ['--scheduled' => true])->expectsOutputToContain('is off')->assertSuccessful();
    expect(DB::table('interests')->where('status', 'pending')->count())->toBe(1);

    SiteSetting::setValue('interest_expiry_enabled', '1');
    $this->artisan('interests:expire', ['--scheduled' => true])->assertSuccessful();
    expect(DB::table('interests')->where('status', 'expired')->count())->toBe(1);

    $event = collect(app(Illuminate\Console\Scheduling\Schedule::class)->events())->first(fn ($e) => str_contains($e->command, 'interests:expire'));
    expect($event?->expression)->toBe('30 7 * * *');
});

<?php

use App\Support\MemberFunnel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
| Admin audit phase 3 (Oct 2026), per site: the member funnel, retention by
| month joined, and sign-up sources ("How did you hear about us?").
*/

const FUNNEL_TABLES = ['users', 'profiles', 'profile_photos', 'interests', 'subscriptions', 'login_history'];

beforeEach(function () {
    foreach (FUNNEL_TABLES as $t) {
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
        $t->boolean('onboarding_completed')->default(false);
        $t->string('how_did_you_hear_about_us')->nullable();
        $t->string('registration_source')->nullable();
        $t->unsignedBigInteger('created_by_staff_id')->nullable();
        $t->softDeletes();
        $t->timestamps();
    });
    Schema::create('profile_photos', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('profile_id');
        $t->string('approval_status')->default('approved');
        $t->timestamps();
    });
    Schema::create('interests', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('sender_profile_id');
        $t->unsignedBigInteger('receiver_profile_id');
        $t->string('status')->default('pending');
        $t->timestamps();
    });
    Schema::create('subscriptions', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('user_id');
        $t->string('payment_status')->default('pending');
        $t->timestamps();
    });
    Schema::create('login_history', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('user_id');
        $t->timestamp('logged_in_at')->nullable();
        $t->timestamps();
    });
});

afterEach(function () {
    foreach (FUNNEL_TABLES as $t) {
        Schema::dropIfExists($t);
    }
});

function funnelMember(int $id, array $profile = [], array $user = []): void
{
    DB::table('users')->insert(array_merge(['id' => $id], $user));
    DB::table('profiles')->insert(array_merge(['id' => $id, 'user_id' => $id, 'full_name' => "M{$id}", 'created_at' => now()->subDays(5)], $profile));
}

it('follows the members who joined through each step', function () {
    funnelMember(1, ['onboarding_completed' => true]);
    funnelMember(2, ['onboarding_completed' => true]);
    funnelMember(3, ['onboarding_completed' => true]);
    funnelMember(4);
    funnelMember(5, ['onboarding_completed' => true, 'created_at' => now()->subDays(200)]);   // before the period
    DB::table('profile_photos')->insert([['profile_id' => 1, 'approval_status' => 'approved'], ['profile_id' => 2, 'approval_status' => 'approved'], ['profile_id' => 3, 'approval_status' => 'pending']]);
    DB::table('interests')->insert([
        ['sender_profile_id' => 1, 'receiver_profile_id' => 5, 'status' => 'accepted'],
        ['sender_profile_id' => 5, 'receiver_profile_id' => 2, 'status' => 'pending'],
    ]);
    DB::table('subscriptions')->insert([['user_id' => 1, 'payment_status' => 'paid'], ['user_id' => 2, 'payment_status' => 'failed']]);

    $steps = collect(MemberFunnel::funnel(now()->subDays(90), now()))->keyBy('key');

    expect($steps->map->count->all())->toBe(['joined' => 4, 'finished' => 3, 'photo' => 2, 'interest' => 2, 'connected' => 1, 'paid' => 1])
        ->and($steps['finished']['of_joined'])->toBe(75)
        ->and($steps['photo']['of_previous'])->toBe(67)
        ->and($steps['joined']['of_previous'])->toBeNull();
});

it('counts members as back after 1 and 3 months only once they are old enough', function () {
    $now = Carbon::parse('2026-10-30 12:00:00');
    Carbon::setTestNow($now);
    // June: one came back after 3 months (activity in Oct), one never came back
    funnelMember(1, ['created_at' => '2026-06-10 10:00:00', 'onboarding_completed' => true], ['last_login_at' => '2026-10-20 09:00:00']);
    funnelMember(2, ['created_at' => '2026-06-12 10:00:00'], ['last_login_at' => '2026-06-12 10:30:00']);
    // September: back after a month through a login in login_history; 3 months not reached yet
    funnelMember(3, ['created_at' => '2026-09-05 10:00:00', 'onboarding_completed' => true]);
    DB::table('login_history')->insert(['user_id' => 3, 'logged_in_at' => '2026-10-15 08:00:00']);
    // October: too early for both
    funnelMember(4, ['created_at' => '2026-10-25 10:00:00']);
    // staff accounts are not members
    funnelMember(5, ['created_at' => '2026-10-01 10:00:00'], ['staff_role_id' => 5]);

    $months = collect(MemberFunnel::retention(6, $now))->keyBy('month');

    expect($months['2026-06'])->toMatchArray(['joined' => 2, 'finished' => 50, 'after_1m' => 50, 'after_3m' => 50])
        ->and($months['2026-09'])->toMatchArray(['joined' => 1, 'after_1m' => 100, 'after_3m' => null])
        ->and($months['2026-10'])->toMatchArray(['joined' => 1, 'after_1m' => null, 'after_3m' => null])
        ->and($months['2026-07']['joined'])->toBe(0)
        ->and(array_key_first($months->all()))->toBe('2026-10');   // newest first

    Carbon::setTestNow();
});

it('groups sign-ups by how they heard about the site, staff-added and unanswered apart', function () {
    funnelMember(1, ['how_did_you_hear_about_us' => 'Google Search', 'onboarding_completed' => true, 'registration_source' => 'Mobile']);
    funnelMember(2, ['how_did_you_hear_about_us' => 'Google Search', 'registration_source' => 'Mobile']);
    funnelMember(3, ['how_did_you_hear_about_us' => 'Friends', 'onboarding_completed' => true, 'registration_source' => 'Desktop']);
    funnelMember(4, ['how_did_you_hear_about_us' => '']);
    funnelMember(5, ['how_did_you_hear_about_us' => 'Friends', 'created_by_staff_id' => 9]);
    DB::table('interests')->insert(['sender_profile_id' => 1, 'receiver_profile_id' => 3, 'status' => 'accepted']);

    $sources = collect(MemberFunnel::sources(now()->subDays(30), now()))->keyBy('source');

    expect($sources->keys()->all())->toBe(['Google Search', 'Friends', 'Not answered', 'Added by staff'])
        ->and($sources['Google Search'])->toMatchArray(['joined' => 2, 'share' => 40, 'finished' => 50, 'connected' => 50])
        ->and($sources['Friends'])->toMatchArray(['joined' => 1, 'finished' => 100, 'connected' => 100])
        ->and($sources['Added by staff']['joined'])->toBe(1);

    $devices = collect(MemberFunnel::devices(now()->subDays(30), now()))->keyBy('device');
    expect($devices['Mobile']['joined'])->toBe(2)->and($devices['Not recorded']['joined'])->toBe(2);
});

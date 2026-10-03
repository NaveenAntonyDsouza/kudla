<?php

use App\Mail\InterestReceivedMail;
use App\Mail\InterestReminderMail;
use App\Mail\MembershipEndingTomorrowMail;
use App\Mail\MembershipExpiredMail;
use App\Mail\PaymentReminderMail;
use App\Models\Interest;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Sleep;

/*
|--------------------------------------------------------------------------
| New engagement emails (October 2026 email benchmark)
|--------------------------------------------------------------------------
| - Interest received: Accept / Decline buttons open the interest page with
|   that reply chosen (Shaadi's pattern); the sender is a Matri ID plus a
|   one-line summary, never a name.
| - "Members are waiting for your reply": interests unanswered 3–30 days,
|   blocked/ignored pairs excluded, at most weekly, respects the setting.
| - "Complete your payment": a checkout left pending 2–72 h, unless the
|   member paid around then; at most weekly; follows Promotions setting.
| - Expiry: an extra "ends tomorrow" email and a templated "has ended" one.
*/

beforeEach(function () {
    Schema::create('users', function (Blueprint $t) {
        $t->id();
        $t->string('name')->nullable();
        $t->string('email')->nullable();
        $t->string('password')->nullable();
        $t->string('role')->nullable();
        $t->unsignedBigInteger('staff_role_id')->nullable();
        $t->unsignedBigInteger('branch_id')->nullable();
        $t->boolean('is_active')->default(true);
        $t->json('notification_preferences')->nullable();
        $t->timestamp('last_interest_reminder_at')->nullable();
        $t->timestamp('last_payment_reminder_at')->nullable();
        $t->timestamps();
    });
    Schema::create('profiles', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('user_id');
        $t->unsignedBigInteger('branch_id')->nullable();
        $t->string('matri_id')->nullable();
        $t->string('full_name')->nullable();
        $t->date('date_of_birth')->nullable();
        $t->boolean('is_active')->default(true);
        $t->string('suspension_status')->nullable();
        $t->boolean('onboarding_completed')->default(true);
        $t->softDeletes();
        $t->timestamps();
    });
    Schema::create('location_info', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('profile_id');
        $t->string('native_district')->nullable();
        $t->string('native_state')->nullable();
        $t->timestamps();
    });
    Schema::create('education_details', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('profile_id');
        $t->string('occupation')->nullable();
        $t->timestamps();
    });
    Schema::create('interests', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('sender_profile_id');
        $t->unsignedBigInteger('receiver_profile_id');
        $t->string('status')->default('pending');
        $t->boolean('is_trashed_by_receiver')->default(false);
        $t->timestamps();
    });
    Schema::create('blocked_profiles', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('profile_id');
        $t->unsignedBigInteger('blocked_profile_id');
        $t->timestamps();
    });
    Schema::create('ignored_profiles', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('profile_id');
        $t->unsignedBigInteger('ignored_profile_id');
        $t->timestamps();
    });
    Schema::create('subscriptions', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('user_id');
        $t->unsignedBigInteger('branch_id')->nullable();
        $t->string('plan_name')->nullable();
        $t->string('payment_status')->default('pending');
        $t->timestamps();
    });
    Schema::create('membership_plans', function (Blueprint $t) {
        $t->id();
        $t->string('plan_name');
        $t->timestamps();
    });
    Schema::create('user_memberships', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('user_id');
        $t->unsignedBigInteger('plan_id');
        $t->boolean('is_active')->default(true);
        $t->timestamp('starts_at')->nullable();
        $t->timestamp('ends_at')->nullable();
        $t->timestamps();
    });
    foreach (['site_settings' => 'key', 'email_templates' => 'slug'] as $table => $unique) {
        Schema::create($table, function (Blueprint $t) use ($unique) {
            $t->id();
            $t->string($unique)->unique();
            $t->text('value')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
    }
    Schema::create('theme_settings', function (Blueprint $t) {
        $t->id();
        $t->string('primary_color')->nullable();
        $t->string('logo_url')->nullable();
        $t->timestamps();
    });

    Mail::fake();
    Sleep::fake();
});

afterEach(function () {
    foreach (['theme_settings', 'email_templates', 'site_settings', 'user_memberships', 'membership_plans', 'subscriptions', 'ignored_profiles', 'blocked_profiles', 'interests', 'education_details', 'location_info', 'profiles', 'users'] as $table) {
        Schema::dropIfExists($table);
    }
});

/** A member with a profile; returns [user, profile id]. */
function erMember(string $matriId, array $user = []): array
{
    $u = User::create(array_merge(['name' => "Member {$matriId}", 'email' => strtolower($matriId) . '@example.test', 'password' => 'x', 'role' => 'user'], $user));
    $profileId = DB::table('profiles')->insertGetId([
        'user_id' => $u->id, 'matri_id' => $matriId, 'full_name' => "Asha Priya {$matriId}",
        'date_of_birth' => now()->subYears(27)->subMonth()->toDateString(), 'created_at' => now(), 'updated_at' => now(),
    ]);
    DB::table('location_info')->insert(['profile_id' => $profileId, 'native_district' => 'Udupi', 'created_at' => now(), 'updated_at' => now()]);
    DB::table('education_details')->insert(['profile_id' => $profileId, 'occupation' => 'Nurse', 'created_at' => now(), 'updated_at' => now()]);

    return [$u->fresh(), $profileId];
}

function erInterest(int $from, int $to, int $daysAgo, string $status = 'pending'): int
{
    return DB::table('interests')->insertGetId([
        'sender_profile_id' => $from, 'receiver_profile_id' => $to, 'status' => $status,
        'created_at' => now()->subDays($daysAgo), 'updated_at' => now()->subDays($daysAgo),
    ]);
}

it('puts Accept and Decline in the interest email, and names the sender only by Matri ID', function () {
    [, $sender] = erMember('KM200001');
    [$receiver, $receiverProfile] = erMember('KM200002');
    $interest = Interest::find(erInterest($sender, $receiverProfile, 0));

    $mail = new InterestReceivedMail($interest);
    $vars = (fn () => $this->templateVariables())->call($mail);

    expect($vars['ACCEPT_URL'])->toEndWith("/interests/{$interest->id}?reply=accept#reply")
        ->and($vars['DECLINE_URL'])->toEndWith("/interests/{$interest->id}?reply=decline#reply")
        ->and($vars['SENDER_SUMMARY'])->toBe('27 yrs · Udupi · Nurse')
        ->and(implode(' ', $vars))->not->toContain('Asha Priya KM200001')
        ->and($mail->headers()->text['List-Unsubscribe'])->toContain("/unsubscribe/{$receiver->id}/email_interest");
});

it('reminds a member about interests unanswered for 3+ days, once a week at most', function () {
    [$receiver, $me] = erMember('KM300001');
    [, $waiting] = erMember('KM300002');
    [, $tooNew] = erMember('KM300003');
    [, $answered] = erMember('KM300004');
    [, $blocked] = erMember('KM300005');

    erInterest($waiting, $me, 5);
    erInterest($tooNew, $me, 1);
    erInterest($answered, $me, 6, 'accepted');
    erInterest($blocked, $me, 7);
    DB::table('blocked_profiles')->insert(['profile_id' => $me, 'blocked_profile_id' => $blocked]);

    $this->artisan('engagement:send-interest-reminders')->assertSuccessful();

    Mail::assertSent(InterestReminderMail::class, function (InterestReminderMail $mail) use ($receiver) {
        return $mail->hasTo($receiver->email) && $mail->total === 1
            && $mail->interests->pluck('senderProfile.matri_id')->all() === ['KM300002'];
    });
    expect($receiver->fresh()->last_interest_reminder_at)->not->toBeNull();

    // Same week: no second reminder
    $this->artisan('engagement:send-interest-reminders')->assertSuccessful();
    Mail::assertSentCount(1);
});

it('skips interest reminders for members who switched interest emails off, or when the feature is off', function () {
    [, $me] = erMember('KM400001', ['notification_preferences' => ['email_interest' => false]]);
    [, $waiting] = erMember('KM400002');
    erInterest($waiting, $me, 5);

    $this->artisan('engagement:send-interest-reminders')->assertSuccessful();
    Mail::assertNothingSent();

    [, $other] = erMember('KM400003');
    erInterest($waiting, $other, 5);
    SiteSetting::setValue('interest_reminders_enabled', '0');
    $this->artisan('engagement:send-interest-reminders')->assertSuccessful();
    Mail::assertNothingSent();
});

it('reminds a member who left checkout, unless they paid around then', function () {
    [$left] = erMember('KM500001');
    [$paid] = erMember('KM500002');
    [$justNow] = erMember('KM500003');
    [$optedOut] = erMember('KM500004', ['notification_preferences' => ['email_promotions' => false]]);

    $pending = fn (User $u, int $hoursAgo) => DB::table('subscriptions')->insert(['user_id' => $u->id, 'plan_name' => 'Gold', 'payment_status' => 'pending', 'created_at' => now()->subHours($hoursAgo), 'updated_at' => now()]);
    $pending($left, 5);
    $pending($paid, 5);
    DB::table('subscriptions')->insert(['user_id' => $paid->id, 'plan_name' => 'Gold', 'payment_status' => 'paid', 'created_at' => now()->subHours(4), 'updated_at' => now()]);
    $pending($justNow, 1);
    $pending($optedOut, 5);

    $this->artisan('engagement:send-payment-reminders')->assertSuccessful();

    Mail::assertSent(PaymentReminderMail::class, fn ($mail) => $mail->hasTo($left->email) && $mail->planName === 'Gold');
    Mail::assertSentCount(1);

    $this->artisan('engagement:send-payment-reminders')->assertSuccessful();
    Mail::assertSentCount(1);
});

it('sends the ends-tomorrow and has-ended membership emails', function () {
    $this->mock(NotificationService::class)->shouldReceive('send');
    $planId = DB::table('membership_plans')->insertGetId(['plan_name' => 'Gold', 'created_at' => now(), 'updated_at' => now()]);
    [$tomorrow] = erMember('KM600001');
    [$ended] = erMember('KM600002');
    DB::table('user_memberships')->insert([
        ['user_id' => $tomorrow->id, 'plan_id' => $planId, 'is_active' => true, 'ends_at' => now()->addDay(), 'created_at' => now(), 'updated_at' => now()],
        ['user_id' => $ended->id, 'plan_id' => $planId, 'is_active' => true, 'ends_at' => now()->subDay(), 'created_at' => now(), 'updated_at' => now()],
    ]);

    $this->artisan('membership:expiry-reminders')->assertSuccessful();

    Mail::assertQueued(MembershipEndingTomorrowMail::class, fn ($mail) => $mail->hasTo($tomorrow->email));
    Mail::assertQueued(MembershipExpiredMail::class, fn ($mail) => $mail->hasTo($ended->email));
    expect(DB::table('user_memberships')->where('user_id', $ended->id)->value('is_active'))->toBeFalsy();
});

<?php

use App\Mail\InterestReceivedMail;
use App\Mail\MembershipActivatedMail;
use App\Mail\PhotoApprovedMail;
use App\Mail\PhotoRequestApprovedMail;
use App\Mail\PhotoRequestReceivedMail;
use App\Models\PhotoRequest;
use App\Mail\ProfileApprovedMail;
use App\Mail\ProfileRejectedMail;
use App\Mail\WelcomeMail;
use App\Models\Interest;
use App\Models\MembershipPlan;
use App\Models\Profile;
use App\Models\User;
use App\Models\UserMembership;
use App\Services\MemberEmailService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Member lifecycle emails
|--------------------------------------------------------------------------
| MemberEmailService is the single path for member-facing lifecycle mail,
| plus the model hooks that trigger it:
|   - Profile updated: is_approved false → true   → ProfileApprovedMail
|   - Profile updated: onboarding_completed → true → WelcomeMail (new accounts)
|   - UserMembership created active               → MembershipActivatedMail
| Profiles/memberships CREATED already approved/active (admin create, bulk
| import) must not trigger them — those members get the staff welcome.
|
| Inline partial schema, same approach as SettingsControllerTest: the full
| migration set has a MySQL-only fulltext index SQLite can't build.
*/

beforeEach(function () {
    Schema::create('users', function (Blueprint $t) {
        $t->id();
        $t->string('name')->nullable();
        $t->string('email')->nullable();
        $t->string('password')->nullable();
        $t->string('phone')->nullable();
        $t->string('role')->nullable();
        $t->unsignedBigInteger('branch_id')->nullable();
        $t->boolean('is_active')->default(true);
        $t->json('notification_preferences')->nullable();
        $t->timestamps();
    });
    Schema::create('profiles', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('user_id');
        $t->string('matri_id', 20)->unique();
        $t->string('full_name')->nullable();
        $t->boolean('is_approved')->default(false);
        $t->boolean('is_active')->default(true);
        $t->boolean('onboarding_completed')->default(false);
        $t->unsignedBigInteger('branch_id')->nullable();
        $t->timestamp('deleted_at')->nullable();
        $t->timestamps();
    });
    Schema::create('membership_plans', function (Blueprint $t) {
        $t->id();
        $t->string('plan_name');
        $t->integer('duration_months')->default(1);
        $t->timestamps();
    });
    Schema::create('user_memberships', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('user_id');
        $t->unsignedBigInteger('plan_id');
        $t->unsignedBigInteger('transaction_id')->nullable();
        $t->timestamp('starts_at')->nullable();
        $t->timestamp('ends_at')->nullable();
        $t->boolean('is_active')->default(true);
        $t->timestamps();
    });
    Schema::create('photo_requests', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('requester_profile_id');
        $t->unsignedBigInteger('target_profile_id');
        $t->string('status')->default('pending');
        $t->timestamps();
    });
    // Empty on purpose: asserting recipients renders the envelope, which
    // looks up the template and falls back to the class's default subject.
    Schema::create('email_templates', function (Blueprint $t) {
        $t->id();
        $t->string('slug');
        $t->boolean('is_active')->default(true);
        $t->timestamps();
    });

    Mail::fake();
});

afterEach(function () {
    foreach (['email_templates', 'photo_requests', 'user_memberships', 'membership_plans', 'profiles', 'users'] as $table) {
        Schema::dropIfExists($table);
    }
});

function emailMember(array $user = [], array $profile = []): Profile
{
    $u = User::create(array_merge([
        'name' => 'Test Member',
        'email' => 'member@example.test',
        'password' => 'x',
    ], $user));

    // matri_id given so Profile::creating doesn't look up the prefix setting.
    return Profile::create(array_merge([
        'user_id' => $u->id,
        'matri_id' => 'AM' . random_int(100000, 999999),
        'full_name' => 'Test Member',
        'is_approved' => false,
        'onboarding_completed' => false,
    ], $profile));
}

/* ---------------- Profile approval ---------------- */

it('emails the member when an admin approves their pending profile', function () {
    $profile = emailMember();

    $profile->update(['is_approved' => true]);

    Mail::assertQueued(ProfileApprovedMail::class, fn ($m) => $m->hasTo('member@example.test'));
});

it('does not email "approved" for a profile created already approved', function () {
    emailMember(profile: ['is_approved' => true]);

    Mail::assertNotQueued(ProfileApprovedMail::class);
});

it('does not re-send "approved" on unrelated edits to an approved profile', function () {
    $profile = emailMember(profile: ['is_approved' => true]);

    $profile->update(['full_name' => 'Renamed']);

    Mail::assertNotQueued(ProfileApprovedMail::class);
});

/* ---------------- Welcome ---------------- */

it('sends the welcome email once registration completes', function () {
    $profile = emailMember();

    $profile->update(['onboarding_completed' => true]);
    $profile->update(['onboarding_completed' => true]); // no change → no second email

    Mail::assertQueued(WelcomeMail::class, 1);
});

it('skips the welcome email for an old account finishing registration late', function () {
    $profile = emailMember();
    $profile->user->forceFill(['created_at' => now()->subDays(MemberEmailService::WELCOME_WINDOW_DAYS + 1)])->save();

    $profile->update(['onboarding_completed' => true]);

    Mail::assertNotQueued(WelcomeMail::class);
});

it('does not send the welcome email for members created complete (admin / import)', function () {
    emailMember(profile: ['onboarding_completed' => true]);

    Mail::assertNotQueued(WelcomeMail::class);
});

/* ---------------- Membership activation ---------------- */

it('emails "plan active" when an active membership is created', function () {
    $profile = emailMember();
    $plan = MembershipPlan::create(['plan_name' => 'Gold', 'duration_months' => 3]);

    UserMembership::create([
        'user_id' => $profile->user_id,
        'plan_id' => $plan->id,
        'starts_at' => now(),
        'ends_at' => now()->addMonths(3),
        'is_active' => true,
    ]);

    Mail::assertQueued(MembershipActivatedMail::class, fn ($m) => $m->planName === 'Gold' && $m->hasTo('member@example.test'));
});

it('never fails membership creation when the member or plan cannot be loaded', function () {
    // No user row 999 and no plan row 999: the email lookup fails inside the
    // hook. The membership must still be created — this is the payment path.
    $membership = UserMembership::create(['user_id' => 999, 'plan_id' => 999, 'is_active' => true]);

    expect($membership->exists)->toBeTrue();
    Mail::assertNothingQueued();
});

it('does not email for an inactive membership record', function () {
    $profile = emailMember();
    $plan = MembershipPlan::create(['plan_name' => 'Gold']);

    UserMembership::create(['user_id' => $profile->user_id, 'plan_id' => $plan->id, 'is_active' => false]);

    Mail::assertNotQueued(MembershipActivatedMail::class);
});

/* ---------------- Delivery rules ---------------- */

it('skips members with no email address', function () {
    $profile = emailMember(user: ['email' => null]);

    app(MemberEmailService::class)->photosApproved($profile->user);

    Mail::assertNothingQueued();
});

it('honours the member opting out of interest emails', function () {
    $profile = emailMember(user: ['notification_preferences' => ['email_interest' => false]]);

    app(MemberEmailService::class)->interestReceived($profile->user, new Interest());

    Mail::assertNotQueued(InterestReceivedMail::class);
});

it('sends interest emails when the member has not opted out', function () {
    $profile = emailMember();

    app(MemberEmailService::class)->interestReceived($profile->user, new Interest());

    Mail::assertQueued(InterestReceivedMail::class);
});

it('never lets a mail failure break the action that triggered it', function () {
    $profile = emailMember();
    Mail::shouldReceive('to')->andThrow(new RuntimeException('SMTP down'));

    // Must not throw.
    app(MemberEmailService::class)->photosApproved($profile->user);

    expect(true)->toBeTrue();
});

it('emails the reason when an admin requests profile changes', function () {
    $profile = emailMember();

    app(MemberEmailService::class)->profileChangesRequested($profile->user, 'Some profile details are incomplete');

    Mail::assertQueued(ProfileRejectedMail::class, fn ($m) => $m->reason === 'Some profile details are incomplete'
        && $m->hasTo('member@example.test'));
});

/* ---------------- Photo requests ---------------- */

it('emails the member whose photos were requested, and the requester on approval', function () {
    $requester = emailMember(user: ['email' => 'asker@example.test']);
    $target = emailMember(user: ['email' => 'owner@example.test']);

    $request = PhotoRequest::create([
        'requester_profile_id' => $requester->id,
        'target_profile_id' => $target->id,
        'status' => 'pending',
    ]);
    Mail::assertQueued(PhotoRequestReceivedMail::class, fn ($m) => $m->hasTo('owner@example.test'));
    Mail::assertNotQueued(PhotoRequestApprovedMail::class);

    $request->update(['status' => 'approved']);
    Mail::assertQueued(PhotoRequestApprovedMail::class, fn ($m) => $m->hasTo('asker@example.test'));
});

it('does not email about photo requests when the member turned off interest emails', function () {
    $requester = emailMember(user: ['email' => 'asker@example.test']);
    $target = emailMember(user: ['email' => 'owner@example.test', 'notification_preferences' => ['email_interest' => false]]);

    PhotoRequest::create(['requester_profile_id' => $requester->id, 'target_profile_id' => $target->id, 'status' => 'pending']);

    Mail::assertNotQueued(PhotoRequestReceivedMail::class);
});

it('does not email the requester when a request is ignored', function () {
    $requester = emailMember(user: ['email' => 'asker@example.test']);
    $target = emailMember(user: ['email' => 'owner@example.test']);
    $request = PhotoRequest::create(['requester_profile_id' => $requester->id, 'target_profile_id' => $target->id, 'status' => 'pending']);

    $request->update(['status' => 'ignored']);

    Mail::assertNotQueued(PhotoRequestApprovedMail::class);
});

it('queues a photo-approved email for the member', function () {
    $profile = emailMember();

    app(MemberEmailService::class)->photosApproved($profile->user);

    Mail::assertQueued(PhotoApprovedMail::class, fn ($m) => $m->hasTo('member@example.test'));
});

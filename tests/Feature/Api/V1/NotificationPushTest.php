<?php

use App\Models\Device;
use App\Models\Notification;
use App\Models\User;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Kreait\Firebase\Exception\Messaging\NotFound;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Laravel\Firebase\Facades\Firebase;

/*
|--------------------------------------------------------------------------
| NotificationService — push dispatch (step-07)
|--------------------------------------------------------------------------
| send() now writes the in-app row AND fires FCM push to active devices,
| gated by user prefs + quiet hours, with graceful degradation when
| Firebase isn't configured.
|
| The Firebase facade is mocked via Mockery — kreait extends Laravel's
| standard Facade, so Firebase::shouldReceive('messaging') swaps the
| underlying instance for the duration of the test.
|
| Reference: docs/mobile-app/phase-2a-api/week-04-interests-payment-push/step-07-notification-push-dispatch.md
*/

function createNotificationTables(): void
{
    if (! Schema::hasTable('users')) {
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name')->nullable();
            $t->string('email')->nullable();
            $t->string('password')->nullable();
            $t->string('phone')->nullable();
            $t->string('role')->nullable();
            $t->unsignedBigInteger('staff_role_id')->nullable();
            $t->unsignedBigInteger('branch_id')->nullable();
            $t->timestamp('phone_verified_at')->nullable();
            $t->timestamp('email_verified_at')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamp('last_login_at')->nullable();
            $t->timestamp('last_reengagement_sent_at')->nullable();
            $t->integer('reengagement_level')->default(0);
            $t->timestamp('last_weekly_match_sent_at')->nullable();
            $t->integer('nudges_sent_count')->default(0);
            $t->timestamp('last_nudge_sent_at')->nullable();
            $t->json('notification_preferences')->nullable();
            $t->timestamps();
        });
    }
    if (! Schema::hasTable('devices')) {
        Schema::create('devices', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->unsignedBigInteger('personal_access_token_id')->nullable();
            $t->string('fcm_token', 255)->unique();
            $t->string('platform', 10);
            $t->string('device_model', 100)->nullable();
            $t->string('app_version', 20)->nullable();
            $t->string('os_version', 20)->nullable();
            $t->string('locale', 10)->default('en');
            $t->timestamp('last_seen_at')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->index('user_id');
        });
    }
    if (! Schema::hasTable('notifications')) {
        Schema::create('notifications', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->unsignedBigInteger('profile_id')->nullable();
            // Use string (not enum) in tests — SQLite handles ENUM as
            // CHECK constraint and we want flexibility for future types.
            $t->string('type', 50);
            $t->string('title', 200);
            $t->text('message');
            $t->json('data')->nullable();
            $t->boolean('is_read')->default(false);
            $t->timestamps();
        });
    }
}

function dropNotificationTables(): void
{
    Schema::dropIfExists('notifications');
    Schema::dropIfExists('devices');
    Schema::dropIfExists('users');
}

function makeNotifUser(int $id = 9100, array $overrides = []): User
{
    return User::create(array_merge([
        'id' => $id,
        'name' => "User {$id}",
        'email' => "u{$id}@e.com",
        'is_active' => true,
    ], $overrides));
}

function seedDevice(int $userId, string $token = 'fcm_xx', bool $active = true, ?string $platform = 'android'): Device
{
    return Device::create([
        'user_id' => $userId,
        'fcm_token' => $token,
        'platform' => $platform,
        'is_active' => $active,
    ]);
}

/**
 * Swap the Firebase facade with a Mockery so tests can assert on the
 * Messaging::send() calls. Returns the messaging mock for chaining
 * shouldReceive('send') expectations on it.
 *
 * Uses Facade::swap() rather than ::shouldReceive() because the latter
 * doesn't reliably forward chained calls through kreait's project-
 * manager indirection.
 */
function mockFirebaseMessaging(): \Mockery\MockInterface
{
    $messaging = Mockery::mock(\Kreait\Firebase\Contract\Messaging::class);

    $projectManager = Mockery::mock();
    $projectManager->shouldReceive('messaging')->andReturn($messaging);

    Firebase::swap($projectManager);

    return $messaging;
}

/** Make Firebase::messaging() throw (simulates missing credentials). */
function mockFirebaseUnconfigured(): void
{
    $projectManager = Mockery::mock();
    $projectManager->shouldReceive('messaging')->andThrow(new \RuntimeException('No credentials configured'));

    Firebase::swap($projectManager);
}

beforeEach(function () {
    createNotificationTables();
});

afterEach(function () {
    Mockery::close();
    Firebase::clearResolvedInstances();
    dropNotificationTables();
});

/* ==================================================================
 |  Happy path — push fires to active devices
 | ================================================================== */

it('send dispatches push to all active devices for the user', function () {
    $user = makeNotifUser();
    seedDevice($user->id, 'fcm_token_A');
    seedDevice($user->id, 'fcm_token_B');

    $messaging = mockFirebaseMessaging();
    $messaging->shouldReceive('send')
        ->twice()
        ->with(Mockery::type(CloudMessage::class))
        ->andReturn(['name' => 'projects/x/messages/y']);

    app(NotificationService::class)->send(
        $user,
        'interest_received',
        'Interest Received',
        'Someone showed interest.',
        null,
        ['interest_id' => 7],
    );

    expect(Notification::where('user_id', $user->id)->count())->toBe(1);
});

it('send updates last_seen_at on each successful device send', function () {
    Carbon::setTestNow('2026-04-26 10:00:00');
    $user = makeNotifUser();
    $device = seedDevice($user->id, 'fcm_active');

    $messaging = mockFirebaseMessaging();
    $messaging->shouldReceive('send')->once()->andReturn(['name' => 'ok']);

    app(NotificationService::class)->send($user, 'interest_received', 't', 'm');

    expect($device->fresh()->last_seen_at?->format('Y-m-d H:i'))->toBe('2026-04-26 10:00');
    Carbon::setTestNow();
});

it('send is silent no-op when user has no devices', function () {
    $user = makeNotifUser();
    // No mock — if push were attempted, Firebase::messaging() would throw.
    app(NotificationService::class)->send($user, 'interest_received', 't', 'm');

    expect(Notification::where('user_id', $user->id)->count())->toBe(1);
});

it('send skips inactive devices', function () {
    $user = makeNotifUser();
    seedDevice($user->id, 'fcm_active', active: true);
    seedDevice($user->id, 'fcm_inactive', active: false);

    $messaging = mockFirebaseMessaging();
    // Only the active device should be targeted.
    $messaging->shouldReceive('send')->once()->andReturn(['name' => 'ok']);

    app(NotificationService::class)->send($user, 'interest_received', 't', 'm');
});

/* ==================================================================
 |  Error handling — graceful degradation
 | ================================================================== */

it('send writes notification row even when Firebase is not configured', function () {
    $user = makeNotifUser();
    seedDevice($user->id);

    mockFirebaseUnconfigured();

    app(NotificationService::class)->send($user, 'interest_received', 't', 'm');

    expect(Notification::where('user_id', $user->id)->count())->toBe(1);
});

it('send marks device inactive when Firebase returns NotFound (stale token)', function () {
    $user = makeNotifUser();
    $device = seedDevice($user->id, 'fcm_stale');

    $messaging = mockFirebaseMessaging();
    $messaging->shouldReceive('send')->once()->andThrow(new NotFound('token unregistered'));

    app(NotificationService::class)->send($user, 'interest_received', 't', 'm');

    expect($device->fresh()->is_active)->toBeFalse();
});

it('send continues to next device when one device throws', function () {
    $user = makeNotifUser();
    seedDevice($user->id, 'fcm_first');
    $second = seedDevice($user->id, 'fcm_second');

    $messaging = mockFirebaseMessaging();
    $messaging->shouldReceive('send')
        ->once()->ordered()
        ->andThrow(new \RuntimeException('transient network'));
    $messaging->shouldReceive('send')
        ->once()->ordered()
        ->andReturn(['name' => 'ok']);

    app(NotificationService::class)->send($user, 'interest_received', 't', 'm');

    // Second device's send succeeded → last_seen_at should be updated.
    expect($second->fresh()->last_seen_at)->not->toBeNull();
});

/* ==================================================================
 |  shouldPush — preferences + quiet hours
 | ================================================================== */

it('send respects push_interest=false preference for non-priority types', function () {
    $user = makeNotifUser(overrides: [
        'notification_preferences' => ['push_interest' => false],
    ]);
    seedDevice($user->id);

    // No messaging mock — shouldPush() must short-circuit before Firebase
    // is invoked. If push were attempted, Firebase::messaging() would
    // throw (no real credentials in tests), tripping the test.

    app(NotificationService::class)->send($user, 'interest_received', 't', 'm');

    expect(Notification::where('user_id', $user->id)->count())->toBe(1);
});

it('send bypasses quiet hours for high-priority types (interest_accepted)', function () {
    Carbon::setTestNow('2026-04-26 23:30:00');  // inside 22:00→07:00 quiet window

    $user = makeNotifUser(overrides: [
        'notification_preferences' => [
            'push_interest' => false,  // even with pref off
            'quiet_hours_start' => '22:00',
            'quiet_hours_end' => '07:00',
        ],
    ]);
    seedDevice($user->id);

    $messaging = mockFirebaseMessaging();
    $messaging->shouldReceive('send')->once()->andReturn(['name' => 'ok']);

    app(NotificationService::class)->send($user, 'interest_accepted', 't', 'm');

    Carbon::setTestNow();
});

it('send respects quiet hours for non-priority types — same-day window', function () {
    Carbon::setTestNow('2026-04-26 14:30:00');  // inside 13:00→16:00 window

    $user = makeNotifUser(overrides: [
        'notification_preferences' => [
            'quiet_hours_start' => '13:00',
            'quiet_hours_end' => '16:00',
        ],
    ]);
    seedDevice($user->id);

    app(NotificationService::class)->send($user, 'interest_received', 't', 'm');

    expect(Notification::where('user_id', $user->id)->count())->toBe(1);
    Carbon::setTestNow();
});

it('send respects overnight quiet hours window (22:00 → 07:00)', function () {
    Carbon::setTestNow('2026-04-26 02:30:00');  // 02:30 — inside the overnight window

    $user = makeNotifUser(overrides: [
        'notification_preferences' => [
            'quiet_hours_start' => '22:00',
            'quiet_hours_end' => '07:00',
        ],
    ]);
    seedDevice($user->id);

    app(NotificationService::class)->send($user, 'interest_received', 't', 'm');

    expect(Notification::where('user_id', $user->id)->count())->toBe(1);
    Carbon::setTestNow();
});

it('send does NOT skip when current time is outside quiet hours', function () {
    Carbon::setTestNow('2026-04-26 10:00:00');  // outside 22:00→07:00

    $user = makeNotifUser(overrides: [
        'notification_preferences' => [
            'quiet_hours_start' => '22:00',
            'quiet_hours_end' => '07:00',
        ],
    ]);
    seedDevice($user->id);

    $messaging = mockFirebaseMessaging();
    $messaging->shouldReceive('send')->once()->andReturn(['name' => 'ok']);

    app(NotificationService::class)->send($user, 'interest_received', 't', 'm');

    Carbon::setTestNow();
});

/* ==================================================================
 |  pushPrefKey routing — different types map to different prefs
 | ================================================================== */

it('send maps profile_view to push_views preference', function () {
    $user = makeNotifUser(overrides: [
        'notification_preferences' => [
            'push_views' => false,    // disabling push_views must block the send
            'push_interest' => true,  // push_interest stays on, must NOT save us
        ],
    ]);
    seedDevice($user->id);

    app(NotificationService::class)->send($user, 'profile_view', 't', 'm');

    expect(Notification::where('user_id', $user->id)->count())->toBe(1);
});

it('send works with empty data array — defaults notification_id + type into FCM data', function () {
    $user = makeNotifUser();
    seedDevice($user->id);

    $captured = null;
    $messaging = mockFirebaseMessaging();
    $messaging->shouldReceive('send')->once()
        ->with(Mockery::on(function (CloudMessage $msg) use (&$captured) {
            // jsonSerialize() exposes the message structure we sent.
            $captured = $msg->jsonSerialize();
            return true;
        }))
        ->andReturn(['name' => 'ok']);

    app(NotificationService::class)->send($user, 'interest_received', 't', 'm');

    // Even without caller-supplied data, notification_id + type get added.
    expect($captured['data']['type'])->toBe('interest_received');
    expect($captured['data']['notification_id'])->not->toBeEmpty();
});

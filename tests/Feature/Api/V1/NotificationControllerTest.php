<?php

use App\Http\Controllers\Api\V1\NotificationController;
use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| NotificationController — list + mark-read endpoints
|--------------------------------------------------------------------------
| Covers the read-side of the notifications surface that step-08 lays
| down on top of the existing in-app rows + push (step-07).
|
| Tests dispatch directly on the controller (not through the HTTP kernel)
| so we don't need Sanctum guard wiring — same pattern used across the
| step-04+ v1 tests. We DO need a real Eloquent query, so we stand up
| inline users + notifications tables in :memory: SQLite.
*/

function createNotifControllerTables(): void
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
    if (! Schema::hasTable('notifications')) {
        Schema::create('notifications', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->unsignedBigInteger('profile_id')->nullable();
            // String (not enum) here — SQLite handles ENUM via CHECK constraint
            // and we need to store any underscore-cased type in tests.
            $t->string('type', 50);
            $t->string('title', 200);
            $t->text('message');
            $t->json('data')->nullable();
            $t->boolean('is_read')->default(false);
            $t->timestamps();
        });
    }
}

function dropNotifControllerTables(): void
{
    Schema::dropIfExists('notifications');
    Schema::dropIfExists('users');
}

function makeNotifControllerUser(int $id = 8800): User
{
    return User::create([
        'id' => $id,
        'name' => "User {$id}",
        'email' => "u{$id}@e.com",
        'is_active' => true,
    ]);
}

function seedNotification(int $userId, array $overrides = []): Notification
{
    return Notification::create(array_merge([
        'user_id' => $userId,
        'profile_id' => null,
        'type' => 'interest_received',
        'title' => 'Interest Received',
        'message' => 'Someone showed interest.',
        'data' => ['interest_id' => 99],
        'is_read' => false,
    ], $overrides));
}

function notifRequest(User $user, string $method = 'GET', array $query = [], array $body = [], string $path = '/api/v1/notifications'): Request
{
    $r = Request::create($path, $method, $method === 'GET' ? $query : $body);
    $r->setUserResolver(fn () => $user);

    return $r;
}

beforeEach(function () {
    createNotifControllerTables();
});

afterEach(function () {
    dropNotifControllerTables();
});

/* ==================================================================
 |  GET /notifications — index
 | ================================================================== */

it('index returns paginated notifications for the authenticated user, latest first', function () {
    $user = makeNotifControllerUser();
    Carbon::setTestNow('2026-04-26 09:00:00');
    seedNotification($user->id, ['title' => 'older']);
    Carbon::setTestNow('2026-04-26 12:00:00');
    seedNotification($user->id, ['title' => 'newer']);
    Carbon::setTestNow();

    $response = app(NotificationController::class)->index(notifRequest($user));
    $payload = $response->getData(true);

    expect($response->getStatusCode())->toBe(200);
    expect($payload['success'])->toBeTrue();
    expect($payload['data'])->toHaveCount(2);
    expect($payload['data'][0]['title'])->toBe('newer');  // latest-first
    expect($payload['data'][1]['title'])->toBe('older');
    expect($payload['meta'])->toHaveKeys(['page', 'per_page', 'total', 'last_page', 'unread_count']);
});

it('index only returns notifications belonging to the authenticated user', function () {
    $owner = makeNotifControllerUser(8800);
    $stranger = makeNotifControllerUser(8801);

    seedNotification($owner->id, ['title' => 'mine']);
    seedNotification($stranger->id, ['title' => 'not mine']);

    $response = app(NotificationController::class)->index(notifRequest($owner));
    $payload = $response->getData(true);

    expect($payload['data'])->toHaveCount(1);
    expect($payload['data'][0]['title'])->toBe('mine');
    expect($payload['meta']['total'])->toBe(1);
});

it('index respects ?filter=unread', function () {
    $user = makeNotifControllerUser();
    seedNotification($user->id, ['title' => 'read', 'is_read' => true]);
    seedNotification($user->id, ['title' => 'unread', 'is_read' => false]);

    $response = app(NotificationController::class)->index(
        notifRequest($user, query: ['filter' => 'unread']),
    );
    $payload = $response->getData(true);

    expect($payload['data'])->toHaveCount(1);
    expect($payload['data'][0]['title'])->toBe('unread');
});

it('index respects per_page, capped at 50', function () {
    $user = makeNotifControllerUser();
    foreach (range(1, 75) as $i) {
        seedNotification($user->id, ['title' => "n{$i}"]);
    }

    // Default
    $r1 = app(NotificationController::class)->index(notifRequest($user));
    expect($r1->getData(true)['meta']['per_page'])->toBe(20);

    // Custom (within cap)
    $r2 = app(NotificationController::class)->index(
        notifRequest($user, query: ['per_page' => 5]),
    );
    expect($r2->getData(true)['meta']['per_page'])->toBe(5);
    expect($r2->getData(true)['data'])->toHaveCount(5);

    // Cap (over the max)
    $r3 = app(NotificationController::class)->index(
        notifRequest($user, query: ['per_page' => 999]),
    );
    expect($r3->getData(true)['meta']['per_page'])->toBe(50);
});

it('index meta includes unread_count for badge sync', function () {
    $user = makeNotifControllerUser();
    seedNotification($user->id, ['is_read' => true]);
    seedNotification($user->id, ['is_read' => false]);
    seedNotification($user->id, ['is_read' => false]);

    $response = app(NotificationController::class)->index(notifRequest($user));

    expect($response->getData(true)['meta']['unread_count'])->toBe(2);
});

it('index renders icon_type for each underscore-cased type', function () {
    $user = makeNotifControllerUser();
    // Stagger created_at so latest()-ordering is deterministic.
    Carbon::setTestNow('2026-04-26 10:00:00');
    seedNotification($user->id, ['type' => 'interest_received']);
    Carbon::setTestNow('2026-04-26 10:00:01');
    seedNotification($user->id, ['type' => 'profile_view']);
    Carbon::setTestNow('2026-04-26 10:00:02');
    seedNotification($user->id, ['type' => 'system']);
    Carbon::setTestNow();

    $items = app(NotificationController::class)->index(notifRequest($user))->getData(true)['data'];

    // Latest first: system → profile_view → interest_received
    expect($items[0]['icon_type'])->toBe('bell');         // system
    expect($items[1]['icon_type'])->toBe('profile');      // profile_view
    expect($items[2]['icon_type'])->toBe('interest');     // interest_received
});

it('index resource shape carries from_profile_id when profile_id is set', function () {
    $user = makeNotifControllerUser();
    Carbon::setTestNow('2026-04-26 10:00:00');
    seedNotification($user->id, ['profile_id' => 555]);
    Carbon::setTestNow('2026-04-26 10:00:01');
    seedNotification($user->id, ['profile_id' => null]);
    Carbon::setTestNow();

    $items = app(NotificationController::class)->index(notifRequest($user))->getData(true)['data'];

    // Latest first: null-profile then 555.
    expect($items[0]['from_profile_id'])->toBeNull();
    expect($items[1]['from_profile_id'])->toBe(555);
});

/* ==================================================================
 |  GET /notifications/unread-count
 | ================================================================== */

it('unreadCount returns the count for the authenticated user only', function () {
    $owner = makeNotifControllerUser(8800);
    $stranger = makeNotifControllerUser(8801);

    seedNotification($owner->id, ['is_read' => false]);
    seedNotification($owner->id, ['is_read' => false]);
    seedNotification($owner->id, ['is_read' => true]);   // already read
    seedNotification($stranger->id, ['is_read' => false]);  // someone else's

    $response = app(NotificationController::class)->unreadCount(notifRequest($owner));

    expect($response->getStatusCode())->toBe(200);
    expect($response->getData(true)['data']['unread_count'])->toBe(2);
});

it('unreadCount returns 0 when no unread notifications', function () {
    $user = makeNotifControllerUser();
    seedNotification($user->id, ['is_read' => true]);

    $response = app(NotificationController::class)->unreadCount(notifRequest($user));

    expect($response->getData(true)['data']['unread_count'])->toBe(0);
});

/* ==================================================================
 |  POST /notifications/{notification}/read — mark single
 | ================================================================== */

it('markRead marks a single notification as read', function () {
    $user = makeNotifControllerUser();
    $notification = seedNotification($user->id, ['is_read' => false]);

    $response = app(NotificationController::class)->markRead(
        notifRequest($user, method: 'POST', path: "/api/v1/notifications/{$notification->id}/read"),
        $notification,
    );

    expect($response->getStatusCode())->toBe(200);
    expect($response->getData(true)['data']['notification'])->toBe([
        'id' => (int) $notification->id,
        'is_read' => true,
    ]);
    expect($notification->fresh()->is_read)->toBeTrue();
});

it('markRead returns 403 when notification belongs to a different user', function () {
    $owner = makeNotifControllerUser(8800);
    $stranger = makeNotifControllerUser(8801);
    $notification = seedNotification($owner->id);

    $response = app(NotificationController::class)->markRead(
        notifRequest($stranger, method: 'POST'),
        $notification,
    );

    expect($response->getStatusCode())->toBe(403);
    expect($response->getData(true)['error']['code'])->toBe('UNAUTHORIZED');
    expect($notification->fresh()->is_read)->toBeFalse();  // not flipped
});

it('markRead is idempotent — already-read notification still returns success', function () {
    $user = makeNotifControllerUser();
    $notification = seedNotification($user->id, ['is_read' => true]);

    $response = app(NotificationController::class)->markRead(
        notifRequest($user, method: 'POST'),
        $notification,
    );

    expect($response->getStatusCode())->toBe(200);
    expect($response->getData(true)['data']['notification']['is_read'])->toBeTrue();
});

/* ==================================================================
 |  POST /notifications/read-all — bulk mark
 | ================================================================== */

it('markAllRead marks every unread notification for the user', function () {
    $user = makeNotifControllerUser();
    seedNotification($user->id, ['is_read' => false]);
    seedNotification($user->id, ['is_read' => false]);
    seedNotification($user->id, ['is_read' => true]);  // already read — not counted

    $response = app(NotificationController::class)->markAllRead(notifRequest($user, method: 'POST'));

    expect($response->getStatusCode())->toBe(200);
    expect($response->getData(true)['data']['marked_read'])->toBe(2);
    expect(Notification::where('user_id', $user->id)->where('is_read', false)->count())->toBe(0);
});

it('markAllRead does not affect other users notifications', function () {
    $owner = makeNotifControllerUser(8800);
    $stranger = makeNotifControllerUser(8801);
    seedNotification($owner->id, ['is_read' => false]);
    $strangersUnread = seedNotification($stranger->id, ['is_read' => false]);

    app(NotificationController::class)->markAllRead(notifRequest($owner, method: 'POST'));

    // Stranger's notification stays unread.
    expect($strangersUnread->fresh()->is_read)->toBeFalse();
});

it('markAllRead returns 0 marked when inbox is already empty', function () {
    $user = makeNotifControllerUser();
    seedNotification($user->id, ['is_read' => true]);

    $response = app(NotificationController::class)->markAllRead(notifRequest($user, method: 'POST'));

    expect($response->getData(true)['data']['marked_read'])->toBe(0);
});

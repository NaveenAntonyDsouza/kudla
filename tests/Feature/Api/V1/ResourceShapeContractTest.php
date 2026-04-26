<?php

use App\Http\Resources\V1\NotificationResource;
use App\Http\Resources\V1\UserResource;
use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;

/*
|--------------------------------------------------------------------------
| Resource shape contract — UserResource + NotificationResource
|--------------------------------------------------------------------------
| Pins the JSON keys + types every Flutter consumer depends on. Drift on
| any of these breaks the mobile client.
|
| ProfileCardResource is pinned in ProfileCardResourceTest.php.
| ProfileResource     is pinned in ProfileResourceTest.php.
| PhotoResource       is pinned in PhotoResourceTest.php.
| DashboardResource   is a trivial pass-through (the controller builds
|                     the shape and DashboardServiceTest covers it).
|
| These tests use in-memory Eloquent instances (no DB) — pure shape
| transformation verification.
*/

/* ==================================================================
 |  UserResource
 | ================================================================== */

it('UserResource exposes exactly the 11 documented public keys', function () {
    $user = new User();
    $user->forceFill([
        'id' => 42,
        'name' => 'Naveen DSouza',
        'email' => 'naveen@example.com',
        'phone' => '9876543210',
        'role' => 'user',
        'branch_id' => 1,
        'is_active' => 1,
        'email_verified_at' => Carbon::parse('2026-04-20 10:00:00'),
        'phone_verified_at' => null,
        'last_login_at' => Carbon::parse('2026-04-26 12:34:56'),
        'created_at' => Carbon::parse('2026-04-01 00:00:00'),
    ]);

    $data = (new UserResource($user))->resolve();

    $expected = [
        'id', 'name', 'email', 'phone', 'role', 'branch_id', 'is_active',
        'email_verified_at', 'phone_verified_at', 'last_login_at', 'created_at',
    ];

    foreach ($expected as $key) {
        expect($data)->toHaveKey($key);
    }
    expect(array_keys($data))->toHaveCount(count($expected));
});

it('UserResource never leaks password or remember_token', function () {
    $user = new User();
    $user->forceFill([
        'id' => 42,
        'email' => 'x@example.com',
        'password' => '$2y$10$shouldnotleak',
        'remember_token' => 'shouldnotleak',
    ]);

    $data = (new UserResource($user))->resolve();

    expect($data)->not->toHaveKey('password');
    expect($data)->not->toHaveKey('remember_token');
});

it('UserResource is_active is a real bool, not a 1/0 int', function () {
    $user = new User();
    $user->forceFill(['id' => 42, 'is_active' => 1]);

    $data = (new UserResource($user))->resolve();

    expect($data['is_active'])->toBeBool()->toBeTrue();
});

it('UserResource emits ISO 8601 timestamps or null', function () {
    $user = new User();
    $user->forceFill([
        'id' => 42,
        'email_verified_at' => Carbon::parse('2026-04-20 10:00:00'),
        'phone_verified_at' => null,
        'last_login_at' => Carbon::parse('2026-04-26 12:34:56'),
        'created_at' => Carbon::parse('2026-04-01 00:00:00'),
    ]);

    $data = (new UserResource($user))->resolve();

    expect($data['email_verified_at'])->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/');
    expect($data['phone_verified_at'])->toBeNull();
    expect($data['last_login_at'])->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/');
    expect($data['created_at'])->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/');
});

/* ==================================================================
 |  NotificationResource
 | ================================================================== */

function buildNotification(array $overrides = []): Notification
{
    $n = new Notification();
    $n->exists = true;
    $n->forceFill(array_merge([
        'id' => 87,
        'type' => 'interest_received',
        'title' => 'New interest from Anita',
        'message' => 'Anita sent you an interest. Tap to view.',
        'data' => ['interest_id' => 42, 'sender_matri_id' => 'AM000200'],
        'is_read' => 0,
        'profile_id' => 200,
        'created_at' => Carbon::parse('2026-04-26 09:00:00'),
    ], $overrides));

    return $n;
}

it('NotificationResource exposes exactly the 9 documented keys', function () {
    $data = (new NotificationResource(buildNotification()))->resolve();

    $expected = [
        'id', 'type', 'title', 'message', 'data', 'is_read',
        'created_at', 'icon_type', 'from_profile_id',
    ];

    foreach ($expected as $key) {
        expect($data)->toHaveKey($key);
    }
    expect(array_keys($data))->toHaveCount(count($expected));
});

it('NotificationResource id is int + is_read is real bool', function () {
    $data = (new NotificationResource(buildNotification(['id' => 42, 'is_read' => 1])))->resolve();

    expect($data['id'])->toBeInt()->toBe(42);
    expect($data['is_read'])->toBeBool()->toBeTrue();
});

it('NotificationResource data is an array (defaults to [] when null)', function () {
    $withData = (new NotificationResource(buildNotification()))->resolve();
    $noData = (new NotificationResource(buildNotification(['data' => null])))->resolve();

    expect($withData['data'])->toBeArray()->toMatchArray([
        'interest_id' => 42,
        'sender_matri_id' => 'AM000200',
    ]);
    expect($noData['data'])->toBeArray()->toBeEmpty();
});

it('NotificationResource maps interest types to icon=interest', function () {
    foreach (['interest_received', 'interest_accepted', 'interest_declined'] as $type) {
        $data = (new NotificationResource(buildNotification(['type' => $type])))->resolve();
        expect($data['icon_type'])->toBe('interest');
    }
});

it('NotificationResource maps profile_view to icon=profile', function () {
    $data = (new NotificationResource(buildNotification(['type' => 'profile_view'])))->resolve();
    expect($data['icon_type'])->toBe('profile');
});

it('NotificationResource defaults unknown type to icon=bell', function () {
    $data = (new NotificationResource(buildNotification(['type' => 'something_new'])))->resolve();
    expect($data['icon_type'])->toBe('bell');
});

it('NotificationResource exposes from_profile_id when profile_id is set, null otherwise', function () {
    $with = (new NotificationResource(buildNotification(['profile_id' => 200])))->resolve();
    $without = (new NotificationResource(buildNotification(['profile_id' => null])))->resolve();

    expect($with['from_profile_id'])->toBe(200);
    expect($without['from_profile_id'])->toBeNull();
});

it('NotificationResource emits ISO 8601 created_at', function () {
    $data = (new NotificationResource(buildNotification()))->resolve();

    expect($data['created_at'])->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/');
});

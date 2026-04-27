<?php

use App\Http\Controllers\Api\V1\DeviceController;
use App\Models\Device;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\PersonalAccessToken;

/*
|--------------------------------------------------------------------------
| DeviceController — register + revoke
|--------------------------------------------------------------------------
| Pins the anti-hijack contract (Phase 2a security audit, Vuln 2):
| keying register() on (user_id, fcm_token) — not fcm_token alone — so a
| stolen FCM token can't be replayed against /devices to take ownership of
| another user's row, redirect their pushes, and revoke their Sanctum
| session via DELETE /devices/{id}.
|
| Inline Schema::create mirrors the production migration AFTER the
| 2026_04_27_220500_fix_devices_unique_to_user_fcm_pair migration:
| composite unique on (user_id, fcm_token) instead of global unique on
| fcm_token.
*/

function buildDeviceUser(int $id = 9100): User
{
    $u = new User();
    $u->exists = true;
    $u->forceFill([
        'id' => $id,
        'email' => "dev{$id}@e.com",
        'is_active' => true,
    ]);

    return $u;
}

function deviceRequest(User $user, array $body, ?int $tokenId = null): Request
{
    $r = Request::create('/api/v1/devices', 'POST', $body);
    $r->setUserResolver(fn () => $user);

    // Stub currentAccessToken() to return a fake PAT so the controller
    // can record personal_access_token_id without going through Sanctum.
    if ($tokenId !== null) {
        $token = new PersonalAccessToken();
        $token->forceFill(['id' => $tokenId, 'tokenable_id' => $user->id]);
        $user->withAccessToken($token);
    }

    return $r;
}

beforeEach(function () {
    if (! Schema::hasTable('devices')) {
        Schema::create('devices', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->unsignedBigInteger('personal_access_token_id')->nullable();
            $t->string('fcm_token', 255);
            $t->string('platform', 10);
            $t->string('device_model', 100)->nullable();
            $t->string('app_version', 20)->nullable();
            $t->string('os_version', 20)->nullable();
            $t->string('locale', 10)->default('en');
            $t->timestamp('last_seen_at')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->index('user_id');
            $t->unique(['user_id', 'fcm_token'], 'devices_user_fcm_unique');
        });
    } else {
        Device::query()->delete();
    }
});

afterAll(function () {
    Schema::dropIfExists('devices');
});

it('registers a new device row for an authenticated user', function () {
    $user = buildDeviceUser(9100);
    $controller = app(DeviceController::class);

    $response = $controller->register(deviceRequest($user, [
        'fcm_token' => 'tok-A',
        'platform' => 'android',
        'app_version' => '1.0.0',
    ], tokenId: 1));

    expect($response->getStatusCode())->toBe(201);
    $deviceId = $response->getData(true)['data']['device_id'];

    $row = Device::find($deviceId);
    expect($row->user_id)->toBe(9100);
    expect($row->fcm_token)->toBe('tok-A');
    expect($row->is_active)->toBeTrue();
});

it('is idempotent for the same (user, fcm_token) pair — re-register updates the same row', function () {
    $user = buildDeviceUser(9100);
    $controller = app(DeviceController::class);

    $first = $controller->register(deviceRequest($user, [
        'fcm_token' => 'tok-A',
        'platform' => 'android',
    ], tokenId: 1));

    // FCM rotated app version — re-register with a fresh token id.
    $second = $controller->register(deviceRequest($user, [
        'fcm_token' => 'tok-A',
        'platform' => 'android',
        'app_version' => '1.0.1',
    ], tokenId: 2));

    $firstId = $first->getData(true)['data']['device_id'];
    $secondId = $second->getData(true)['data']['device_id'];

    expect($firstId)->toBe($secondId);
    expect(Device::count())->toBe(1);
    $row = Device::find($firstId);
    expect($row->app_version)->toBe('1.0.1');
    expect($row->personal_access_token_id)->toBe(2);
});

/* ==================================================================
 |  THE SECURITY REGRESSION — anti-hijack guard
 | ================================================================== */

it('does NOT rewrite ownership when a different user registers an existing fcm_token (anti-hijack)', function () {
    // Acceptance gate: Phase 2a security audit Vuln 2.
    // User A registers their token. Attacker (User B) submits the SAME
    // token from their own session. Expected: User A's row is set
    // inactive (legitimate device hand-off semantics), and User B gets
    // a separate row owned by them. CRITICAL: User A's row must NOT be
    // re-keyed to user_id=B (the pre-fix bug).
    $userA = buildDeviceUser(9100);
    $userB = buildDeviceUser(9200);
    $controller = app(DeviceController::class);

    $controller->register(deviceRequest($userA, [
        'fcm_token' => 'shared-tok',
        'platform' => 'android',
    ], tokenId: 1));

    $controller->register(deviceRequest($userB, [
        'fcm_token' => 'shared-tok',
        'platform' => 'android',
    ], tokenId: 2));

    // Two rows, distinct user_ids. User A's row was deactivated, not hijacked.
    expect(Device::count())->toBe(2);

    $aRow = Device::where('user_id', 9100)->first();
    expect($aRow)->not->toBeNull();
    expect($aRow->user_id)->toBe(9100);  // ← not rewritten to 9200
    expect($aRow->is_active)->toBeFalse();  // deactivated by the controller pre-pass

    $bRow = Device::where('user_id', 9200)->first();
    expect($bRow)->not->toBeNull();
    expect($bRow->is_active)->toBeTrue();
    expect($bRow->personal_access_token_id)->toBe(2);

    // The rows are distinct.
    expect($aRow->id)->not->toBe($bRow->id);
});

it('preserves user A active status when user B registers a DIFFERENT fcm_token', function () {
    // Sanity: the deactivation pre-pass must only fire on token-collision,
    // not blanket-deactivate every prior row.
    $userA = buildDeviceUser(9100);
    $userB = buildDeviceUser(9200);
    $controller = app(DeviceController::class);

    $controller->register(deviceRequest($userA, [
        'fcm_token' => 'tok-A',
        'platform' => 'android',
    ], tokenId: 1));

    $controller->register(deviceRequest($userB, [
        'fcm_token' => 'tok-B',
        'platform' => 'android',
    ], tokenId: 2));

    expect(Device::where('user_id', 9100)->where('is_active', true)->count())->toBe(1);
    expect(Device::where('user_id', 9200)->where('is_active', true)->count())->toBe(1);
});

# Step 15 — Device Registration (FCM Token Store)

## Goal
`POST /api/v1/devices` stores the Flutter app's FCM token so we can push notifications. Called after every login + on FCM token refresh.

## Prerequisites
- [ ] [step-14 — me/logout](step-14-me-logout.md) complete

## Procedure

### 1. Create migration

```bash
php artisan make:migration create_devices_table
```

Edit:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->onDelete('cascade');
            $t->foreignId('personal_access_token_id')->nullable()
                ->constrained('personal_access_tokens')
                ->onDelete('set null');
            $t->string('fcm_token', 255)->unique();
            $t->string('platform', 10);              // android | ios
            $t->string('device_model', 100)->nullable();
            $t->string('app_version', 20)->nullable();
            $t->string('os_version', 20)->nullable();
            $t->string('locale', 10)->default('en');
            $t->timestamp('last_seen_at')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();

            $t->index('user_id');
            $t->index(['user_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
```

Run:
```bash
php artisan migrate
```

### 2. Create model

```bash
php artisan make:model Device
```

Edit `app/Models/Device.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Device extends Model
{
    protected $fillable = [
        'user_id', 'personal_access_token_id', 'fcm_token',
        'platform', 'device_model', 'app_version', 'os_version',
        'locale', 'last_seen_at', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_seen_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

### 3. Add relationship to User

In `app/Models/User.php`:

```php
public function devices(): \Illuminate\Database\Eloquent\Relations\HasMany
{
    return $this->hasMany(Device::class);
}
```

### 4. Create DeviceController

Create `app/Http/Controllers/Api/V1/DeviceController.php`:

```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Responses\ApiResponse;
use App\Models\Device;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceController extends BaseApiController
{
    /**
     * Register or refresh an FCM device token.
     *
     * Idempotent on fcm_token — if the same token re-registers,
     * we update `last_seen_at` in place.
     *
     * @group Devices
     * @authenticated
     */
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'fcm_token' => 'required|string|max:255',
            'platform' => 'required|in:android,ios',
            'device_model' => 'nullable|string|max:100',
            'app_version' => 'nullable|string|max:20',
            'os_version' => 'nullable|string|max:20',
            'locale' => 'nullable|string|max:10',
        ]);

        $user = $request->user();
        $currentAccessTokenId = $user->currentAccessToken()?->id;

        $device = Device::updateOrCreate(
            ['fcm_token' => $data['fcm_token']],
            [
                'user_id' => $user->id,
                'personal_access_token_id' => $currentAccessTokenId,
                'platform' => $data['platform'],
                'device_model' => $data['device_model'] ?? null,
                'app_version' => $data['app_version'] ?? null,
                'os_version' => $data['os_version'] ?? null,
                'locale' => $data['locale'] ?? 'en',
                'last_seen_at' => now(),
                'is_active' => true,
            ],
        );

        return ApiResponse::created(['device_id' => $device->id]);
    }

    /**
     * Revoke a device (user-initiated from settings → active sessions).
     * Also revokes the associated Sanctum token.
     *
     * @group Devices
     * @authenticated
     */
    public function revoke(Request $request, Device $device): JsonResponse
    {
        abort_if($device->user_id !== $request->user()->id, 403);

        if ($device->personal_access_token_id) {
            \Laravel\Sanctum\PersonalAccessToken::find($device->personal_access_token_id)?->delete();
        }
        $device->update(['is_active' => false]);

        return ApiResponse::ok(['revoked' => true]);
    }
}
```

### 5. Register routes

```php
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/devices', [\App\Http\Controllers\Api\V1\DeviceController::class, 'register']);
    Route::delete('/devices/{device}', [\App\Http\Controllers\Api\V1\DeviceController::class, 'revoke']);
});
```

### 6. Test

```bash
TOKEN="<from-login>"

curl -X POST http://localhost:8000/api/v1/devices \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{
    "fcm_token":"dpU-k3zTEST_TOKEN_123",
    "platform":"android",
    "device_model":"Pixel 8 Pro",
    "app_version":"1.0.0",
    "os_version":"14"
  }'
# Expect: {"success":true,"data":{"device_id":1}}

# Re-register same token — should update, not create duplicate
curl -X POST http://localhost:8000/api/v1/devices \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"fcm_token":"dpU-k3zTEST_TOKEN_123","platform":"android"}'
# Same device_id returned
```

### 7. Pest test

Create `tests/Feature/Api/V1/DeviceRegistrationTest.php`:

```php
<?php

use App\Models\Device;
use App\Models\User;
use function Pest\Laravel\postJson;

it('registers a device', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $response = postJson('/api/v1/devices', [
        'fcm_token' => 'fcm_12345',
        'platform' => 'android',
        'device_model' => 'Pixel',
        'app_version' => '1.0.0',
    ], ['Authorization' => "Bearer $token"]);

    $response->assertCreated()->assertJsonStructure(['data' => ['device_id']]);
    expect(Device::where('fcm_token', 'fcm_12345')->exists())->toBeTrue();
});

it('is idempotent on duplicate fcm_token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $r1 = postJson('/api/v1/devices', [
        'fcm_token' => 'fcm_same',
        'platform' => 'android',
    ], ['Authorization' => "Bearer $token"]);

    $r2 = postJson('/api/v1/devices', [
        'fcm_token' => 'fcm_same',
        'platform' => 'android',
    ], ['Authorization' => "Bearer $token"]);

    expect($r1->json('data.device_id'))->toBe($r2->json('data.device_id'));
    expect(Device::where('fcm_token', 'fcm_same')->count())->toBe(1);
});
```

## Verification

- [ ] Migration runs
- [ ] Registering a new device creates a row
- [ ] Registering same `fcm_token` updates in place (same id returned)
- [ ] Revoking device also revokes the Sanctum token
- [ ] Tests pass

## Commit

```bash
git add database/migrations/ app/Models/Device.php app/Models/User.php app/Http/Controllers/Api/V1/DeviceController.php routes/api.php tests/Feature/Api/V1/DeviceRegistrationTest.php
git commit -m "phase-2a wk-02: step-15 device registration for FCM tokens"
```

## Next step
→ [week-02-acceptance.md](week-02-acceptance.md)

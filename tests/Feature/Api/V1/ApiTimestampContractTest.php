<?php

use App\Http\Resources\V1\NotificationResource;
use App\Models\Notification;
use Illuminate\Support\Carbon;

/*
|--------------------------------------------------------------------------
| API timestamp contract: always UTC ISO-8601
|--------------------------------------------------------------------------
| The mobile API must serialize timestamps as UTC ISO-8601 (offset
| +00:00) REGARDLESS of the app's display timezone. The web + admin
| run in Asia/Kolkata (config/app.php timezone), but the API is a
| data contract the Flutter client localises on-device.
|
| This guards against a repeat of the May-2026 incident: switching
| app.timezone to Asia/Kolkata silently flipped every API
| ->toIso8601String() from +00:00 to +05:30 — same instant, but a
| changed wire format the Phase 2a app + Bruno smokes were built
| against. The fix was the ->toUtcIso() macro (always UTC); these
| tests fail loudly if anyone reverts a resource to ->toIso8601String()
| or changes the macro to honour the display timezone.
|
| Deliberately dependency-light: the macro tests are pure Carbon, and
| the resource test builds an in-memory model (no DB / auth / HTTP),
| so this stays fast and non-flaky.
*/

it('toUtcIso converts an IST timestamp to UTC +00:00 and does not mutate the original', function () {
    $ist = Carbon::parse('2026-05-25 15:24:20', 'Asia/Kolkata');

    expect($ist->toUtcIso())->toBe('2026-05-25T09:54:20+00:00');

    // copy() inside the macro means the source instance keeps its tz.
    expect($ist->format('H:i'))->toBe('15:24');
    expect($ist->timezoneName)->toBe('Asia/Kolkata');
});

it('toUtcIso on an already-UTC timestamp stays +00:00', function () {
    $utc = Carbon::parse('2026-05-25 09:54:20', 'UTC');

    expect($utc->toUtcIso())->toBe('2026-05-25T09:54:20+00:00');
});

it('NotificationResource emits created_at as UTC even when the app runs in IST', function () {
    // Simulate the production reality: app.timezone is IST, so an
    // Eloquent date-cast attribute is an IST-zoned Carbon.
    config(['app.timezone' => 'Asia/Kolkata']);

    $notification = new Notification();
    $notification->forceFill([
        'id' => 1,
        'type' => 'photo_request',
        'title' => 'Photo Request',
        'message' => 'Someone requested your photos.',
        'data' => [],
        'is_read' => false,
        'profile_id' => 42,
        'created_at' => Carbon::parse('2026-05-25 15:24:20', 'Asia/Kolkata'),
    ]);

    $arr = (new NotificationResource($notification))->toArray(request());

    // The contract: UTC offset, not +05:30.
    expect($arr['created_at'])->toBe('2026-05-25T09:54:20+00:00');

    // Bonus guard: a notification type added after the ENUM->VARCHAR
    // migration flows through untouched (would've thrown pre-migration).
    expect($arr['type'])->toBe('photo_request');
});

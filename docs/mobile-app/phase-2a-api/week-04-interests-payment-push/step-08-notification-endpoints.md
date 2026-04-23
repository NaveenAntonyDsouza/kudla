# Step 8 — Notification Endpoints

## Goal
- `GET /api/v1/notifications` — paginated list
- `POST /api/v1/notifications/{id}/read` — mark read
- `POST /api/v1/notifications/read-all`
- `GET /api/v1/notifications/unread-count`

## Procedure

### 1. `NotificationController`

```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Responses\ApiResponse;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends BaseApiController
{
    /** @authenticated @group Notifications */
    public function index(Request $request): JsonResponse
    {
        $query = Notification::where('user_id', $request->user()->id);
        if ($request->query('filter') === 'unread') {
            $query->where('is_read', false);
        }

        $paginator = $query->latest()->paginate(20);
        $items = $paginator->getCollection()->map(fn ($n) => [
            'id' => $n->id,
            'type' => $n->type,
            'title' => $n->title,
            'body' => $n->body,
            'data' => $n->data,
            'is_read' => (bool) $n->is_read,
            'created_at' => $n->created_at->toIso8601String(),
            'icon_type' => $this->iconType($n->type),
        ]);

        return ApiResponse::ok($items->values(), [
            'page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
            'unread_count' => Notification::where('user_id', $request->user()->id)->where('is_read', false)->count(),
        ]);
    }

    /** @authenticated @group Notifications */
    public function markRead(Request $request, Notification $notification): JsonResponse
    {
        abort_if($notification->user_id !== $request->user()->id, 403);
        $notification->update(['is_read' => true]);
        return ApiResponse::ok(['notification' => ['id' => $notification->id, 'is_read' => true]]);
    }

    /** @authenticated @group Notifications */
    public function markAllRead(Request $request): JsonResponse
    {
        $count = Notification::where('user_id', $request->user()->id)->where('is_read', false)->update(['is_read' => true]);
        return ApiResponse::ok(['marked_read' => $count]);
    }

    /** @authenticated @group Notifications */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = Notification::where('user_id', $request->user()->id)->where('is_read', false)->count();
        return ApiResponse::ok(['unread_count' => $count]);
    }

    private function iconType(string $type): string
    {
        return match (true) {
            str_starts_with($type, 'interest.') => 'interest',
            str_starts_with($type, 'photo_request.') => 'photo',
            str_starts_with($type, 'profile.') => 'profile',
            str_starts_with($type, 'match.') => 'match',
            str_starts_with($type, 'membership.') => 'membership',
            default => 'bell',
        };
    }
}
```

### 2. Routes

```php
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/notifications', [\App\Http\Controllers\Api\V1\NotificationController::class, 'index']);
    Route::post('/notifications/{notification}/read', [\App\Http\Controllers\Api\V1\NotificationController::class, 'markRead']);
    Route::post('/notifications/read-all', [\App\Http\Controllers\Api\V1\NotificationController::class, 'markAllRead']);
    Route::get('/notifications/unread-count', [\App\Http\Controllers\Api\V1\NotificationController::class, 'unreadCount']);
});
```

## Verification
- [ ] All 4 endpoints work
- [ ] Mark-all-read updates in bulk
- [ ] Cannot mark someone else's notifications read

## Commit
```bash
git commit -am "phase-2a wk-04: step-08 notification endpoints"
```

## Next step
→ [step-09-shortlist-views.md](step-09-shortlist-views.md)

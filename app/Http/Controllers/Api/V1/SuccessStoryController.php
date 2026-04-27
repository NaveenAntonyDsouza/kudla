<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Responses\ApiResponse;
use App\Models\Testimonial;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Success-stories (a.k.a. testimonials) endpoints.
 *
 *   GET  /api/v1/success-stories      paginated, public, approved-only
 *   POST /api/v1/success-stories      auth, multipart, lands as is_visible=false
 *
 * Backed by the existing `testimonials` table (NOT a SuccessStory
 * model — the step-13 doc has the wrong name). Visibility flag is
 * `is_visible`, submitter is `submitted_by_user_id` — both per the
 * actual schema, not the doc.
 *
 * Submitted stories ALWAYS land in pending state — admin reviews
 * via the existing Filament screen before they appear in the public
 * feed.
 *
 * Reference: docs/mobile-app/phase-2a-api/week-04-interests-payment-push/step-13-engagement-public.md
 */
class SuccessStoryController extends BaseApiController
{
    private const DEFAULT_PER_PAGE = 10;

    private const MAX_PER_PAGE = 30;

    /** Storage disk for uploaded story photos. */
    private const DISK = 'public';

    /** Storage prefix for uploaded story photos. */
    private const STORAGE_PREFIX = 'success-stories';

    /* ==================================================================
     |  GET /success-stories
     | ================================================================== */

    /**
     * Public feed of approved success stories, latest weddings first.
     *
     * @group Success Stories
     *
     * @queryParam page integer Optional. Default 1.
     * @queryParam per_page integer Optional. 1-30. Default 10.
     *
     * @response 200 scenario="success" {
     *   "success": true,
     *   "data": [{"id": 1, "couple_names": "Anita & Ravi", "story": "...", "photo_url": "https://.../photo.jpg", "wedding_date": "2026-02-14", "location": "Mumbai"}],
     *   "meta": {"page": 1, "per_page": 10, "total": 1, "last_page": 1}
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $this->resolvePerPage($request);
        $paginator = $this->paginateApproved($perPage);

        $items = collect($paginator->items())
            ->map(fn (Testimonial $t) => $this->shape($t))
            ->all();

        return ApiResponse::ok($items, [
            'page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
        ]);
    }

    /* ==================================================================
     |  POST /success-stories
     | ================================================================== */

    /**
     * Submit a success story. Lands as `is_visible=false` — admin
     * approval gates publication.
     *
     * @authenticated
     *
     * @group Success Stories
     *
     * @bodyParam couple_names string required Max 200.
     * @bodyParam story string required 20-2000 chars.
     * @bodyParam wedding_date date Optional. ISO date, ≤ today.
     * @bodyParam location string Optional. Max 100.
     * @bodyParam photo file Optional. JPG/PNG/WEBP, max 3 MB.
     *
     * @response 201 scenario="success" {
     *   "success": true,
     *   "data": {"story_id": 42, "status": "pending", "message": "Thanks! ..."}
     * }
     * @response 422 scenario="validation" {"success": false, "error": {"code": "VALIDATION_FAILED", ...}}
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'couple_names' => 'required|string|max:200',
            'story' => 'required|string|min:20|max:2000',
            'wedding_date' => 'nullable|date|before_or_equal:today',
            'location' => 'nullable|string|max:100',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:3072',
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store(self::STORAGE_PREFIX, self::DISK);
        }

        $story = Testimonial::create([
            'couple_names' => $data['couple_names'],
            'story' => $data['story'],
            'wedding_date' => $data['wedding_date'] ?? null,
            'location' => $data['location'] ?? null,
            'photo_url' => $photoPath,
            'submitted_by_user_id' => $request->user()->id,
            'is_visible' => false,  // admin must approve
            'display_order' => 0,
        ]);

        return ApiResponse::created([
            'story_id' => (int) $story->id,
            'status' => 'pending',
            'message' => "Thanks! We'll review and publish soon.",
        ]);
    }

    /* ==================================================================
     |  Test seams
     | ================================================================== */

    protected function paginateApproved(int $perPage): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return Testimonial::query()
            ->where('is_visible', true)
            ->orderBy('display_order')
            ->orderByDesc('wedding_date')
            ->paginate($perPage);
    }

    /* ==================================================================
     |  Helpers
     | ================================================================== */

    /**
     * @return array<string,mixed>
     */
    private function shape(Testimonial $t): array
    {
        return [
            'id' => (int) $t->id,
            'couple_names' => (string) $t->couple_names,
            'story' => (string) $t->story,
            'photo_url' => $t->photo_url ? Storage::disk(self::DISK)->url($t->photo_url) : null,
            'wedding_date' => $t->wedding_date?->toDateString(),
            'location' => $t->location,
        ];
    }

    private function resolvePerPage(Request $request): int
    {
        return max(1, min(self::MAX_PER_PAGE, (int) $request->query('per_page', self::DEFAULT_PER_PAGE)));
    }
}

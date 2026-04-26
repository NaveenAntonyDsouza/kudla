<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Responses\ApiResponse;
use App\Models\StaticPage;
use Illuminate\Http\JsonResponse;

/**
 * Public static-page reader.
 *
 *   GET /api/v1/static-pages/{slug}
 *
 * Backed by the StaticPage model — DB-driven CMS pages (about, terms,
 * privacy, refund-policy, etc.) edited by admin in Filament.
 *
 * Variable substitution ({{ app_name }}, {{ email }}, {{ current_year }})
 * is delegated to StaticPage::getRenderedContentAttribute so the API
 * and web flows render the same final HTML — no copy-paste of the
 * substitution map.
 *
 * Lookup is cached for 1h via StaticPage::getBySlug; admin saves bust
 * the cache via the model's clearCache hook.
 *
 * Reference: docs/mobile-app/phase-2a-api/week-04-interests-payment-push/step-13-engagement-public.md
 */
class StaticPageController extends BaseApiController
{
    /**
     * @group Static Pages
     *
     * @urlParam slug string required Page slug (lowercase, hyphens only).
     *
     * @response 200 scenario="success" {
     *   "success": true,
     *   "data": {"slug": "about", "title": "About Us", "content_html": "<p>...</p>", "meta_title": "...", "meta_description": "...", "updated_at": "2026-04-26T..."}
     * }
     * @response 404 scenario="not-found" {"success": false, "error": {"code": "NOT_FOUND", "message": "Page not found."}}
     */
    public function show(string $slug): JsonResponse
    {
        $page = $this->findPageBySlug($slug);
        if (! $page) {
            return ApiResponse::error('NOT_FOUND', 'Page not found.', null, 404);
        }

        return ApiResponse::ok([
            'slug' => (string) $page->slug,
            'title' => (string) $page->title,
            'content_html' => $page->rendered_content,
            'meta_title' => $page->meta_title,
            'meta_description' => $page->meta_description,
            'updated_at' => $page->updated_at?->toIso8601String(),
        ]);
    }

    /* ==================================================================
     |  Test seam
     | ================================================================== */

    protected function findPageBySlug(string $slug): ?StaticPage
    {
        return StaticPage::getBySlug($slug);
    }
}

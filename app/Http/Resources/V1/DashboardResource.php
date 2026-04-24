<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Dashboard payload wrapper. Flutter calls this once per app launch.
 *
 * The controller (step-3 of this week) assembles a fully-shaped array
 * — CTA block, stats, 5 horizontal carousels (ProfileCardResource lists),
 * and discover teasers — then passes it to `DashboardResource::make(...)`.
 *
 * This class is intentionally a trivial pass-through. All shape logic
 * lives in DashboardController::show so the UI-safe checklist is
 * verified at that single assembly point, not duplicated here.
 *
 * Design reference: docs/mobile-app/design/04-profile-api.md §4.2
 */
class DashboardResource extends JsonResource
{
    public function toArray($request): array
    {
        // The controller pre-builds the full shape; we just pass through.
        return is_array($this->resource) ? $this->resource : (array) $this->resource;
    }
}

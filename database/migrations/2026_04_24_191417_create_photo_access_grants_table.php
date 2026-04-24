<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * photo_access_grants — binary per-viewer photo access.
 *
 * Tracks "grantee can see grantor's gated / blurred photos." Created by
 * App\Services\PhotoAccessService::grant(), typically in response to a
 * PhotoRequest being approved (wired in step-11). Consumed by
 * PhotoResource::shouldBlurFor when evaluating the final blur decision
 * for gated_premium / blur_non_premium rules (wired in step-9 onwards).
 *
 * Distinct from `photo_requests` — that table owns the
 * request→approve/ignore LIFECYCLE (pending → approved | ignored).
 * `photo_access_grants` owns the RESULT (boolean "has access").
 * Approving a request creates a grant; revoking clears it.
 *
 * Design reference:
 *   docs/mobile-app/phase-2a-api/week-03-profiles-photos-search/step-08-photo-access-grants.md
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('photo_access_grants', function (Blueprint $table) {
            $table->id();

            // The profile whose photos are being unlocked (grantor),
            // and the profile who now sees them (grantee).
            $table->foreignId('grantor_profile_id')
                ->constrained('profiles')
                ->cascadeOnDelete();
            $table->foreignId('grantee_profile_id')
                ->constrained('profiles')
                ->cascadeOnDelete();

            // When the grant was created. useCurrent() means INSERTs that
            // omit this column get the DB's current timestamp as the default.
            $table->timestamp('granted_at')->useCurrent();

            // One grant per (grantor, grantee) pair — idempotent grants.
            $table->unique(['grantor_profile_id', 'grantee_profile_id']);

            // Lookup pattern from the viewer's perspective:
            // "which profiles have granted me access?" — scan by grantee.
            $table->index('grantee_profile_id');

            // No created_at/updated_at — PhotoAccessGrant is a thin join
            // row, not an audit record. Use granted_at as the only time.
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('photo_access_grants');
    }
};

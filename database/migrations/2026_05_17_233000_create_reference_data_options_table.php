<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-row admin-editable dropdown options with Active / Inactive toggle.
 *
 * Replaces the inline arrays in config/reference_data.php for ~25 flat
 * lists (religion, complexion, body_type, blood_group, family_status,
 * languages, hobbies, etc.). Each option is its own row, so admins can:
 *
 *   • flip a single value's `is_active` to false → that value disappears
 *     from dropdowns for NEW selections without losing any historical
 *     data on existing profiles
 *   • change the display order (`sort_order`)
 *   • add new values (e.g. add "Marathi" to languages) without a deploy
 *
 * SEPARATE from the existing JSON-in-site_settings path (used by the
 * textarea ReferenceDataEditor for GROUPED lists — educational
 * qualifications, occupation categories, country/state, etc.). Those
 * lists have nested structure that a flat options table can't model;
 * they stay on the existing path. GatewayConfigProvider merges both
 * at boot — table override wins for categories that have rows here.
 *
 * Schema notes:
 *   • Flat (no separate categories table) — the category column is
 *     just a string. ~25 categories × ~10 values each ≈ 250 rows.
 *     Way under any scale we need to worry about.
 *   • Unique on (category, value) — makes the seeder idempotent and
 *     prevents the admin form from saving duplicates.
 *   • Indexed on (category, is_active, sort_order) — every dropdown
 *     read uses this exact filter + order.
 *   • value is the string stored on profiles (e.g. "Hindu", "O+ve").
 *     label is optional — set it to show a different display string
 *     from what's stored, useful for i18n later. We use it cosmetically
 *     today; defaults to value when blank.
 *   • is_active gates the option from new dropdowns. When false, the
 *     value still appears on profiles that already have it; view-level
 *     code adds the user's current value to the rendered <select> even
 *     if the option is inactive (preserves UX so the user doesn't see
 *     their value disappear).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reference_data_options', function (Blueprint $t) {
            $t->id();
            $t->string('category', 60);
            $t->string('value', 200);
            $t->string('label', 250)->nullable();
            $t->integer('sort_order')->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();

            $t->unique(['category', 'value']);
            $t->index(['category', 'is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reference_data_options');
    }
};

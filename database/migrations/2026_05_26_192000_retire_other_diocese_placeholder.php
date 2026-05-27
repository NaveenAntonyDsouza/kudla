<?php

use App\Models\ReferenceDataOption;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Retire the literal "Other" diocese row. It's redundant: the cascade
     * dropdowns add a client-side "Other (not listed)" escape on every list, so
     * a member can always enter an unlisted diocese without it. Removing it:
     *   - makes the active diocese count exactly 174 (132 Latin + 31 Syro-Malabar
     *     + 11 Syro-Malankara), matching the authoritative total, and
     *   - avoids a duplicate "Other" showing in the Roman Catholic (Latin) list.
     * Deactivated (not deleted) so any stored diocese="Other" value is preserved
     * and it's reversible.
     */
    public function up(): void
    {
        ReferenceDataOption::where('category', 'diocese')
            ->where('value', 'Other')
            ->update(['is_active' => false]);
    }

    public function down(): void
    {
        ReferenceDataOption::where('category', 'diocese')
            ->where('value', 'Other')
            ->update(['is_active' => true]);
    }
};

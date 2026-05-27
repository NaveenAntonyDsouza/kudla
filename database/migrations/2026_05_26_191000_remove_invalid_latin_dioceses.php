<?php

use App\Models\ReferenceDataOption;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Remove diocese entries that aren't valid current Latin Catholic dioceses
     * (per authoritative research), bringing Latin to the canonical 132:
     *   - Durgapur    — a CNI (Protestant) diocese, not Catholic
     *   - Gangtok     — covered by the Catholic Diocese of Darjeeling
     *   - Surat       — covered by the Diocese of Baroda
     *   - Silchar     — renamed to the Diocese of Aizawl (1996)
     *   - Jodhpur     — covered by the Diocese of Ajmer
     *   - Chengalpattu — duplicate of Chingleput (keep Chingleput)
     *
     * Deactivated (not deleted) so it's reversible and any stored value on a
     * profile is preserved. Hard-delete later from the admin if desired.
     */
    public function up(): void
    {
        ReferenceDataOption::where('category', 'diocese')
            ->whereIn('value', ['Durgapur', 'Gangtok', 'Surat', 'Silchar', 'Jodhpur', 'Chengalpattu'])
            ->update(['is_active' => false]);
    }

    public function down(): void
    {
        ReferenceDataOption::where('category', 'diocese')
            ->whereIn('value', ['Durgapur', 'Gangtok', 'Surat', 'Silchar', 'Jodhpur', 'Chengalpattu'])
            ->update(['is_active' => true]);
    }
};

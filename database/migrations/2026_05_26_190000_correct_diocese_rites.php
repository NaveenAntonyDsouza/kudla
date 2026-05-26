<?php

use App\Models\ReferenceDataOption;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Correct the diocese rite tagging after an authoritative cross-check
     * against the Catholic Church in India's eparchy lists (catholic-hierarchy
     * + Wikipedia). The initial best-effort seed left these Eastern-rite
     * eparchies tagged Latin:
     *   - Syro-Malabar: Thuckalay, Faridabad, Gorakhpur, Kalyan, Rajkot, Palghat
     *   - Syro-Malankara: Tiruvalla
     * (Trivandrum is left Latin — it has both a Latin archdiocese and a
     * Syro-Malankara major archeparchy; the separate "Syro-Malankara" list
     * entry covers the Malankara side.) Idempotent; admin-editable afterwards.
     */
    public function up(): void
    {
        $toSyroMalabar = ['Thuckalay', 'Faridabad', 'Gorakhpur', 'Kalyan', 'Rajkot', 'Palghat'];
        $toSyroMalankara = ['Tiruvalla'];

        ReferenceDataOption::where('category', 'diocese')
            ->whereIn('value', $toSyroMalabar)
            ->update(['cascade_group' => 'Syro-Malabar']);

        ReferenceDataOption::where('category', 'diocese')
            ->whereIn('value', $toSyroMalankara)
            ->update(['cascade_group' => 'Syro-Malankara']);
    }

    public function down(): void
    {
        $reverted = ['Thuckalay', 'Faridabad', 'Gorakhpur', 'Kalyan', 'Rajkot', 'Palghat', 'Tiruvalla'];

        ReferenceDataOption::where('category', 'diocese')
            ->whereIn('value', $reverted)
            ->update(['cascade_group' => 'Latin']);
    }
};

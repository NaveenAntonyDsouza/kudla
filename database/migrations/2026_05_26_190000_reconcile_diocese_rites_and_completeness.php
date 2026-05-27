<?php

use App\Models\ReferenceDataOption;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Bring the diocese list closer to the authoritative Catholic-dioceses-of-
     * India set (132 Latin / 31 Syro-Malabar / 11 Syro-Malankara). Only
     * high-confidence changes — eparchies with NO Latin counterpart, plus
     * clearly-missing dioceses. Dual-rite cities (Trivandrum, Rajkot, Gorakhpur)
     * are intentionally left Latin since a flat single-name list can't hold both
     * jurisdictions; admins can re-tag them via Dropdown Options → Rite. The
     * "Other (not listed)" escape covers anything still absent.
     */
    public function up(): void
    {
        $diocese = fn () => ReferenceDataOption::where('category', 'diocese');

        // 1) Re-tag Eastern-rite eparchies that have no Latin counterpart
        //    (they were defaulted to Latin).
        $diocese()->whereIn('value', ['Kalyan', 'Faridabad', 'Palghat'])
            ->update(['cascade_group' => 'Syro-Malabar']);
        $diocese()->where('value', 'Tiruvalla')
            ->update(['cascade_group' => 'Syro-Malankara']);

        // 2) Replace the placeholder "Syro-Malankara" entry with the real
        //    Malankara Major Archeparchy of Trivandrum (kept distinct from the
        //    Latin Trivandrum diocese).
        $diocese()->where('value', 'Syro-Malankara')
            ->update(['value' => 'Trivandrum (Malankara)', 'cascade_group' => 'Syro-Malankara']);

        // 3) De-duplicate: keep the official Catholic name "Poona", hide "Pune".
        $diocese()->where('value', 'Pune')->update(['is_active' => false]);

        // 4) Add clearly-missing eparchies / dioceses, rite-tagged.
        $add = [
            'Syro-Malabar' => [
                'Shamshabad', 'Hosur', 'Ramanathapuram', 'Tellicherry',
                'Bhadravathi', 'Mandya', 'Jagdalpur',
            ],
            'Syro-Malankara' => [
                'Marthandom', 'Muvattupuzha', 'Puthur', 'Khadki', 'Gurgaon',
            ],
            'Latin' => [
                'Vasai', 'Sindhudurg', 'Rayagada', 'Buxar', 'Gumla', 'Jashpur',
                'Dindigul', 'Kuzhithurai', 'Jowai', 'Simla and Chandigarh',
            ],
        ];

        $order = 1000;
        foreach ($add as $rite => $names) {
            foreach ($names as $name) {
                ReferenceDataOption::firstOrCreate(
                    ['category' => 'diocese', 'value' => $name],
                    ['cascade_group' => $rite, 'group_label' => null, 'sort_order' => $order, 'is_active' => true],
                );
                // Ensure the rite is set even if the row already existed.
                $diocese()->where('value', $name)->update(['cascade_group' => $rite]);
                $order += 10;
            }
        }
    }

    public function down(): void
    {
        $diocese = fn () => ReferenceDataOption::where('category', 'diocese');

        $diocese()->whereIn('value', ['Kalyan', 'Faridabad', 'Palghat', 'Tiruvalla'])
            ->update(['cascade_group' => 'Latin']);
        $diocese()->where('value', 'Trivandrum (Malankara)')
            ->update(['value' => 'Syro-Malankara', 'cascade_group' => 'Syro-Malankara']);
        $diocese()->where('value', 'Pune')->update(['is_active' => true]);
        $diocese()->whereIn('value', [
            'Shamshabad', 'Hosur', 'Ramanathapuram', 'Tellicherry', 'Bhadravathi',
            'Mandya', 'Jagdalpur', 'Marthandom', 'Muvattupuzha', 'Puthur', 'Khadki',
            'Gurgaon', 'Vasai', 'Sindhudurg', 'Rayagada', 'Buxar', 'Gumla', 'Jashpur',
            'Dindigul', 'Kuzhithurai', 'Jowai', 'Simla and Chandigarh',
        ])->delete();
    }
};

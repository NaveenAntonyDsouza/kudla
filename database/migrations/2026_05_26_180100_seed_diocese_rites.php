<?php

use App\Models\ReferenceDataOption;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Tag each diocese with its rite (cascade_group) for the Denomination →
     * Diocese cascade. Default Latin — the majority of the list and the
     * dominant Roman Catholic case — then mark the dioceses I'm confident are
     * Syro-Malabar or Syro-Malankara eparchies.
     *
     * Best-effort: it's admin-editable in Dropdown Options (the "Rite" field),
     * a full-list fallback applies if a rite ends up with no dioceses, and the
     * "Other" diocese option remains as an escape hatch — so a mis-tag can
     * never block a signup. Idempotent.
     */
    public function up(): void
    {
        $syroMalabar = [
            'Changanacherry', 'Ernakulam-Angamaly', 'Kothamangalam', 'Palai',
            'Kanjirapally', 'Idukki', 'Irinjalakuda', 'Mananthavady', 'Thamarassery',
            'Belthangady', 'Trichur', 'Kottayam', 'Satna', 'Ujjain', 'Sagar',
            'Adilabad', 'Chanda', 'Bijnor',
        ];

        $syroMalankara = [
            'Mavelikara', 'Pathanamthitta', 'Parassala', 'Battery', 'Syro-Malankara',
        ];

        // Default every diocese to Latin…
        ReferenceDataOption::where('category', 'diocese')
            ->update(['cascade_group' => 'Latin']);

        // …then override the Eastern-rite eparchies.
        ReferenceDataOption::where('category', 'diocese')
            ->whereIn('value', $syroMalabar)
            ->update(['cascade_group' => 'Syro-Malabar']);

        ReferenceDataOption::where('category', 'diocese')
            ->whereIn('value', $syroMalankara)
            ->update(['cascade_group' => 'Syro-Malankara']);
    }

    public function down(): void
    {
        ReferenceDataOption::where('category', 'diocese')->update(['cascade_group' => null]);
    }
};

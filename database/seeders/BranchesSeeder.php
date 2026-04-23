<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchesSeeder extends Seeder
{
    /**
     * Seed the default Head Office branch.
     * This ensures any data without an explicit branch can default here,
     * and gives admins a safe starting point.
     */
    public function run(): void
    {
        Branch::firstOrCreate(
            ['code' => 'HO'],
            [
                'name' => 'Head Office',
                'location' => 'Mangalore',
                'state' => 'Karnataka',
                'is_active' => true,
                'is_head_office' => true,
                'notes' => 'Default head office branch — auto-created. All staff and data not assigned to a specific branch belong here.',
            ]
        );
    }
}

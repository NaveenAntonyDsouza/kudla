<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            StaffRolesSeeder::class,
            BranchesSeeder::class,
            CommunitySeeder::class,
            MembershipPlanSeeder::class,
            FaqSeeder::class,
            SiteSettingsSeeder::class,
            ThemeSettingsSeeder::class,
            EmailTemplateSeeder::class,
        ]);

        // Create default admin user
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'phone' => '0000000000',
            'role' => 'admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('Super Admin');
    }
}

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
            CommunitySeeder::class,
            MembershipPlanSeeder::class,
            FaqSeeder::class,
            SiteSettingsSeeder::class,
            ThemeSettingsSeeder::class,
        ]);

        // Create default admin user
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@anugrahamatrimony.com',
            'password' => bcrypt('password'),
            'phone' => '9481618143',
            'role' => 'admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('Super Admin');
    }
}

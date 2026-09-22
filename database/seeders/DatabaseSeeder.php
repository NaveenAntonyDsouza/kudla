<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

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

        // Create the first admin. Email and password come from ADMIN_EMAIL /
        // ADMIN_PASSWORD so a deploy never ships the well-known
        // admin@example.com / "password" pair; without ADMIN_PASSWORD a random
        // one is generated and printed once, here, for the operator to save.
        $email = env('ADMIN_EMAIL', 'admin@example.com');
        $password = env('ADMIN_PASSWORD') ?: Str::password(16);

        $admin = User::create([
            'name' => 'Admin',
            'email' => $email,
            'password' => bcrypt($password),
            'phone' => '0000000000',
            'role' => 'admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('Super Admin');

        if (! env('ADMIN_PASSWORD')) {
            $this->command?->warn("Admin created: {$email} / {$password}  — save this now, it is not stored anywhere else.");
        }
    }
}

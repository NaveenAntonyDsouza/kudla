<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            'manage_users',
            'manage_profiles',
            'approve_profiles',
            'verify_id_proofs',
            'manage_plans',
            'manage_transactions',
            'manage_testimonials',
            'manage_faqs',
            'manage_communities',
            'manage_settings',
            'view_reports',
            'moderate_photos',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        // Every member gets the 'User' role on creation (MemberCreationService,
        // Filament CreateUser). Without this row those calls throw
        // RoleDoesNotExist and leave an orphan user behind — so it belongs in
        // the seeder, not in a manual step after each deploy.
        Role::firstOrCreate(['name' => 'User', 'guard_name' => 'web']);

        // Create roles and assign permissions
        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin']);
        $superAdmin->givePermissionTo(Permission::all());

        $moderator = Role::firstOrCreate(['name' => 'Moderator']);
        $moderator->givePermissionTo([
            'manage_profiles',
            'approve_profiles',
            'verify_id_proofs',
            'manage_testimonials',
            'moderate_photos',
        ]);

        $support = Role::firstOrCreate(['name' => 'Support Agent']);
        $support->givePermissionTo([
            'manage_faqs',
            'view_reports',
        ]);
    }
}

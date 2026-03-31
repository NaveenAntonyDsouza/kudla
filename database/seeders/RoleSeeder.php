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
            Permission::create(['name' => $perm]);
        }

        // Create roles and assign permissions
        $superAdmin = Role::create(['name' => 'Super Admin']);
        $superAdmin->givePermissionTo(Permission::all());

        $moderator = Role::create(['name' => 'Moderator']);
        $moderator->givePermissionTo([
            'manage_profiles',
            'approve_profiles',
            'verify_id_proofs',
            'manage_testimonials',
            'moderate_photos',
        ]);

        $support = Role::create(['name' => 'Support Agent']);
        $support->givePermissionTo([
            'manage_faqs',
            'view_reports',
        ]);
    }
}

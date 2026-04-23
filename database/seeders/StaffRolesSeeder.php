<?php

namespace Database\Seeders;

use App\Models\StaffRole;
use App\Models\StaffRolePermission;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StaffRolesSeeder extends Seeder
{
    public function run(): void
    {
        // Create 10 system roles
        $roles = [
            ['slug' => 'super_admin', 'name' => 'Super Admin', 'description' => 'Owner — full control, locked role', 'sort_order' => 1],
            ['slug' => 'admin', 'name' => 'Admin', 'description' => 'Platform manager — all operations except super-admin management', 'sort_order' => 2],
            ['slug' => 'manager', 'name' => 'Manager', 'description' => 'Head office manager — oversees all branches', 'sort_order' => 3],
            ['slug' => 'branch_manager', 'name' => 'Branch Manager', 'description' => 'Runs a specific branch — full control within that branch', 'sort_order' => 4],
            ['slug' => 'staff', 'name' => 'Staff', 'description' => 'Head office worker — handles members across all branches', 'sort_order' => 5],
            ['slug' => 'branch_staff', 'name' => 'Branch Staff', 'description' => 'Office worker at a specific branch — handles walk-ins', 'sort_order' => 6],
            ['slug' => 'telecaller', 'name' => 'Telecaller', 'description' => 'Outbound calls + lead conversion — assigned leads only', 'sort_order' => 7],
            ['slug' => 'moderator', 'name' => 'Moderator', 'description' => 'Content/photo/ID approvals + suspensions only', 'sort_order' => 8],
            ['slug' => 'support_agent', 'name' => 'Support Agent', 'description' => 'Read-only + contact inbox reply', 'sort_order' => 9],
            ['slug' => 'finance', 'name' => 'Finance', 'description' => 'Payments/refunds/coupons/revenue reports only', 'sort_order' => 10],
        ];

        $createdRoles = [];
        foreach ($roles as $role) {
            $createdRoles[$role['slug']] = StaffRole::updateOrCreate(
                ['slug' => $role['slug']],
                array_merge($role, ['is_system' => true, 'is_active' => true])
            );
        }

        // Seed default permissions per role
        foreach ($this->defaultPermissions() as $slug => $perms) {
            $role = $createdRoles[$slug];

            foreach ($perms as $key => $scope) {
                StaffRolePermission::updateOrCreate(
                    ['staff_role_id' => $role->id, 'permission_key' => $key],
                    ['scope' => $scope]
                );
            }
        }

        // Migrate existing users' role enum to the new staff_role_id
        DB::table('users')->where('role', 'admin')->update([
            'staff_role_id' => $createdRoles['super_admin']->id,
        ]);
        DB::table('users')->where('role', 'moderator')->update([
            'staff_role_id' => $createdRoles['moderator']->id,
        ]);
        DB::table('users')->where('role', 'support')->update([
            'staff_role_id' => $createdRoles['support_agent']->id,
        ]);
    }

    /**
     * Default permission scopes for each role.
     * Only non-default permissions are listed (defaults are 'no' / 'none').
     */
    protected function defaultPermissions(): array
    {
        // Helper — all permissions from config
        $all = config('permissions.permissions');

        // Helper to build "all yes/all" permission set
        $allPermissions = fn (string $scopedValue, string $simpleValue) => collect($all)
            ->mapWithKeys(fn ($def, $key) => [
                $key => $def['type'] === 'scoped' ? $scopedValue : $simpleValue,
            ])
            ->toArray();

        return [
            // Super Admin gets EVERYTHING (but the model short-circuits this anyway)
            'super_admin' => $allPermissions('all', 'yes'),

            // Admin: almost all 'all'/'yes', except restricted system ops
            'admin' => array_merge(
                $allPermissions('all', 'yes'),
                [
                    'manage_super_admins' => 'no',
                    'database_backup' => 'no',
                    'system_health' => 'no',
                ]
            ),

            // Manager: head office, all branches, member/lead/content ops
            'manager' => array_merge(
                $allPermissions('all', 'yes'),
                [
                    // No staff/role management, no settings, no system
                    'manage_staff' => 'no',
                    'manage_roles' => 'no',
                    'manage_super_admins' => 'no',
                    'manage_site_settings' => 'no',
                    'manage_seo_settings' => 'no',
                    'manage_gateway_settings' => 'no',
                    'database_backup' => 'no',
                    'system_health' => 'no',
                    // No delete members
                    'delete_member' => 'none',
                    'delete_lead' => 'none',
                    // No hard-hitting finance
                    'refund_payment' => 'no',
                ]
            ),

            // Branch Manager: own branch only
            'branch_manager' => [
                'view_member' => 'own',
                'edit_member' => 'own',
                'approve_member' => 'own',
                'unapprove_member' => 'own',
                'suspend_member' => 'own',
                'toggle_active' => 'own',
                'add_note_to_member' => 'own',
                'view_member_notes' => 'own',
                'mark_vip' => 'yes',
                'feature_profile' => 'yes',
                'photo_approval' => 'own',
                'id_proof_approval' => 'own',
                'horoscope_approval' => 'own',
                'view_lead' => 'own',
                'add_lead' => 'yes',
                'edit_lead' => 'own',
                'assign_lead' => 'yes',
                'manage_call_log' => 'yes',
                'register_on_behalf' => 'yes',
                'manage_bulk_import' => 'yes',
                'renew_plan' => 'own',
                'view_payment_history' => 'own',
                'manage_coupons' => 'yes',
                'manage_payouts' => 'own',
                'reply_contact' => 'yes',
                'advanced_search' => 'yes',
                'match_making' => 'own',
                'admin_recommendation' => 'yes',
                'view_user_reports' => 'yes',
                'view_engagement_reports' => 'yes',
                'view_revenue_reports' => 'yes',
            ],

            // Staff: head office, all branches, no delete, no settings
            'staff' => [
                'view_member' => 'all',
                'edit_member' => 'all',
                'approve_member' => 'all',
                'suspend_member' => 'all',
                'toggle_active' => 'all',
                'add_note_to_member' => 'all',
                'view_member_notes' => 'all',
                'photo_approval' => 'all',
                'id_proof_approval' => 'all',
                'horoscope_approval' => 'all',
                'view_lead' => 'all',
                'add_lead' => 'yes',
                'edit_lead' => 'all',
                'manage_call_log' => 'yes',
                'register_on_behalf' => 'yes',
                'manage_bulk_import' => 'yes',
                'renew_plan' => 'all',
                'view_payment_history' => 'all',
                'reply_contact' => 'yes',
                'advanced_search' => 'yes',
                'match_making' => 'all',
                'admin_recommendation' => 'yes',
            ],

            // Branch Staff: own branch only
            'branch_staff' => [
                'view_member' => 'own',
                'edit_member' => 'own',
                'approve_member' => 'own',
                'toggle_active' => 'own',
                'add_note_to_member' => 'own',
                'view_member_notes' => 'own',
                'photo_approval' => 'own',
                'id_proof_approval' => 'own',
                'horoscope_approval' => 'own',
                'view_lead' => 'own',
                'add_lead' => 'yes',
                'edit_lead' => 'own',
                'manage_call_log' => 'yes',
                'register_on_behalf' => 'yes',
                'view_payment_history' => 'own',
                'manage_payouts' => 'own',
                'advanced_search' => 'yes',
                'match_making' => 'own',
            ],

            // Telecaller: assigned leads only
            'telecaller' => [
                'view_member' => 'own',
                'edit_member' => 'own',
                'add_note_to_member' => 'own',
                'view_member_notes' => 'own',
                'view_lead' => 'own',
                'add_lead' => 'yes',
                'edit_lead' => 'own',
                'manage_call_log' => 'yes',
                'register_on_behalf' => 'yes',
                'advanced_search' => 'yes',
            ],

            // Moderator: approvals + suspension only
            'moderator' => [
                'view_member' => 'all',
                'view_member_notes' => 'all',
                'approve_member' => 'all',
                'unapprove_member' => 'all',
                'suspend_member' => 'all',
                'ban_member' => 'all',
                'toggle_active' => 'all',
                'photo_approval' => 'all',
                'photo_delete' => 'all',
                'id_proof_approval' => 'all',
                'id_proof_delete' => 'all',
                'horoscope_approval' => 'all',
                'horoscope_delete' => 'all',
                'view_user_reports' => 'yes',
            ],

            // Support Agent: read-only + contact inbox
            'support_agent' => [
                'view_member' => 'all',
                'view_member_notes' => 'all',
                'view_lead' => 'all',
                'reply_contact' => 'yes',
                'advanced_search' => 'yes',
            ],

            // Finance: payments, refunds, coupons, revenue
            'finance' => [
                'view_member' => 'all',
                'view_payment_history' => 'all',
                'refund_payment' => 'yes',
                'manage_coupons' => 'yes',
                'manage_payouts' => 'all',
                'edit_plan' => 'yes',
                'renew_plan' => 'all',
                'view_revenue_reports' => 'yes',
                'view_user_reports' => 'yes',
            ],
        ];
    }
}

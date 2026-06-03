<?php

use App\Models\StaffRole;
use App\Models\StaffRolePermission;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Splits `edit_plan` (membership-plan pricing/definitions CRUD) from the new
     * `assign_member_plan` (assign/change a member's plan to record an offline
     * payment). The member-assignment surfaces — the ChangeMembershipPlan page
     * and the "Assign Plan" member-row button — now gate on `assign_member_plan`,
     * while MembershipPlanResource (pricing) keeps `edit_plan`.
     *
     * Grant the new permission to every role that previously relied on edit_plan
     * for that flow (super_admin / admin / manager / finance) so none of them
     * lose the ability, PLUS the operational "staff" role — so front-office staff
     * can record offline payments WITHOUT also being able to edit plan pricing.
     *
     * Idempotent (updateOrCreate) — safe to re-run.
     */
    public function up(): void
    {
        $slugs = ['super_admin', 'admin', 'manager', 'finance', 'staff'];

        StaffRole::whereIn('slug', $slugs)->get()->each(function (StaffRole $role) {
            StaffRolePermission::updateOrCreate(
                ['staff_role_id' => $role->id, 'permission_key' => 'assign_member_plan'],
                ['scope' => 'yes'],
            );
        });
    }

    public function down(): void
    {
        StaffRolePermission::where('permission_key', 'assign_member_plan')->delete();
    }
};

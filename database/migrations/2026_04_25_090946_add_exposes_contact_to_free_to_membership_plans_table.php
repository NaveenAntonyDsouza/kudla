<?php

use App\Models\MembershipPlan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add membership_plans.exposes_contact_to_free — high-end-tier flag that
 * makes the plan-holder's phone + email visible to FREE members on the
 * profile page, bypassing the in-app chat / interest flow.
 *
 * Models the Shaadi.com "Plus" convention (Diamond Plus + Platinum Plus):
 *   "Allow Free members to view your contact details" — a specific
 *    Plus-tier benefit that lets free members reach out via phone/SMS
 *    directly, without needing to send an interest first.
 *
 * Distinct from `allows_free_member_chat` (added in the previous
 * migration), which controls in-app messaging. Both flags are
 * independent — a plan can have either, both, or neither:
 *   - Standard premium (Silver/Gold/Diamond): both false
 *   - High-end "Plus" (Diamond Plus / future Platinum Plus): both true
 *   - Hypothetical "messaging-only" tier: chat=true, contact=false
 *   - Hypothetical "discoverable-only" tier: chat=false, contact=true
 *
 * Consumed by App\Services\ProfileAccessService::canViewContact() —
 * controls whether the contact section is populated in the
 * /api/v1/profiles/{matriId} response.
 *
 * Reference research:
 *   - https://support.shaadi.com/support/solutions/articles/48000953202
 *     ("Allow Free members to view your contact details — Only for
 *      Plus Plans")
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('membership_plans', function (Blueprint $table) {
            $table->boolean('exposes_contact_to_free')
                ->default(false)
                ->after('allows_free_member_chat')
                ->comment('When true, free members can VIEW this plan-holder\'s phone + email on the profile page (Shaadi-Plus convention).');
        });

        // Backfill — Diamond Plus is the "+" tier and gets both Plus
        // benefits. Free members will see Diamond Plus members' contact
        // details once this flips live.
        try {
            MembershipPlan::where('slug', 'diamond-plus')
                ->update(['exposes_contact_to_free' => true]);
        } catch (\Throwable $e) {
            // Empty table (fresh install / test env) — nothing to backfill.
        }
    }

    public function down(): void
    {
        Schema::table('membership_plans', function (Blueprint $table) {
            $table->dropColumn('exposes_contact_to_free');
        });
    }
};

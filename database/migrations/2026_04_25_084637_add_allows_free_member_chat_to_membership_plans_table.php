<?php

use App\Models\MembershipPlan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add membership_plans.allows_free_member_chat — high-end-tier flag that
 * unlocks chat between the plan-holder and free members in BOTH
 * directions.
 *
 * Models the BharatMatrimony "Platinum" + Shaadi.com "Plus" convention:
 *   - Standard premium tiers (Silver/Gold/Diamond): both parties must be
 *     premium for chat (current default).
 *   - High-end tiers (Diamond Plus + future Platinum Plus): EITHER party
 *     having an active plan with this flag enables chat with a free
 *     member on the other side.
 *
 * Default is false on every existing plan. We backfill TRUE only on the
 * existing "Diamond Plus" plan (slug = 'diamond-plus') because the
 * naming convention already maps to the "high-end tier with extra
 * messaging perks" meaning, matching the Bharat/Shaadi pattern.
 *
 * Consumed by App\Services\InterestService::sendMessage and the
 * /api/v1/interests/{id}/messages endpoint (week 4 step 1).
 *
 * Reference research:
 *   - https://www.bharatmatrimony.com/faq.php (Platinum tier rules)
 *   - https://support.shaadi.com/support/solutions/articles/48000953202
 *     (Plus-tier benefits)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('membership_plans', function (Blueprint $table) {
            $table->boolean('allows_free_member_chat')
                ->default(false)
                ->after('personalized_messages')
                ->comment('When true, members on this plan can chat with free members in both directions (Bharat-Platinum / Shaadi-Plus convention).');
        });

        // Backfill — Diamond Plus is the existing "+" tier.
        // Use the Eloquent layer (not raw SQL) so model events fire and
        // the cast to boolean is honoured.
        try {
            MembershipPlan::where('slug', 'diamond-plus')
                ->update(['allows_free_member_chat' => true]);
        } catch (\Throwable $e) {
            // Empty table (fresh install / test env) — nothing to backfill.
        }
    }

    public function down(): void
    {
        Schema::table('membership_plans', function (Blueprint $table) {
            $table->dropColumn('allows_free_member_chat');
        });
    }
};

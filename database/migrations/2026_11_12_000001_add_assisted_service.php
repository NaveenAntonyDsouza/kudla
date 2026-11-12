<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Assisted service (12 Nov 2026, the way the big portals run it): a plan marked
 * "assisted" gets one named relationship manager. The manager suggests matches;
 * the member says Yes or No on their own Assisted page (Yes sends the interest,
 * marked as via the manager), or ticks "may send without asking". Staff answer an
 * interest the member received only on the member's instruction, which is kept
 * and shown to the member. The member's consent is their own dated tick.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('membership_plans', 'is_assisted')) {
            Schema::table('membership_plans', function (Blueprint $table) {
                $table->boolean('is_assisted')->default(false)->after('priority_support');
            });
        }

        if (! Schema::hasColumn('profiles', 'relationship_manager_id')) {
            Schema::table('profiles', function (Blueprint $table) {
                $table->foreignId('relationship_manager_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('assisted_consent_at')->nullable();
                $table->boolean('assisted_auto_send')->default(false);
            });
        }

        if (! Schema::hasColumn('admin_recommendations', 'member_decision')) {
            Schema::table('admin_recommendations', function (Blueprint $table) {
                $table->string('member_decision', 10)->nullable()->after('interest_sent'); // yes, no, auto
                $table->timestamp('decided_at')->nullable()->after('member_decision');
                $table->unsignedBigInteger('interest_id')->nullable()->after('decided_at');
                $table->date('meeting_on')->nullable()->after('interest_id');
                $table->string('meeting_note', 255)->nullable()->after('meeting_on');
            });
        }

        if (! Schema::hasColumn('interests', 'sent_by_admin_id')) {
            Schema::table('interests', function (Blueprint $table) {
                $table->unsignedBigInteger('sent_by_admin_id')->nullable()->after('source');
            });
        }

        if (! Schema::hasColumn('interest_replies', 'replied_by_admin_id')) {
            Schema::table('interest_replies', function (Blueprint $table) {
                $table->unsignedBigInteger('replied_by_admin_id')->nullable()->after('answered_via');
                $table->string('member_instruction', 255)->nullable()->after('replied_by_admin_id');
            });
        }

        if (! Schema::hasTable('assisted_events')) {
            Schema::create('assisted_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('profile_id')->constrained('profiles')->cascadeOnDelete();
                $table->unsignedBigInteger('admin_user_id')->nullable(); // null: the member did it
                $table->string('action', 40);
                $table->unsignedBigInteger('other_profile_id')->nullable();
                $table->json('details')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->index(['profile_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('assisted_events');
        Schema::table('interest_replies', fn (Blueprint $t) => $t->dropColumn(['replied_by_admin_id', 'member_instruction']));
        Schema::table('interests', fn (Blueprint $t) => $t->dropColumn('sent_by_admin_id'));
        Schema::table('admin_recommendations', fn (Blueprint $t) => $t->dropColumn(['member_decision', 'decided_at', 'interest_id', 'meeting_on', 'meeting_note']));
        Schema::table('profiles', function (Blueprint $t) {
            $t->dropConstrainedForeignId('relationship_manager_id');
            $t->dropColumn(['assisted_consent_at', 'assisted_auto_send']);
        });
        Schema::table('membership_plans', fn (Blueprint $t) => $t->dropColumn('is_assisted'));
    }
};

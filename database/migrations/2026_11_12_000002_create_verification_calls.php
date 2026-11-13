<?php

use App\Models\StaffRole;
use App\Models\StaffRolePermission;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Verification calls (12 Nov 2026, owner: "our staff has to call every new profile at
 * least once for verification"). One row per new member: who the call is given to, how
 * it went (to call, call back, verified, could not reach, wrong number, details did not
 * match, not looking any more), how many tries and when the next one is due. Every try
 * is also a Call note in the member's history (App\Support\VerificationCalls).
 *
 * The new staff right "verification_calls" puts a staff member in the rotation that new
 * members are shared out to. Given to the roles that make calls: Staff, Branch Staff and
 * Telecaller. The owner can change it in Staff Roles.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('verification_calls')) {
            Schema::create('verification_calls', function (Blueprint $table) {
                $table->id();
                $table->foreignId('profile_id')->unique()->constrained('profiles')->cascadeOnDelete();
                $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('assigned_at')->nullable();
                $table->string('status', 20)->default('waiting');
                $table->unsignedSmallInteger('attempts')->default(0);
                $table->unsignedSmallInteger('no_answers')->default(0);
                $table->timestamp('last_attempt_at')->nullable();
                $table->foreignId('last_attempt_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('due_at')->nullable();
                $table->timestamp('done_at')->nullable();
                $table->foreignId('done_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['status', 'due_at']);
                $table->index(['assigned_to', 'status']);
            });
        }

        // "Away": new members skip this staff member until the day given (2099 = until switched back)
        if (! Schema::hasColumn('users', 'calls_away_until')) {
            Schema::table('users', fn (Blueprint $table) => $table->date('calls_away_until')->nullable());
        }

        StaffRole::whereIn('slug', ['staff', 'branch_staff', 'telecaller'])->get()->each(function (StaffRole $role) {
            StaffRolePermission::updateOrCreate(
                ['staff_role_id' => $role->id, 'permission_key' => 'verification_calls'],
                ['scope' => 'yes'],
            );
        });
    }

    public function down(): void
    {
        StaffRolePermission::where('permission_key', 'verification_calls')->delete();
        Schema::dropIfExists('verification_calls');
        if (Schema::hasColumn('users', 'calls_away_until')) {
            Schema::table('users', fn (Blueprint $table) => $table->dropColumn('calls_away_until'));
        }
    }
};

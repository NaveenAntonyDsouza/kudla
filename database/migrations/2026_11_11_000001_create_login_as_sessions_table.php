<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Login as member" (11 Nov 2026): one row for every time a staff account
 * opened the member site as a member — who, which member, why, when, and the
 * pages opened. No foreign keys: the record must outlive a deleted member or
 * staff account. The member is kept by Matri ID only (no name, phone or
 * email), so erasing a member's data leaves nothing personal here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_as_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('staff_user_id')->index();
            $table->unsignedBigInteger('member_user_id')->index();
            $table->string('matri_id', 30)->nullable();
            $table->string('reason', 500);
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('started_at')->nullable()->index();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->string('ended_how', 30)->nullable(); // exit | expired | staff_signed_out | member_changed | replaced
            $table->unsignedInteger('pages_opened')->default(0);
            $table->unsignedInteger('blocked_actions')->default(0); // buttons pressed that view-only refused
            $table->json('pages')->nullable(); // [{t: "2:02:11 PM", p: "/profile/KM100855"}, …]
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_as_sessions');
    }
};

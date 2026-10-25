<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row the first time a member's phone / WhatsApp opens to another member
 * (Oct 2026), so the plan limits the site advertises — e.g. Silver "10 contact
 * views per day, 50 in total" — are actually applied (App\Services\ContactViewQuota).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('contact_views')) {
            return;
        }
        Schema::create('contact_views', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('viewer_profile_id');
            $table->unsignedBigInteger('target_profile_id');
            $table->timestamp('created_at')->nullable();
            $table->unique(['viewer_profile_id', 'target_profile_id']);
            $table->index(['viewer_profile_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_views');
    }
};

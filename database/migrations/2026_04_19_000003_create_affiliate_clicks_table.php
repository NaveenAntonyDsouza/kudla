<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tracks every visit that arrives via an affiliate ref code.
     * One row per visit (not per user) so we can measure raw click traffic.
     * registered_user_id and converted_at are filled in later when the user
     * who arrived from this click actually signs up / pays.
     */
    public function up(): void
    {
        Schema::create('affiliate_clicks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete();

            // Hashed identifiers (privacy-safe — not reversible)
            // SHA256 of (IP + APP_KEY) — used for unique-visitor counting
            $table->char('ip_hash', 64)->nullable();
            $table->char('user_agent_hash', 64)->nullable();

            // Marketing context
            $table->string('referrer_url', 500)->nullable(); // where the visitor came from (facebook, google, etc.)
            $table->string('landing_page', 500)->nullable(); // which page on our site they hit
            $table->string('utm_source', 100)->nullable();   // optional UTM tracking
            $table->string('utm_medium', 100)->nullable();
            $table->string('utm_campaign', 100)->nullable();

            $table->timestamp('visited_at');

            // Conversion tracking — set later
            $table->foreignId('registered_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('registered_at')->nullable();
            $table->timestamp('converted_at')->nullable(); // first paid subscription

            $table->timestamps();

            // Indexes for the dashboard queries we'll run
            $table->index(['branch_id', 'visited_at']);
            $table->index(['branch_id', 'registered_at']);
            $table->index(['branch_id', 'converted_at']);
            $table->index('ip_hash'); // for unique-visitor counts
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_clicks');
    }
};

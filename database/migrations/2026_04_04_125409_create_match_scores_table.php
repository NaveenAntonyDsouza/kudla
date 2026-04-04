<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Future-ready: not used in v1 (scores calculated on-the-fly).
        // Activate in v2 when user count exceeds 10K for cached scoring.
        Schema::create('match_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('matched_profile_id')->constrained('profiles')->cascadeOnDelete();
            $table->tinyInteger('score')->unsigned()->default(0); // 0-100
            $table->timestamp('calculated_at')->useCurrent();

            $table->unique(['profile_id', 'matched_profile_id']);
            $table->index(['profile_id', 'score']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('match_scores');
    }
};

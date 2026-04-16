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
        Schema::create('admin_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('for_profile_id')->constrained('profiles')->cascadeOnDelete();
            $table->foreignId('recommended_profile_id')->constrained('profiles')->cascadeOnDelete();
            $table->foreignId('admin_user_id')->constrained('users')->cascadeOnDelete();
            $table->text('admin_note')->nullable();
            $table->string('priority', 10)->default('normal'); // normal, high
            $table->boolean('is_viewed')->default(false);
            $table->boolean('interest_sent')->default(false);
            $table->timestamps();

            $table->unique(['for_profile_id', 'recommended_profile_id'], 'admin_rec_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_recommendations');
    }
};

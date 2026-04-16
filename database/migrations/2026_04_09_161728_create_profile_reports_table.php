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
        Schema::create('profile_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporter_profile_id')->constrained('profiles')->cascadeOnDelete();
            $table->foreignId('reported_profile_id')->constrained('profiles')->cascadeOnDelete();
            $table->string('reason', 50); // fake_profile, inappropriate_photo, harassment, fraud, other
            $table->text('description')->nullable();
            $table->string('status', 20)->default('pending'); // pending, reviewed, action_taken, dismissed
            $table->text('admin_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['reported_profile_id', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profile_reports');
    }
};

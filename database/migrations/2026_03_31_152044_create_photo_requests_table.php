<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('photo_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requester_profile_id')->constrained('profiles')->cascadeOnDelete();
            $table->foreignId('target_profile_id')->constrained('profiles')->cascadeOnDelete();
            $table->enum('status', ['pending', 'approved', 'ignored'])->default('pending');
            $table->timestamps();

            $table->unique(['requester_profile_id', 'target_profile_id']);
            $table->index(['target_profile_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('photo_requests');
    }
};

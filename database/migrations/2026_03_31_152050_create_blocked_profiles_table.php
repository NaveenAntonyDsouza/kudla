<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blocked_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained('profiles')->cascadeOnDelete();
            $table->foreignId('blocked_profile_id')->constrained('profiles')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['profile_id', 'blocked_profile_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blocked_profiles');
    }
};

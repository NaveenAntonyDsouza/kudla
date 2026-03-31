<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lifestyle_info', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained('profiles')->cascadeOnDelete();
            $table->enum('diet', ['vegetarian', 'non_vegetarian', 'eggetarian'])->nullable();
            $table->enum('smoking', ['no', 'occasionally', 'yes'])->nullable();
            $table->enum('drinking', ['no', 'occasionally', 'yes'])->nullable();
            $table->json('hobbies')->nullable();
            $table->json('interests')->nullable();
            $table->json('languages_known')->nullable();
            $table->timestamps();

            $table->index('profile_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lifestyle_info');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profile_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained('profiles')->cascadeOnDelete();
            $table->enum('photo_type', ['profile', 'album', 'family']);
            $table->string('photo_url', 500);
            $table->string('cloudinary_public_id', 255)->nullable();
            $table->string('thumbnail_url', 500)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_visible')->default(true);
            $table->tinyInteger('display_order')->unsigned()->default(0);
            $table->timestamps();

            $table->index(['profile_id', 'photo_type']);
            $table->index(['profile_id', 'is_visible']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_photos');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('photo_privacy_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->unique()->constrained('profiles')->cascadeOnDelete();
            $table->enum('privacy_level', ['visible_to_all', 'interest_accepted', 'hidden'])->default('visible_to_all');
            $table->boolean('show_profile_photo')->default(true);
            $table->boolean('show_album_photos')->default(true);
            $table->boolean('show_family_photos')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('photo_privacy_settings');
    }
};

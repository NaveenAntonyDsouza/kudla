<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_searches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained('profiles')->cascadeOnDelete();
            $table->string('search_name', 100);
            $table->json('criteria');
            $table->timestamps();

            $table->index('profile_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_searches');
    }
};

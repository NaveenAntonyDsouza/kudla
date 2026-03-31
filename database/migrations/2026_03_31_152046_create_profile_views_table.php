<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profile_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('viewer_profile_id')->constrained('profiles')->cascadeOnDelete();
            $table->foreignId('viewed_profile_id')->constrained('profiles')->cascadeOnDelete();
            $table->timestamp('viewed_at')->useCurrent();

            $table->index(['viewed_profile_id', 'viewed_at']);
            $table->index(['viewer_profile_id', 'viewed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_views');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_interest_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained('profiles')->cascadeOnDelete();
            $table->date('usage_date');
            $table->unsignedInteger('count')->default(0);
            $table->timestamps();

            $table->unique(['profile_id', 'usage_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_interest_usage');
    }
};

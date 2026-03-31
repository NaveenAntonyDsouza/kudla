<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('religious_info', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained('profiles')->cascadeOnDelete();
            $table->string('religion', 50)->nullable();
            $table->string('caste', 100)->nullable(); // community name
            $table->string('sub_caste', 100)->nullable();
            $table->string('gotra', 100)->nullable();
            $table->string('nakshatra', 50)->nullable();
            $table->string('rashi', 50)->nullable();
            $table->string('dosh', 50)->nullable(); // manglik status
            $table->timestamps();

            $table->index('profile_id');
            $table->index('religion');
            $table->index('caste');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('religious_info');
    }
};

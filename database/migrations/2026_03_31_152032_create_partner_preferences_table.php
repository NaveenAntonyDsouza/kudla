<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partner_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->unique()->constrained('profiles')->cascadeOnDelete();
            $table->tinyInteger('age_from')->unsigned()->nullable();
            $table->tinyInteger('age_to')->unsigned()->nullable();
            $table->smallInteger('height_from_cm')->unsigned()->nullable();
            $table->smallInteger('height_to_cm')->unsigned()->nullable();
            $table->json('marital_status')->nullable();
            $table->json('religions')->nullable();
            $table->json('communities')->nullable();
            $table->json('education_levels')->nullable();
            $table->json('occupations')->nullable();
            $table->json('countries')->nullable();
            $table->json('states')->nullable();
            $table->json('cities')->nullable();
            $table->string('income_from', 50)->nullable();
            $table->string('income_to', 50)->nullable();
            $table->json('diet')->nullable();
            $table->string('smoking', 20)->nullable();
            $table->string('drinking', 20)->nullable();
            $table->string('physical_status', 30)->nullable();
            $table->json('mother_tongues')->nullable();
            $table->text('about_partner')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_preferences');
    }
};

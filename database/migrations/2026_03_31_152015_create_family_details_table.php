<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('family_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained('profiles')->cascadeOnDelete();
            $table->string('father_name', 100)->nullable();
            $table->string('father_occupation', 200)->nullable();
            $table->string('mother_name', 100)->nullable();
            $table->string('mother_occupation', 200)->nullable();
            $table->enum('family_type', ['joint', 'nuclear'])->nullable();
            $table->enum('family_values', ['traditional', 'moderate', 'liberal'])->nullable();
            $table->enum('family_status', ['middle_class', 'upper_middle', 'rich', 'affluent'])->nullable();
            $table->tinyInteger('num_brothers')->unsigned()->default(0);
            $table->tinyInteger('brothers_married')->unsigned()->default(0);
            $table->tinyInteger('num_sisters')->unsigned()->default(0);
            $table->tinyInteger('sisters_married')->unsigned()->default(0);
            $table->string('family_living_in', 100)->nullable();
            $table->text('about_family')->nullable();
            $table->timestamps();

            $table->index('profile_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('family_details');
    }
};

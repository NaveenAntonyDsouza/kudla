<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('education_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained('profiles')->cascadeOnDelete();
            $table->string('highest_education', 100)->nullable();
            $table->string('education_detail', 200)->nullable();
            $table->string('college_name', 200)->nullable();
            $table->string('occupation', 100)->nullable();
            $table->string('occupation_detail', 200)->nullable();
            $table->string('employer_name', 200)->nullable();
            $table->string('annual_income', 50)->nullable(); // stored as range string: "5-10 Lakhs"
            $table->string('working_city', 100)->nullable();
            $table->timestamps();

            $table->index('profile_id');
            $table->fullText(['occupation', 'employer_name', 'college_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('education_details');
    }
};

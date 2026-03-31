<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('location_info', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained('profiles')->cascadeOnDelete();
            $table->string('country', 100)->default('India');
            $table->string('state', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('native_place', 100)->nullable();
            $table->string('citizenship', 100)->nullable();
            $table->enum('residency_status', ['citizen', 'permanent_resident', 'work_visa', 'student_visa'])->default('citizen');
            $table->string('grew_up_in', 100)->nullable();
            $table->boolean('is_nri')->default(false);
            $table->date('outstation_leave_date_from')->nullable();
            $table->date('outstation_leave_date_to')->nullable();
            $table->timestamps();

            $table->index('profile_id');
            $table->index('city');
            $table->index('is_nri');
            $table->fullText(['city', 'native_place']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('location_info');
    }
};

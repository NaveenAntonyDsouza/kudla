<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('matri_id', 10)->unique(); // e.g. MP100001
            $table->string('full_name', 100);
            $table->enum('gender', ['male', 'female']);
            $table->date('date_of_birth');
            $table->enum('created_by', ['self', 'parent', 'sibling', 'friend'])->default('self');
            $table->string('creator_name', 100)->nullable();
            $table->string('creator_contact_number', 15)->nullable();
            $table->enum('marital_status', ['never_married', 'divorced', 'widowed', 'awaiting_divorce'])->default('never_married');
            $table->smallInteger('height_cm')->unsigned()->nullable();
            $table->smallInteger('weight_kg')->unsigned()->nullable();
            $table->enum('physical_status', ['normal', 'physically_challenged'])->default('normal');
            $table->enum('body_type', ['slim', 'average', 'athletic', 'heavy'])->nullable();
            $table->enum('complexion', ['very_fair', 'fair', 'wheatish', 'dark'])->nullable();
            $table->string('blood_group', 5)->nullable();
            $table->text('about_me')->nullable();
            $table->tinyInteger('profile_completion_pct')->unsigned()->default(0);
            $table->boolean('onboarding_completed')->default(false);
            $table->tinyInteger('onboarding_step_completed')->unsigned()->default(1);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_approved')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->boolean('id_proof_verified')->default(false);
            $table->string('how_did_you_hear_about_us', 100)->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('matri_id');
            $table->index('gender');
            $table->index('is_active');
            $table->index('created_at');
            $table->fullText(['full_name', 'about_me']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};

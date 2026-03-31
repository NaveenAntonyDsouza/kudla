<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->string('couple_names', 200);
            $table->text('story');
            $table->string('photo_url', 500)->nullable();
            $table->date('wedding_date')->nullable();
            $table->string('location', 100)->nullable();
            $table->foreignId('submitted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_visible')->default(false); // admin approval required
            $table->tinyInteger('display_order')->unsigned()->default(0);
            $table->timestamps();

            $table->index('is_visible');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('testimonials');
    }
};

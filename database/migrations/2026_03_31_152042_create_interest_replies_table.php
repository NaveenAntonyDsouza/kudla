<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interest_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('interest_id')->constrained('interests')->cascadeOnDelete();
            $table->foreignId('replier_profile_id')->constrained('profiles')->cascadeOnDelete();
            $table->enum('reply_type', ['accept', 'decline']);
            $table->string('template_id', 30)->nullable();
            $table->text('custom_message')->nullable();
            $table->boolean('is_silent_decline')->default(false);
            $table->timestamps();

            $table->index('interest_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interest_replies');
    }
};

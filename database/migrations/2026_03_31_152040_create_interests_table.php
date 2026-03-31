<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_profile_id')->constrained('profiles')->cascadeOnDelete();
            $table->foreignId('receiver_profile_id')->constrained('profiles')->cascadeOnDelete();
            $table->string('template_id', 30)->nullable();
            $table->text('custom_message')->nullable();
            $table->enum('status', ['pending', 'accepted', 'declined', 'cancelled', 'expired'])->default('pending');
            $table->boolean('is_starred_by_sender')->default(false);
            $table->boolean('is_starred_by_receiver')->default(false);
            $table->boolean('is_trashed_by_sender')->default(false);
            $table->boolean('is_trashed_by_receiver')->default(false);
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->unique(['sender_profile_id', 'receiver_profile_id']);
            $table->index(['receiver_profile_id', 'status']);
            $table->index(['sender_profile_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interests');
    }
};

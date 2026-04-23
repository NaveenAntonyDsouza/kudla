<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('call_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('lead_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('profile_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('called_by_staff_id')->constrained('users')->cascadeOnDelete();

            $table->enum('call_type', ['outgoing', 'incoming'])->default('outgoing');
            $table->unsignedSmallInteger('duration_minutes')->default(0);
            $table->string('outcome', 40); // connected, no_answer, busy, voicemail, interested, not_interested, follow_up
            $table->text('notes')->nullable();

            $table->boolean('follow_up_required')->default(false);
            $table->date('follow_up_date')->nullable();

            $table->timestamp('called_at');

            $table->timestamps();

            $table->index(['lead_id', 'called_at']);
            $table->index(['called_by_staff_id', 'called_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('call_logs');
    }
};

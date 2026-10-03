<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per email sent, per failed send attempt, and per unsubscribe —
 * the data behind admin → Reports → Email Activity. Kept 180 days
 * (EmailLog is Prunable; model:prune runs daily).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();
            $table->string('event', 16);              // sent | failed | unsubscribed
            $table->string('email_type', 64)->nullable(); // template slug, or the preference for unsubscribes
            $table->string('subject', 255)->nullable();
            $table->string('recipient', 191)->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['event', 'created_at']);
            $table->index(['email_type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_logs');
    }
};

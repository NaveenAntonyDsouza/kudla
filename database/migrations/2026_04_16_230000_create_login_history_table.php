<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('login_method', 20); // 'password', 'mobile_otp', 'email_otp'
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('logged_in_at')->useCurrent();
            $table->timestamps();

            $table->index(['user_id', 'logged_in_at']);
            $table->index('login_method');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_history');
    }
};

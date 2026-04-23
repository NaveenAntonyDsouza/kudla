<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_user_id')->constrained('users')->cascadeOnDelete();
            $table->date('month'); // First day of target month

            $table->unsignedInteger('registration_target')->default(0);
            $table->unsignedInteger('revenue_target')->default(0); // in paise
            $table->unsignedInteger('call_target')->default(0);

            $table->unsignedInteger('incentive_per_registration')->default(0); // in paise
            $table->decimal('incentive_per_subscription_pct', 5, 2)->default(0);

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['staff_user_id', 'month']);
            $table->index('month');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_targets');
    }
};

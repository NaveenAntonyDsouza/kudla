<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Mangalore Branch"
            $table->string('code', 20)->unique(); // e.g., "MNG" — used in affiliate URLs
            $table->string('location')->nullable(); // City / Area
            $table->string('state')->nullable();
            $table->text('address')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->foreignId('manager_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_head_office')->default(false); // Marks the central HO branch
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('is_active');
            $table->index('is_head_office');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};

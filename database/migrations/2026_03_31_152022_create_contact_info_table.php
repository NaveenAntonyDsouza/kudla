<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_info', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained('profiles')->cascadeOnDelete();
            $table->string('contact_person', 100)->nullable();
            $table->string('contact_relationship', 50)->nullable();
            $table->string('primary_phone', 15)->nullable();
            $table->string('secondary_phone', 15)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('address_line1', 255)->nullable();
            $table->string('address_line2', 255)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('pincode', 10)->nullable();
            $table->timestamps();

            $table->index('profile_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_info');
    }
};

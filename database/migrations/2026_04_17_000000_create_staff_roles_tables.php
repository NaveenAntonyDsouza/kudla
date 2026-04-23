<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->string('slug', 50)->unique();
            $table->string('description', 255)->nullable();
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('staff_role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_role_id')->constrained('staff_roles')->cascadeOnDelete();
            $table->string('permission_key', 60);
            $table->string('scope', 10)->default('no'); // yes/no OR all/own/none
            $table->timestamps();
            $table->unique(['staff_role_id', 'permission_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_role_permissions');
        Schema::dropIfExists('staff_roles');
    }
};

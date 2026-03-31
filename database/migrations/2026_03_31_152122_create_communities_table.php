<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communities', function (Blueprint $table) {
            $table->id();
            $table->string('religion', 50);
            $table->string('community_name', 100);
            $table->json('sub_communities')->nullable();
            $table->boolean('is_active')->default(true);
            $table->tinyInteger('sort_order')->unsigned()->default(0);
            $table->timestamps();

            $table->index('religion');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communities');
    }
};

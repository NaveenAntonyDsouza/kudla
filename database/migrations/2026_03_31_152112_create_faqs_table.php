<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('question', 500);
            $table->text('answer');
            $table->string('category', 50);
            $table->boolean('is_visible')->default(true);
            $table->tinyInteger('display_order')->unsigned()->default(0);
            $table->timestamps();

            $table->index('category');
            $table->index(['is_visible', 'display_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faqs');
    }
};

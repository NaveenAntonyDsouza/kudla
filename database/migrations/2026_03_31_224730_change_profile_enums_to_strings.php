<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->string('marital_status', 50)->default('Unmarried')->change();
            $table->string('physical_status', 50)->default('Normal')->change();
            $table->string('body_type', 50)->nullable()->change();
            $table->string('complexion', 50)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->enum('marital_status', ['never_married', 'divorced', 'widowed', 'awaiting_divorce'])->default('never_married')->change();
            $table->enum('physical_status', ['normal', 'physically_challenged'])->default('normal')->change();
            $table->enum('body_type', ['slim', 'average', 'athletic', 'heavy'])->nullable()->change();
            $table->enum('complexion', ['very_fair', 'fair', 'wheatish', 'dark'])->nullable()->change();
        });
    }
};

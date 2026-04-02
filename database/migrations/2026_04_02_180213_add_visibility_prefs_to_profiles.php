<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->boolean('only_same_religion')->default(false)->after('search_visible_to_taller');
            $table->boolean('only_same_denomination')->default(false)->after('only_same_religion');
            $table->boolean('only_same_mother_tongue')->default(false)->after('only_same_denomination');
        });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn(['only_same_religion', 'only_same_denomination', 'only_same_mother_tongue']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Make users.email nullable so admins can create members without an email
 * address (matches the already-nullable users.phone). The unique index on
 * email is preserved — MySQL permits multiple NULLs in a unique index, so
 * any number of members may have no email.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
        });
    }

    public function down(): void
    {
        // NB: will fail if any user has a NULL email at rollback time.
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable(false)->change();
        });
    }
};

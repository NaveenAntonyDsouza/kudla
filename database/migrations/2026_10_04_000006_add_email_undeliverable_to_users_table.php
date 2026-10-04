<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "This address bounced permanently" mark (App\Support\EmailSuppression).
 * The address is stored so the mark lapses by itself once the email changes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'email_undeliverable_at')) {
                $table->timestamp('email_undeliverable_at')->nullable();
                $table->string('email_undeliverable_address')->nullable();
                $table->string('email_undeliverable_reason', 190)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['email_undeliverable_at', 'email_undeliverable_address', 'email_undeliverable_reason']);
        });
    }
};

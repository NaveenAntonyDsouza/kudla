<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Where the member pressed Accept or Decline. Since 26 Oct 2026 they can
 * answer straight from the interest email, but nothing recorded it in the
 * database — the only trace was an analytics event, and the weekly review
 * could not say whether anyone used the buttons (2 Nov 2026).
 *
 * 'email' = the buttons in the email; null = on the site or in the app.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('interest_replies', function (Blueprint $table) {
            if (! Schema::hasColumn('interest_replies', 'answered_via')) {
                $table->string('answered_via', 20)->nullable()->after('reply_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('interest_replies', function (Blueprint $table) {
            if (Schema::hasColumn('interest_replies', 'answered_via')) {
                $table->dropColumn('answered_via');
            }
        });
    }
};

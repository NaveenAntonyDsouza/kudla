<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('membership_plans', function (Blueprint $table) {
            $table->unsignedInteger('view_contacts_limit')->default(0)->after('can_view_contact');
            $table->unsignedInteger('daily_contact_views')->default(0)->after('view_contacts_limit');
            $table->boolean('personalized_messages')->default(false)->after('daily_contact_views');
            $table->boolean('featured_profile')->default(false)->after('personalized_messages');
            $table->boolean('priority_support')->default(false)->after('featured_profile');
            $table->boolean('is_popular')->default(false)->after('priority_support');
        });
    }

    public function down(): void
    {
        Schema::table('membership_plans', function (Blueprint $table) {
            $table->dropColumn([
                'view_contacts_limit',
                'daily_contact_views',
                'personalized_messages',
                'featured_profile',
                'priority_support',
                'is_popular',
            ]);
        });
    }
};

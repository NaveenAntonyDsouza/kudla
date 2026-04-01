<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('partner_preferences', function (Blueprint $table) {
            // Primary requirements
            $table->json('complexion')->nullable()->after('marital_status');
            $table->json('body_type')->nullable()->after('complexion');
            $table->json('children_status')->nullable()->after('body_type');
            $table->json('family_status')->nullable()->after('children_status');

            // Religious requirements
            $table->json('denomination')->nullable()->after('religions');
            $table->json('diocese')->nullable()->after('denomination');
            $table->json('caste')->nullable()->after('diocese');
            $table->json('sub_caste')->nullable()->after('caste');
            $table->json('muslim_sect')->nullable()->after('sub_caste');
            $table->json('muslim_community')->nullable()->after('muslim_sect');
            $table->json('jain_sect')->nullable()->after('muslim_community');
            $table->json('manglik')->nullable()->after('jain_sect');
            $table->json('languages_known')->nullable()->after('mother_tongues');

            // Professional requirements
            $table->json('educational_qualifications')->nullable()->after('education_levels');
            $table->json('employment_status')->nullable()->after('occupations');

            // Location requirements
            $table->json('working_countries')->nullable()->after('cities');
            $table->json('working_states')->nullable()->after('working_countries');
            $table->json('working_districts')->nullable()->after('working_states');
            $table->json('residing_countries')->nullable()->after('working_districts');
            $table->json('residential_status')->nullable()->after('residing_countries');
            $table->json('native_countries')->nullable()->after('residential_status');
            $table->json('native_states')->nullable()->after('native_countries');
            $table->json('native_districts')->nullable()->after('native_states');
        });
    }

    public function down(): void
    {
        Schema::table('partner_preferences', function (Blueprint $table) {
            $table->dropColumn([
                'complexion', 'body_type', 'children_status', 'family_status',
                'denomination', 'diocese', 'caste', 'sub_caste',
                'muslim_sect', 'muslim_community', 'jain_sect', 'manglik', 'languages_known',
                'educational_qualifications', 'employment_status',
                'working_countries', 'working_states', 'working_districts',
                'residing_countries', 'residential_status',
                'native_countries', 'native_states', 'native_districts',
            ]);
        });
    }
};

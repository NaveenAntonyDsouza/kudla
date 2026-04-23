<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add all missing columns needed for the full registration flow
     * (children, religious, education, location, contact, family, lifestyle,
     * social media, and differently-abled info).
     */
    public function up(): void
    {
        // 1. profiles — children custody fields
        Schema::table('profiles', function (Blueprint $table) {
            $table->tinyInteger('children_with_me')->unsigned()->default(0)->nullable()->after('marital_status');
            $table->tinyInteger('children_not_with_me')->unsigned()->default(0)->nullable()->after('children_with_me');
        });

        // 2. religious_info — multi-religion fields
        Schema::table('religious_info', function (Blueprint $table) {
            $table->string('denomination', 100)->nullable();
            $table->string('diocese', 150)->nullable();
            $table->string('diocese_name', 150)->nullable();
            $table->text('parish_name_place')->nullable();
            $table->time('time_of_birth')->nullable();
            $table->string('place_of_birth', 100)->nullable();
            $table->string('jathakam_upload_url', 500)->nullable();
            $table->string('muslim_sect', 50)->nullable();
            $table->string('muslim_community', 100)->nullable();
            $table->string('religious_observance', 50)->nullable();
            $table->string('jain_sect', 50)->nullable();
            $table->string('other_religion_name', 100)->nullable();
        });

        // 3. education_details — education & employment extras
        Schema::table('education_details', function (Blueprint $table) {
            $table->string('education_level', 50)->nullable();
            $table->string('employment_category', 50)->nullable();
            $table->string('working_country', 100)->nullable();
            $table->string('working_state', 100)->nullable();
            $table->string('working_district', 100)->nullable();
        });

        // 4. location_info — native location fields
        Schema::table('location_info', function (Blueprint $table) {
            $table->string('native_country', 100)->nullable();
            $table->string('native_state', 100)->nullable();
            $table->string('native_district', 100)->nullable();
            $table->string('pin_zip_code', 10)->nullable();
        });

        // 5. contact_info — extended contact & address fields
        Schema::table('contact_info', function (Blueprint $table) {
            $table->string('whatsapp_number', 15)->nullable();
            $table->text('communication_address')->nullable();
            $table->string('residential_phone_number', 20)->nullable();
            $table->string('preferred_call_time', 100)->nullable();
            $table->string('alternate_email', 255)->nullable();
            $table->string('reference_name', 100)->nullable();
            $table->string('reference_relationship', 50)->nullable();
            $table->string('reference_mobile', 15)->nullable();
            $table->boolean('present_address_same_as_comm')->default(false);
            $table->text('present_address')->nullable();
            $table->string('present_pin_zip_code', 10)->nullable();
            $table->boolean('permanent_address_same_as_comm')->default(false);
            $table->boolean('permanent_address_same_as_present')->default(false);
            $table->text('permanent_address')->nullable();
            $table->string('permanent_pin_zip_code', 10)->nullable();
        });

        // 6. family_details — extended family fields
        Schema::table('family_details', function (Blueprint $table) {
            $table->string('father_house_name', 100)->nullable();
            $table->string('father_native_place', 100)->nullable();
            $table->string('mother_house_name', 100)->nullable();
            $table->string('mother_native_place', 100)->nullable();
            $table->tinyInteger('brothers_unmarried')->unsigned()->default(0);
            $table->tinyInteger('brothers_priest')->unsigned()->default(0);
            $table->tinyInteger('sisters_unmarried')->unsigned()->default(0);
            $table->tinyInteger('sisters_nun')->unsigned()->default(0);
            $table->text('candidate_asset_details')->nullable();
            $table->text('about_candidate_family')->nullable();
        });

        // 7. lifestyle_info — cultural & preference fields
        Schema::table('lifestyle_info', function (Blueprint $table) {
            $table->string('cultural_background', 50)->nullable();
            $table->json('favorite_music')->nullable();
            $table->json('preferred_books')->nullable();
            $table->json('preferred_movies')->nullable();
            $table->json('sports_fitness_games')->nullable();
            $table->json('favorite_cuisine')->nullable();
        });

        // 8. social_media_links — additional link fields
        Schema::table('social_media_links', function (Blueprint $table) {
            $table->string('youtube_url', 255)->nullable();
            $table->string('website_url', 255)->nullable();
        });

        // 9. NEW TABLE: differently_abled_info
        Schema::create('differently_abled_info', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained('profiles')->cascadeOnDelete();
            $table->string('category', 100)->nullable();
            $table->string('specify', 100)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop new table first
        Schema::dropIfExists('differently_abled_info');

        Schema::table('social_media_links', function (Blueprint $table) {
            $table->dropColumn(['youtube_url', 'website_url']);
        });

        Schema::table('lifestyle_info', function (Blueprint $table) {
            $table->dropColumn([
                'cultural_background', 'favorite_music', 'preferred_books',
                'preferred_movies', 'sports_fitness_games', 'favorite_cuisine',
            ]);
        });

        Schema::table('family_details', function (Blueprint $table) {
            $table->dropColumn([
                'father_house_name', 'father_native_place',
                'mother_house_name', 'mother_native_place',
                'brothers_unmarried', 'brothers_priest',
                'sisters_unmarried', 'sisters_nun',
                'candidate_asset_details', 'about_candidate_family',
            ]);
        });

        Schema::table('contact_info', function (Blueprint $table) {
            $table->dropColumn([
                'whatsapp_number', 'communication_address', 'residential_phone_number',
                'preferred_call_time', 'alternate_email',
                'reference_name', 'reference_relationship', 'reference_mobile',
                'present_address_same_as_comm', 'present_address', 'present_pin_zip_code',
                'permanent_address_same_as_comm', 'permanent_address_same_as_present',
                'permanent_address', 'permanent_pin_zip_code',
            ]);
        });

        Schema::table('location_info', function (Blueprint $table) {
            $table->dropColumn(['native_country', 'native_state', 'native_district', 'pin_zip_code']);
        });

        Schema::table('education_details', function (Blueprint $table) {
            $table->dropColumn([
                'education_level', 'employment_category',
                'working_country', 'working_state', 'working_district',
            ]);
        });

        Schema::table('religious_info', function (Blueprint $table) {
            $table->dropColumn([
                'denomination', 'diocese', 'diocese_name', 'parish_name_place',
                'time_of_birth', 'place_of_birth', 'jathakam_upload_url',
                'muslim_sect', 'muslim_community', 'religious_observance',
                'jain_sect', 'other_religion_name',
            ]);
        });

        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn(['children_with_me', 'children_not_with_me']);
        });
    }
};

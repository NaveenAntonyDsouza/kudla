<?php

namespace Database\Seeders;

use App\Models\EducationDetail;
use App\Models\FamilyDetail;
use App\Models\LifestyleInfo;
use App\Models\LocationInfo;
use App\Models\PartnerPreference;
use App\Models\Profile;
use App\Models\ReligiousInfo;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds 10 diverse test profiles (5 male, 5 female) with varying
 * partner preferences to thoroughly test the matching engine.
 *
 * Run: php artisan db:seed --class=TestMatchingSeeder
 */
class TestMatchingSeeder extends Seeder
{
    public function run(): void
    {
        $profiles = [
            // ── FEMALES ──
            [
                'email' => 'maria.test@test.com',
                'profile' => [
                    'full_name' => 'Maria D\'Souza',
                    'gender' => 'female',
                    'date_of_birth' => '1998-05-15', // 27 yrs
                    'marital_status' => 'Unmarried',
                    'height' => '160 cm',
                    'mother_tongue' => 'Konkani',
                ],
                'religious' => ['religion' => 'Christian', 'denomination' => 'Latin Catholic'],
                'education' => ['highest_education' => 'B.Tech', 'occupation' => 'Software Engineer', 'working_country' => 'India', 'working_state' => 'Karnataka'],
                'location' => ['native_country' => 'India', 'native_state' => 'Karnataka', 'residing_country' => 'India'],
                'family' => ['family_status' => 'Upper middle class', 'family_type' => 'nuclear'],
                'lifestyle' => ['diet' => 'Non-Vegetarian'],
                'prefs' => [
                    'age_from' => 27, 'age_to' => 35,
                    'religions' => ['Christian'],
                    'denomination' => ['Latin Catholic', 'Syrian Catholic'],
                    'mother_tongues' => ['Konkani', 'Tulu', 'Kannada'],
                    'education_levels' => ['B.Tech', 'M.Tech', 'MBA', 'M.Sc'],
                    'occupations' => ['Software Engineer', 'Manager', 'Doctor', 'Business'],
                    'height_from_cm' => '165', 'height_to_cm' => '185',
                    'native_states' => ['Karnataka', 'Kerala', 'Goa'],
                    'working_countries' => ['India', 'United States', 'United Kingdom'],
                    'marital_status' => ['Unmarried'],
                    'diet' => ['Non-Vegetarian', 'Eggetarian'],
                    'family_status' => ['Upper middle class', 'Rich'],
                ],
            ],
            [
                'email' => 'priya.test@test.com',
                'profile' => [
                    'full_name' => 'Priya Sharma',
                    'gender' => 'female',
                    'date_of_birth' => '1996-08-22', // 29 yrs
                    'marital_status' => 'Unmarried',
                    'height' => '155 cm',
                    'mother_tongue' => 'Kannada',
                ],
                'religious' => ['religion' => 'Hindu', 'caste' => 'Brahmin'],
                'education' => ['highest_education' => 'MBBS', 'occupation' => 'Doctor', 'working_country' => 'India', 'working_state' => 'Karnataka'],
                'location' => ['native_country' => 'India', 'native_state' => 'Karnataka', 'residing_country' => 'India'],
                'family' => ['family_status' => 'Rich', 'family_type' => 'joint'],
                'lifestyle' => ['diet' => 'Vegetarian'],
                'prefs' => [
                    'age_from' => 29, 'age_to' => 38,
                    'religions' => ['Hindu'],
                    'caste' => ['Brahmin', 'Kshatriya'],
                    'mother_tongues' => ['Kannada', 'Hindi'],
                    'education_levels' => ['MBBS', 'MD', 'M.Tech', 'MBA'],
                    'occupations' => ['Doctor', 'Software Engineer', 'Business'],
                    'height_from_cm' => '170', 'height_to_cm' => '190',
                    'native_states' => ['Karnataka', 'Maharashtra'],
                    'working_countries' => ['India'],
                    'marital_status' => ['Unmarried'],
                    'diet' => ['Vegetarian'],
                    'family_status' => ['Upper middle class', 'Rich', 'Affluent'],
                ],
            ],
            [
                'email' => 'sarah.test@test.com',
                'profile' => [
                    'full_name' => 'Sarah Thomas',
                    'gender' => 'female',
                    'date_of_birth' => '1995-12-10', // 30 yrs
                    'marital_status' => 'Divorced',
                    'height' => '162 cm',
                    'mother_tongue' => 'Malayalam',
                ],
                'religious' => ['religion' => 'Christian', 'denomination' => 'Syrian Catholic'],
                'education' => ['highest_education' => 'MBA', 'occupation' => 'Manager', 'working_country' => 'United Arab Emirates', 'working_state' => 'Dubai'],
                'location' => ['native_country' => 'India', 'native_state' => 'Kerala', 'residing_country' => 'United Arab Emirates'],
                'family' => ['family_status' => 'Upper middle class', 'family_type' => 'nuclear'],
                'lifestyle' => ['diet' => 'Non-Vegetarian'],
                'prefs' => [
                    'age_from' => 30, 'age_to' => 42,
                    'religions' => ['Christian'],
                    'denomination' => ['Syrian Catholic', 'Latin Catholic', 'Marthomite'],
                    'mother_tongues' => ['Malayalam', 'English'],
                    'education_levels' => ['MBA', 'M.Tech', 'CA'],
                    'occupations' => ['Manager', 'Business', 'Software Engineer'],
                    'height_from_cm' => '170', 'height_to_cm' => '190',
                    'native_states' => ['Kerala', 'Karnataka'],
                    'working_countries' => ['United Arab Emirates', 'India', 'United States'],
                    'marital_status' => ['Unmarried', 'Divorced'],
                    'diet' => ['Non-Vegetarian', 'Eggetarian'],
                    'family_status' => ['Upper middle class', 'Rich'],
                ],
            ],
            [
                'email' => 'anjali.test@test.com',
                'profile' => [
                    'full_name' => 'Anjali Nair',
                    'gender' => 'female',
                    'date_of_birth' => '2000-03-01', // 26 yrs
                    'marital_status' => 'Unmarried',
                    'height' => '158 cm',
                    'mother_tongue' => 'Malayalam',
                ],
                'religious' => ['religion' => 'Hindu', 'caste' => 'Nair'],
                'education' => ['highest_education' => 'B.Sc Nursing', 'occupation' => 'Nurse', 'working_country' => 'United Kingdom', 'working_state' => 'London'],
                'location' => ['native_country' => 'India', 'native_state' => 'Kerala', 'residing_country' => 'United Kingdom'],
                'family' => ['family_status' => 'Middle class', 'family_type' => 'nuclear'],
                'lifestyle' => ['diet' => 'Non-Vegetarian'],
                'prefs' => [
                    'age_from' => 26, 'age_to' => 34,
                    'religions' => ['Hindu'],
                    'caste' => ['Nair', 'Menon', 'Pillai'],
                    'mother_tongues' => ['Malayalam'],
                    'education_levels' => ['B.Tech', 'MBBS', 'MBA'],
                    'height_from_cm' => '168', 'height_to_cm' => '185',
                    'native_states' => ['Kerala'],
                    'working_countries' => ['United Kingdom', 'India', 'United States'],
                    'marital_status' => ['Unmarried'],
                    'diet' => ['Non-Vegetarian', 'Eggetarian', 'Vegetarian'],
                    'family_status' => ['Middle class', 'Upper middle class', 'Rich'],
                ],
            ],
            [
                'email' => 'fatima.test@test.com',
                'profile' => [
                    'full_name' => 'Fatima Khan',
                    'gender' => 'female',
                    'date_of_birth' => '1997-07-18', // 28 yrs
                    'marital_status' => 'Unmarried',
                    'height' => '157 cm',
                    'mother_tongue' => 'Urdu',
                ],
                'religious' => ['religion' => 'Muslim', 'muslim_sect' => 'Sunni'],
                'education' => ['highest_education' => 'B.Com', 'occupation' => 'Accountant', 'working_country' => 'India', 'working_state' => 'Karnataka'],
                'location' => ['native_country' => 'India', 'native_state' => 'Karnataka', 'residing_country' => 'India'],
                'family' => ['family_status' => 'Middle class', 'family_type' => 'joint'],
                'lifestyle' => ['diet' => 'Non-Vegetarian'],
                'prefs' => [
                    'age_from' => 28, 'age_to' => 36,
                    'religions' => ['Muslim'],
                    'mother_tongues' => ['Urdu', 'Hindi', 'Kannada'],
                    'education_levels' => ['B.Tech', 'MBA', 'B.Com', 'CA'],
                    'occupations' => ['Business', 'Manager', 'Software Engineer', 'Accountant'],
                    'height_from_cm' => '168', 'height_to_cm' => '185',
                    'native_states' => ['Karnataka', 'Maharashtra', 'Tamil Nadu'],
                    'working_countries' => ['India', 'United Arab Emirates', 'Saudi Arabia'],
                    'marital_status' => ['Unmarried'],
                    'diet' => ['Non-Vegetarian'],
                    'family_status' => ['Middle class', 'Upper middle class'],
                ],
            ],

            // ── MALES ──
            [
                'email' => 'rahul.test@test.com',
                'profile' => [
                    'full_name' => 'Rahul Shetty',
                    'gender' => 'male',
                    'date_of_birth' => '1994-11-20', // 31 yrs
                    'marital_status' => 'Unmarried',
                    'height' => '175 cm',
                    'mother_tongue' => 'Tulu',
                ],
                'religious' => ['religion' => 'Christian', 'denomination' => 'Latin Catholic'],
                'education' => ['highest_education' => 'M.Tech', 'occupation' => 'Software Engineer', 'working_country' => 'United States', 'working_state' => 'California'],
                'location' => ['native_country' => 'India', 'native_state' => 'Karnataka', 'residing_country' => 'United States'],
                'family' => ['family_status' => 'Rich', 'family_type' => 'nuclear'],
                'lifestyle' => ['diet' => 'Non-Vegetarian'],
                'prefs' => [
                    'age_from' => 24, 'age_to' => 30,
                    'religions' => ['Christian'],
                    'denomination' => ['Latin Catholic'],
                    'mother_tongues' => ['Tulu', 'Konkani', 'Kannada'],
                    'education_levels' => ['B.Tech', 'M.Tech', 'MBA', 'MBBS'],
                    'occupations' => ['Software Engineer', 'Doctor', 'Manager', 'Nurse'],
                    'height_from_cm' => '150', 'height_to_cm' => '170',
                    'native_states' => ['Karnataka', 'Goa'],
                    'working_countries' => ['India', 'United States'],
                    'marital_status' => ['Unmarried'],
                    'diet' => ['Non-Vegetarian', 'Eggetarian'],
                    'family_status' => ['Upper middle class', 'Rich'],
                ],
            ],
            [
                'email' => 'arun.test@test.com',
                'profile' => [
                    'full_name' => 'Arun Kumar',
                    'gender' => 'male',
                    'date_of_birth' => '1993-04-05', // 33 yrs
                    'marital_status' => 'Unmarried',
                    'height' => '178 cm',
                    'mother_tongue' => 'Kannada',
                ],
                'religious' => ['religion' => 'Hindu', 'caste' => 'Brahmin'],
                'education' => ['highest_education' => 'MD', 'occupation' => 'Doctor', 'working_country' => 'India', 'working_state' => 'Karnataka'],
                'location' => ['native_country' => 'India', 'native_state' => 'Karnataka', 'residing_country' => 'India'],
                'family' => ['family_status' => 'Rich', 'family_type' => 'joint'],
                'lifestyle' => ['diet' => 'Vegetarian'],
                'prefs' => [
                    'age_from' => 25, 'age_to' => 32,
                    'religions' => ['Hindu'],
                    'caste' => ['Brahmin'],
                    'mother_tongues' => ['Kannada', 'Hindi'],
                    'education_levels' => ['MBBS', 'MD', 'B.Tech', 'M.Sc'],
                    'occupations' => ['Doctor', 'Software Engineer', 'Professor'],
                    'height_from_cm' => '150', 'height_to_cm' => '168',
                    'native_states' => ['Karnataka', 'Maharashtra'],
                    'working_countries' => ['India'],
                    'marital_status' => ['Unmarried'],
                    'diet' => ['Vegetarian'],
                    'family_status' => ['Upper middle class', 'Rich', 'Affluent'],
                ],
            ],
            [
                'email' => 'joseph.test@test.com',
                'profile' => [
                    'full_name' => 'Joseph Mathew',
                    'gender' => 'male',
                    'date_of_birth' => '1991-09-12', // 34 yrs
                    'marital_status' => 'Divorced',
                    'height' => '172 cm',
                    'mother_tongue' => 'Malayalam',
                ],
                'religious' => ['religion' => 'Christian', 'denomination' => 'Syrian Catholic'],
                'education' => ['highest_education' => 'MBA', 'occupation' => 'Business', 'working_country' => 'United Arab Emirates', 'working_state' => 'Dubai'],
                'location' => ['native_country' => 'India', 'native_state' => 'Kerala', 'residing_country' => 'United Arab Emirates'],
                'family' => ['family_status' => 'Upper middle class', 'family_type' => 'nuclear'],
                'lifestyle' => ['diet' => 'Non-Vegetarian'],
                'prefs' => [
                    'age_from' => 26, 'age_to' => 35,
                    'religions' => ['Christian'],
                    'denomination' => ['Syrian Catholic', 'Latin Catholic'],
                    'mother_tongues' => ['Malayalam', 'Konkani'],
                    'education_levels' => ['MBA', 'B.Tech', 'M.Tech', 'B.Sc Nursing'],
                    'occupations' => ['Manager', 'Nurse', 'Software Engineer'],
                    'height_from_cm' => '152', 'height_to_cm' => '170',
                    'native_states' => ['Kerala', 'Karnataka'],
                    'working_countries' => ['United Arab Emirates', 'India', 'United Kingdom'],
                    'marital_status' => ['Unmarried', 'Divorced'],
                    'diet' => ['Non-Vegetarian'],
                    'family_status' => ['Middle class', 'Upper middle class', 'Rich'],
                ],
            ],
            [
                'email' => 'vikram.test@test.com',
                'profile' => [
                    'full_name' => 'Vikram Menon',
                    'gender' => 'male',
                    'date_of_birth' => '1996-01-30', // 30 yrs
                    'marital_status' => 'Unmarried',
                    'height' => '180 cm',
                    'mother_tongue' => 'Malayalam',
                ],
                'religious' => ['religion' => 'Hindu', 'caste' => 'Menon'],
                'education' => ['highest_education' => 'B.Tech', 'occupation' => 'Software Engineer', 'working_country' => 'United Kingdom', 'working_state' => 'London'],
                'location' => ['native_country' => 'India', 'native_state' => 'Kerala', 'residing_country' => 'United Kingdom'],
                'family' => ['family_status' => 'Upper middle class', 'family_type' => 'nuclear'],
                'lifestyle' => ['diet' => 'Non-Vegetarian'],
                'prefs' => [
                    'age_from' => 23, 'age_to' => 29,
                    'religions' => ['Hindu'],
                    'caste' => ['Nair', 'Menon', 'Pillai'],
                    'mother_tongues' => ['Malayalam'],
                    'education_levels' => ['B.Tech', 'MBBS', 'B.Sc Nursing', 'MBA'],
                    'occupations' => ['Nurse', 'Software Engineer', 'Doctor'],
                    'height_from_cm' => '150', 'height_to_cm' => '168',
                    'native_states' => ['Kerala'],
                    'working_countries' => ['United Kingdom', 'India'],
                    'marital_status' => ['Unmarried'],
                    'diet' => ['Non-Vegetarian', 'Vegetarian'],
                    'family_status' => ['Middle class', 'Upper middle class'],
                ],
            ],
            [
                'email' => 'ahmed.test@test.com',
                'profile' => [
                    'full_name' => 'Ahmed Patel',
                    'gender' => 'male',
                    'date_of_birth' => '1995-06-25', // 30 yrs
                    'marital_status' => 'Unmarried',
                    'height' => '174 cm',
                    'mother_tongue' => 'Urdu',
                ],
                'religious' => ['religion' => 'Muslim', 'muslim_sect' => 'Sunni'],
                'education' => ['highest_education' => 'MBA', 'occupation' => 'Business', 'working_country' => 'India', 'working_state' => 'Karnataka'],
                'location' => ['native_country' => 'India', 'native_state' => 'Karnataka', 'residing_country' => 'India'],
                'family' => ['family_status' => 'Upper middle class', 'family_type' => 'joint'],
                'lifestyle' => ['diet' => 'Non-Vegetarian'],
                'prefs' => [
                    'age_from' => 22, 'age_to' => 30,
                    'religions' => ['Muslim'],
                    'mother_tongues' => ['Urdu', 'Hindi'],
                    'education_levels' => ['B.Com', 'MBA', 'B.Tech'],
                    'occupations' => ['Accountant', 'Manager', 'Teacher'],
                    'height_from_cm' => '150', 'height_to_cm' => '165',
                    'native_states' => ['Karnataka', 'Tamil Nadu'],
                    'working_countries' => ['India', 'United Arab Emirates'],
                    'marital_status' => ['Unmarried'],
                    'diet' => ['Non-Vegetarian'],
                    'family_status' => ['Middle class', 'Upper middle class'],
                ],
            ],
        ];

        foreach ($profiles as $data) {
            // Skip if email already exists
            if (User::where('email', $data['email'])->exists()) {
                $this->command->info("Skipping {$data['email']} — already exists");
                continue;
            }

            // Create user
            $user = User::create([
                'name' => $data['profile']['full_name'],
                'email' => $data['email'],
                'phone' => null,
                'password' => Hash::make('Test@1234'),
                'email_verified_at' => now(),
            ]);

            // Create profile
            $profile = Profile::create(array_merge($data['profile'], [
                'user_id' => $user->id,
                'is_active' => true,
                'is_approved' => true,
                'onboarding_completed' => true,
                'onboarding_step_completed' => 4,
                'profile_completion_pct' => 85,
            ]));

            // Related records
            ReligiousInfo::create(array_merge(['profile_id' => $profile->id], $data['religious']));
            EducationDetail::create(array_merge(['profile_id' => $profile->id], $data['education']));
            LocationInfo::create(array_merge(['profile_id' => $profile->id], $data['location']));
            FamilyDetail::create(array_merge(['profile_id' => $profile->id], $data['family']));
            LifestyleInfo::create(array_merge(['profile_id' => $profile->id], $data['lifestyle']));

            // Partner preferences
            PartnerPreference::create(array_merge(['profile_id' => $profile->id], $data['prefs']));

            $this->command->info("Created {$profile->matri_id} | {$data['profile']['full_name']} | {$data['profile']['gender']}");
        }

        $this->command->info("\nDone! Total profiles: " . Profile::count());
    }
}

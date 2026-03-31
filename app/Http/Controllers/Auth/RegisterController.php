<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterStep1Request;
use App\Http\Requests\RegisterStep2Request;
use App\Http\Requests\RegisterStep3Request;
use App\Http\Requests\RegisterStep4Request;
use App\Http\Requests\RegisterStep5Request;
use App\Models\Community;
use App\Models\ContactInfo;
use App\Models\EducationDetail;
use App\Models\FamilyDetail;
use App\Models\LocationInfo;
use App\Models\Profile;
use App\Models\ReligiousInfo;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    // ── Step 1: Basic Info + Religion ──────────────────────────────

    public function showStep1()
    {
        $communities = Community::active()->orderBy('religion')->orderBy('sort_order')->get();

        // Diocese list for Christian denomination
        $dioceses = [
            'Adilabad', 'Agra', 'Ahmedabad', 'Aizawl', 'Ajmer', 'Allahabad',
            'Alleppey', 'Amravati', 'Aurangabad', 'Balasore', 'Bangalore',
            'Baroda', 'Baruipur', 'Bellary', 'Bhopal', 'Bhagalpur',
            'Bijnor', 'Bongaigaon', 'Buxar', 'Calicut', 'Chanda',
            'Changanassery', 'Chingleput', 'Cochin', 'Coimbatore',
            'Cuttack-Bhubaneshwar', 'Daltonganj', 'Darjeeling', 'Delhi',
            'Dharmapuri', 'Dibrugarh', 'Dindigul', 'Diphu', 'Dumka',
            'Eluru', 'Ernakulam-Angamaly', 'Faridabad', 'Ganjam',
            'Gorakhpur', 'Gulbarga', 'Guntur', 'Guwahati', 'Gwalior',
            'Hazaribag', 'Hyderabad', 'Idukki', 'Imphal', 'Indore',
            'Itanagar', 'Jabalpur', 'Jaipur', 'Jalandhar', 'Jamshedpur',
            'Jhansi', 'Kanjirapally', 'Kannur', 'Karwar', 'Khandwa',
            'Khammam', 'Kohima', 'Kolhapur', 'Kollam', 'Kottapuram',
            'Kottayam', 'Kumbakonam', 'Lucknow', 'Madras-Mylapore',
            'Madurai', 'Mangalore', 'Mananthavady', 'Meerut', 'Miao',
            'Muzaffarpur', 'Mysore', 'Nagpur', 'Nanded', 'Nashik',
            'Nellore', 'Neyyattinkara', 'Nongstoin', 'Ootacamund',
            'Palai', 'Palghat', 'Patna', 'Poona', 'Port Blair',
            'Punalur', 'Raiganj', 'Raigarh', 'Raipur', 'Rajkot',
            'Ranchi', 'Rewari', 'Rohtak', 'Rourkela', 'Sagar',
            'Salem', 'Sambalpur', 'Satna', 'Shimla-Chandigarh',
            'Shimoga', 'Simdega', 'Singhbhum', 'Sivagangai', 'Sultanpet',
            'Surat', 'Tezpur', 'Thamarassery', 'Thanjavur', 'Thiruchirapalli',
            'Thiruvananthapuram', 'Trichur', 'Tuticorin', 'Udaipur',
            'Udupi', 'Ujjain', 'Varanasi', 'Verapoly', 'Vijayapuram',
            'Vijayawada', 'Visakhapatnam', 'Warangal', 'Other',
        ];

        // Denominations grouped
        $denominations = [
            'Catholic' => [
                'Syrian Catholic', 'Latin Catholic', 'Malankara Catholic',
                'Anglo Indian', 'Knanaya Catholic', 'Nadar Christian', 'Cheramar Christian',
            ],
            'Non-Catholic' => [
                'Jacobite', 'Orthodox', 'Marthomite', 'CSI Christian',
                'Knanaya Jacobite', 'Chaldean Christian',
                'Malabar Independent Syrian Church', 'Anglican Church of India',
                'Pentecostal', 'Brethren', 'Protestant', 'Evangelist',
                'Salvation Army', 'Other',
            ],
        ];

        return view('auth.register-step1', compact('communities', 'dioceses', 'denominations'));
    }

    public function storeStep1(RegisterStep1Request $request)
    {
        $validated = $request->validated();

        // Create user
        $user = User::create([
            'name' => $validated['full_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
            'role' => 'user',
            'is_active' => true,
        ]);

        // Create profile
        $profile = Profile::create([
            'user_id' => $user->id,
            'full_name' => $validated['full_name'],
            'gender' => $validated['gender'],
            'date_of_birth' => $validated['date_of_birth'],
            'marital_status' => $validated['marital_status'],
            'height_cm' => $validated['height_cm'] ?? null,
            'complexion' => $validated['complexion'] ?? null,
            'body_type' => $validated['body_type'] ?? null,
            'physical_status' => $validated['physical_status'] ?? null,
            'children_with_me' => $validated['children_with_me'] ?? 0,
            'children_not_with_me' => $validated['children_not_with_me'] ?? 0,
            'onboarding_step_completed' => 1,
        ]);

        // Create religious info
        ReligiousInfo::create([
            'profile_id' => $profile->id,
            'religion' => $validated['religion'],
            'caste' => $validated['caste'] ?? null,
            'sub_caste' => $validated['sub_caste'] ?? null,
            'gotra' => $validated['gotra'] ?? null,
            'nakshatra' => $validated['nakshatra'] ?? null,
            'rashi' => $validated['rashi'] ?? null,
            'dosh' => $validated['dosh'] ?? null,
            'denomination' => $validated['denomination'] ?? null,
            'diocese' => $validated['diocese'] ?? null,
            'diocese_name' => $validated['diocese_name'] ?? null,
            'parish_name_place' => $validated['parish_name_place'] ?? null,
            'time_of_birth' => $validated['time_of_birth'] ?? null,
            'place_of_birth' => $validated['place_of_birth'] ?? null,
            'muslim_sect' => $validated['muslim_sect'] ?? null,
            'muslim_community' => $validated['muslim_community'] ?? null,
            'religious_observance' => $validated['religious_observance'] ?? null,
            'jain_sect' => $validated['jain_sect'] ?? null,
            'other_religion_name' => $validated['other_religion_name'] ?? null,
        ]);

        Auth::login($user);

        return redirect()->route('register.step2');
    }

    // ── Step 2: Education & Professional ──────────────────────────

    public function showStep2()
    {
        return view('auth.register-step2');
    }

    public function storeStep2(RegisterStep2Request $request)
    {
        $profile = auth()->user()->profile;

        EducationDetail::updateOrCreate(
            ['profile_id' => $profile->id],
            $request->validated()
        );

        $profile->update(['onboarding_step_completed' => 2]);

        return redirect()->route('register.step3');
    }

    // ── Step 3: Family Details ────────────────────────────────────

    public function showStep3()
    {
        return view('auth.register-step3');
    }

    public function storeStep3(RegisterStep3Request $request)
    {
        $profile = auth()->user()->profile;

        FamilyDetail::updateOrCreate(
            ['profile_id' => $profile->id],
            $request->validated()
        );

        $profile->update(['onboarding_step_completed' => 3]);

        return redirect()->route('register.step4');
    }

    // ── Step 4: Location & Contact ────────────────────────────────

    public function showStep4()
    {
        return view('auth.register-step4');
    }

    public function storeStep4(RegisterStep4Request $request)
    {
        $profile = auth()->user()->profile;
        $validated = $request->validated();

        // Split location fields
        $locationFields = [
            'country', 'state', 'city', 'native_place',
            'native_country', 'native_state', 'native_district',
            'pin_zip_code', 'citizenship', 'residency_status', 'grew_up_in',
        ];

        $contactFields = [
            'contact_person', 'contact_relationship',
            'primary_phone', 'secondary_phone', 'whatsapp_number',
            'communication_address', 'present_address', 'present_pin_zip_code',
            'permanent_address', 'permanent_pin_zip_code',
        ];

        LocationInfo::updateOrCreate(
            ['profile_id' => $profile->id],
            collect($validated)->only($locationFields)->toArray()
        );

        ContactInfo::updateOrCreate(
            ['profile_id' => $profile->id],
            collect($validated)->only($contactFields)->toArray()
        );

        $profile->update(['onboarding_step_completed' => 4]);

        return redirect()->route('register.step5');
    }

    // ── Step 5: Profile Creation Details ──────────────────────────

    public function showStep5()
    {
        return view('auth.register-step5');
    }

    public function storeStep5(RegisterStep5Request $request)
    {
        $profile = auth()->user()->profile;

        $profile->update([
            'created_by' => $request->created_by,
            'creator_name' => $request->creator_name,
            'creator_contact_number' => $request->creator_contact_number,
            'how_did_you_hear_about_us' => $request->how_did_you_hear_about_us,
            'onboarding_step_completed' => 5,
        ]);

        return redirect()->route('register.verify');
    }

    // ── OTP Verification ──────────────────────────────────────────

    public function showVerify()
    {
        return view('auth.register-verify');
    }

    public function sendOtp(Request $request)
    {
        $phone = auth()->user()->phone;
        $otpService = app(OtpService::class);
        $otpService->sendOtp($phone);

        return back()->with('otp_sent', true);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate(['otp' => 'required|digits:6']);

        $otpService = app(OtpService::class);

        if (! $otpService->verifyOtp(auth()->user()->phone, $request->otp)) {
            return back()->withErrors(['otp' => 'Invalid or expired OTP. Please try again.']);
        }

        $user = auth()->user();
        $user->update(['phone_verified_at' => now()]);
        $user->profile->update(['onboarding_completed' => true]);

        return redirect()->route('register.complete');
    }

    public function complete()
    {
        $profile = auth()->user()->profile;

        return view('auth.register-complete', compact('profile'));
    }
}

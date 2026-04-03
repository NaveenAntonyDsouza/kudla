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
use App\Models\DifferentlyAbledInfo;
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
    // ── Step 1: Register Free (Account Only) ─────────────────────

    public function showStep1()
    {
        // Pre-fill from DB when authenticated user comes back to edit
        $profile = null;
        $user = auth()->user();
        if ($user) {
            $profile = $user->profile;
        }

        return view('auth.register-step1', compact('profile', 'user'));
    }

    public function storeStep1(RegisterStep1Request $request)
    {
        $validated = $request->validated();

        // If already authenticated, update existing user/profile instead of creating new
        if ($existingUser = auth()->user()) {
            $existingUser->profile?->update([
                'full_name' => $validated['full_name'],
                'gender' => $validated['gender'],
                'date_of_birth' => $validated['date_of_birth'],
            ]);

            return redirect()->route('register.step2');
        }

        // Create user
        $user = User::create([
            'name' => $validated['full_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
            'role' => 'user',
            'is_active' => true,
        ]);

        // Create profile (basic info only)
        Profile::create([
            'user_id' => $user->id,
            'full_name' => $validated['full_name'],
            'gender' => $validated['gender'],
            'date_of_birth' => $validated['date_of_birth'],
            'onboarding_step_completed' => 1,
            'is_active' => true,
        ]);

        Auth::login($user);

        return redirect()->route('register.step2');
    }

    // ── Step 2: Primary & Religious Information ──────────────────

    public function showStep2()
    {
        $profile = auth()->user()->profile;
        $religiousInfo = $profile?->religiousInfo;
        $familyDetail = $profile?->familyDetail;

        return view('auth.register-step2', compact('profile', 'religiousInfo', 'familyDetail'));
    }

    public function storeStep2(RegisterStep2Request $request)
    {
        $profile = auth()->user()->profile;
        $validated = $request->validated();

        // Update profile with physical details
        $profile->update([
            'height' => $validated['height'],
            'complexion' => $validated['complexion'],
            'body_type' => $validated['body_type'],
            'physical_status' => $validated['physical_status'] ?? null,
            'marital_status' => $validated['marital_status'],
            'children_with_me' => $validated['children_with_me'] ?? 0,
            'children_not_with_me' => $validated['children_not_with_me'] ?? 0,
            'onboarding_step_completed' => 2,
        ]);

        // Create family detail with family_status
        FamilyDetail::updateOrCreate(
            ['profile_id' => $profile->id],
            ['family_status' => $validated['family_status'] ?? null]
        );

        // Create religious info
        ReligiousInfo::updateOrCreate(
            ['profile_id' => $profile->id],
            [
                'religion' => $validated['religion'],
                'caste' => $validated['caste'] ?? null,
                'sub_caste' => $validated['sub_caste'] ?? null,
                'gotra' => $validated['gotra'] ?? null,
                'nakshatra' => $validated['nakshatra'] ?? null,
                'rashi' => $validated['rashi'] ?? null,
                'dosh' => $validated['manglik'] ?? null,
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
            ]
        );

        // Save differently abled info
        if (($validated['physical_status'] ?? '') === 'Differently Abled') {
            DifferentlyAbledInfo::updateOrCreate(
                ['profile_id' => $profile->id],
                [
                    'category' => $validated['da_category'] ?? null,
                    'specify' => $validated['da_category_other'] ?? null,
                    'description' => $validated['da_description'] ?? null,
                ]
            );
        }

        // Handle jathakam file upload
        if ($request->hasFile('jathakam')) {
            $path = $request->file('jathakam')->store('jathakam', 'public');
            $profile->religiousInfo->update(['jathakam_upload_url' => $path]);
        }

        return redirect()->route('register.step3');
    }

    // ── Step 3: Education & Professional ─────────────────────────

    public function showStep3()
    {
        $profile = auth()->user()->profile;
        $educationDetail = $profile?->educationDetail;

        return view('auth.register-step3', compact('profile', 'educationDetail'));
    }

    public function storeStep3(RegisterStep3Request $request)
    {
        $profile = auth()->user()->profile;

        EducationDetail::updateOrCreate(
            ['profile_id' => $profile->id],
            $request->validated()
        );

        $profile->update(['onboarding_step_completed' => 3]);

        return redirect()->route('register.step4');
    }

    // ── Step 4: Location & Contact ───────────────────────────────

    public function showStep4()
    {
        $profile = auth()->user()->profile;
        $locationInfo = $profile?->locationInfo;
        $contactInfo = $profile?->contactInfo;

        return view('auth.register-step4', compact('profile', 'locationInfo', 'contactInfo'));
    }

    public function storeStep4(RegisterStep4Request $request)
    {
        $profile = auth()->user()->profile;
        $validated = $request->validated();

        // Location fields
        LocationInfo::updateOrCreate(
            ['profile_id' => $profile->id],
            [
                'native_country' => $validated['native_country'] ?? null,
                'native_state' => $validated['native_state'] ?? null,
                'native_district' => $validated['native_district'] ?? null,
                'pin_zip_code' => $validated['pin_zip_code'] ?? null,
            ]
        );

        // Contact fields (custodian = contact_person in DB)
        ContactInfo::updateOrCreate(
            ['profile_id' => $profile->id],
            [
                'whatsapp_number' => $validated['whatsapp_number'] ?? null,
                'primary_phone' => $validated['mobile_number'] ?? null,
                'contact_person' => $validated['custodian_name'] ?? null,
                'contact_relationship' => $validated['custodian_relation'] ?? null,
                'communication_address' => $validated['communication_address'] ?? null,
                'pincode' => $validated['pin_zip_code'] ?? null,
            ]
        );

        $profile->update(['onboarding_step_completed' => 4]);

        return redirect()->route('register.step5');
    }

    // ── Step 5: Profile Creation Details ─────────────────────────

    public function showStep5()
    {
        $profile = auth()->user()->profile;

        return view('auth.register-step5', compact('profile'));
    }

    public function storeStep5(RegisterStep5Request $request)
    {
        $profile = auth()->user()->profile;
        $validated = $request->validated();

        $profile->update([
            'created_by' => $validated['created_by'],
            'creator_name' => $validated['creator_name'] ?? null,
            'creator_contact_number' => $validated['creator_contact_number'] ?? null,
            'how_did_you_hear_about_us' => $validated['how_did_you_hear_about_us'] ?? null,
            'onboarding_step_completed' => 5,
        ]);

        return redirect()->route('register.verifyemail');
    }

    // ── OTP Verification ─────────────────────────────────────────

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

    // ── Email OTP Verification ───────────────────────────────────

    public function showVerifyEmail()
    {
        return view('auth.register-verify-email');
    }

    public function sendEmailOtp(Request $request)
    {
        $email = auth()->user()->email;
        $otp = random_int(100000, 999999);

        session(['email_otp' => \Illuminate\Support\Facades\Hash::make((string) $otp), 'email_otp_expires' => now()->addMinutes(10)]);

        // Send OTP via email
        \Illuminate\Support\Facades\Mail::raw("Your Anugraha Matrimony email verification code is: {$otp}\n\nThis code expires in 10 minutes.", function ($message) use ($email) {
            $message->to($email)->subject('Email Verification OTP - Anugraha Matrimony');
        });

        return back()->with('email_otp_sent', true);
    }

    public function verifyEmailOtp(Request $request)
    {
        $request->validate(['otp' => 'required|digits:6']);

        $storedOtp = session('email_otp');
        $expiresAt = session('email_otp_expires');

        if (! $storedOtp || ! \Illuminate\Support\Facades\Hash::check((string) $request->otp, $storedOtp) || now()->gt($expiresAt)) {
            return back()->withErrors(['otp' => 'Invalid or expired OTP. Please try again.'])->with('email_otp_sent', true);
        }

        $user = auth()->user();
        $user->update(['email_verified_at' => now()]);

        session()->forget(['email_otp', 'email_otp_expires']);

        return redirect()->route('register.verify');
    }

    public function complete()
    {
        $profile = auth()->user()->profile;

        // Mark onboarding as complete (even if verification was skipped)
        if (! $profile->onboarding_completed) {
            $profile->update(['onboarding_completed' => true]);
        }

        return view('auth.register-complete', compact('profile'));
    }
}

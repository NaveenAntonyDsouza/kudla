<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Http\Requests\RegisterStep1Request;
use App\Http\Requests\RegisterStep2Request;
use App\Http\Requests\RegisterStep3Request;
use App\Http\Requests\RegisterStep4Request;
use App\Http\Requests\RegisterStep5Request;
use App\Models\Community;
use App\Models\ContactInfo;
use App\Models\EducationDetail;
use App\Models\LifestyleInfo;
use App\Models\LocationInfo;
use App\Models\Profile;
use App\Models\ProfilePhoto;
use App\Models\ReligiousInfo;
use App\Models\User;
use App\Services\AffiliateTracker;
use App\Services\ImageProcessingService;
use App\Services\OtpService;
use App\Services\PhotoStorageService;
use App\Services\WatermarkService;
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
            $existingUser->update([
                'name' => $validated['full_name'],
                'phone' => $validated['phone'],
                'email' => $validated['email'],
            ]);
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

        // Affiliate attribution — if the user arrived via ?ref=CODE,
        // stamp branch_id on the user and link the click to the registration.
        // Safe to call even when no cookie is set (no-op).
        app(AffiliateTracker::class)->attributeRegistration($request, $user);

        // Create profile (basic info only)
        // Auto-approve if admin setting is enabled
        $autoApprove = SiteSetting::getValue('auto_approve_profiles', '1') === '1';

        // Re-read user so we pick up branch_id assigned by attributeRegistration above
        $user->refresh();

        Profile::create([
            'user_id' => $user->id,
            'full_name' => $validated['full_name'],
            'gender' => $validated['gender'],
            'date_of_birth' => $validated['date_of_birth'],
            'onboarding_step_completed' => 1,
            'is_active' => true,
            'is_approved' => $autoApprove,
            'branch_id' => $user->branch_id, // mirror user's branch attribution
            // Web sign-up: classify Desktop/Mobile/Tablet from the UA.
            'registration_source' => \App\Support\DeviceDetector::type($request->userAgent()),
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

        // Update profile. complexion / body_type / physical_status and
        // family_status are now collected in onboarding step 1 (deferred to
        // slim registration), so they're no longer set here.
        $profile->update([
            'height' => $validated['height'],
            'marital_status' => $validated['marital_status'],
            'children_with_me' => $validated['children_with_me'] ?? 0,
            'children_not_with_me' => $validated['children_not_with_me'] ?? 0,
            'mother_tongue' => $validated['mother_tongue'],
            'onboarding_step_completed' => 2,
        ]);

        // Other languages known → lifestyle_info (where languages_known lives).
        LifestyleInfo::updateOrCreate(
            ['profile_id' => $profile->id],
            ['languages_known' => $validated['languages_known'] ?? []]
        );

        // Create religious info. Clear fields that don't belong to the chosen
        // religion, so changing religion (e.g. back-and-edit during onboarding)
        // leaves no stale data behind.
        $relData = [
            'religion' => $validated['religion'],
            'caste' => $validated['caste'] ?? null,
            'other_caste_name' => $validated['other_caste_name'] ?? null,
            'sub_caste' => $validated['sub_caste'] ?? null,
            'gotra' => $validated['gotra'] ?? null,
            'nakshatra' => $validated['nakshatra'] ?? null,
            'rashi' => $validated['rashi'] ?? null,
            'dosh' => $validated['manglik'] ?? null,
            'denomination' => $validated['denomination'] ?? null,
            'other_denomination_name' => $validated['other_denomination_name'] ?? null,
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
        ];
        $relData = ReligiousInfo::clearFieldsForReligion($relData, $validated['religion']);
        ReligiousInfo::updateOrCreate(
            ['profile_id' => $profile->id],
            $relData
        );

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
                'native_place' => $validated['native_place'] ?? null,
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

        // Profile creation details (merged from the former Step 5).
        // Step 5's role is now taken by the optional photo step; we mark
        // onboarding_step_completed = 5 here because Step 4 now wraps the
        // entire data-entry portion of registration. Backward-compat: any
        // user mid-funnel who lands on the old /register/step-5 form gets
        // forwarded through showStep5() below.
        $profile->update([
            'created_by' => $validated['created_by'],
            'creator_name' => $validated['creator_name'] ?? null,
            'creator_contact_number' => $validated['creator_contact_number'] ?? null,
            'how_did_you_hear_about_us' => $validated['how_did_you_hear_about_us'] ?? null,
            'onboarding_step_completed' => 5,
        ]);

        return $this->redirectAfterStep5();
    }

    // ── Step 5 (legacy): now lives merged into Step 4. ───────────
    //
    // Kept as a backward-compat forwarder for any user who was mid-funnel
    // when the merge deployed and is still holding a /register/step-5 URL.
    // The form they were about to submit posts to /register/step-5 — we
    // accept that submission, persist whichever fields it carried, then
    // forward through the same photo detour the merged Step 4 uses.

    public function showStep5()
    {
        // Old form view is gone; bounce them back to Step 4 (which now
        // includes the fields they'd have filled here).
        return redirect()->route('register.step4');
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

        return $this->redirectAfterStep5();
    }

    /**
     * Determine where to redirect after Step 5 based on verification settings.
     */
    private function redirectAfterStep5()
    {
        $user = auth()->user();

        // Detour through the optional photo step if no profile photo on file
        // yet. The photo screen has its own "Skip" button that posts to
        // /register/photo/skip and lands here from the verify-or-complete
        // branch — so this only fires once per registration.
        $hasProfilePhoto = $user->profile?->profilePhotos()
            ->ofType('profile')->visible()->exists() ?? false;
        if (! $hasProfilePhoto) {
            return redirect()->route('register.photo');
        }

        return $this->redirectAfterPhotoStep();
    }

    /**
     * Verification + completion routing. Called from redirectAfterStep5() when
     * a profile photo already exists, and from {storePhoto, skipPhoto} once
     * the photo step is decided either way.
     */
    private function redirectAfterPhotoStep()
    {
        $emailEnabled = SiteSetting::getValue('email_verification_enabled', '1') === '1';
        $phoneEnabled = SiteSetting::getValue('phone_verification_enabled', '0') === '1';

        $user = auth()->user();

        // Skip email verification if disabled or already verified
        if ($emailEnabled && !$user->email_verified_at) {
            return redirect()->route('register.verifyemail');
        }

        // Skip phone verification if disabled or already verified
        if ($phoneEnabled && !$user->phone_verified_at) {
            return redirect()->route('register.verify');
        }

        // Both disabled or already verified — go straight to complete
        $user->profile->update(['onboarding_completed' => true]);
        return redirect()->route('register.complete');
    }

    // ── Photo Step (optional) ────────────────────────────────────
    //
    // Sits between Step 5 and email/phone verification. Always reachable
    // via /register/photo, but {redirectAfterStep5} only routes here when
    // the user has no visible profile photo yet — so re-visiting after a
    // successful upload moves on to verify/complete.

    public function showPhoto()
    {
        $profile = auth()->user()->profile;

        // If a profile photo already exists, the photo step is unnecessary.
        if ($profile->profilePhotos()->ofType('profile')->visible()->exists()) {
            return $this->redirectAfterPhotoStep();
        }

        return view('auth.register-photo', compact('profile'));
    }

    public function storePhoto(
        Request $request,
        WatermarkService $watermarkService,
        ImageProcessingService $imageProcessor,
        PhotoStorageService $photoStorage
    ) {
        $profile = auth()->user()->profile;

        // Same size limit as /manage-photos — keeps web + API + admin aligned.
        $maxKilobytes = (int) config('matrimony.max_photo_size_mb', 5) * 1024;
        $request->validate([
            'photo' => "required|image|mimes:jpg,jpeg,png,gif,webp|max:{$maxKilobytes}",
        ]);

        // Approval honours the existing site setting. Defaults to auto-approve
        // (matches /manage-photos behaviour and the .env.example default).
        $autoApprove = SiteSetting::getValue('auto_approve_profile_photos', '1') === '1';
        $approvalStatus = $autoApprove
            ? ProfilePhoto::STATUS_APPROVED
            : ProfilePhoto::STATUS_PENDING;

        // Archive any prior profile photos (defensive — shouldn't exist at
        // registration but a re-submit could create one).
        $profile->profilePhotos()->visible()->ofType('profile')
            ->update(['is_visible' => false, 'is_primary' => false]);

        // Process upload — generates 3 size variants + preserves original.
        // Picks storage driver from SiteSetting `active_storage_driver`,
        // falls back to local if the configured driver isn't available.
        $folder = "photos/{$profile->id}";
        $activeDriver = $photoStorage->getActiveDriver();
        if (! $photoStorage->isDriverConfigured($activeDriver)) {
            $activeDriver = PhotoStorageService::DRIVER_LOCAL;
        }
        $paths = $imageProcessor->processUpload(
            $request->file('photo'),
            $folder,
            $activeDriver
        );

        ProfilePhoto::create([
            'profile_id' => $profile->id,
            'photo_type' => 'profile',
            'photo_url' => $paths['full'],
            'thumbnail_url' => $paths['thumb'],
            'medium_url' => $paths['medium'],
            'original_url' => $paths['original'],
            'storage_driver' => $paths['driver'],
            'is_primary' => $autoApprove,
            'is_visible' => true,
            'display_order' => 1,
            'approval_status' => $approvalStatus,
            'approved_at' => $autoApprove ? now() : null,
        ]);

        return $this->redirectAfterPhotoStep();
    }

    public function skipPhoto()
    {
        // No-op on data — just route on. Profile remains photo-less; the
        // "Add a photo" nudge banner on the dashboard will keep prompting.
        return $this->redirectAfterPhotoStep();
    }

    // ── OTP Verification ─────────────────────────────────────────

    public function showVerify()
    {
        // Skip if phone verification is disabled
        if (SiteSetting::getValue('phone_verification_enabled', '0') !== '1') {
            auth()->user()->profile->update(['onboarding_completed' => true]);
            return redirect()->route('register.complete');
        }

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
        // Skip if email verification is disabled
        if (SiteSetting::getValue('email_verification_enabled', '1') !== '1') {
            $phoneEnabled = SiteSetting::getValue('phone_verification_enabled', '0') === '1';
            if ($phoneEnabled && !auth()->user()->phone_verified_at) {
                return redirect()->route('register.verify');
            }
            auth()->user()->profile->update(['onboarding_completed' => true]);
            return redirect()->route('register.complete');
        }

        return view('auth.register-verify-email');
    }

    public function sendEmailOtp(Request $request)
    {
        $email = auth()->user()->email;
        $otp = random_int(100000, 999999);

        session(['email_otp' => \Illuminate\Support\Facades\Hash::make((string) $otp), 'email_otp_expires' => now()->addMinutes(10)]);

        // Send OTP via email
        $siteName = SiteSetting::getValue('site_name', config('app.name'));
        \Illuminate\Support\Facades\Mail::raw("Your {$siteName} email verification code is: {$otp}\n\nThis code expires in 10 minutes.", function ($message) use ($email, $siteName) {
            $message->to($email)->subject("Email Verification OTP - {$siteName}");
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

        // After email verified, check if phone verification is needed
        $phoneEnabled = SiteSetting::getValue('phone_verification_enabled', '0') === '1';
        if ($phoneEnabled && !$user->phone_verified_at) {
            return redirect()->route('register.verify');
        }

        // Phone disabled or already verified — complete registration
        $user->profile->update(['onboarding_completed' => true]);
        return redirect()->route('register.complete');
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

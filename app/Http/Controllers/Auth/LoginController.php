<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (! Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'These credentials do not match our records.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        $user = Auth::user();
        $user->update(['last_login_at' => now()]);

        if ($user->profile && ! $user->profile->onboarding_completed) {
            $step = $user->profile->onboarding_step_completed;

            if ($step >= 5) {
                return redirect()->route('register.verifyemail');
            }

            return redirect()->route('register.step'.($step + 1));
        }

        return redirect()->intended('/dashboard');
    }

    public function sendLoginOtp(Request $request)
    {
        // Check if mobile OTP login is enabled
        if (SiteSetting::getValue('mobile_otp_login_enabled', '1') !== '1') {
            return back()->withErrors(['phone' => 'Mobile OTP login is currently disabled.']);
        }

        $request->validate(['phone' => 'required|digits:10']);

        $user = User::where('phone', $request->phone)->first();

        if (! $user) {
            return back()->withErrors(['phone' => 'No account found with this phone number.'])->withInput();
        }

        $otpService = app(OtpService::class);
        $otpService->sendOtp($request->phone);

        return back()->with(['otp_sent' => true, 'login_phone' => $request->phone]);
    }

    public function verifyLoginOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|digits:10',
            'otp' => 'required|digits:6',
        ]);

        $otpService = app(OtpService::class);

        if (! $otpService->verifyOtp($request->phone, $request->otp)) {
            return back()->withErrors(['otp' => 'Invalid or expired OTP.'])->withInput();
        }

        $user = User::where('phone', $request->phone)->first();
        Auth::login($user, true);
        $request->session()->regenerate();
        $user->update(['last_login_at' => now()]);

        if ($user->profile && ! $user->profile->onboarding_completed) {
            $step = $user->profile->onboarding_step_completed;

            if ($step >= 5) {
                return redirect()->route('register.verifyemail');
            }

            return redirect()->route('register.step'.($step + 1));
        }

        return redirect()->intended('/dashboard');
    }

    /**
     * Send OTP to email for login.
     */
    public function sendEmailLoginOtp(Request $request)
    {
        // Check if email OTP login is enabled
        if (SiteSetting::getValue('email_otp_login_enabled', '0') !== '1') {
            return back()->withErrors(['login_email_otp' => 'Email OTP login is currently disabled.']);
        }

        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return back()->withErrors(['login_email_otp' => 'No account found with this email address.'])->withInput();
        }

        // Generate OTP
        $otp = app()->environment('local') ? '123456' : (string) random_int(100000, 999999);

        // Store hashed OTP in session
        session([
            'login_email_otp' => Hash::make($otp),
            'login_email_otp_expires' => now()->addMinutes(config('matrimony.otp_expiry_minutes', 10)),
            'login_email_otp_address' => $request->email,
        ]);

        // In local dev, log the OTP
        if (app()->environment('local')) {
            Log::info("DEV Email Login OTP for {$request->email}: {$otp}");
        } else {
            // Send OTP via email
            $siteName = SiteSetting::getValue('site_name', config('app.name'));
            Mail::raw(
                "Your {$siteName} login verification code is: {$otp}\n\nThis code expires in 10 minutes.\n\nIf you did not request this, please ignore this email.",
                function ($message) use ($request, $siteName) {
                    $message->to($request->email)->subject("Login OTP - {$siteName}");
                }
            );
        }

        return back()->with(['email_otp_sent' => true, 'login_email' => $request->email]);
    }

    /**
     * Verify email OTP and login.
     */
    public function verifyEmailLoginOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|digits:6',
        ]);

        $storedOtp = session('login_email_otp');
        $expiresAt = session('login_email_otp_expires');
        $storedEmail = session('login_email_otp_address');

        // Validate OTP
        if (! $storedOtp || ! $expiresAt || $storedEmail !== $request->email) {
            return back()->withErrors(['otp' => 'Invalid session. Please request a new OTP.'])->withInput();
        }

        if (now()->gt($expiresAt)) {
            session()->forget(['login_email_otp', 'login_email_otp_expires', 'login_email_otp_address']);
            return back()->withErrors(['otp' => 'OTP has expired. Please request a new one.'])->withInput();
        }

        if (! Hash::check((string) $request->otp, $storedOtp)) {
            return back()->withErrors(['otp' => 'Invalid OTP. Please try again.'])->withInput();
        }

        // OTP verified — login user
        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return back()->withErrors(['otp' => 'Account not found.']);
        }

        // Clear OTP session data
        session()->forget(['login_email_otp', 'login_email_otp_expires', 'login_email_otp_address']);

        Auth::login($user, true);
        $request->session()->regenerate();
        $user->update(['last_login_at' => now()]);

        // Mark email as verified if not already
        if (! $user->email_verified_at) {
            $user->update(['email_verified_at' => now()]);
        }

        if ($user->profile && ! $user->profile->onboarding_completed) {
            $step = $user->profile->onboarding_step_completed;

            if ($step >= 5) {
                return redirect()->route('register.verifyemail');
            }

            return redirect()->route('register.step'.($step + 1));
        }

        return redirect()->intended('/dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}

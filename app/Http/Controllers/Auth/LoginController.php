<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}

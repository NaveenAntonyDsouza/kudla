<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\MemberEmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    public function showForm()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        try {
            $status = Password::sendResetLink($request->only('email'));
        } catch (\Throwable $e) {
            // The mailbox refused (e.g. the hosting provider's daily sending limit)
            report($e);

            return back()->withErrors(['email' => "We couldn't send the reset email right now. Please try again in a little while."])->withInput();
        }

        // One answer whether or not the email is registered (privacy check, 5 Nov 2026:
        // "We can't find a user with that email address" told anyone who had a profile).
        // A second request for the same email within a minute is quietly not resent.
        if (! in_array($status, [Password::RESET_LINK_SENT, Password::INVALID_USER, Password::RESET_THROTTLED], true)) {
            report(new \RuntimeException("Password reset link not sent: {$status}"));
        }

        return back()->with('success', 'If an account uses this email, we have sent it a link to reset the password. Please check your inbox and spam folder.');
    }

    public function showResetForm(Request $request, string $token)
    {
        return view('auth.reset-password', ['token' => $token, 'email' => $request->email]);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:6|max:64|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill(['password' => bcrypt($password)])->save();
                app(MemberEmailService::class)->passwordChanged($user);
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('success', 'Password reset successfully! Please login.')
            : back()->withErrors(['email' => [__($status)]]);
    }
}

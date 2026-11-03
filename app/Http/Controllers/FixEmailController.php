<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use App\Support\Analytics;
use App\Support\EmailSuppression;
use App\Support\OtpResendNotice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * "Your email is not reaching you": a member whose address bounced can give
 * a new one themselves (weekly review, 2 Nov 2026: 46 members were waiting
 * for a staff phone call, the oldest 29 days, and heard nothing from the
 * site meanwhile).
 *
 * The code goes to the NEW address, so a member can only move their account
 * to a mailbox they can open.
 */
class FixEmailController extends Controller
{
    public function show()
    {
        return view('settings.fix-email', ['user' => auth()->user()]);
    }

    public function sendCode(Request $request)
    {
        $user = auth()->user();
        $request->validate([
            // No DNS check: the code we send to the address proves it works,
            // and a lookup only turns a slow resolver into a refused form
            'email' => ['required', 'email:rfc', 'max:191', 'unique:users,email,' . $user->id],
        ], [
            'email.unique' => 'That email is already used by another member.',
        ]);

        $email = Str::lower(trim($request->input('email')));
        $code = random_int(100000, 999999);

        session([
            'fix_email_address' => $email,
            'fix_email_code' => Hash::make((string) $code),
            'fix_email_expires' => now()->addMinutes(10),
        ]);

        $siteName = SiteSetting::getValue('site_name', config('app.name'));
        try {
            Mail::raw("Your {$siteName} confirmation code is: {$code}\n\nThis code expires in 10 minutes.\n\nIf you did not ask to change your email address, you can ignore this message.", function ($message) use ($email, $siteName) {
                $message->to($email)->subject("Confirm your email address - {$siteName}");
            });
        } catch (\Throwable $e) {
            report($e);
            session()->forget(['fix_email_address', 'fix_email_code', 'fix_email_expires']);

            return back()->withErrors(['email' => "We couldn't send the code right now. Please try again in a little while."]);
        }

        return back()->with(array_filter([
            'fix_email_sent' => true,
            'otp_resent' => $request->boolean('resend') ? OtpResendNotice::email($email) : null,
        ]));
    }

    public function confirm(Request $request)
    {
        $request->validate(['code' => 'required|digits:6']);

        $email = session('fix_email_address');
        $stored = session('fix_email_code');
        $expires = session('fix_email_expires');

        if (! $email || ! $stored || ! Hash::check((string) $request->input('code'), $stored) || now()->gt($expires)) {
            return back()->withErrors(['code' => 'That code is wrong or has expired. Please ask for a new one.'])
                ->with('fix_email_sent', true);
        }

        $user = auth()->user();
        $user->forceFill(['email' => $email, 'email_verified_at' => now()])->save();

        // The block was recorded against the old address; the member has just
        // proved they can open the new one, so the site may write again. The
        // flag lives on the member's row, which now carries the new address.
        EmailSuppression::clear($email);

        session()->forget(['fix_email_address', 'fix_email_code', 'fix_email_expires']);
        Analytics::track('email_fixed');

        return redirect()->route('dashboard')->with('success', 'Thank you — your email address is updated. You will start getting matches and interest alerts again.');
    }
}

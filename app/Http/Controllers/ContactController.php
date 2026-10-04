<?php

namespace App\Http\Controllers;

use App\Models\ContactSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

class ContactController extends Controller
{
    public function show()
    {
        return view('pages.contact');
    }

    /** Messages one visitor (IP) may send per window, and the window in seconds. */
    public const MAX_PER_WINDOW = 3;
    public const WINDOW_SECONDS = 600;

    /** A person needs at least this long to fill the form in. */
    public const MIN_SECONDS_ON_FORM = 3;

    public function submit(Request $request)
    {
        $thanks = 'Thank you for reaching out! We will get back to you within 24-48 hours.';

        // Bots: filled the hidden field, or posted without / too soon after
        // loading the form. They get the normal thank-you so they don't adapt;
        // nothing is saved or emailed.
        if ($this->looksAutomated($request)) {
            \Log::info('Contact form: automated submission dropped', ['ip' => $request->ip()]);

            return back()->with('success', $thanks);
        }

        $key = 'contact-form:'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, self::MAX_PER_WINDOW)) {
            return back()->withInput()->withErrors([
                'message' => 'You have sent several messages in the last few minutes. Please wait a little and try again, or call us.',
            ]);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'subject' => 'required|string|max:100',
            'message' => 'required|string|max:2000',
        ]);

        RateLimiter::hit($key, self::WINDOW_SECONDS);
        \App\Support\Analytics::track('contact_message_sent', ['subject' => $validated['subject'], 'member' => auth()->check()]);

        // Save to database
        ContactSubmission::create([
            'user_id' => auth()->id(),
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'subject' => $validated['subject'],
            'message' => $validated['message'],
            'ip_address' => $request->ip(),
        ]);

        // Also send email to admin
        $adminEmail = \App\Models\SiteSetting::getValue('contact_email', config('mail.from.address'));

        try {
            Mail::raw(
                "New Contact Us Inquiry\n\n" .
                "Name: {$validated['name']}\n" .
                "Email: {$validated['email']}\n" .
                "Phone: " . ($validated['phone'] ?? 'Not provided') . "\n" .
                "Subject: {$validated['subject']}\n\n" .
                "Message:\n{$validated['message']}\n\n" .
                "---\n" .
                "Sent from " . config('app.name') . " Contact Form\n" .
                (auth()->check() ? "User: " . auth()->user()->profile?->matri_id : "Guest user"),
                function ($mail) use ($adminEmail, $validated) {
                    $mail->to($adminEmail)
                        ->subject("Contact: {$validated['subject']}")
                        ->replyTo($validated['email'], $validated['name']);
                }
            );
        } catch (\Exception $e) {
            \Log::error('Contact form email failed: ' . $e->getMessage());
        }

        return back()->with('success', $thanks);
    }

    private function looksAutomated(Request $request): bool
    {
        if (filled($request->input('website'))) {
            return true;
        }

        try {
            $openedAt = (int) decrypt((string) $request->input('form_ts'));
        } catch (\Throwable) {
            return true; // posted without loading our form
        }

        return now()->timestamp - $openedAt < self::MIN_SECONDS_ON_FORM;
    }
}

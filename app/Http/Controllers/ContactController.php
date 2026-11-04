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
        if ($reason = $this->looksAutomated($request)) {
            \Log::info('Contact form: automated submission dropped', ['ip' => $request->ip(), 'reason' => $reason]);
            self::countSpam($reason);

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

    /** Why a submission looks automated, or null when it looks like a person. */
    private function looksAutomated(Request $request): ?string
    {
        if (filled($request->input('website'))) {
            return 'hidden_field';
        }

        try {
            $openedAt = (int) decrypt((string) $request->input('form_ts'));
        } catch (\Throwable) {
            return 'no_form'; // posted without loading our form
        }

        return now()->timestamp - $openedAt < self::MIN_SECONDS_ON_FORM ? 'too_fast' : null;
    }

    /** Dropped bot messages are kept this many days, counted per day and reason. */
    public const SPAM_COUNT_DAYS = 90;

    public const SPAM_REASONS = ['hidden_field', 'no_form', 'too_fast'];

    /**
     * Count a dropped bot message (Nov 2026: the weekly review could not see
     * them — the log line is info level and live sites keep errors only).
     */
    public static function countSpam(string $reason): void
    {
        try {
            $key = 'contact-spam:' . now()->toDateString() . ':' . $reason;
            \Illuminate\Support\Facades\Cache::add($key, 0, now()->addDays(self::SPAM_COUNT_DAYS));
            \Illuminate\Support\Facades\Cache::increment($key);
        } catch (\Throwable $e) {
            report($e); // counting must never break the reply
        }
    }

    /**
     * Dropped bot messages for the last $days days, for the weekly review:
     * ['2026-11-04' => ['hidden_field' => 3, 'no_form' => 0, 'too_fast' => 1], …], newest first.
     *
     * @return array<string, array<string, int>>
     */
    public static function spamCounts(int $days = 7): array
    {
        $out = [];
        for ($i = 0; $i < $days; $i++) {
            $date = now()->subDays($i)->toDateString();
            foreach (self::SPAM_REASONS as $reason) {
                $out[$date][$reason] = (int) \Illuminate\Support\Facades\Cache::get("contact-spam:{$date}:{$reason}", 0);
            }
        }

        return $out;
    }
}

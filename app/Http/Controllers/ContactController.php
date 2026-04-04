<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function show()
    {
        return view('pages.contact');
    }

    public function submit(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'subject' => 'required|string|max:100',
            'message' => 'required|string|max:2000',
        ]);

        // Send email to admin
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
            // Log but don't fail — still show success to user
            \Log::error('Contact form email failed: ' . $e->getMessage());
        }

        return back()->with('success', 'Thank you for reaching out! We will get back to you within 24-48 hours.');
    }
}

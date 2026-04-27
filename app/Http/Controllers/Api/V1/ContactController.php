<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Responses\ApiResponse;
use App\Models\ContactSubmission;
use App\Models\SiteSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

/**
 * Public contact form endpoint.
 *
 *   POST /api/v1/contact   (anonymous OK; throttled 5/hour)
 *
 * The submission is always persisted (canonical record); the email
 * notification to the admin is best-effort — if SMTP isn't configured
 * yet on a fresh CodeCanyon install, the contact form still works.
 *
 * Reference: docs/mobile-app/phase-2a-api/week-04-interests-payment-push/step-13-engagement-public.md
 */
class ContactController extends BaseApiController
{
    /**
     * Submit a contact-form message.
     *
     * @group Contact
     *
     * @bodyParam name string required Max 120.
     * @bodyParam email string required Valid email.
     * @bodyParam phone string Optional. Max 20 (international-tolerant).
     * @bodyParam subject string required Max 200.
     * @bodyParam message string required Max 2000.
     *
     * @response 201 scenario="success" {
     *   "success": true,
     *   "data": {"submission_id": 42, "message": "Thanks! We'll reply within 24 hours."}
     * }
     * @response 422 scenario="validation" {"success": false, "error": {"code": "VALIDATION_FAILED", ...}}
     */
    public function submit(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:200',
            'phone' => 'nullable|string|max:20',
            'subject' => 'required|string|max:200',
            'message' => 'required|string|max:2000',
        ]);

        $submission = ContactSubmission::create(array_merge($data, [
            'user_id' => $request->user()?->id,
            'ip_address' => $request->ip(),
            'status' => 'new',
        ]));

        $this->notifyAdmin($submission);

        return ApiResponse::created([
            'submission_id' => (int) $submission->id,
            'message' => "Thanks! We'll reply within 24 hours.",
        ]);
    }

    /**
     * Best-effort admin notification. Wrapped in try/catch so a missing
     * SMTP config (fresh CodeCanyon install) doesn't take the contact
     * form down — the buyer can configure mail later and replay history
     * via Filament admin.
     */
    private function notifyAdmin(ContactSubmission $submission): void
    {
        $adminEmail = (string) SiteSetting::getValue('email', '');
        if ($adminEmail === '') {
            return;
        }

        $body = sprintf(
            "New contact form submission #%d\n\n".
            "Name:    %s\nEmail:   %s\nPhone:   %s\nSubject: %s\n\nMessage:\n%s",
            $submission->id,
            $submission->name,
            $submission->email,
            $submission->phone ?: '—',
            $submission->subject,
            $submission->message,
        );

        try {
            Mail::raw($body, function ($mail) use ($adminEmail, $submission) {
                $mail->to($adminEmail)
                    ->subject('New contact: '.$submission->subject)
                    ->replyTo($submission->email, $submission->name);
            });
        } catch (\Throwable $e) {
            // Mail mis-configured — submission already persisted. Log
            // for dev visibility without breaking the user-facing flow.
            report($e);
        }
    }
}

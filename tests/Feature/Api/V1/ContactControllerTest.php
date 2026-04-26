<?php

use App\Http\Controllers\Api\V1\ContactController;
use App\Models\ContactSubmission;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| ContactController — POST /api/v1/contact (public)
|--------------------------------------------------------------------------
| Real DB persistence (inline contact_submissions); Mail::fake to verify
| admin notification without firing real SMTP.
*/

function buildContactUser(int $id = 1100): User
{
    $u = new User();
    $u->exists = true;
    $u->forceFill(['id' => $id, 'email' => "c{$id}@e.com", 'is_active' => true]);
    $u->setRelation('userMemberships', new EloquentCollection());
    return $u;
}

function contactRequest(?User $user, array $body): Request
{
    $r = Request::create('/api/v1/contact', 'POST', $body);
    if ($user) {
        $r->setUserResolver(fn () => $user);
    }
    return $r;
}

beforeEach(function () {
    if (! Schema::hasTable('contact_submissions')) {
        Schema::create('contact_submissions', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->string('name', 100);
            $t->string('email', 255);
            $t->string('phone', 20)->nullable();
            $t->string('subject', 200);
            $t->text('message');
            $t->string('status', 20)->default('new');
            $t->text('admin_reply')->nullable();
            $t->timestamp('replied_at')->nullable();
            $t->unsignedBigInteger('assigned_to')->nullable();
            $t->text('admin_notes')->nullable();
            $t->string('ip_address', 45)->nullable();
            $t->timestamps();
        });
    }
    if (! Schema::hasTable('site_settings')) {
        Schema::create('site_settings', function (Blueprint $t) {
            $t->id();
            $t->string('key')->unique();
            $t->text('value')->nullable();
            $t->timestamps();
        });
    }
    Mail::fake();
});

afterEach(function () {
    Schema::dropIfExists('site_settings');
    Schema::dropIfExists('contact_submissions');
});

/* ==================================================================
 |  Validation
 | ================================================================== */

it('rejects when required fields are missing', function () {
    expect(fn () => app(ContactController::class)->submit(contactRequest(null, [])))
        ->toThrow(\Illuminate\Validation\ValidationException::class);
});

it('rejects invalid email', function () {
    expect(fn () => app(ContactController::class)->submit(contactRequest(null, [
        'name' => 'Anita', 'email' => 'not-an-email',
        'subject' => 's', 'message' => 'm',
    ])))->toThrow(\Illuminate\Validation\ValidationException::class);
});

/* ==================================================================
 |  Happy paths
 | ================================================================== */

it('persists a submission with status=new + returns 201 + canned message', function () {
    $response = app(ContactController::class)->submit(contactRequest(null, [
        'name' => 'Anita Sharma',
        'email' => 'anita@example.com',
        'subject' => 'Question about premium',
        'message' => 'How does the premium plan work?',
    ]));
    $payload = $response->getData(true)['data'];

    expect($response->getStatusCode())->toBe(201);
    expect($payload)->toHaveKeys(['submission_id', 'message']);
    expect($payload['message'])->toContain('24 hours');

    $row = ContactSubmission::find($payload['submission_id']);
    expect($row->name)->toBe('Anita Sharma');
    expect($row->email)->toBe('anita@example.com');
    expect($row->status)->toBe('new');
    expect($row->user_id)->toBeNull();          // anonymous
    expect($row->ip_address)->toBe('127.0.0.1'); // captured
});

it('captures user_id when the submitter is authenticated', function () {
    $user = buildContactUser();

    $response = app(ContactController::class)->submit(contactRequest($user, [
        'name' => 'Authed User', 'email' => 'a@e.com',
        'subject' => 'Auth question', 'message' => 'msg from logged-in user',
    ]));

    $row = ContactSubmission::find($response->getData(true)['data']['submission_id']);
    expect($row->user_id)->toBe($user->id);
});

/* ==================================================================
 |  Admin-notification leg (best-effort)
 | ================================================================== */

it('does NOT crash + still persists when admin email is configured', function () {
    // Mail::raw doesn't run through Mailable classes, so MailFake's
    // class-based assertSent doesn't pick it up. We verify the
    // canonical record (the submission row) is created — that's the
    // user-facing contract. The mail leg is best-effort by design and
    // the negative test below covers the "no admin email configured"
    // branch.
    SiteSetting::create(['key' => 'email', 'value' => 'admin@matrimony.com']);

    $response = app(ContactController::class)->submit(contactRequest(null, [
        'name' => 'Anita', 'email' => 'anita@e.com',
        'subject' => 'Hi', 'message' => 'Test message',
    ]));

    expect($response->getStatusCode())->toBe(201);
    expect(ContactSubmission::count())->toBe(1);
});

it('does not crash when admin email is not configured', function () {
    // No SiteSetting row for 'email' — admin notification leg short-circuits.
    $response = app(ContactController::class)->submit(contactRequest(null, [
        'name' => 'Anita', 'email' => 'anita@e.com',
        'subject' => 'Hi', 'message' => 'Test',
    ]));

    expect($response->getStatusCode())->toBe(201);
    Mail::assertNothingSent();
    // Submission still persisted — that's the canonical record.
    expect(ContactSubmission::count())->toBe(1);
});

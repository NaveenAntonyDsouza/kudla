<?php

use App\Http\Controllers\ContactController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Contact form spam guard (web)
|--------------------------------------------------------------------------
| A bot sent the same ad 5 times in 12 seconds on kudla (4 Oct 2026); each
| one was saved and emailed to the admin. Now: a hidden "website" field and
| a "form open for at least 3 seconds" check drop bots silently (they still
| see the thank-you), and one visitor can send 3 messages per 10 minutes.
*/

beforeEach(function () {
    Schema::create('contact_submissions', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('user_id')->nullable();
        $t->string('name', 100);
        $t->string('email', 255);
        $t->string('phone', 20)->nullable();
        $t->string('subject', 200);
        $t->text('message');
        $t->string('status', 20)->default('new');
        $t->string('ip_address', 45)->nullable();
        $t->timestamps();
    });
    Schema::create('site_settings', function (Blueprint $t) {
        $t->id();
        $t->string('key')->unique();
        $t->text('value')->nullable();
        $t->timestamps();
    });
    Mail::fake();
    RateLimiter::clear('contact-form:127.0.0.1');
});

afterEach(function () {
    Schema::dropIfExists('contact_submissions');
    Schema::dropIfExists('site_settings');
});

function contactForm(array $overrides = []): array
{
    return array_merge([
        'name' => 'Asha Rao', 'email' => 'asha@example.test', 'subject' => 'General Inquiry',
        'message' => 'How do I change my photo?', 'website' => '',
        'form_ts' => encrypt(now()->subSeconds(30)->timestamp),
    ], $overrides);
}

it('accepts a real message', function () {
    $this->from('/contact-us')->post('/contact-us', contactForm())
        ->assertRedirect('/contact-us')->assertSessionHas('success');

    expect(DB::table('contact_submissions')->count())->toBe(1);
});

it('drops bots silently: hidden field filled, no form timestamp, or sent too fast', function (array $bot) {
    $this->from('/contact-us')->post('/contact-us', contactForm($bot))
        ->assertRedirect('/contact-us')->assertSessionHas('success');

    expect(DB::table('contact_submissions')->count())->toBe(0);
    Mail::assertNothingOutgoing();
})->with([
    'hidden field' => [['website' => 'https://spam.example']],
    'no timestamp' => [['form_ts' => null]],
    'forged timestamp' => [['form_ts' => '1700000000']],
    'too fast' => [fn () => ['form_ts' => encrypt(now()->timestamp)]],
]);

it('lets one visitor send 3 messages per 10 minutes', function () {
    foreach (range(1, ContactController::MAX_PER_WINDOW) as $i) {
        $this->from('/contact-us')->post('/contact-us', contactForm())->assertSessionHas('success');
    }

    $this->from('/contact-us')->post('/contact-us', contactForm())->assertSessionHasErrors('message');
    expect(DB::table('contact_submissions')->count())->toBe(ContactController::MAX_PER_WINDOW);

    $this->travel(ContactController::WINDOW_SECONDS + 1)->seconds();
    $this->from('/contact-us')->post('/contact-us', contactForm(['form_ts' => encrypt(now()->subSeconds(30)->timestamp)]))
        ->assertSessionHas('success');
});

it('puts the hidden field and the timestamp on the form', function () {
    // The full page needs the site layout; the form source is what matters
    $form = file_get_contents(resource_path('views/pages/contact.blade.php'));

    expect($form)->toContain('name="website"')->toContain('name="form_ts" value="{{ encrypt(now()->timestamp) }}"');
});

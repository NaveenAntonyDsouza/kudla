<?php

use App\Mail\PhotoApprovedMail;
use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Email template Active switch
|--------------------------------------------------------------------------
| Switching a template to Inactive in admin (Email Templates → Active) must
| stop that email — DatabaseMailable::send() checks EmailTemplate::isDisabled
| before sending. Previously an inactive template still sent a generic,
| near-empty "You have a new notification" email.
|
| Sends for real through the `array` mailer (MAIL_MAILER=array in
| phpunit.xml) and counts the messages that reach the transport — Mail::fake
| would bypass send() and prove nothing.
*/

beforeEach(function () {
    Schema::create('users', function (Blueprint $t) {
        $t->id();
        $t->string('name')->nullable();
        $t->string('email')->nullable();
        $t->string('password')->nullable();
        $t->unsignedBigInteger('branch_id')->nullable();
        $t->timestamps();
    });
    Schema::create('email_templates', function (Blueprint $t) {
        $t->id();
        $t->string('slug')->unique();
        $t->string('name')->nullable();
        $t->string('subject')->nullable();
        $t->text('body_html')->nullable();
        $t->json('variables')->nullable();
        $t->boolean('is_active')->default(true);
        $t->timestamps();
    });
    // Read while rendering (site name, brand colours for the email wrapper).
    Schema::create('site_settings', function (Blueprint $t) {
        $t->id();
        $t->string('key')->unique();
        $t->text('value')->nullable();
        $t->timestamps();
    });
    Schema::create('theme_settings', function (Blueprint $t) {
        $t->id();
        $t->string('primary_color')->nullable();
        $t->string('primary_hover')->nullable();
        $t->string('primary_light')->nullable();
        $t->string('secondary_color')->nullable();
        $t->string('logo_url')->nullable();
        $t->timestamps();
    });
});

afterEach(function () {
    foreach (['theme_settings', 'site_settings', 'email_templates', 'users'] as $table) {
        Schema::dropIfExists($table);
    }
});

function sentCount(): int
{
    return count(Mail::mailer()->getSymfonyTransport()->messages());
}

function switchOffMember(): User
{
    return User::create(['name' => 'Test Member', 'email' => 'member@example.test', 'password' => 'x']);
}

it('sends the email while its template is active', function () {
    EmailTemplate::create([
        'slug' => 'photo-approved', 'name' => 'Photo Approved', 'is_active' => true,
        'subject' => 'Photo approved - {{SITE_NAME}}', 'body_html' => '<p>Dear {{USER_NAME}}</p>',
    ]);

    Mail::to('member@example.test')->send(new PhotoApprovedMail(switchOffMember()));

    expect(sentCount())->toBe(1);
});

it('does not send the email once an admin switches its template off', function () {
    EmailTemplate::create([
        'slug' => 'photo-approved', 'name' => 'Photo Approved', 'is_active' => false,
        'subject' => 'Photo approved', 'body_html' => '<p>x</p>',
    ]);

    Mail::to('member@example.test')->send(new PhotoApprovedMail(switchOffMember()));

    expect(sentCount())->toBe(0);
});

it('still sends with the built-in fallback when no template row exists', function () {
    Mail::to('member@example.test')->send(new PhotoApprovedMail(switchOffMember()));

    expect(sentCount())->toBe(1);
});

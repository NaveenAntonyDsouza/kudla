<?php

use App\Mail\PasswordChangedMail;
use App\Mail\WeeklyMatchSuggestionsMail;
use App\Mail\WelcomeMail;
use App\Models\EmailTemplate;
use App\Models\User;
use App\Services\MemberEmailService;
use App\Support\EmailPreheaders;
use App\Support\MemberName;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Email foundations (October 2026 email benchmark)
|--------------------------------------------------------------------------
| - Member-supplied text is escaped in email bodies; unfilled {{CODES}} are
|   removed instead of reaching members (two competitors shipped them).
| - Preview text renders, and links back to the site carry campaign tags
|   for GA4, except signed links.
| - Preference-backed emails carry one-tap unsubscribe headers (RFC 8058),
|   and the one-tap POST works without a login.
| - Members are named "First L." with their Matri ID in match emails.
| - A password change sends a security alert.
| - No email template names a real person (owner's rule).
*/

beforeEach(function () {
    Schema::create('users', function (Blueprint $t) {
        $t->id();
        $t->string('name')->nullable();
        $t->string('email')->nullable();
        $t->string('password')->nullable();
        $t->string('role')->nullable();
        $t->unsignedBigInteger('staff_role_id')->nullable();
        $t->unsignedBigInteger('branch_id')->nullable();
        $t->boolean('is_active')->default(true);
        $t->json('notification_preferences')->nullable();
        $t->timestamp('last_login_at')->nullable();
        $t->rememberToken();
        $t->timestamps();
    });
    Schema::create('profiles', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('user_id');
        $t->unsignedBigInteger('branch_id')->nullable();
        $t->string('matri_id')->nullable();
        $t->string('full_name')->nullable();
        $t->boolean('is_active')->default(true);
        $t->string('suspension_status')->nullable();
        $t->boolean('onboarding_completed')->default(true);
        $t->integer('onboarding_step_completed')->default(5);
        $t->softDeletes();
        $t->timestamps();
    });
    Schema::create('email_templates', function (Blueprint $t) {
        $t->id();
        $t->string('slug')->unique();
        $t->string('name')->nullable();
        $t->string('subject')->nullable();
        $t->string('preheader')->nullable();
        $t->text('body_html')->nullable();
        $t->json('variables')->nullable();
        $t->boolean('is_active')->default(true);
        $t->timestamps();
    });
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
    foreach (['theme_settings', 'site_settings', 'email_templates', 'profiles', 'users'] as $table) {
        Schema::dropIfExists($table);
    }
});

function efMember(string $email = 'member@example.test'): User
{
    $user = User::create(['name' => 'Naveen Antony Dsouza', 'email' => $email, 'password' => Hash::make('old-secret-1'), 'role' => 'user']);
    DB::table('profiles')->insert(['user_id' => $user->id, 'matri_id' => 'KM100717', 'full_name' => $user->name, 'created_at' => now(), 'updated_at' => now()]);

    return $user->fresh();
}

it('escapes member-supplied text in the body, keeps *_HTML markup, and leaves subjects plain', function () {
    $template = new EmailTemplate([
        'slug' => 'x',
        'subject' => 'Hello {{USER_NAME}}',
        'preheader' => 'For {{USER_NAME}}',
        'body_html' => '<p>{{USER_NAME}}</p>{{CARDS_HTML}}',
    ]);

    $rendered = $template->render(['USER_NAME' => '<a href="https://evil.test">Win</a>', 'CARDS_HTML' => '<b>card</b>']);

    expect($rendered['body'])->toContain('&lt;a href=&quot;https://evil.test&quot;&gt;')
        ->not->toContain('<a href="https://evil.test">')
        ->toContain('<b>card</b>')
        ->and($rendered['subject'])->toBe('Hello <a href="https://evil.test">Win</a>')
        ->and($rendered['preheader'])->toBe('For <a href="https://evil.test">Win</a>');
});

it('removes placeholders the email does not fill instead of showing them', function () {
    $template = new EmailTemplate(['slug' => 'x', 'subject' => 'Hi {{NAME}}{{TYPO}}', 'body_html' => '<p>Expires in {{EXPIRY_IN_DAYS}} days</p>']);

    $rendered = $template->render(['NAME' => 'Asha']);

    expect($rendered['subject'])->toBe('Hi Asha')
        ->and($rendered['body'])->toBe('<p>Expires in  days</p>');
});

it('adds one-tap unsubscribe headers to preference emails only', function () {
    $user = efMember();

    $weekly = (new WeeklyMatchSuggestionsMail($user, collect()))->headers()->text;
    $welcome = (new WelcomeMail($user))->headers()->text;

    expect($weekly['List-Unsubscribe'])->toStartWith('<')->toContain("/unsubscribe/{$user->id}/email_weekly_matches?signature=")
        ->and($weekly['List-Unsubscribe-Post'])->toBe('List-Unsubscribe=One-Click')
        ->and($weekly['Feedback-ID'])->toStartWith('weekly-match-suggestions:')
        ->and($welcome)->not->toHaveKey('List-Unsubscribe')
        ->and($welcome['Feedback-ID'])->toStartWith('welcome:');
});

it('renders the preview text and tags site links for GA4, but not signed or outside links', function () {
    $user = efMember();
    EmailTemplate::create([
        'slug' => 'weekly-match-suggestions', 'name' => 'Weekly', 'subject' => 'Your matches',
        'preheader' => 'Matches for {{USER_NAME}}',
        'body_html' => '<a href="{{MATCHES_URL}}">All</a> <a href="{{UNSUBSCRIBE_URL}}">Unsubscribe</a> <a href="https://example.org/x">Outside</a>',
    ]);

    $html = (new WeeklyMatchSuggestionsMail($user, collect()))->render();

    expect($html)->toContain('Matches for Naveen')
        ->toContain('/matches?utm_source=email&amp;utm_medium=email&amp;utm_campaign=weekly-match-suggestions')
        ->toContain('href="https://example.org/x"');

    preg_match('/href="([^"]*unsubscribe[^"]*)"/', $html, $unsubscribe);
    expect($unsubscribe[1])->toContain('signature=')->not->toContain('utm_');
});

it('unsubscribes with a one-tap POST to the signed link, without a login', function () {
    $user = efMember();
    $url = $user->unsubscribeUrl('email_weekly_matches');

    $this->post($url, ['List-Unsubscribe' => 'One-Click'])->assertOk();
    expect($user->fresh()->wantsNotification('email_weekly_matches'))->toBeFalse();

    $this->post($url . 'tampered')->assertForbidden();
});

it('needs a signed link to switch emails back on', function () {
    $user = efMember();

    $this->get("/resubscribe/{$user->id}/email_weekly_matches")->assertForbidden();
});

it('keeps campaign tags when a logged-out member is sent to the login page', function () {
    $this->get('/matches?utm_source=email&utm_medium=email&utm_campaign=weekly-match-suggestions')
        ->assertRedirect('/login?utm_source=email&utm_medium=email&utm_campaign=weekly-match-suggestions');
});

it('names other members as first name and last initial', function () {
    expect(MemberName::short('Priya Shetty'))->toBe('Priya S.')
        ->and(MemberName::short('Dr Maria Celine Dsouza'))->toBe('Maria D.')
        ->and(MemberName::short('Ashwitha'))->toBe('Ashwitha')
        ->and(MemberName::short('  '))->toBe('')
        ->and(MemberName::first("Naveen Antony D'Souza"))->toBe('Naveen');
});

it('sends a security alert when a member changes their password', function () {
    Mail::fake();
    $user = efMember();

    $this->actingAs($user)
        ->post('/settings/password', ['current_password' => 'old-secret-1', 'new_password' => 'new-secret-2', 'new_password_confirmation' => 'new-secret-2'])
        ->assertSessionHasNoErrors();

    Mail::assertQueued(PasswordChangedMail::class, fn ($mail) => $mail->hasTo('member@example.test'));
});

it('sends the password alert even to members who switched other emails off', function () {
    Mail::fake();
    $user = efMember();
    $user->update(['notification_preferences' => ['email_interest' => false, 'email_promotions' => false]]);

    app(MemberEmailService::class)->passwordChanged($user);

    Mail::assertQueued(PasswordChangedMail::class);
    expect((new PasswordChangedMail($user, 'now'))->headers()->text)->not->toHaveKey('List-Unsubscribe');
});

it('seeds every template with preview text, only known placeholders, and no real person\'s name', function () {
    (new Database\Seeders\EmailTemplateSeeder)->run();

    $globals = ['SITE_NAME', 'SITE_URL', 'LOGIN_URL', 'PRIMARY_COLOR', 'PRIMARY_HOVER', 'PRIMARY_LIGHT', 'SECONDARY_COLOR', 'LOGO_URL', 'TAGLINE'];

    foreach (EmailTemplate::all() as $template) {
        expect($template->preheader)->not->toBeEmpty("{$template->slug} has no preview text");

        preg_match_all('/\{\{([A-Z0-9_]+)\}\}/', $template->subject . $template->preheader . $template->body_html, $m);
        $unknown = array_diff(array_unique($m[1]), array_merge($template->variables ?? [], $globals));
        expect($unknown)->toBe([], "{$template->slug} uses undeclared placeholders");

        expect(stripos($template->subject . $template->body_html . $template->preheader, 'Naveen'))->toBeFalse("{$template->slug} names a real person");
    }

    expect(array_diff(EmailTemplate::pluck('slug')->all(), array_keys(EmailPreheaders::DEFAULTS)))->toBe([]);
});

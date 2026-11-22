<?php

use App\Models\MemberEvent;
use App\Models\User;
use App\Support\AdminModules;
use App\Support\MemberEvents;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\PrivacyBench\Bench;

/*
| Where sign-ups start (owner 22 Nov 2026, asked whether to remove the registration form
| from the home page: "Measure first"). Each new account records one signup_started event
| whose subject is the page the form was on: home / register_page / other.
*/

beforeEach(function () {
    Bench::guard();
    Mail::fake();
    Http::fake();
    Cache::flush();
    SiteSetting::setValue(AdminModules::PAYMENT_INSIGHTS, '1');
});

function signUp(string $tag, ?string $from): User
{
    test()->post('/register', array_filter([
        'full_name' => 'Start '.$tag, 'gender' => 'female', 'date_of_birth' => '1996-03-03',
        'phone' => '96'.str_pad((string) crc32($tag) % 100000000, 8, '0', STR_PAD_LEFT),
        'email' => 'start-'.$tag.'@bench.test', 'password' => 'start-pass-9', 'accept_terms' => '1',
        'signup_from' => $from,
    ], fn ($v) => $v !== null))->assertRedirect(route('register.step2'));
    auth()->logout();

    return User::where('email', 'start-'.$tag.'@bench.test')->firstOrFail();
}

it('records whether a sign-up started on the home page, the register page or elsewhere', function () {
    $cases = ['home' => 'home', 'register_page' => 'register_page', 'something-made-up' => 'other', null => 'other'];
    foreach ($cases as $sent => $expected) {
        $user = signUp('t'.md5((string) $sent), $sent === '' ? null : $sent);
        $events = MemberEvent::where('user_id', $user->id)->where('event', MemberEvents::SIGNUP_STARTED)->get();
        expect($events)->toHaveCount(1)
            ->and($events->first()->subject)->toBe($expected)
            ->and($events->first()->data)->toBeNull();               // no names, emails or phones
    }
});

it('puts the right hidden mark on every sign-up form', function () {
    expect((string) $this->get('/register')->getContent())->toContain('name="signup_from" value="register_page"');
    foreach (['pages/home/garland', 'pages/home/classic', 'pages/home'] as $view) {
        expect(file_get_contents(resource_path("views/{$view}.blade.php")))->toContain('name="signup_from" value="home"');
    }
});

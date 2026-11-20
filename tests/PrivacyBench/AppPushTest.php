<?php

use App\Models\Device;
use App\Models\User;
use App\Services\NotificationService;
use App\Support\EmailAlerts;
use App\Support\LoginAs;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Tests\PrivacyBench\Bench;

/*
| Push notifications for the Kudla WebView app 1.0.7 (20 Nov 2026). The app keeps the phone's
| push token in the km_app_push cookie; a page opened by a signed-in member links it to that
| member, signing out unlinks it, and Manage Alerts gets an "App notifications" section only
| for members with a linked phone. Never during a staff "Login as member" visit.
*/

const APP_UA = 'Mozilla/5.0 (Linux; Android 14; wv) AppleWebKit/537.36 Chrome/130 Mobile Safari/537.36 KudlaMatrimonyApp/1.0.7';

beforeEach(function () {
    Bench::guard();
    Mail::fake();
    Http::fake();
    $this->b = Bench::build();
    foreach ($this->b->m as $p) {
        $p->forceFill(['onboarding_completed' => true])->save();
    }
    $this->appGet = fn (User $user, string $url, string $token = 'phoneTokenA:APA91b_x-1') => $this->actingAs($user)
        ->withUnencryptedCookie('km_app_push', $token)
        ->withHeader('User-Agent', APP_UA)
        ->get($url);
});

it('links the phone to the signed-in member on the next page, once per sign-in', function () {
    $t = $this->b->m['T']->user;

    ($this->appGet)($t, '/dashboard')->assertOk();
    ($this->appGet)($t, '/dashboard')->assertOk();

    $rows = Device::where('user_id', $t->id)->get();
    expect($rows)->toHaveCount(1)
        ->and($rows[0]->fcm_token)->toBe('phoneTokenA:APA91b_x-1')
        ->and($rows[0]->is_active)->toBeTrue()
        ->and($rows[0]->platform)->toBe('android')
        ->and($rows[0]->app_version)->toBe('1.0.7')
        ->and($rows[0]->os_version)->toBe('Android 14')
        ->and($rows[0]->device_model)->toBeNull();          // nothing more about the phone is kept
});

it('ignores the cookie for visitors and for a malformed token', function () {
    $this->withUnencryptedCookie('km_app_push', 'phoneTokenA')->withHeader('User-Agent', APP_UA)->get('/');
    ($this->appGet)($this->b->m['T']->user, '/dashboard', 'bad token<script>');

    expect(Device::count())->toBe(0);
});

it('moves the phone to the member signed in now, so the first one gets no more pushes', function () {
    $t = $this->b->m['T']->user;
    $v = $this->b->m['V']->user;

    ($this->appGet)($t, '/dashboard')->assertOk();
    auth()->logout();
    session()->flush();
    ($this->appGet)($v, '/dashboard')->assertOk();

    expect(Device::where('user_id', $t->id)->where('is_active', true)->count())->toBe(0)
        ->and(Device::where('user_id', $v->id)->where('is_active', true)->count())->toBe(1);
});

it('stops pushes to the phone when the member signs out in the app', function () {
    $t = $this->b->m['T']->user;
    ($this->appGet)($t, '/dashboard')->assertOk();

    $this->withUnencryptedCookie('km_app_push', 'phoneTokenA:APA91b_x-1')->withHeader('User-Agent', APP_UA)->post('/logout')->assertRedirect();

    expect(Device::where('user_id', $t->id)->first()->is_active)->toBeFalse();
});

it('never links a phone during a staff "Login as member" visit', function () {
    $t = $this->b->m['T'];
    $staffRole = DB::table('staff_roles')->where('slug', 'super_admin')->value('id');
    $owner = tap(User::create([
        'name' => 'Owner Push Bench', 'email' => 'owner-push@bench.test', 'password' => bcrypt('x-login-as-pass-7'),
        'role' => 'admin', 'is_active' => true, 'staff_role_id' => $staffRole, 'email_verified_at' => now(),
    ]), fn (User $u) => $u->saveAppAuthenticationSecret('BENCHSECRETBENCH'))->fresh();
    request()->setLaravelSession(app('session.store'));
    auth('admin')->setUser($owner);
    LoginAs::start($owner, $t, 'Member phoned: no notifications on her phone');

    $this->withUnencryptedCookie('km_app_push', 'staffPhoneToken')->withHeader('User-Agent', APP_UA)->get('/dashboard')->assertOk();

    expect(Device::count())->toBe(0);
});

it('asks the app to offer notifications only on app pages of a signed-in member', function () {
    $t = $this->b->m['T']->user;

    expect(($this->appGet)($t, '/dashboard')->getContent())->toContain('KudlaApp.signedIn()')
        ->and($this->actingAs($t)->withHeader('User-Agent', 'Mozilla/5.0 Chrome/130')->get('/dashboard')->getContent())->not->toContain('KudlaApp.signedIn()');
});

it('shows App notifications in Manage Alerts only with a linked phone, all ticked', function () {
    $t = $this->b->m['T']->user;
    $t->update(['notification_preferences' => null]);

    $html = $this->actingAs($t)->get('/settings?section=manage_alerts')->assertOk()->getContent();
    expect($html)->not->toContain('data-alert-channel="App notifications"')->not->toContain('name="push_section"');

    Device::create(['user_id' => $t->id, 'fcm_token' => 'tokT', 'platform' => 'android', 'is_active' => true]);
    $html = $this->actingAs($t)->get('/settings?section=manage_alerts')->assertOk()->getContent();
    expect($html)->toContain('data-alert-channel="App notifications"')->toContain('name="push_section"');
    foreach (array_keys(EmailAlerts::PUSH) as $key) {
        expect($html)->toMatch('/data-alert="'.$key.'">\s*<input type="checkbox" name="'.$key.'" value="1"\s*checked/');
    }
});

it('saves the app ticks, keeps everything else, and never switches them off from a page that did not show them', function () {
    $t = $this->b->m['T']->user;
    $t->update(['notification_preferences' => ['email_weekly_matches' => false, 'quiet_hours_start' => '22:00']]);
    $emailAndWa = array_merge(array_keys(EmailAlerts::LIST), array_keys(EmailAlerts::WHATSAPP));

    // page without the app section: push keys untouched
    $this->actingAs($t)->post('/settings/alerts', array_fill_keys($emailAndWa, '1'))->assertRedirect();
    expect($t->fresh()->notification_preferences)->not->toHaveKey('push_interest');

    // page with the app section, "Photos" unticked
    $ticked = array_merge($emailAndWa, ['push_interest', 'push_account']);
    $this->actingAs($t)->post('/settings/alerts', array_fill_keys($ticked, '1') + ['push_section' => '1'])->assertRedirect();
    $t = $t->fresh();
    expect($t->wantsNotification('push_photos'))->toBeFalse()
        ->and($t->wantsNotification('push_interest'))->toBeTrue()
        ->and($t->wantsNotification('push_account'))->toBeTrue()
        ->and($t->notification_preferences['quiet_hours_start'])->toBe('22:00');
});

it('sends each push under the tick the page shows, accepted interests always, with the page to open', function () {
    $svc = app(NotificationService::class);
    expect($svc->pushPrefKey('interest_received'))->toBe('push_interest')
        ->and($svc->pushPrefKey('interest_expired'))->toBe('push_interest')
        ->and($svc->pushPrefKey('photo_request'))->toBe('push_photos')
        ->and($svc->pushPrefKey('photo_approved'))->toBe('push_photos')
        ->and($svc->pushPrefKey('membership_expiring'))->toBe('push_account')
        ->and($svc->pushPrefKey('id_proof_rejected'))->toBe('push_account');

    $t = $this->b->m['T']->user;
    Device::create(['user_id' => $t->id, 'fcm_token' => 'tokT', 'platform' => 'android', 'is_active' => true]);
    $sent = [];
    $messaging = Mockery::mock(\Kreait\Firebase\Contract\Messaging::class);
    $messaging->shouldReceive('send')->andReturnUsing(function ($m) use (&$sent) {
        $sent[] = json_decode(json_encode($m), true);

        return [];
    });
    Firebase::shouldReceive('messaging')->andReturn($messaging);

    // photos switched off, interests on
    $t->update(['notification_preferences' => ['push_photos' => false]]);
    $svc->send($t->fresh(), 'photo_request', 'Photo request', 'A member asked for your photo.');
    $svc->send($t->fresh(), 'interest_received', 'New interest', 'You have a new interest.', null, ['interest_id' => 5]);
    // interests switched off: accepted still comes through
    $t->update(['notification_preferences' => ['push_interest' => false]]);
    $svc->send($t->fresh(), 'interest_declined', 'Interest declined', 'x');
    $svc->send($t->fresh(), 'interest_accepted', 'Interest accepted', 'y', null, ['interest_id' => 6]);
    // photos and account follow "Interests" until set (old behaviour kept)
    $svc->send($t->fresh(), 'membership_expiring', 'Membership', 'z');

    expect(array_column(array_column($sent, 'data'), 'type'))->toBe(['interest_received', 'interest_accepted'])
        ->and($sent[0]['data']['url'])->toBe(route('interests.show', 5))
        ->and($sent[1]['data']['url'])->toBe(route('interests.show', 6))
        ->and($sent[0]['token'])->toBe('tokT');
});

it('asks members on an older app to update, only when the newest version is set', function () {
    $t = $this->b->m['T']->user;
    $old = 'Mozilla/5.0 (Linux; Android 14; wv) AppleWebKit/537.36 Chrome/130 Mobile Safari/537.36 KudlaMatrimonyApp/1.0.6';
    $untagged = 'Mozilla/5.0 (Linux; Android 11; wv) AppleWebKit/537.36 Chrome/100 Mobile Safari/537.36';

    expect(\App\Support\AppUpdate::versionOf($old))->toBe('1.0.6')
        ->and(\App\Support\AppUpdate::versionOf($untagged))->toBe('')
        ->and(\App\Support\AppUpdate::versionOf('Mozilla/5.0 (Linux; Android 14) Chrome/130 Mobile'))->toBeNull()
        ->and(\App\Support\AppUpdate::versionOf('Mozilla/5.0 (Linux; Android 14; wv) Instagram 300'))->toBeNull();

    // off until set (every site today)
    config(['services.android_app.package' => 'com.books.KudlaMatrimony', 'services.android_app.latest_version' => null]);
    expect($this->actingAs($t)->withHeader('User-Agent', $old)->get('/dashboard')->getContent())->not->toContain('app-update-bar');

    config(['services.android_app.latest_version' => '1.0.7']);
    $html = $this->actingAs($t)->withHeader('User-Agent', $old)->get('/dashboard')->getContent();
    expect($html)->toContain('id="app-update-bar"')->toContain('play.google.com/store/apps/details?id=com.books.KudlaMatrimony');
    expect($this->actingAs($t)->withHeader('User-Agent', $untagged)->get('/dashboard')->getContent())->toContain('id="app-update-bar"');   // apps from before 1.0.4
    auth()->logout();
    expect($this->withHeader('User-Agent', $untagged)->get('/')->getContent())->toContain('id="app-update-bar"');   // visitors too
    $this->actingAs($t);
    expect($this->actingAs($t)->withHeader('User-Agent', APP_UA)->get('/dashboard')->getContent())->not->toContain('id="app-update-bar"');   // up to date
    expect($this->actingAs($t)->withHeader('User-Agent', 'Mozilla/5.0 Chrome/130')->get('/dashboard')->getContent())->not->toContain('id="app-update-bar"');   // a browser
    expect($this->actingAs($t)->withHeader('User-Agent', $old)->withUnencryptedCookie('app_update_later', '1')->get('/dashboard')->getContent())->not->toContain('id="app-update-bar"');   // "Later"
});

it('serves assetlinks.json only when the app and its fingerprint are set', function () {
    config(['services.android_app.package' => null, 'services.android_app.sha256' => null]);
    $this->get('/.well-known/assetlinks.json')->assertNotFound();

    config(['services.android_app.package' => 'com.books.KudlaMatrimony', 'services.android_app.sha256' => 'AA:BB, CC:DD']);
    $this->get('/.well-known/assetlinks.json')->assertOk()
        ->assertJsonPath('0.target.package_name', 'com.books.KudlaMatrimony')
        ->assertJsonPath('0.target.sha256_cert_fingerprints', ['AA:BB', 'CC:DD']);
});

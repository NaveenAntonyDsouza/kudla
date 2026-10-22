<?php

use App\Filament\Pages\WhatsAppSettings;
use App\Models\ProfilePhoto;
use App\Models\SiteSetting;
use App\Models\User;
use App\Models\WhatsAppMessage;
use App\Services\ImageProcessingService;
use App\Support\WhatsAppCloud;
use Filament\Facades\Filament;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

/*
| WhatsApp Cloud API (Oct 2026): Meta's webhook stores messages, adds photos
| from a member's number to their profile for approval, and auto-replies.
*/

const WA_TABLES = ['users', 'profiles', 'profile_photos', 'contact_info', 'site_settings', 'whatsapp_messages'];

beforeEach(function () {
    foreach (WA_TABLES as $t) {
        Schema::dropIfExists($t);
    }
    Schema::create('users', function (Blueprint $t) {
        $t->id();
        $t->string('name')->nullable();
        $t->string('email')->nullable();
        $t->string('phone')->nullable();
        $t->string('password')->nullable();
        $t->string('role')->nullable();
        $t->unsignedBigInteger('staff_role_id')->nullable();
        $t->unsignedBigInteger('branch_id')->nullable();
        $t->rememberToken();
        $t->timestamps();
    });
    Schema::create('profiles', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('user_id');
        $t->string('matri_id')->nullable();
        $t->string('full_name')->nullable();
        $t->boolean('is_active')->default(true);
        $t->timestamps();
        $t->softDeletes();
    });
    Schema::create('profile_photos', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('profile_id');
        $t->string('photo_type')->default('profile');
        $t->string('photo_url')->nullable();
        $t->string('thumbnail_url')->nullable();
        $t->string('medium_url')->nullable();
        $t->string('original_url')->nullable();
        $t->string('cloudinary_public_id')->nullable();
        $t->string('storage_driver')->nullable();
        $t->boolean('is_primary')->default(false);
        $t->boolean('is_visible')->default(true);
        $t->integer('display_order')->default(0);
        $t->string('approval_status')->default('approved');
        $t->string('rejection_reason')->nullable();
        $t->unsignedBigInteger('approved_by')->nullable();
        $t->timestamp('approved_at')->nullable();
        $t->timestamps();
    });
    Schema::create('contact_info', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('profile_id');
        $t->string('primary_phone')->nullable();
        $t->string('whatsapp_number')->nullable();
        $t->timestamps();
    });
    Schema::create('site_settings', function (Blueprint $t) {
        $t->id();
        $t->string('key')->unique();
        $t->text('value')->nullable();
        $t->timestamps();
    });
    (require base_path('database/migrations/2026_10_16_000001_create_whatsapp_messages_table.php'))->up();
    Cache::flush();

    SiteSetting::setValue('site_name', 'Kudla Matrimony');
    SiteSetting::setValue('wa_cloud_enabled', '1');
    SiteSetting::setValue('wa_cloud_phone_number_id', '1112223334');
    SiteSetting::setValue('wa_cloud_verify_token', 'verify-me');
    WhatsAppCloud::storeSecret('wa_cloud_access_token', 'EAA-test-token');
    WhatsAppCloud::storeSecret('wa_cloud_app_secret', 'app-secret');

    DB::table('users')->insert([
        ['id' => 1, 'name' => 'Admin', 'role' => 'admin', 'phone' => null],
        ['id' => 2, 'name' => 'Asha', 'role' => 'user', 'phone' => '98450 12345'],
        ['id' => 3, 'name' => 'Ravi', 'role' => 'user', 'phone' => '9900011111'],
        ['id' => 4, 'name' => 'Rekha', 'role' => 'user', 'phone' => '+91 99000 11111'], // shares Ravi's number
    ]);
    DB::table('profiles')->insert([
        ['id' => 2, 'user_id' => 2, 'matri_id' => 'KM100002', 'full_name' => 'Asha Salian'],
        ['id' => 3, 'user_id' => 3, 'matri_id' => 'KM100003', 'full_name' => 'Ravi Kotian'],
        ['id' => 4, 'user_id' => 4, 'matri_id' => 'KM100004', 'full_name' => 'Rekha Kotian'],
    ]);

    Http::fake([
        // A fresh id per send, as Meta does (the table keeps ids unique).
        'graph.facebook.com/v25.0/1112223334/messages' => fn () => Http::response(['messages' => [['id' => 'wamid.OUT'.uniqid('', true)]]]),
        'graph.facebook.com/v25.0/MEDIA1' => Http::response(['url' => 'https://lookaside.fbsbx.com/media/MEDIA1', 'mime_type' => 'image/png']),
        'lookaside.fbsbx.com/*' => Http::response(base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII='), 200, ['Content-Type' => 'image/png']),
    ]);
});

afterEach(function () {
    foreach (WA_TABLES as $t) {
        Schema::dropIfExists($t);
    }
});

function waPost(array $messages = [], array $statuses = [], ?string $secret = 'app-secret')
{
    $body = json_encode(['object' => 'whatsapp_business_account', 'entry' => [['id' => 'WABA', 'changes' => [[
        'field' => 'messages',
        'value' => array_filter([
            'messaging_product' => 'whatsapp',
            'contacts' => $messages ? [['wa_id' => $messages[0]['from'], 'profile' => ['name' => 'Asha']]] : null,
            'messages' => $messages ?: null,
            'statuses' => $statuses ?: null,
        ]),
    ]]]]]);

    return test()->call('POST', '/api/v1/webhooks/whatsapp', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_HUB_SIGNATURE_256' => 'sha256='.hash_hmac('sha256', $body, (string) $secret),
    ], $body);
}

it('answers Meta\'s webhook check only with the right verify token', function () {
    $this->get('/api/v1/webhooks/whatsapp?hub_mode=subscribe&hub_verify_token=verify-me&hub_challenge=12345')
        ->assertOk()->assertSee('12345');
    $this->get('/api/v1/webhooks/whatsapp?hub_mode=subscribe&hub_verify_token=wrong&hub_challenge=12345')
        ->assertForbidden();
});

it('rejects webhook calls not signed with the app secret', function () {
    waPost([['id' => 'wamid.1', 'from' => '919845012345', 'type' => 'text', 'text' => ['body' => 'Hi']]], [], 'someone-else')
        ->assertForbidden();
    expect(WhatsAppMessage::count())->toBe(0);
});

it('stores a text message, links the member and auto-replies once', function () {
    waPost([['id' => 'wamid.1', 'from' => '919845012345', 'type' => 'text', 'text' => ['body' => 'Hi, I need help']]])->assertOk();
    waPost([['id' => 'wamid.1', 'from' => '919845012345', 'type' => 'text', 'text' => ['body' => 'Hi, I need help']]])->assertOk(); // Meta retry
    waPost([['id' => 'wamid.2', 'from' => '919845012345', 'type' => 'text', 'text' => ['body' => 'Hello?']]])->assertOk();

    $in = WhatsAppMessage::where('direction', 'in')->orderBy('id')->get();
    expect($in)->toHaveCount(2)
        ->and($in[0]->body)->toBe('Hi, I need help')
        ->and($in[0]->profile_id)->toBe(2)
        ->and($in[0]->contact_name)->toBe('Asha');

    $out = WhatsAppMessage::where('direction', 'out')->get();
    expect($out)->toHaveCount(1) // second message is within the 12-hour auto-reply gap
        ->and($out[0]->body)->toContain('Thank you for your message to Kudla Matrimony')
        ->and($out[0]->status)->toBe('sent');

    Http::assertSent(fn ($r) => str_contains($r->url(), '/messages') && $r['to'] === '919845012345'
        && $r->hasHeader('Authorization', 'Bearer EAA-test-token'));
});

it('adds a photo from a member\'s number to their profile for approval', function () {
    $this->mock(ImageProcessingService::class, fn ($m) => $m->shouldReceive('processUpload')->once()
        ->andReturn(['original' => 'o.png', 'full' => 'f.webp', 'medium' => 'm.webp', 'thumb' => 't.webp', 'driver' => 'public']));

    waPost([['id' => 'wamid.P1', 'from' => '919845012345', 'type' => 'image', 'image' => ['id' => 'MEDIA1', 'mime_type' => 'image/jpeg']]])->assertOk();

    $photo = ProfilePhoto::first();
    expect($photo->profile_id)->toBe(2)
        ->and($photo->photo_type)->toBe('profile')
        ->and($photo->approval_status)->toBe('pending')
        ->and($photo->is_primary)->toBeFalse();

    $msg = WhatsAppMessage::where('wa_message_id', 'wamid.P1')->first();
    expect($msg->status)->toBe('attached')->and($msg->profile_photo_id)->toBe($photo->id);
    expect(WhatsAppMessage::where('direction', 'out')->value('body'))->toContain('(KM100002)');
});

it('leaves a photo from a shared number for staff', function () {
    $this->mock(ImageProcessingService::class, fn ($m) => $m->shouldNotReceive('processUpload'));

    waPost([['id' => 'wamid.P2', 'from' => '919900011111', 'type' => 'image', 'image' => ['id' => 'MEDIA1']]])->assertOk();

    expect(ProfilePhoto::count())->toBe(0);
    $msg = WhatsAppMessage::where('wa_message_id', 'wamid.P2')->first();
    expect($msg->profile_id)->toBeNull()
        ->and($msg->note)->toContain('KM100003')->toContain('KM100004');
});

it('records delivery receipts without stepping back', function () {
    WhatsAppMessage::create(['direction' => 'out', 'wa_message_id' => 'wamid.O1', 'wa_id' => '919845012345', 'type' => 'text', 'status' => 'sent']);

    waPost([], [['id' => 'wamid.O1', 'status' => 'read']]);
    waPost([], [['id' => 'wamid.O1', 'status' => 'delivered']]);

    expect(WhatsAppMessage::where('wa_message_id', 'wamid.O1')->value('status'))->toBe('read');
});

it('does nothing while switched off', function () {
    SiteSetting::setValue('wa_cloud_enabled', '0');

    waPost([['id' => 'wamid.9', 'from' => '919845012345', 'type' => 'text', 'text' => ['body' => 'Hi']]])->assertOk();

    expect(WhatsAppMessage::count())->toBe(0);
    Http::assertNothingSent();
});

it('saves secrets encrypted and keeps them when left empty', function () {
    $this->actingAs(User::find(1));
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    Livewire::test(WhatsAppSettings::class)
        ->assertDontSee('EAA-test-token')
        ->set('data.wa_cloud_access_token', 'EAA-new-token')
        ->set('data.wa_cloud_phone_number_id', '999888777')
        ->call('save')
        ->assertHasNoErrors();

    $stored = DB::table('site_settings')->where('key', 'wa_cloud_access_token')->value('value');
    expect($stored)->not->toContain('EAA-new-token')
        ->and(WhatsAppCloud::accessToken())->toBe('EAA-new-token')
        ->and(WhatsAppCloud::appSecret())->toBe('app-secret') // left empty: kept
        ->and(WhatsAppCloud::phoneNumberId())->toBe('999888777');
});

/* ── Inbox ─────────────────────────────────────────────────────────── */

function waInboxAsAdmin()
{
    test()->actingAs(User::find(1));
    Filament::setCurrentPanel(Filament::getPanel('admin'));
}

it('lists conversations, opens one as read and replies within 24 hours', function () {
    waInboxAsAdmin();
    WhatsAppMessage::create(['direction' => 'in', 'wa_message_id' => 'wamid.A', 'wa_id' => '919845012345', 'profile_id' => 2, 'type' => 'text', 'body' => 'Please call me', 'status' => 'received']);
    WhatsAppMessage::create(['direction' => 'in', 'wa_message_id' => 'wamid.B', 'wa_id' => '971501234567', 'contact_name' => 'Gulf Uncle', 'type' => 'text', 'body' => 'Hello', 'status' => 'received']);

    expect(\App\Filament\Pages\WhatsAppInbox::getNavigationBadge())->toBe('2');

    Livewire::test(\App\Filament\Pages\WhatsAppInbox::class)
        ->assertSee('Asha Salian')->assertSee('Gulf Uncle')
        ->set('filter', 'unknown')->assertDontSee('Asha Salian')->assertSee('Gulf Uncle')
        ->set('filter', 'all')
        ->call('open', '919845012345')
        ->assertSee('Please call me')
        ->set('reply', 'We will call you today.')
        ->call('send')
        ->assertSet('reply', '');

    expect(WhatsAppMessage::where('wa_message_id', 'wamid.A')->value('read_at'))->not->toBeNull();
    $out = WhatsAppMessage::where('direction', 'out')->first();
    expect($out->body)->toBe('We will call you today.')
        ->and($out->sent_by)->toBe(1)
        ->and($out->profile_id)->toBe(2)
        ->and($out->status)->toBe('sent');
});

it('closes the reply box after 24 hours', function () {
    waInboxAsAdmin();
    $old = WhatsAppMessage::create(['direction' => 'in', 'wa_message_id' => 'wamid.OLD', 'wa_id' => '919845012345', 'type' => 'text', 'body' => 'Old', 'status' => 'received']);
    $old->forceFill(['created_at' => now()->subHours(30)])->save();

    Livewire::test(\App\Filament\Pages\WhatsAppInbox::class)
        ->call('open', '919845012345')
        ->assertSee('within 24 hours')
        ->set('reply', 'Hi')
        ->call('send');

    expect(WhatsAppMessage::where('direction', 'out')->count())->toBe(0);
    Http::assertNothingSent();
});

it('links an unknown number to a member and adds their photo live', function () {
    waInboxAsAdmin();
    $this->mock(ImageProcessingService::class, fn ($m) => $m->shouldReceive('processUpload')->once()
        ->andReturn(['original' => 'o.png', 'full' => 'f.webp', 'medium' => 'm.webp', 'thumb' => 't.webp', 'driver' => 'public']));
    $photoMsg = WhatsAppMessage::create(['direction' => 'in', 'wa_message_id' => 'wamid.IMG', 'wa_id' => '971501234567', 'type' => 'image', 'media_id' => 'MEDIA1', 'status' => 'received']);

    Livewire::test(\App\Filament\Pages\WhatsAppInbox::class)
        ->call('open', '971501234567')
        ->assertSee('Not linked to a member')
        ->set('linkMatriId', 'km100002')
        ->call('linkMember')
        ->call('attachPhoto', $photoMsg->id);

    $photo = ProfilePhoto::first();
    expect($photo->profile_id)->toBe(2)
        ->and($photo->approval_status)->toBe('approved')
        ->and($photo->is_primary)->toBeTrue()
        ->and($photo->approved_by)->toBe(1)
        ->and($photoMsg->fresh()->profile_photo_id)->toBe($photo->id)
        ->and(WhatsAppMessage::where('wa_id', '971501234567')->value('profile_id'))->toBe(2);
});

it('shows a member\'s photo to staff only, fetched once from Meta', function () {
    Storage::fake('local');
    $msg = WhatsAppMessage::create(['direction' => 'in', 'wa_message_id' => 'wamid.IMG2', 'wa_id' => '919845012345', 'type' => 'image', 'media_id' => 'MEDIA1', 'status' => 'received']);
    $url = route('filament.admin.whatsapp-media', $msg);

    $this->get($url)->assertRedirect(); // not logged in

    waInboxAsAdmin();
    $this->get($url)->assertOk()->assertHeader('Content-Type', 'image/png');
    $this->get($url)->assertOk();

    Http::assertSentCount(2); // media lookup + download, then served from the kept copy
    Storage::disk('local')->assertExists("whatsapp-media/{$msg->id}");
});

it('asks the member to reply with the approved template once the 24 hours are over', function () {
    waInboxAsAdmin();
    SiteSetting::setValue('wa_cloud_templates_enabled', '1');
    $old = WhatsAppMessage::create(['direction' => 'in', 'wa_message_id' => 'wamid.OLD2', 'wa_id' => '919845012345', 'profile_id' => 2, 'type' => 'text', 'body' => 'Hello?', 'status' => 'received']);
    $old->forceFill(['created_at' => now()->subHours(30)])->save();

    Livewire::test(\App\Filament\Pages\WhatsAppInbox::class)
        ->call('open', '919845012345')
        ->assertSee('Ask them to reply')
        ->call('sendReplyWaiting', 'en')
        ->call('sendReplyWaiting', 'en'); // second click the same day does nothing

    Http::assertSentCount(1);
    Http::assertSent(fn ($r) => $r['template']['name'] === 'reply_waiting'
        && $r['template']['components'][0]['parameters'][0]['text'] === 'Asha');
    expect(WhatsAppMessage::where('direction', 'out')->value('body'))->toBe('Hi Asha, the Kudla Matrimony team has a reply to your message. Please reply to this message (any text is fine) and we will answer you here straight away.');
});

/* ── Staff email alerts ────────────────────────────────────────────── */

it('emails staff about a new message, once per member every 30 minutes', function () {
    \Illuminate\Support\Facades\Mail::fake();
    SiteSetting::setValue('contact_email', 'office@example.com');

    waPost([['id' => 'wamid.A1', 'from' => '919845012345', 'type' => 'text', 'text' => ['body' => "Hi, what's the fee?"]]])->assertOk();
    waPost([['id' => 'wamid.A2', 'from' => '919845012345', 'type' => 'text', 'text' => ['body' => 'Hello?']]])->assertOk();

    \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\WhatsAppMessageAlert::class, 1);
    \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\WhatsAppMessageAlert::class, function ($mail) {
        $text = $mail->render();

        return $mail->hasTo('office@example.com')
            && $mail->envelope()->subject === 'WhatsApp: new message from Asha Salian (KM100002)'
            && str_contains($text, "Message: Hi, what's the fee?")
            && str_contains($text, 'whats-app-inbox?chat=919845012345');
    });
});

it('sends alerts to the chosen address, says what happened to a photo, and can be switched off', function () {
    \Illuminate\Support\Facades\Mail::fake();
    SiteSetting::setValue('wa_cloud_alert_email', 'staff@example.com');
    $this->mock(ImageProcessingService::class, fn ($m) => $m->shouldReceive('processUpload')->once()
        ->andReturn(['original' => 'o.png', 'full' => 'f.webp', 'medium' => 'm.webp', 'thumb' => 't.webp', 'driver' => 'public']));

    waPost([['id' => 'wamid.P9', 'from' => '919845012345', 'type' => 'image', 'image' => ['id' => 'MEDIA1']]])->assertOk();

    \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\WhatsAppMessageAlert::class, fn ($mail) => $mail->hasTo('staff@example.com')
        && str_contains($mail->render(), 'Added to KM100002 as a profile photo'));

    SiteSetting::setValue('wa_cloud_alert_enabled', '0');
    waPost([['id' => 'wamid.P10', 'from' => '919900011111', 'type' => 'text', 'text' => ['body' => 'Hi']]])->assertOk();
    \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\WhatsAppMessageAlert::class, 1);
});

it('does not offer the paid "please reply" message until approved messages are switched on', function () {
    waInboxAsAdmin();
    $old = WhatsAppMessage::create(['direction' => 'in', 'wa_message_id' => 'wamid.OLD3', 'wa_id' => '919845012345', 'profile_id' => 2, 'type' => 'text', 'body' => 'Hello?', 'status' => 'received']);
    $old->forceFill(['created_at' => now()->subHours(30)])->save();

    Livewire::test(\App\Filament\Pages\WhatsAppInbox::class)
        ->call('open', '919845012345')
        ->assertDontSee('Ask them to reply')
        ->assertSee('payment card')
        ->call('sendReplyWaiting', 'en');

    Http::assertNothingSent();
});

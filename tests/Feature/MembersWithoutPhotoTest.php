<?php

use App\Filament\Pages\MembersWithoutPhoto;
use App\Models\Profile;
use App\Models\ProfileNote;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

/*
| Members Without Photo (Oct 2026): staff ask members on WhatsApp for a photo
| and mark them as asked; members leave the list once a photo is added.
*/

beforeEach(function () {
    foreach (['users', 'profiles', 'profile_photos', 'profile_notes', 'contact_info', 'religious_info', 'site_settings'] as $t) {
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
        $t->unsignedBigInteger('branch_id')->nullable();
        $t->string('matri_id')->nullable();
        $t->string('full_name')->nullable();
        $t->string('gender')->nullable();
        $t->boolean('is_active')->default(true);
        $t->timestamps();
        $t->softDeletes();
    });
    Schema::create('profile_photos', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('profile_id');
        $t->boolean('is_primary')->default(true);
        $t->boolean('is_visible')->default(true);
        $t->string('approval_status')->default('approved');
        $t->timestamps();
    });
    Schema::create('profile_notes', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('profile_id');
        $t->unsignedBigInteger('admin_user_id')->nullable();
        $t->string('note_type')->nullable();
        $t->text('note')->nullable();
        $t->date('follow_up_date')->nullable();
        $t->timestamp('follow_up_completed_at')->nullable();
        $t->unsignedBigInteger('follow_up_completed_by')->nullable();
        $t->timestamps();
    });
    Schema::create('contact_info', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('profile_id');
        $t->string('primary_phone')->nullable();
        $t->string('whatsapp_number')->nullable();
        $t->timestamps();
    });
    Schema::create('religious_info', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('profile_id');
        $t->string('caste')->nullable();
        $t->timestamps();
    });
    Schema::create('site_settings', function (Blueprint $t) {
        $t->id();
        $t->string('key')->unique();
        $t->text('value')->nullable();
        $t->timestamps();
    });
    Cache::flush();
    \App\Models\SiteSetting::setValue('photo_via_whatsapp', '1');
    \App\Models\SiteSetting::setValue('site_name', 'Kudla Matrimony');

    DB::table('users')->insert([
        ['id' => 1, 'name' => 'Admin', 'role' => 'admin', 'phone' => null, 'created_at' => now(), 'updated_at' => now()],
        ['id' => 2, 'name' => 'Asha', 'role' => 'user', 'phone' => '9845012345', 'created_at' => now(), 'updated_at' => now()],
        ['id' => 3, 'name' => 'Ravi', 'role' => 'user', 'phone' => '9845099999', 'created_at' => now(), 'updated_at' => now()],
        ['id' => 4, 'name' => 'Meera', 'role' => 'user', 'phone' => '9845088888', 'created_at' => now(), 'updated_at' => now()],
    ]);
    DB::table('profiles')->insert([
        ['id' => 2, 'user_id' => 2, 'matri_id' => 'KM100002', 'full_name' => 'Asha Salian', 'gender' => 'Female', 'created_at' => now(), 'updated_at' => now()],
        ['id' => 3, 'user_id' => 3, 'matri_id' => 'KM100003', 'full_name' => 'Ravi Kotian', 'gender' => 'Male', 'created_at' => now(), 'updated_at' => now()],
        ['id' => 4, 'user_id' => 4, 'matri_id' => 'KM100004', 'full_name' => 'Meera Rai', 'gender' => 'Female', 'created_at' => now(), 'updated_at' => now()],
    ]);
    DB::table('profile_photos')->insert([
        ['profile_id' => 3, 'approval_status' => 'approved'], // has a photo
        ['profile_id' => 4, 'approval_status' => 'rejected'], // rejected: still needs one
    ]);
    DB::table('contact_info')->insert(['profile_id' => 2, 'whatsapp_number' => '+971 50 123 4567']);

    $this->actingAs(User::find(1));
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

afterEach(function () {
    foreach (['users', 'profiles', 'profile_photos', 'profile_notes', 'contact_info', 'religious_info', 'site_settings'] as $t) {
        Schema::dropIfExists($t);
    }
});

it('lists active members with no approved or pending photo', function () {
    expect(MembersWithoutPhoto::query()->pluck('matri_id')->sort()->values()->all())->toBe(['KM100002', 'KM100004']);
});

it('opens WhatsApp with a ready-made message, using the WhatsApp number first', function () {
    $asha = Profile::with(['contactInfo', 'user'])->find(2);
    $meera = Profile::with(['contactInfo', 'user'])->find(4);

    expect(MembersWithoutPhoto::whatsappUrl($asha))->toStartWith('https://wa.me/971501234567?text=')
        ->and(MembersWithoutPhoto::whatsappUrl($meera))->toStartWith('https://wa.me/919845088888?text=')
        ->and(MembersWithoutPhoto::message($asha))->toContain('Hi Asha, greetings from Kudla Matrimony!')
        ->toContain('(KM100002)');
});

it('records "asked" as a note in the member history and shows the page', function () {
    Livewire::test(MembersWithoutPhoto::class)
        ->assertSee('KM100002')
        ->assertSee('KM100004')
        ->assertDontSee('KM100003')
        ->callTableAction('markAsked', Profile::find(2));

    $note = ProfileNote::where('profile_id', 2)->first();
    expect($note->note)->toStartWith(MembersWithoutPhoto::NOTE_PREFIX)
        ->and($note->note_type)->toBe('whatsapp')
        ->and($note->admin_user_id)->toBe(1)
        ->and(MembersWithoutPhoto::query()->find(2)->photo_asked_at)->not->toBeNull();
});

it('is hidden where photos by WhatsApp are switched off', function () {
    expect(MembersWithoutPhoto::canAccess())->toBeTrue();

    \App\Models\SiteSetting::setValue('photo_via_whatsapp', '0');
    expect(MembersWithoutPhoto::canAccess())->toBeFalse();
});

/* ── Through the WhatsApp API (Oct 2026) ───────────────────────────── */

function wpEnableApi(): void
{
    Schema::dropIfExists('whatsapp_messages');
    (require base_path('database/migrations/2026_10_16_000001_create_whatsapp_messages_table.php'))->up();
    \App\Models\SiteSetting::setValue('wa_cloud_enabled', '1');
    \App\Models\SiteSetting::setValue('wa_cloud_phone_number_id', '1112223334');
    \App\Support\WhatsAppCloud::storeSecret('wa_cloud_access_token', 'EAA-test-token');
    \Illuminate\Support\Facades\Http::fake([
        'graph.facebook.com/*' => \Illuminate\Support\Facades\Http::response(['messages' => [['id' => 'wamid.T'.uniqid()]]]),
    ]);
}

it('sends the approved photo request with the name and member ID, and marks the member asked', function () {
    wpEnableApi();

    Livewire::test(MembersWithoutPhoto::class)
        ->assertTableActionHidden('whatsapp', Profile::find(2))
        ->callTableAction('sendRequest', Profile::find(2), ['language' => 'kn']);

    \Illuminate\Support\Facades\Http::assertSent(fn ($r) => $r['to'] === '971501234567'
        && $r['template']['name'] === 'profile_photo_missing'
        && $r['template']['language']['code'] === 'kn'
        && $r['template']['components'][0]['parameters'][0]['text'] === 'Asha'
        && $r['template']['components'][0]['parameters'][1]['text'] === 'KM100002');

    $out = \App\Models\WhatsAppMessage::first();
    expect($out->type)->toBe('template')
        ->and($out->profile_id)->toBe(2)
        ->and($out->body)->toContain('ನಮಸ್ಕಾರ Asha')->toContain('KM100002')
        ->and(ProfileNote::where('profile_id', 2)->value('note'))->toStartWith(MembersWithoutPhoto::NOTE_PREFIX)
        ->and(MembersWithoutPhoto::query()->find(2)->photo_asked_at)->not->toBeNull();
});

it('sends to several members at once, skipping anyone asked this week', function () {
    wpEnableApi();
    ProfileNote::create(['profile_id' => 4, 'admin_user_id' => 1, 'note_type' => 'whatsapp', 'note' => MembersWithoutPhoto::NOTE_PREFIX.' asked.']);

    Livewire::test(MembersWithoutPhoto::class)
        ->callTableBulkAction('sendRequests', Profile::whereIn('id', [2, 4])->get(), ['language' => 'auto']);

    \Illuminate\Support\Facades\Http::assertSentCount(1);
    expect(\App\Models\WhatsAppMessage::pluck('profile_id')->all())->toBe([2]);
});

it('can use the friendlier marketing template instead', function () {
    wpEnableApi();
    \App\Models\SiteSetting::setValue('wa_cloud_photo_template', 'photo_request');

    MembersWithoutPhoto::sendRequest(Profile::with(['contactInfo', 'user'])->find(4));

    \Illuminate\Support\Facades\Http::assertSent(fn ($r) => $r['template']['name'] === 'photo_request'
        && $r['template']['language']['code'] === 'en'
        && $r['to'] === '919845088888');
    expect(\App\Models\WhatsAppMessage::value('body'))->toStartWith('Hi Meera, this is the Kudla Matrimony team.');
});

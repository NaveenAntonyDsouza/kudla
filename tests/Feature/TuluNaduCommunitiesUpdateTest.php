<?php

use App\Console\Commands\UpdateTuluNaduCommunities as Update;
use App\Mail\CommunityUpdateRequestMail;
use App\Models\Community;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

/*
| Kudla caste list from the Tulu Nadu research (owner approved 8 Oct 2026):
| new communities and sub-castes, members and partner preferences moved to
| current names, "Salian" / "Konkani" switched off and their members asked
| to choose their community.
*/

class TnuFakeNotifications extends NotificationService
{
    public array $sent = [];

    public function __construct() {}

    public function send(User $user, string $type, string $title, string $message, ?int $fromProfileId = null, array $data = []): void
    {
        $this->sent[] = [$user->id, $type];
        DB::table('notifications')->insert(['user_id' => $user->id, 'type' => $type, 'title' => $title, 'message' => $message]);
    }
}

beforeEach(function () {
    Schema::create('communities', function (Blueprint $t) {
        $t->id();
        $t->string('religion', 50);
        $t->string('community_name', 100);
        $t->json('sub_communities')->nullable();
        $t->boolean('is_active')->default(true);
        $t->unsignedTinyInteger('sort_order')->default(0);
        $t->timestamps();
    });
    Schema::create('users', function (Blueprint $t) {
        $t->id();
        $t->string('name')->nullable();
        $t->string('email')->nullable();
        $t->string('role')->nullable();
        $t->unsignedBigInteger('staff_role_id')->nullable();
        $t->timestamp('email_undeliverable_at')->nullable();
        $t->timestamps();
    });
    Schema::create('profiles', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('user_id');
        $t->string('matri_id')->nullable();
        $t->softDeletes();
        $t->timestamps();
    });
    Schema::create('religious_info', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('profile_id');
        $t->string('religion')->nullable();
        $t->string('caste')->nullable();
        $t->string('other_caste_name')->nullable();
        $t->string('sub_caste')->nullable();
        $t->string('other_religion_name')->nullable();
        $t->timestamps();
    });
    Schema::create('partner_preferences', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('profile_id');
        $t->json('caste')->nullable();
        $t->timestamps();
    });
    Schema::create('notifications', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('user_id');
        $t->string('type');
        $t->string('title')->nullable();
        $t->text('message')->nullable();
        $t->timestamps();
    });
    Schema::create('admin_activity_log', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('admin_user_id')->nullable();
        $t->string('action');
        $t->string('model_type')->nullable();
        $t->unsignedBigInteger('model_id')->nullable();
        $t->json('changes')->nullable();
        $t->string('ip_address')->nullable();
        $t->timestamps();
    });

    $order = 0;
    foreach ([
        ['Billava', ['Poojary', 'Billava']], ['Bunts', ['Bunt', 'Jain Bunt', 'Shetty', 'Nadoja']],
        ['GSB - Goud Saraswat Brahmin', ['Shenoy', 'Pai']], ['Mogaveera', ['Kharvi', 'Mogaveera']],
        ['Brahmin', ['Havyaka Brahmin']], ['Scheduled Caste (SC)', ['Mundala']], ['Konkani', ['Shetty', 'Prabhu', 'Konkani']],
        ['Kulal', ['Kulal', 'Kulala']], ['Bhandary', ['Bhandari']], ['Devadiga (Moily)', ['Devadiga']],
        ['Padmashali', ['Padmashali']], ['Vishwakarma', ['Achari']], ['Salian', ['Salian']], ['Gowda - Tulu', ['Tulu Gowda']],
        ['Madiwala', []], ['Other / Not Listed', []],
    ] as [$name, $subs]) {
        Community::create(['religion' => 'Hindu', 'community_name' => $name, 'sub_communities' => $subs, 'is_active' => true, 'sort_order' => ++$order]);
    }

    $member = function (int $id, ?string $caste, ?string $sub = null, ?string $typed = null, string $religion = 'Hindu', ?string $otherReligion = null) {
        DB::table('users')->insert(['id' => $id, 'name' => "Member {$id}", 'email' => "m{$id}@example.com"]);
        DB::table('profiles')->insert(['id' => $id, 'user_id' => $id, 'matri_id' => "KM10{$id}"]);
        DB::table('religious_info')->insert(['profile_id' => $id, 'religion' => $religion, 'caste' => $caste, 'sub_caste' => $sub, 'other_caste_name' => $typed, 'other_religion_name' => $otherReligion]);
    };
    $member(1, 'Tulu Gowda', 'Tulu Gowda');
    $member(2, 'Gowda - Tulu', 'Tulu Gowda');
    $member(3, 'Other / Not Listed', null, 'Gatty (2A)');
    $member(4, 'Other / Not Listed', null, ' thiyya ');
    $member(5, 'Other / Not Listed', 'Mundala');
    $member(6, 'Konkani', 'Prabhu');
    $member(7, 'Konkani', 'Konkani');
    $member(8, 'Salian', 'Salian');
    $member(9, 'Brahmin', 'koteshwara');
    $member(10, 'Devadiga', 'Devadiga');
    $member(11, 'Other / Not Listed', null, 'Reddy');           // not on our list: stays as typed
    $member(12, null, null, null, 'Other', 'Muslim');
    DB::table('partner_preferences')->insert([
        ['profile_id' => 1, 'caste' => json_encode(['Billava', 'Tulu Gowda', 'Gowda - Tulu'])],
        ['profile_id' => 2, 'caste' => json_encode(['Devadiga', 'Bunts'])],
        ['profile_id' => 3, 'caste' => json_encode(['Devadiga (Moily)'])],
    ]);

    $this->notes = new TnuFakeNotifications();
    app()->instance(NotificationService::class, $this->notes);
    Mail::fake();
});

afterEach(function () {
    foreach (['admin_activity_log', 'notifications', 'partner_preferences', 'religious_info', 'profiles', 'users', 'communities'] as $t) {
        Schema::dropIfExists($t);
    }
});

function tnuCaste(int $profileId): array
{
    $r = DB::table('religious_info')->where('profile_id', $profileId)->first();

    return [$r->religion, $r->caste, $r->sub_caste, $r->other_caste_name];
}

function tnuSubs(string $name): ?array
{
    return Community::where('community_name', $name)->first()?->sub_communities;
}

it('refuses to touch a site that does not have the Tulu Nadu list', function () {
    Community::where('community_name', 'Billava')->delete();

    $this->artisan('communities:tulu-nadu-update')->assertFailed();
    expect(Community::where('community_name', 'Gatti')->exists())->toBeFalse();
});

it('changes nothing on a dry run', function () {
    $before = [DB::table('communities')->count(), DB::table('religious_info')->pluck('caste')->all()];

    $this->artisan('communities:tulu-nadu-update', ['--dry-run' => true])->assertSuccessful();

    expect([DB::table('communities')->count(), DB::table('religious_info')->pluck('caste')->all()])->toBe($before);
});

it('updates the list: new communities, Gowda for Tulu + Arebhashe, fixed sub-castes, Salian / Konkani off', function () {
    $this->artisan('communities:tulu-nadu-update')->assertSuccessful();

    foreach (array_keys(Update::ADD) as $name) {
        expect(Community::where('community_name', $name)->where('is_active', true)->exists())->toBeTrue();
    }
    expect(tnuSubs('Thiyya / Ezhava'))->toBe(['Thiyya', 'Ezhava'])
        ->and(Community::where('community_name', 'Gowda - Tulu')->exists())->toBeFalse()
        ->and(tnuSubs(Update::GOWDA))->toBe(['Tulu Gowda', 'Arebhashe Gowda', "Don't wish to specify"])
        ->and(tnuSubs('Bunts'))->toContain('Nadava')->not->toContain('Nadoja')
        ->and(tnuSubs('Mogaveera'))->toBe(['Mogaveera', 'Tulu Mogaveera', 'Kannada Mogaveera'])
        ->and(tnuSubs('Brahmin'))->toContain('Koteshwara Brahmin')
        ->and(Community::whereIn('community_name', ['Salian', 'Konkani'])->where('is_active', true)->count())->toBe(0)
        // "Other / Not Listed" stays at the end of the list
        ->and(Community::orderBy('sort_order')->get()->last()->community_name)->toBe('Other / Not Listed')
        ->and(Community::max('sort_order'))->toBeLessThanOrEqual(255);
});

it('moves members to current names so nobody drops out of caste searches', function () {
    $this->artisan('communities:tulu-nadu-update')->assertSuccessful();

    expect(tnuCaste(1))->toBe(['Hindu', Update::GOWDA, 'Tulu Gowda', null])
        ->and(tnuCaste(2))->toBe(['Hindu', Update::GOWDA, 'Tulu Gowda', null])
        ->and(tnuCaste(3))->toBe(['Hindu', 'Gatti', null, null])
        ->and(tnuCaste(4))->toBe(['Hindu', 'Thiyya / Ezhava', 'Thiyya', null])
        ->and(tnuCaste(5))->toBe(['Hindu', 'Scheduled Caste (SC)', 'Mundala', null])
        ->and(tnuCaste(6))->toBe(['Hindu', 'GSB - Goud Saraswat Brahmin', 'Prabhu', null])
        ->and(tnuCaste(7))->toBe(['Hindu', 'Konkani', 'Konkani', null])          // asked, not guessed
        ->and(tnuCaste(8))->toBe(['Hindu', 'Salian', 'Salian', null])            // asked, not guessed
        ->and(tnuCaste(9))->toBe(['Hindu', 'Brahmin', 'Koteshwara Brahmin', null])
        ->and(tnuCaste(10))->toBe(['Hindu', 'Devadiga (Moily)', 'Devadiga', null])
        ->and(tnuCaste(11))->toBe(['Hindu', 'Other / Not Listed', null, 'Reddy'])
        ->and(tnuCaste(12)[0])->toBe('Muslim');

    $prefs = DB::table('partner_preferences')->orderBy('profile_id')->pluck('caste')->map(fn ($j) => json_decode($j, true))->all();
    expect($prefs)->toBe([['Billava', Update::GOWDA], ['Devadiga (Moily)', 'Bunts'], ['Devadiga (Moily)']]);
});

it('can run again without changing anything twice', function () {
    $this->artisan('communities:tulu-nadu-update')->assertSuccessful();
    $snapshot = [DB::table('communities')->orderBy('id')->get()->toArray(), DB::table('religious_info')->orderBy('id')->get()->toArray(), DB::table('partner_preferences')->orderBy('id')->get()->toArray()];

    // a later second, so a touched updated_at shows up every time, not only
    // when the two runs straddle a second (it flaked once, 10 Oct 2026)
    $this->travel(5)->seconds();
    $this->artisan('communities:tulu-nadu-update')->assertSuccessful();

    expect([DB::table('communities')->orderBy('id')->get()->toArray(), DB::table('religious_info')->orderBy('id')->get()->toArray(), DB::table('partner_preferences')->orderBy('id')->get()->toArray()])
        ->toEqual($snapshot);
});

it('asks Salian / Konkani members once, in the app and by email', function () {
    $this->artisan('communities:tulu-nadu-update', ['--ask' => true])->assertSuccessful();
    $this->artisan('communities:tulu-nadu-update', ['--ask' => true])->assertSuccessful();

    expect($this->notes->sent)->toEqualCanonicalizing([[7, 'community_update_request'], [8, 'community_update_request']]);
    Mail::assertSent(CommunityUpdateRequestMail::class, 2);
    Mail::assertSent(CommunityUpdateRequestMail::class, fn ($m) => $m->user->id === 8 && $m->currentValue === 'Salian');
});

it('explains in the email why the value is not a community', function () {
    expect(CommunityUpdateRequestMail::REASONS['Salian'])->toContain('bari')->toContain('Billava')
        ->and(CommunityUpdateRequestMail::REASONS['Konkani'])->toContain('language')->toContain('GSB');
});

it('keeps the old Discover address of the renamed Gowda community working', function () {
    $this->artisan('communities:tulu-nadu-update')->assertSuccessful();
    Illuminate\Support\Facades\Cache::flush();

    $response = app(App\Http\Controllers\DiscoverController::class)->results('hindu-matrimony', 'gowda-tulu');

    expect($response->getStatusCode())->toBe(301)
        ->and($response->headers->get('Location'))->toEndWith('/hindu-matrimony/gowda-tulu-arebhashe');
});

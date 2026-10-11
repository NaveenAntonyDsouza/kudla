<?php

use App\Console\Commands\UpdateKannadaCommunities as Kannada;
use App\Models\Community;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
| Kannada Dating's caste list for all of Karnataka (owner, 11 Oct 2026):
| sub-castes for the big Kannada communities, Brahmins as one caste with
| sub-castes, new communities, Tulu leftovers from the September copy fixed.
| Runs only on kannadadating.com.
*/

beforeEach(function () {
    config(['app.url' => 'https://kannadadating.com']);
    Schema::create('communities', function (Blueprint $t) {
        $t->id();
        $t->string('religion', 50);
        $t->string('community_name', 100);
        $t->json('sub_communities')->nullable();
        $t->boolean('is_active')->default(true);
        $t->unsignedTinyInteger('sort_order')->default(0);
        $t->timestamps();
    });
    Schema::create('religious_info', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('profile_id');
        $t->string('religion')->nullable();
        $t->string('caste')->nullable();
        $t->string('sub_caste')->nullable();
        $t->timestamps();
    });
    Schema::create('partner_preferences', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('profile_id');
        $t->json('caste')->nullable();
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

    // Kannada Dating as on 11 Oct 2026 (a September copy of Kudla's list plus Kannada communities)
    $order = 0;
    foreach ([
        ['Vokkaliga', []], ['Lingayat / Veerashaiva', []], ['Kuruba', []], ['Smartha Brahmin', []], ['Havyaka Brahmin', ['Havyaka']],
        ['Kunchitiga', []], ['Idiga / Ediga', []], ['Bunts', ['Bunt', 'Jain Bunt', 'Shetty', 'Nadoja']], ['Mogaveera', ['Kharvi', 'Mogaveera']],
        ['Devadiga', ['Devadiga']], ['Kulala', ['Kulal', 'Kulala']], ['Madiwala', []], ['Salian', ['Salian']], ['Konkani', ['Shetty', 'Prabhu', 'Konkani']],
        ['Tulu Gowda', ['Tulu Gowda']], ['Scheduled Caste', []], ['Scheduled Tribe', []], ['Other / Not Listed', []],
    ] as [$name, $subs]) {
        Community::create(['religion' => 'Hindu', 'community_name' => $name, 'sub_communities' => $subs, 'is_active' => true, 'sort_order' => ++$order]);
    }
    Community::create(['religion' => 'Christian', 'community_name' => 'Roman Catholic', 'sub_communities' => ['Mangalorean Catholic'], 'is_active' => true, 'sort_order' => 1]);

    foreach ([1 => ['Vokkaliga', null], 2 => ['Smartha Brahmin', null], 3 => ['Kunchitiga', null], 4 => ['Tulu Gowda', 'Tulu Gowda'], 5 => ['Devadiga', 'Devadiga']] as $id => [$caste, $sub]) {
        DB::table('religious_info')->insert(['profile_id' => $id, 'religion' => 'Hindu', 'caste' => $caste, 'sub_caste' => $sub]);
    }
    DB::table('partner_preferences')->insert(['profile_id' => 1, 'caste' => json_encode(['Smartha Brahmin', 'Vokkaliga', 'Tulu Gowda'])]);
});

afterEach(function () {
    foreach (['communities', 'religious_info', 'partner_preferences', 'admin_activity_log'] as $table) {
        Schema::dropIfExists($table);
    }
});

function kcuSubs(string $name): ?array
{
    return Community::where('community_name', $name)->first()?->sub_communities;
}

function kcuMember(int $profileId): array
{
    $r = DB::table('religious_info')->where('profile_id', $profileId)->first();

    return [$r->caste, $r->sub_caste];
}

it('refuses to touch any site but Kannada Dating', function () {
    config(['app.url' => 'https://kudlamatrimony.com']);

    $this->artisan('communities:kannada-update')->assertFailed();

    expect(Community::where('community_name', 'Brahmin')->exists())->toBeFalse();
});

it('changes nothing on a dry run', function () {
    $before = [DB::table('communities')->get()->toArray(), DB::table('religious_info')->get()->toArray()];

    $this->artisan('communities:kannada-update', ['--dry-run' => true])->assertSuccessful();

    expect([DB::table('communities')->get()->toArray(), DB::table('religious_info')->get()->toArray()])->toEqual($before);
});

it('gives the Kannada communities their sub-castes, Brahmins one caste, and fixes the Tulu leftovers', function () {
    $this->artisan('communities:kannada-update')->assertSuccessful();

    expect(kcuSubs('Vokkaliga'))->toContain('Gangadikara', 'Kunchitiga', 'Hallikar', 'Morasu')
        ->and(kcuSubs('Lingayat / Veerashaiva'))->toContain('Panchamasali', 'Banajiga', 'Jangama')
        ->and(kcuSubs('Kuruba'))->toContain('Hande')
        ->and(kcuSubs('Brahmin'))->toContain('Smartha Brahmin', 'Madhwa Brahmin', 'Havyaka Brahmin', 'Babbur Kamme')
        ->and(kcuSubs('Bunts'))->not->toContain('Nadoja')
        ->and(kcuSubs('Mogaveera'))->not->toContain('Kharvi')
        ->and(Community::where('community_name', 'Kharvi')->where('is_active', true)->exists())->toBeTrue()
        ->and(kcuSubs('Scheduled Tribe'))->toContain('Nayaka / Valmiki / Beda', 'Jenu Kuruba', 'Soliga')
        ->and(kcuSubs(Kannada::GOWDA))->toBe(['Tulu Gowda', 'Arebhashe Gowda', "Don't wish to specify"])
        ->and(Community::whereIn('community_name', ['Salian', 'Konkani', 'Smartha Brahmin', 'Havyaka Brahmin', 'Kunchitiga'])->where('is_active', true)->count())->toBe(0)
        ->and(Community::where('community_name', 'Tulu Gowda')->exists())->toBeFalse()
        ->and(kcuSubs('Roman Catholic'))->toBe(['Mangalorean Catholic'])                       // Christian list untouched
        ->and(Community::where('religion', 'Hindu')->where('is_active', true)->orderByDesc('sort_order')->value('community_name'))->toBe('Other / Not Listed');
});

it('moves members and partner preferences so nobody drops out of a search', function () {
    $this->artisan('communities:kannada-update')->assertSuccessful();

    expect(kcuMember(1))->toBe(['Vokkaliga', null])
        ->and(kcuMember(2))->toBe(['Brahmin', 'Smartha Brahmin'])
        ->and(kcuMember(3))->toBe(['Vokkaliga', 'Kunchitiga'])
        ->and(kcuMember(4))->toBe([Kannada::GOWDA, 'Tulu Gowda'])
        ->and(kcuMember(5))->toBe(['Devadiga (Moily)', 'Devadiga'])
        ->and(json_decode(DB::table('partner_preferences')->value('caste'), true))->toBe(['Brahmin', 'Vokkaliga', Kannada::GOWDA])
        ->and(DB::table('admin_activity_log')->where('action', 'communities_kannada_update')->count())->toBe(1);
});

it('can run again without changing anything', function () {
    $this->artisan('communities:kannada-update')->assertSuccessful();
    $snapshot = [DB::table('communities')->orderBy('id')->get()->toArray(), DB::table('religious_info')->orderBy('id')->get()->toArray(), DB::table('partner_preferences')->get()->toArray()];

    $this->travel(5)->seconds();
    $this->artisan('communities:kannada-update')->assertSuccessful();

    expect([DB::table('communities')->orderBy('id')->get()->toArray(), DB::table('religious_info')->orderBy('id')->get()->toArray(), DB::table('partner_preferences')->get()->toArray()])->toEqual($snapshot);
});

it('keeps the old Discover addresses of merged and renamed communities working', function () {
    $this->artisan('communities:kannada-update')->assertSuccessful();
    Illuminate\Support\Facades\Cache::flush();
    $discover = app(App\Http\Controllers\DiscoverController::class);

    foreach (['smartha-brahmin' => 'brahmin', 'kunchitiga' => 'vokkaliga', 'tulu-gowda' => 'gowda-tulu-arebhashe', 'devadiga' => 'devadiga-moily'] as $old => $new) {
        $response = $discover->results('hindu-matrimony', $old);
        expect($response->getStatusCode())->toBe(301)
            ->and($response->headers->get('Location'))->toEndWith("/hindu-matrimony/{$new}");
    }
});

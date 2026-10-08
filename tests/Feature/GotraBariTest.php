<?php

use App\Models\PartnerPreference;
use App\Models\Profile;
use App\Models\ReferenceDataOption;
use App\Models\ReligiousInfo;
use App\Support\GotraBari;
use App\Traits\ProfileQueryFilters;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
| Gotra / Bari (owner approved 8 Oct 2026): one field for a Brahmin gotra
| (father's line) or a Tulu bari (mother's line). The same one counts as
| brother and sister, so the site says "Same bari as you" and members can
| hide those profiles.
*/

function gbProfile(int $id, ?string $gotra, bool $exclude = false): Profile
{
    $p = new Profile(['matri_id' => "KM1000{$id}"]);
    $p->id = $id;
    $p->setRelation('religiousInfo', new ReligiousInfo(['gotra' => $gotra]));
    $p->setRelation('partnerPreference', new PartnerPreference(['exclude_same_gotra' => $exclude]));

    return $p;
}

it('spots the same bari or gotra, ignoring case and spaces, and never "Don\'t Know"', function () {
    expect(GotraBari::shared(gbProfile(1, 'Bangera'), gbProfile(2, ' bangera ')))->toBe(['value' => 'bangera', 'kind' => 'bari'])
        ->and(GotraBari::shared(gbProfile(1, 'Kashyapa'), gbProfile(2, 'Kashyapa')))->toBe(['value' => 'Kashyapa', 'kind' => 'gotra'])
        ->and(GotraBari::shared(gbProfile(1, 'Bangera'), gbProfile(2, 'Salian')))->toBeNull()
        ->and(GotraBari::shared(gbProfile(1, "Don't Know"), gbProfile(2, "Don't Know")))->toBeNull()
        ->and(GotraBari::shared(gbProfile(1, 'Other'), gbProfile(2, 'other')))->toBeNull()
        ->and(GotraBari::shared(gbProfile(1, null), gbProfile(2, null)))->toBeNull()
        ->and(GotraBari::shared(gbProfile(1, 'Bangera'), gbProfile(1, 'Bangera')))->toBeNull();   // yourself
});

it('lists the options flat for the admin form, whether or not they are grouped', function () {
    config(['reference_data.gothram_list' => ['Common Tulu baris' => ['Amin', 'Bangera'], 'Brahmin gotras' => ['Atri'], 'Not sure / not listed' => ["Don't Know", 'Other']]]);
    expect(GotraBari::flatOptions())->toBe(['Amin', 'Bangera', 'Atri', "Don't Know", 'Other']);

    config(['reference_data.gothram_list' => ['Atri', 'Other']]);
    expect(GotraBari::flatOptions())->toBe(['Atri', 'Other']);
});

it('hides profiles of my own bari only when I ask for it', function () {
    Schema::create('profiles', function (Blueprint $t) {
        $t->id();
        $t->string('matri_id')->nullable();
        $t->softDeletes();
        $t->timestamps();
    });
    Schema::create('religious_info', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('profile_id');
        $t->string('gotra')->nullable();
        $t->timestamps();
    });
    foreach ([2 => 'BANGERA', 3 => 'Salian', 4 => null, 5 => 'Bangera '] as $id => $gotra) {
        DB::table('profiles')->insert(['id' => $id]);
        DB::table('religious_info')->insert(['profile_id' => $id, 'gotra' => $gotra]);
    }
    $filters = new class {
        use ProfileQueryFilters;

        public function ids(Profile $me): array
        {
            $q = Profile::query();
            $this->excludeSameGotra($q, $me);

            return $q->orderBy('id')->pluck('id')->all();
        }
    };

    expect($filters->ids(gbProfile(1, 'Bangera', exclude: true)))->toBe([3, 4])
        ->and($filters->ids(gbProfile(1, 'Bangera', exclude: false)))->toBe([2, 3, 4, 5])
        ->and($filters->ids(gbProfile(1, "Don't Know", exclude: true)))->toBe([2, 3, 4, 5]);

    Schema::dropIfExists('religious_info');
    Schema::dropIfExists('profiles');
});

it('adds the reviewed baris in groups, gotras after them, Don\'t Know / Other last, once', function () {
    Schema::create('reference_data_options', function (Blueprint $t) {
        $t->id();
        $t->string('category', 50);
        $t->string('group_label')->nullable();
        $t->string('cascade_group')->nullable();
        $t->string('value', 200);
        $t->string('label', 250)->nullable();
        $t->integer('sort_order')->default(0);
        $t->boolean('is_active')->default(true);
        $t->timestamps();
    });
    foreach (['Atri', 'Kashyapa', "Don't Know", 'Other'] as $i => $v) {
        ReferenceDataOption::create(['category' => 'gothram', 'value' => $v, 'sort_order' => $i * 10]);
    }

    $this->artisan('reference:add-tulu-baris')->assertSuccessful();
    $after = DB::table('reference_data_options')->orderBy('id')->get()->toArray();
    $this->artisan('reference:add-tulu-baris')->assertSuccessful();

    $rows = ReferenceDataOption::where('category', 'gothram')->orderBy('sort_order')->orderBy('id')->get()
        ->map(fn ($r) => ['value' => $r->value, 'group_label' => $r->group_label])->all();
    $list = ReferenceDataOption::assembleList($rows);

    expect(array_keys($list))->toBe(['Common Tulu baris', 'Bunt balis', 'Nadava balis', 'Gowda baris', 'Brahmin gotras', 'Not sure / not listed'])
        ->and($list['Gowda baris'])->toContain('Balasanna')
        ->and($list['Brahmin gotras'])->toBe(['Atri', 'Kashyapa'])
        ->and($list['Not sure / not listed'])->toBe(["Don't Know", 'Other'])
        ->and(DB::table('reference_data_options')->orderBy('id')->get()->toArray())->toEqual($after);   // second run: no change

    Schema::dropIfExists('reference_data_options');
});

it('offers a searchable, grouped picker that still submits one gotra value', function () {
    config(['reference_data.gothram_list' => ['Common Tulu baris' => ['Amin', 'Bangera'], 'Brahmin gotras' => ['Atri'], 'Not sure / not listed' => ["Don't Know", 'Other']]]);

    $html = Blade::render('<form><x-gotra-bari-select :value="$v" /></form>', ['v' => 'Balasanna']);

    expect($html)->toContain('Gotra / Bari')
        ->toContain('Common Tulu baris')
        ->toContain('name="gotra" value="Balasanna"')                  // a typed bari is kept
        ->toContain("gbChoice === 'Other' ? (gbTyped.trim() || 'Other') : gbChoice")
        ->toContain('family line from your mother');
});

it('shows the note and the preference where members will see them', function () {
    $page = file_get_contents(resource_path('views/profile/preview.blade.php'));
    $card = file_get_contents(resource_path('views/components/profile-card.blade.php'));

    expect($page)->toContain('\App\Support\GotraBari::shared(auth()->user()?->profile, $profile)')
        ->toContain('Same {{ $sameLineage[\'kind\'] }} as you')
        ->and($card)->toContain('\App\Support\GotraBari::shared($viewerProfile, $p)')
        ->and(file_get_contents(resource_path('views/onboarding/partner-preferences.blade.php')))->toContain('<x-same-gotra-checkbox')
        ->and(file_get_contents(resource_path('views/profile/sections/partner-edit.blade.php')))->toContain('<x-same-gotra-checkbox')
        ->and(file_get_contents(app_path('Http/Controllers/SearchController.php')))->toContain('respectLineagePreference: false');
});

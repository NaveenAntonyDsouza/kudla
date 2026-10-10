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

function gbProfile(int $id, ?string $gotra, bool $exclude = false, ?string $caste = 'Billava'): Profile
{
    $p = new Profile(['matri_id' => "KM1000{$id}"]);
    $p->id = $id;
    $p->setRelation('religiousInfo', new ReligiousInfo(['gotra' => $gotra, 'caste' => $caste]));
    $p->setRelation('partnerPreference', new PartnerPreference(['exclude_same_gotra' => $exclude]));

    return $p;
}

it('spots the same bari or gotra, ignoring case and spaces, and never "Don\'t Know"', function () {
    expect(GotraBari::shared(gbProfile(1, 'Bangera'), gbProfile(2, ' bangera ')))->toBe(['value' => 'bangera', 'kind' => 'bari'])
        ->and(GotraBari::shared(gbProfile(1, 'Kashyapa', caste: 'Brahmin'), gbProfile(2, 'Kashyapa', caste: 'Brahmin')))->toBe(['value' => 'Kashyapa', 'kind' => 'gotra'])
        ->and(GotraBari::shared(gbProfile(1, 'Bangera'), gbProfile(2, 'Salian')))->toBeNull()
        ->and(GotraBari::shared(gbProfile(1, "Don't Know"), gbProfile(2, "Don't Know")))->toBeNull()
        ->and(GotraBari::shared(gbProfile(1, 'Other'), gbProfile(2, 'other')))->toBeNull()
        ->and(GotraBari::shared(gbProfile(1, null), gbProfile(2, null)))->toBeNull()
        ->and(GotraBari::shared(gbProfile(1, 'Bangera'), gbProfile(1, 'Bangera')))->toBeNull();   // yourself
});

it('only counts the same bari within the same caste (owner, 10 Oct 2026), every Brahmin community as one', function () {
    expect(GotraBari::shared(gbProfile(1, 'Bangera', caste: 'Bunts'), gbProfile(2, 'Bangera', caste: 'Billava')))->toBeNull()
        ->and(GotraBari::shared(gbProfile(1, 'Bangera', caste: 'Bunts'), gbProfile(2, 'Bangera', caste: ' bunts ')))->toBe(['value' => 'Bangera', 'kind' => 'bari'])
        ->and(GotraBari::shared(gbProfile(1, 'Kashyapa', caste: 'Brahmin'), gbProfile(2, 'Kashyapa', caste: 'GSB - Goud Saraswat Brahmin')))->toBe(['value' => 'Kashyapa', 'kind' => 'gotra'])
        ->and(GotraBari::shared(gbProfile(1, 'Sanaga', caste: 'Vishwakarma'), gbProfile(2, 'Sanaga', caste: 'Vishwakarma')))->toBe(['value' => 'Sanaga', 'kind' => 'gotra'])
        ->and(GotraBari::shared(gbProfile(1, 'Bangera', caste: 'Other / Not Listed'), gbProfile(2, 'Bangera', caste: 'Other / Not Listed')))->toBeNull()
        ->and(GotraBari::shared(gbProfile(1, 'Bangera', caste: null), gbProfile(2, 'Bangera', caste: null)))->toBeNull();
});

it('labels the field by caste and puts that caste\'s groups first', function () {
    expect(GotraBari::label('Bunts'))->toBe('Bari')
        ->and(GotraBari::label('Gowda (Tulu / Arebhashe)'))->toBe('Bari')
        ->and(GotraBari::label('Brahmin'))->toBe('Gotra')
        ->and(GotraBari::label('Rajapur Saraswat Brahmin (RSB)'))->toBe('Gotra')
        ->and(GotraBari::label('Vishwakarma'))->toBe('Gotra')
        ->and(GotraBari::label('Ganiga'))->toBe('Gotra / Bari')
        ->and(GotraBari::label('Other / Not Listed'))->toBe('Gotra / Bari')
        ->and(GotraBari::label(null))->toBe('Gotra / Bari')
        ->and(GotraBari::firstGroups('Bunts'))->toBe(['Bunt balis', 'Nadava balis'])
        ->and(GotraBari::firstGroups('Havyaka Brahmin'))->toBe(['Brahmin gotras'])
        ->and(GotraBari::firstGroups('Billava'))->toBe([])
        ->and(GotraBari::help('Billava'))->toContain("your mother's bari")
        ->and(GotraBari::help('Vishwakarma'))->toContain('Sanaga')
        ->and(GotraBari::casteHints())->toHaveKey('bunts')
        ->and(GotraBari::casteHints())->not->toHaveKey('ganiga');
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
        $t->string('caste')->nullable();
        $t->string('gotra')->nullable();
        $t->timestamps();
    });
    $rows = [2 => ['Billava', 'BANGERA'], 3 => ['Billava', 'Salian'], 4 => ['Billava', null], 5 => ['Billava', 'Bangera '], 6 => ['Bunts', 'Bangera'],
        7 => ['Brahmin', 'Kashyapa'], 8 => ['Havyaka Brahmin', 'Kashyapa'], 9 => ['Vishwakarma', 'Kashyapa']];
    foreach ($rows as $id => [$caste, $gotra]) {
        DB::table('profiles')->insert(['id' => $id]);
        DB::table('religious_info')->insert(['profile_id' => $id, 'caste' => $caste, 'gotra' => $gotra]);
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

    expect($filters->ids(gbProfile(1, 'Bangera', exclude: true)))->toBe([3, 4, 6, 7, 8, 9])       // the Bunt Bangera stays
        ->and($filters->ids(gbProfile(1, 'Bangera', exclude: false)))->toBe([2, 3, 4, 5, 6, 7, 8, 9])
        ->and($filters->ids(gbProfile(1, "Don't Know", exclude: true)))->toBe([2, 3, 4, 5, 6, 7, 8, 9])
        ->and($filters->ids(gbProfile(1, 'Bangera', exclude: true, caste: 'Other / Not Listed')))->toBe([2, 3, 4, 5, 6, 7, 8, 9])
        ->and($filters->ids(gbProfile(1, 'Kashyapa', exclude: true, caste: 'Shivalli Brahmin')))->toBe([2, 3, 4, 5, 6, 9]);   // every Brahmin community

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

    expect(array_keys($list))->toBe(['Common Tulu baris', 'Bunt balis', 'Nadava balis', 'Gowda baris', 'Brahmin gotras', 'Vishwakarma gotras', 'Not sure / not listed'])
        ->and($list['Gowda baris'])->toContain('Balasanna')
        ->and($list['Bunt balis'])->toContain('Karkatarannaya', 'Chalyannaya')
        ->and($list['Brahmin gotras'])->toBe(['Atri', 'Kashyapa', 'Naidhruva', 'Angirasa'])
        ->and($list['Vishwakarma gotras'])->toBe(['Sanaga', 'Sanatana', 'Abhuvanasa', 'Pratnasa', 'Suparna'])
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

it('labels the picker "Bari" for a Bunt, with the Bunt groups first, and follows a caste changed on the page', function () {
    config(['reference_data.gothram_list' => ['Common Tulu baris' => ['Amin'], 'Bunt balis' => ['Aarya Bannaya'], 'Brahmin gotras' => ['Atri']]]);

    $html = Blade::render('<form><x-gotra-bari-select :value="$v" :caste="$c" /></form>', ['v' => '', 'c' => 'Bunts']);

    expect($html)->toContain('<span x-text="gbLabel">Bari</span>')
        ->toContain('your mother&#039;s bari')
        ->toContain('const first = (gbFirst) || []')
        ->not->toContain('first-groups=')                                      // a prop, not a stray attribute
        ->toContain("@change.window=\"if (\$event.target.name === 'caste') gbCaste = \$event.target.value\"")
        ->and(Blade::render('<x-gotra-bari-select :caste="$c" />', ['c' => 'Brahmin']))->toContain('<span x-text="gbLabel">Gotra</span>');
});

it('lists gotras / baris members typed that are not in the list, with their caste', function () {
    config(['reference_data.gothram_list' => ['Common Tulu baris' => ['Bangera'], 'Not sure / not listed' => ["Don't Know", 'Other']]]);
    Schema::create('profiles', function (Blueprint $t) {
        $t->id();
        $t->string('matri_id')->nullable();
        $t->string('full_name')->nullable();
        $t->softDeletes();
        $t->timestamps();
    });
    Schema::create('religious_info', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('profile_id');
        $t->string('caste')->nullable();
        $t->string('other_caste_name')->nullable();
        $t->string('gotra')->nullable();
        $t->timestamps();
    });
    foreach ([1 => ['Gowda (Tulu / Arebhashe)', 'Balasanna'], 2 => ['Gowda (Tulu / Arebhashe)', 'balasanna '], 3 => ['Billava', 'Bangera'], 4 => ['Billava', "Don't Know"], 5 => ['Billava', 'Other']] as $id => [$caste, $gotra]) {
        DB::table('profiles')->insert(['id' => $id, 'matri_id' => "KM{$id}"]);
        DB::table('religious_info')->insert(['profile_id' => $id, 'caste' => $caste, 'gotra' => $gotra]);
    }

    $rows = (new \App\Filament\Pages\UnlistedCommunities)->getUnlistedGotras();

    expect($rows)->toHaveCount(1)
        ->and($rows[0]->value)->toBe('Balasanna')
        ->and($rows[0]->count)->toBe(2)
        ->and($rows[0]->castes)->toBe(['Gowda (Tulu / Arebhashe)']);

    Schema::dropIfExists('religious_info');
    Schema::dropIfExists('profiles');
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

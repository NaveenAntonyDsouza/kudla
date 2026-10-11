<?php

use App\Services\DiscoverConfigService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
| "Other / Not Listed" (Oct 2026): kept in the sign-up form so people outside
| the list can still register, but it is not a community, so the homepage and
| the Discover pages never offer it.
*/

beforeEach(function () {
    Schema::dropIfExists('communities');
    Schema::create('communities', function (Blueprint $t) {
        $t->id();
        $t->string('religion');
        $t->string('community_name');
        $t->boolean('is_active')->default(true);
        $t->integer('sort_order')->default(0);
        $t->timestamps();
    });
    DB::table('communities')->insert([
        ['religion' => 'Hindu', 'community_name' => 'Billava', 'is_active' => 1, 'sort_order' => 1],
        ['religion' => 'Hindu', 'community_name' => 'Other / Not Listed', 'is_active' => 1, 'sort_order' => 99],
    ]);
    Cache::flush();
});

it('leaves "Other / Not Listed" out of the Discover community pages', function () {
    $slugs = collect(app(DiscoverConfigService::class)->hinduCastes())->pluck('slug');

    expect($slugs)->toContain('billava')->not->toContain('other-not-listed');
});

it('sends the old "Other / Not Listed" browse page to the community list', function () {
    $this->get('/discover/hindu-matrimony/other-not-listed')->assertRedirect('/discover/hindu-matrimony')->assertStatus(301);
});

it('leaves it out of the homepage community tiles and search box', function () {
    expect(file_get_contents(app_path('Http/Controllers/HomeController.php')))
        ->toContain('->reject(fn ($c) => \App\Models\ReligiousInfo::isOtherCaste($c->community_name))');
});

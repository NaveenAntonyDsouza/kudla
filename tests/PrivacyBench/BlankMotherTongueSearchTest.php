<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\PrivacyBench\Bench;

/*
| Weekly review, 9 Nov 2026: 135 of 803 live Kudla profiles had no mother tongue, so a
| mother-tongue search never showed them. Now such a search also includes members who
| never gave one, and says how many; other languages stay out, the other filters still
| apply, and "same mother tongue only" members stay hidden from other-language viewers.
*/

beforeEach(function () {
    Mail::fake();
    Http::fake();
    $this->b = Bench::build();
    $this->b->m['W']->forceFill(['mother_tongue' => null])->save();
});

it('includes members with no mother tongue in a mother-tongue search, and says so', function () {
    $b = $this->b;
    $res = $this->actingAs($b->m['V']->user)->get('/search/results?search=1&mother_tongue[]=Tulu');

    $ids = collect($res->viewData('results')->items())->pluck('id');
    expect($ids)->toContain($b->m['W']->id)              // no mother tongue: now included
        ->not->toContain($b->m['T']->id)                   // Konkani: still left out
        ->not->toContain($b->m['TP']->id)
        ->not->toContain($b->m['TL']->id)                  // Tulu, but "same mother tongue only" and V is Konkani
        ->and($res->viewData('searchNote'))->toContain('not given a mother tongue');
});

it('keeps every other filter when it adds the blank ones', function () {
    $b = $this->b;
    $res = $this->actingAs($b->m['V']->user)->get('/search/results?search=1&mother_tongue[]=Tulu&religion[]=Hindu');

    expect(collect($res->viewData('results')->items())->pluck('id'))->not->toContain($b->m['W']->id);   // W is Christian
});

it('does the same in the app search', function () {
    $b = $this->b;
    $json = $this->actingAs($b->m['V']->user, 'sanctum')->getJson('/api/v1/search/partner?mother_tongue[]=Tulu')->json();
    $ids = collect(data_get($json, 'data', []))->pluck('matri_id');

    expect($ids)->toContain($b->m['W']->matri_id)->not->toContain($b->m['T']->matri_id);
});

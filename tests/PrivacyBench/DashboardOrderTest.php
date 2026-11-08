<?php

use App\Services\MatchingService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\PrivacyBench\Bench;

/*
| Dashboard (Nov 2026, owner's choice): Mutual Matches above Recommended Matches,
| and a member who is in both lists is shown only once (under Mutual).
*/

beforeEach(function () {
    Mail::fake();
    Http::fake();
    $this->b = Bench::build();
});

it('shows Mutual Matches above Recommended, without the same member twice', function () {
    $b = $this->b;
    $both = $b->m['V'];
    $onlyRecommended = $b->m['VA'];

    $svc = Mockery::mock(MatchingService::class)->makePartial();
    $svc->shouldReceive('getRecommendations')->andReturn(collect([$both, $onlyRecommended]));
    $svc->shouldReceive('getMutualMatches')->andReturn(new LengthAwarePaginator([$both], 1, 4));
    app()->instance(MatchingService::class, $svc);

    $res = $this->actingAs($b->m['T']->user)->get('/dashboard');

    $res->assertOk()->assertSeeInOrder(['Mutual Matches', 'Recommended Matches']);
    expect($res->viewData('recommendedMatches')->pluck('id')->all())->toBe([$onlyRecommended->id])
        ->and($res->viewData('mutualMatches')->pluck('id')->all())->toBe([$both->id]);
});

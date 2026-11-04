<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\PrivacyBench\Bench;

beforeEach(function () {
    Mail::fake();
    Http::fake();
    Notification::fake();
});

it('builds the test members and their interactions on the bench database only', function () {
    $b = Bench::build();

    expect($b->m)->toHaveCount(10)
        ->and(collect($b->canaries)->countBy(fn ($c) => $c[1])->all())->toMatchArray(['never' => 100, 'contact' => 110, 'photo' => 10])
        ->and($b->interests['VA_T']->fresh()->status)->toBe('accepted')
        ->and($b->interests['VS_T']->fresh()->status)->toBe('pending')
        ->and($b->m['TH']->fresh()->is_hidden)->toBeTrue();
});

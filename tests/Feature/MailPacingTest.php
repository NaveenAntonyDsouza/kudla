<?php

use App\Mail\DatabaseMailable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
| One shared gap between emails sent from scheduled / queued work (Oct 2026),
| so overlapping campaigns can't outrun the mailbox limit together.
*/

beforeEach(function () {
    Schema::dropIfExists('site_settings');
    Schema::create('site_settings', function (Blueprint $t) {
        $t->id();
        $t->string('key')->unique();
        $t->text('value')->nullable();
        $t->timestamps();
    });
    Cache::flush();
});

function paceOnce(): void
{
    (fn () => self::paceBulkSending())->bindTo(null, DatabaseMailable::class)();
}

it('keeps at least the set gap between two emails', function () {
    DB::table('site_settings')->insert(['key' => 'mail_min_gap_seconds', 'value' => '0.3']);

    $start = microtime(true);
    paceOnce();
    paceOnce();
    paceOnce();

    expect(microtime(true) - $start)->toBeGreaterThan(0.55)->toBeLessThan(2.0);
});

it('does not wait when pacing is switched off', function () {
    DB::table('site_settings')->insert(['key' => 'mail_min_gap_seconds', 'value' => '0']);

    $start = microtime(true);
    paceOnce();
    paceOnce();

    expect(microtime(true) - $start)->toBeLessThan(0.2);
});

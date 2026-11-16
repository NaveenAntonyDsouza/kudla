<?php

use App\Support\AdminModules;
use App\Support\LastSeen;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
| "Last seen" on profile cards (weekly review, 16 Nov 2026). The card said "12d ago",
| and nothing at all for a member with no sign-in on record — which read as new, not
| absent. last_login_at was only stamped by the sign-in form until 2 Oct 2026, so an
| empty value means "not seen since then" unless the member joined after that day.
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
    Carbon::setTestNow('2026-11-16 10:00:00');
});

afterEach(function () {
    Carbon::setTestNow();
    Schema::dropIfExists('site_settings');
});

function lastSeenSwitchOn(): void
{
    DB::table('site_settings')->insert(['key' => AdminModules::LAST_SEEN, 'value' => '1']);
    Cache::flush();
}

function seenLabel(?string $lastLogin, ?string $joined = '2026-05-01'): ?string
{
    return LastSeen::forCard($lastLogin ? Carbon::parse($lastLogin) : null, $joined ? Carbon::parse($joined) : null)[0] ?? null;
}

it('keeps the old line while the switch is off', function () {
    expect(seenLabel('2026-11-16 09:30:00'))->toBe('Online now')
        ->and(seenLabel('2026-11-16 02:00:00'))->toBe('Today')
        ->and(seenLabel('2026-11-04 10:00:00'))->toBe('12d ago')
        ->and(seenLabel(null))->toBeNull();
});

it('says when a member was last seen', function () {
    lastSeenSwitchOn();

    expect(seenLabel('2026-11-16 09:30:00'))->toBe('Online now')
        ->and(seenLabel('2026-11-16 02:00:00'))->toBe('Active today')
        ->and(seenLabel('2026-11-15 06:00:00'))->toBe('Last seen yesterday')
        ->and(seenLabel('2026-11-04 10:00:00'))->toBe('Last seen 12 days ago')
        ->and(seenLabel('2026-10-01 10:00:00'))->toBe('Last seen a month ago')
        ->and(seenLabel('2026-08-10 10:00:00'))->toBe('Last seen 3 months ago')
        ->and(seenLabel('2025-09-01 10:00:00'))->toBe('Last seen over a year ago');
});

it('reads an empty sign-in as "not seen since tracking began", or "not signed in yet" for a newer member', function () {
    lastSeenSwitchOn();

    // joined before 2 Oct 2026: they may well have used the site, we just never recorded it
    expect(seenLabel(null, '2026-06-10'))->toBe('Last seen over a month ago');
    // joined after it, e.g. typed in by staff on the console: genuinely never signed in
    expect(seenLabel(null, '2026-10-20'))->toBe('Not signed in yet');

    Carbon::setTestNow('2027-01-10 10:00:00');
    expect(seenLabel(null, '2026-06-10'))->toBe('Last seen over 3 months ago');
});

it('greys out members who have been away, as the card always did', function () {
    lastSeenSwitchOn();

    expect(LastSeen::forCard(Carbon::parse('2026-11-14 08:00:00'), null)[1])->toBe('bg-yellow-400')
        ->and(LastSeen::forCard(Carbon::parse('2026-11-11 08:00:00'), null)[1])->toBe('bg-orange-400')
        ->and(LastSeen::forCard(Carbon::parse('2026-10-01 08:00:00'), null)[1])->toBe('bg-gray-300')
        ->and(LastSeen::forCard(null, Carbon::parse('2026-05-01'))[1])->toBe('bg-gray-300');
});

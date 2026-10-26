<?php

use App\Models\SiteSetting;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

/*
| Uptime alarm (weekly review, Oct 2026: kudla was down 45 minutes on 14 Oct
| and nobody was told). One email when a watched site stops loading, one when
| it is back; a single slow answer that recovers on the retry sends nothing.
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
    config(['mail.default' => 'array']);
});

afterEach(fn () => Schema::dropIfExists('site_settings'));

function uptimeMails(): array
{
    return collect(app('mailer')->getSymfonyTransport()->messages())
        ->map(fn ($m) => $m->getOriginalMessage()->getSubject())->values()->all();
}

it('does nothing until it is set up', function () {
    Http::fake();
    $this->artisan('uptime:watch', ['--retry-after' => 0])->assertSuccessful();
    Http::assertNothingSent();
});

it('emails once when a site goes down and once when it is back', function () {
    SiteSetting::setValue('uptime_watch_urls', "https://kudla.example.test\nhttps://crasta.example.test");
    SiteSetting::setValue('uptime_alert_email', 'owner@example.test');

    $kudlaUp = false;
    Http::fake(function ($request) use (&$kudlaUp) {
        if (str_contains($request->url(), 'kudla') && ! $kudlaUp) {
            return Http::response('Server Error', 500);
        }

        return Http::response('<html><body>ok</body></html>', 200);
    });

    $this->artisan('uptime:watch', ['--retry-after' => 0])->assertSuccessful();
    $this->artisan('uptime:watch', ['--retry-after' => 0])->assertSuccessful();     // within 4 minutes: skipped
    Cache::lock('uptime-watch')->forceRelease();
    $this->artisan('uptime:watch', ['--retry-after' => 0])->assertSuccessful();     // still down: no second alarm
    expect(uptimeMails())->toBe(['kudla.example.test is DOWN']);

    $kudlaUp = true;
    Cache::lock('uptime-watch')->forceRelease();
    $this->artisan('uptime:watch', ['--retry-after' => 0])->assertSuccessful();
    expect(uptimeMails())->toBe(['kudla.example.test is DOWN', 'kudla.example.test is back up']);

    Http::assertSent(fn ($r) => str_contains($r->header('User-Agent')[0] ?? '', 'Mozilla/5.0'));   // Bluehost refuses plain clients
});

it('ignores a blip that recovers on the retry', function () {
    SiteSetting::setValue('uptime_watch_urls', 'https://kudla.example.test');
    SiteSetting::setValue('uptime_alert_email', 'owner@example.test');
    Http::fakeSequence()->push('Timeout', 504)->push('<html>ok</html>', 200);

    $this->artisan('uptime:watch', ['--retry-after' => 0])->assertSuccessful();
    expect(uptimeMails())->toBe([]);
});

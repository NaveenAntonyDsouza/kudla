<?php

use App\Models\Profile;
use App\Models\SiteSetting;
use App\Services\MatchingService;
use App\Support\AdminModules;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\PrivacyBench\Bench;

/*
| Weekly review 23 Nov 2026: 36% of interests went to members away 30 days or more and none was
| answered. With "Recently active members first in matches" on, Recommended, Mutual Matches and the
| weekly email list members seen in the last 30 days first, and the interest button says when a
| member has never signed in. Off (the default) it is exactly the old order.
*/

beforeEach(function () {
    Bench::guard();
    Mail::fake();
    Http::fake();
    SiteSetting::setValue(AdminModules::ACTIVE_FIRST, '0');
    $this->b = Bench::build();
});

afterEach(fn () => SiteSetting::setValue(AdminModules::ACTIVE_FIRST, '0'));

it('puts members seen in the last 30 days first, keeping the old order inside each group', function () {
    $away = $this->b->m['T'];  $recent = $this->b->m['TP'];  $never = $this->b->m['W'];
    DB::table('users')->where('id', $away->user_id)->update(['last_login_at' => now()->subDays(45)]);
    DB::table('users')->where('id', $recent->user_id)->update(['last_login_at' => now()->subDays(2)]);
    DB::table('users')->where('id', $never->user_id)->update(['last_login_at' => null]);

    $list = fn () => collect([$away, $recent, $never])->map(function ($p) {
        $p = Profile::with('user')->find($p->id);
        $p->forceFill(['onboarding_completed' => true]);
        $p->has_photo = true;
        $p->match_score = ['T' => 90, 'TP' => 50, 'W' => 70][array_search($p->id, array_map(fn ($m) => $m->id, $this->b->m))];

        return $p;
    });

    expect(Profile::sortCompletedFirst($list(), 'match_score')->pluck('id')->all())
        ->toBe([$away->id, $never->id, $recent->id]);                      // off: by score only

    SiteSetting::setValue(AdminModules::ACTIVE_FIRST, '1');
    expect(Profile::sortCompletedFirst($list(), 'match_score')->pluck('id')->all())
        ->toBe([$recent->id, $away->id, $never->id]);                      // on: recent first, then by score

    // the real lists still work with the switch on
    expect(app(MatchingService::class)->getRecommendations($this->b->m['V'], 6))->toBeInstanceOf(\Illuminate\Support\Collection::class);
});

it('says on the Send Interest button when a member has never signed in, only with the switch on', function () {
    $target = $this->b->m['W'];
    DB::table('users')->where('id', $target->user_id)->update(['last_login_at' => null]);
    $this->actingAs($this->b->m['V']->user);
    $render = fn () => Blade::render('<x-send-interest-modal :profile="$p" />', ['p' => Profile::with('user')->find($target->id)]);

    expect($render())->not->toContain('data-never-signed-in');
    SiteSetting::setValue(AdminModules::ACTIVE_FIRST, '1');
    expect($render())->toContain('data-never-signed-in')->toContain('has not signed in yet');

    DB::table('users')->where('id', $target->user_id)->update(['last_login_at' => now()->subDays(50)]);
    expect($render())->not->toContain('data-never-signed-in');            // signed in once: no note
});

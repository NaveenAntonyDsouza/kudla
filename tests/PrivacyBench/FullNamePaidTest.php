<?php

use App\Http\Controllers\SearchController;
use App\Http\Resources\V1\ProfileCardResource;
use App\Models\MembershipPlan;
use App\Models\Profile;
use App\Models\SiteSetting;
use App\Support\AdminModules;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\PrivacyBench\Bench;

/*
| Owner 20 Nov 2026 (like Chavara): free members see other members as "Teresa D.", paid members see the
| full name, everyone sees their own. Signed-out visitors never see a surname on the home page.
*/

beforeEach(function () {
    Mail::fake();
    Http::fake();
    $this->b = Bench::build();
    SiteSetting::setValue('free_membership_enabled', '0');
    $plan = MembershipPlan::where('can_view_contact', true)->first() ?? MembershipPlan::first();
    $plan->forceFill(['can_view_contact' => true])->save();
    DB::table('user_memberships')->insert(['user_id' => $this->b->m['VS']->user_id, 'plan_id' => $plan->id, 'is_active' => true,
        'starts_at' => now()->subDay(), 'ends_at' => now()->addMonth(), 'created_at' => now(), 'updated_at' => now()]);
    $this->t = $this->b->m['T'];
    $this->t->update(['full_name' => 'Teresa Bench Dsouza']);
});

it('shows a free member "Teresa D." with an upgrade link, and a paid member the full name', function () {
    $free = $this->b->m['VA'];      // accepted interest with T, so all of her pages list T
    $url = '/profile/' . $this->t->matri_id;

    $page = $this->actingAs($free->user)->get($url)->assertOk()->getContent();
    expect($page)->toContain('Teresa D.')->toContain('data-full-name-upgrade')->not->toContain('Dsouza');
    foreach (['/interests', '/chats', $url . '/print'] as $other) {
        expect((string) $this->actingAs($free->user)->get($other)->getContent())->not->toContain('Dsouza');
    }

    $paid = $this->b->m['VS'];
    $paidPage = $this->actingAs($paid->user)->get($url)->assertOk()->getContent();
    expect($paidPage)->toContain('Teresa Bench Dsouza')->not->toContain('data-full-name-upgrade');

    // her own profile always shows her full name
    expect($this->actingAs($this->t->user)->get('/profile/preview')->getContent())->toContain('Teresa Bench Dsouza');
});

it('shows full names to everyone signed in when the switch is off', function () {
    SiteSetting::setValue(AdminModules::FULL_NAME_PAID, '0');
    $page = $this->actingAs($this->b->m['VA']->user)->get('/profile/' . $this->t->matri_id)->assertOk()->getContent();
    expect($page)->toContain('Teresa Bench Dsouza')->not->toContain('data-full-name-upgrade');
});

it('lets a free member search by first name but not by surname; a paid member can use either', function () {
    $ids = fn (string $word) => SearchController::applyKeyword(Profile::query(), $word)->pluck('id')->all();

    $this->actingAs($this->b->m['VA']->user);
    expect($ids('Dsouza'))->not->toContain($this->t->id)
        ->and($ids('Teresa'))->toContain($this->t->id);

    $this->actingAs($this->b->m['VS']->user);
    request()->attributes->remove('profile_name_full:' . $this->b->m['VA']->user_id);
    expect($ids('Dsouza'))->toContain($this->t->id);
});

it('sends the app the same name', function () {
    $free = $this->b->m['VA']->fresh();
    $paid = $this->b->m['VS']->fresh();
    expect((new ProfileCardResource($this->t->fresh(), $free))->resolve()['full_name'])->toBe('Teresa D.')
        ->and((new ProfileCardResource($this->t->fresh(), $paid))->resolve()['full_name'])->toBe('Teresa Bench Dsouza');
});

it('never shows a surname on the public home page', function () {
    foreach (['modern', 'premium', 'classic', 'harmony', 'parish'] as $template) {
        SiteSetting::setValue('homepage_template', $template);
        cache()->flush();
        $home = (string) $this->get('/')->getContent();
        expect($home)->not->toContain('Dsouza');
        if (in_array($template, ['modern', 'premium'], true)) {
            expect($home)->toMatch('/(Teresa|Bench) [A-Z]\./');   // names are shown, as "Teresa D."
        }
    }
});

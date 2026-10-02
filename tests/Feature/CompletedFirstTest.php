<?php

use App\Models\Profile;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Completed registrations first, half-filled ones after
|--------------------------------------------------------------------------
| Owner's choice (2026-10-02): unfinished profiles stay listed in search,
| discover, matches and the homepage, but after the complete ones — kudla
| had 112 unfinished profiles among 650 search results, 34 with only a
| name and date of birth.
*/

beforeEach(function () {
    Schema::create('profiles', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('user_id')->nullable();
        $t->string('matri_id')->nullable();
        $t->boolean('onboarding_completed')->default(false);
        $t->boolean('is_vip')->default(false);
        $t->softDeletes();
        $t->timestamps();
    });
});

afterEach(function () {
    Schema::dropIfExists('profiles');
});

function cfProfile(string $id, bool $completed, string $created, bool $vip = false): void
{
    DB::table('profiles')->insert([
        'matri_id' => $id, 'onboarding_completed' => $completed, 'is_vip' => $vip,
        'created_at' => $created, 'updated_at' => $created,
    ]);
}

it('lists completed profiles before unfinished ones, then by the chosen sort', function () {
    cfProfile('OLD-DONE', true, '2026-01-01');
    cfProfile('NEW-HALF', false, '2026-10-01');   // newest, but unfinished
    cfProfile('NEW-DONE', true, '2026-09-01');
    cfProfile('VIP-HALF', false, '2026-02-01', vip: true);

    $order = Profile::query()->completedFirst()
        ->orderBy('is_vip', 'desc')->orderBy('created_at', 'desc')
        ->pluck('matri_id')->all();

    expect($order)->toBe(['NEW-DONE', 'OLD-DONE', 'VIP-HALF', 'NEW-HALF']);
});

it('ranks scored matches the same way: completed first, then best score', function () {
    $make = fn (string $id, bool $done, int $score) => tap(new Profile, function ($p) use ($id, $done, $score) {
        $p->matri_id = $id; $p->onboarding_completed = $done; $p->match_score = $score;
    });

    $sorted = Profile::sortCompletedFirst(collect([
        $make('HALF-95', false, 95),
        $make('DONE-60', true, 60),
        $make('DONE-80', true, 80),
        $make('HALF-70', false, 70),
    ]), 'match_score');

    expect($sorted->pluck('matri_id')->all())->toBe(['DONE-80', 'DONE-60', 'HALF-95', 'HALF-70']);
});

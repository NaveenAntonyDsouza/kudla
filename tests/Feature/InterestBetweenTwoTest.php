<?php

use App\Models\Profile;
use App\Services\ProfileAccessService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
| Kudla, 4 Nov 2026: "anyone can see the contact details" of KM100791 and
| KM100865 without an interest. hasAcceptedInterest read
|   (from = a AND to = b) OR (from = b OR to = a)
| because Laravel joins the pairs inside ->orWhere([...]) with OR. Once a
| member had sent ONE accepted interest, every member saw her phone; a member
| who had accepted one saw everyone's. 135 of 154 contact views, 25 Oct–4 Nov.
| The same pattern was in isBlocked, hasAnyInterest and four API spots.
| Here a third member's interests and blocks must open nothing between A and B.
*/

beforeEach(function () {
    Schema::create('interests', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('sender_profile_id');
        $t->unsignedBigInteger('receiver_profile_id');
        $t->string('status')->default('pending');
        $t->timestamp('cancelled_at')->nullable();
        $t->timestamps();
    });
    Schema::create('blocked_profiles', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('profile_id');
        $t->unsignedBigInteger('blocked_profile_id');
        $t->timestamps();
    });
    $this->svc = app(ProfileAccessService::class);
    [$this->a, $this->b] = [tap(new Profile())->forceFill(['id' => 1]), tap(new Profile())->forceFill(['id' => 2])];
    // C = 3: the target B sent C an accepted interest; the viewer A accepted one from C; B blocked C; C blocked A
    DB::table('interests')->insert([
        ['sender_profile_id' => 2, 'receiver_profile_id' => 3, 'status' => 'accepted'],
        ['sender_profile_id' => 3, 'receiver_profile_id' => 1, 'status' => 'accepted'],
    ]);
    DB::table('blocked_profiles')->insert([
        ['profile_id' => 2, 'blocked_profile_id' => 3],
        ['profile_id' => 3, 'blocked_profile_id' => 1],
    ]);
});

afterEach(function () {
    Schema::dropIfExists('interests');
    Schema::dropIfExists('blocked_profiles');
});

it('sees no accepted interest, no interest and no block between two members because of a third', function () {
    expect($this->svc->hasAcceptedInterest($this->a, $this->b))->toBeFalse()
        ->and($this->svc->hasAcceptedInterest($this->b, $this->a))->toBeFalse()
        ->and($this->svc->hasAnyInterest($this->a, $this->b))->toBeFalse()
        ->and($this->svc->isBlocked($this->a, $this->b))->toBeFalse();
});

it('still finds a real accepted interest or block between the two, either way round', function () {
    DB::table('interests')->insert(['sender_profile_id' => 2, 'receiver_profile_id' => 1, 'status' => 'accepted']);
    DB::table('blocked_profiles')->insert(['profile_id' => 1, 'blocked_profile_id' => 2]);

    expect($this->svc->hasAcceptedInterest($this->a, $this->b))->toBeTrue()
        ->and($this->svc->hasAcceptedInterest($this->b, $this->a))->toBeTrue()
        ->and($this->svc->hasAnyInterest($this->a, $this->b))->toBeTrue()
        ->and($this->svc->isBlocked($this->b, $this->a))->toBeTrue();
});

it('builds (from = a AND to = b) OR (from = b AND to = a)', function () {
    $sql = ProfileAccessService::eitherWay(DB::table('interests'), 'sender_profile_id', 'receiver_profile_id', 1, 2)->toRawSql();

    expect(preg_replace('/[`"]/', '', $sql))->toContain('(sender_profile_id = 1 and receiver_profile_id = 2) or (sender_profile_id = 2 and receiver_profile_id = 1)');
});

it('has no ->orWhere([...]) left in the app (it ORs the pairs inside)', function () {
    $hits = collect(\Symfony\Component\Finder\Finder::create()->in(app_path())->name('*.php')->files())
        ->filter(fn ($f) => preg_match('/->orWhere\(\s*\[/', $f->getContents()))
        ->map(fn ($f) => $f->getRelativePathname())->values()->all();

    expect($hits)->toBe([]);
});

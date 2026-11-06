<?php

use App\Support\Scorecard;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Scorecard: "Details checked on calls"
|--------------------------------------------------------------------------
| The member edit page's "Save and mark checked on call" (6 Nov 2026) logs
| member_details_checked. The owner's Scorecard counts the members checked
| each week, each member once, so a second press the same week adds nothing.
*/

beforeEach(function () {
    Schema::dropIfExists('admin_activity_log');
    Schema::create('admin_activity_log', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('admin_user_id')->nullable();
        $t->string('action');
        $t->string('model_type')->nullable();
        $t->unsignedBigInteger('model_id')->nullable();
        $t->json('changes')->nullable();
        $t->string('ip_address')->nullable();
        $t->timestamps();
    });
});

afterEach(function () {
    Schema::dropIfExists('admin_activity_log');
});

function scorecardCheck(int $profileId, string $when, string $action = 'member_details_checked'): void
{
    DB::table('admin_activity_log')->insert([
        'admin_user_id' => 1, 'action' => $action, 'model_type' => 'Profile', 'model_id' => $profileId,
        'created_at' => now()->modify($when), 'updated_at' => now()->modify($when),
    ]);
}

it('counts the members whose details were checked on a call, each member once a week', function () {
    scorecardCheck(5, '-2 days');
    scorecardCheck(5, '-1 day');     // the same member again this week
    scorecardCheck(6, '-3 days');
    scorecardCheck(7, '-9 days');    // last week
    scorecardCheck(8, '-1 day', 'member_profile_updated'); // an edit, not a check

    $row = collect(Scorecard::rows())->firstWhere('key', 'details_checked');

    expect($row['label'])->toBe('Details checked on calls')
        ->and($row['this'])->toBe(2)
        ->and($row['last'])->toBe(1)
        ->and($row['money'])->toBeFalse();
});

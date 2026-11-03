<?php

use App\Support\Scorecard;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Scorecard: "Answered from the email"
|--------------------------------------------------------------------------
| Members have been able to accept or decline straight from the interest
| email since 26 Oct 2026, but nothing recorded it, so the weekly review
| could not say whether anyone used it (2 Nov 2026). The share counts
| answers only — interest_replies also holds the chat messages.
*/

beforeEach(function () {
    Schema::dropIfExists('interest_replies');
    Schema::create('interest_replies', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('interest_id');
        $t->unsignedBigInteger('replier_profile_id')->nullable();
        $t->string('reply_type');
        $t->string('answered_via', 20)->nullable();
        $t->timestamps();
    });
});

afterEach(function () {
    Schema::dropIfExists('interest_replies');
});

function scorecardReply(string $type, ?string $via, string $when = '-2 days'): void
{
    DB::table('interest_replies')->insert([
        'interest_id' => 1, 'replier_profile_id' => 1, 'reply_type' => $type, 'answered_via' => $via,
        'created_at' => now()->modify($when), 'updated_at' => now()->modify($when),
    ]);
}

/** The row the owner reads on admin → Insights → Scorecard. */
function answeredFromEmailRow(): ?array
{
    return collect(Scorecard::rows())->firstWhere('key', 'answered_by_email');
}

it('shows the share of answers that came from the email', function () {
    scorecardReply('accept', 'email');
    scorecardReply('decline', null);
    scorecardReply('decline', null);
    scorecardReply('accept', null);

    expect(answeredFromEmailRow()['this'])->toBe(25);
});

it('ignores chat messages, which share the same table', function () {
    scorecardReply('accept', 'email');
    scorecardReply('message', null);
    scorecardReply('message', null);
    scorecardReply('message', null);

    // one answer, and it came from the email
    expect(answeredFromEmailRow()['this'])->toBe(100);
});

it('counts last week separately', function () {
    scorecardReply('accept', 'email', '-2 days');
    scorecardReply('accept', null, '-2 days');
    scorecardReply('decline', null, '-10 days');
    scorecardReply('decline', null, '-10 days');

    $row = answeredFromEmailRow();
    expect($row['this'])->toBe(50)
        ->and($row['last'])->toBe(0);
});

it('reads nothing rather than failing when no answer has been recorded', function () {
    scorecardReply('message', null);   // chat only, no answers at all

    expect(answeredFromEmailRow()['this'])->toBeNull();
});

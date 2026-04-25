<?php

use App\Http\Controllers\Api\V1\InterestController;
use App\Models\Interest;
use App\Models\Profile;
use App\Models\User;
use App\Services\InterestService;
use App\Services\NotificationService;
use App\Services\ProfileAccessService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| InterestController — 9 endpoints
|--------------------------------------------------------------------------
| Stubs InterestService via a recording fake (FakeInterestService) so
| tests cover controller-layer dispatch, ownership guards, and 24h cancel
| window logic without invoking the real service's DB queries.
|
| Inline `interests` table (via Schema::create) lets star/trash tests
| exercise real $interest->update() calls. Pre-sets senderProfile /
| receiverProfile / replies relations on each Interest fetched from DB
| so loadMissing() in the controller is a no-op and we don't need
| profiles/users/interest_replies inline.
|
| End-to-end behaviour (block check, daily-limit, premium-or-flag rule)
| is covered by Bruno smoke in week-04 step-15 against MySQL.
|
| Reference: docs/mobile-app/reference/ui-safe-api-checklist.md
*/

/** Recording InterestService — captures calls + returns canned responses. */
class FakeInterestService extends InterestService
{
    /** @var array<int, array{method: string, args: array}> */
    public array $calls = [];

    /** Set this in tests to make the next service call throw. */
    public ?\Throwable $nextThrowable = null;

    public function __construct() {}  // skip parent constructor

    public function send(Profile $sender, Profile $receiver, ?string $templateId, ?string $customMessage): Interest
    {
        $this->record('send', compact('templateId', 'customMessage') + [
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
        ]);

        if ($this->nextThrowable) {
            $t = $this->nextThrowable; $this->nextThrowable = null; throw $t;
        }

        // Return an in-memory Interest the controller can render.
        $i = new Interest();
        $i->exists = true;
        $i->forceFill([
            'id' => 9001,
            'sender_profile_id' => $sender->id,
            'receiver_profile_id' => $receiver->id,
            'status' => 'pending',
            'template_id' => $templateId,
            'custom_message' => $customMessage,
            'is_starred_by_sender' => false,
            'is_starred_by_receiver' => false,
            'is_trashed_by_sender' => false,
            'is_trashed_by_receiver' => false,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
        $i->setRelation('senderProfile', null);
        $i->setRelation('receiverProfile', null);
        $i->setRelation('replies', new \Illuminate\Database\Eloquent\Collection());

        return $i;
    }

    public function accept(Interest $interest, ?string $templateId, ?string $customMessage): \App\Models\InterestReply
    {
        $this->record('accept', ['interest_id' => $interest->id, 'template_id' => $templateId]);
        if ($this->nextThrowable) { $t = $this->nextThrowable; $this->nextThrowable = null; throw $t; }
        $interest->forceFill(['status' => 'accepted'])->syncOriginalAttribute('status');
        $reply = new \App\Models\InterestReply();
        $reply->forceFill(['id' => 1, 'interest_id' => $interest->id, 'reply_type' => 'accept']);
        return $reply;
    }

    public function decline(Interest $interest, ?string $templateId, ?string $customMessage, bool $silent = false): \App\Models\InterestReply
    {
        $this->record('decline', ['interest_id' => $interest->id, 'silent' => $silent]);
        if ($this->nextThrowable) { $t = $this->nextThrowable; $this->nextThrowable = null; throw $t; }
        $interest->forceFill(['status' => 'declined'])->syncOriginalAttribute('status');
        $reply = new \App\Models\InterestReply();
        $reply->forceFill(['id' => 2, 'interest_id' => $interest->id, 'reply_type' => 'decline']);
        return $reply;
    }

    public function cancel(Interest $interest): void
    {
        $this->record('cancel', ['interest_id' => $interest->id]);
        if ($this->nextThrowable) { $t = $this->nextThrowable; $this->nextThrowable = null; throw $t; }
        $interest->forceFill(['status' => 'cancelled'])->syncOriginalAttribute('status');
    }

    public function sendMessage(Interest $interest, Profile $sender, string $message): \App\Models\InterestReply
    {
        $this->record('sendMessage', ['interest_id' => $interest->id, 'message' => $message]);
        if ($this->nextThrowable) { $t = $this->nextThrowable; $this->nextThrowable = null; throw $t; }
        $reply = new \App\Models\InterestReply();
        $reply->forceFill(['id' => 99, 'interest_id' => $interest->id, 'reply_type' => 'message', 'custom_message' => $message]);
        return $reply;
    }

    private function record(string $method, array $args): void
    {
        $this->calls[] = ['method' => $method, 'args' => $args];
    }
}

/** Inline interests table — minimal columns, no FKs (defensive for SQLite). */
function createInterestsTable(): void
{
    if (Schema::hasTable('interests')) {
        return;
    }
    Schema::create('interests', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('sender_profile_id');
        $t->unsignedBigInteger('receiver_profile_id');
        $t->string('status')->default('pending');
        $t->string('template_id')->nullable();
        $t->text('custom_message')->nullable();
        $t->boolean('is_starred_by_sender')->default(false);
        $t->boolean('is_starred_by_receiver')->default(false);
        $t->boolean('is_trashed_by_sender')->default(false);
        $t->boolean('is_trashed_by_receiver')->default(false);
        $t->timestamp('cancelled_at')->nullable();
        $t->timestamps();
    });
}

/** Persist an Interest row + return the model with relations pre-set. */
function persistInterest(array $overrides = []): Interest
{
    // created_at isn't in Interest's fillable — extract it before
    // Interest::create so we can apply it via raw DB update afterwards
    // (Laravel's auto-timestamps would otherwise overwrite it with now()).
    $createdAt = $overrides['created_at'] ?? null;
    unset($overrides['created_at']);

    $i = Interest::create(array_merge([
        'sender_profile_id' => 100,
        'receiver_profile_id' => 200,
        'status' => 'pending',
        'is_starred_by_sender' => false,
        'is_starred_by_receiver' => false,
        'is_trashed_by_sender' => false,
        'is_trashed_by_receiver' => false,
    ], $overrides));

    if ($createdAt) {
        \Illuminate\Support\Facades\DB::table('interests')
            ->where('id', $i->id)
            ->update(['created_at' => $createdAt]);
        $i = Interest::find($i->id);  // refresh with the new timestamp
    }

    // Pre-set relations so controller's loadMissing is a no-op (no DB hits
    // on related tables we haven't created).
    $i->setRelation('senderProfile', null);
    $i->setRelation('receiverProfile', null);
    $i->setRelation('replies', new \Illuminate\Database\Eloquent\Collection());

    return $i;
}

/** Build an authenticated User+Profile pair (or no-profile variant). */
function buildInterestUser(int $id, bool $withProfile = true): User
{
    $u = new User();
    $u->exists = true;
    $u->forceFill(['id' => $id, 'email' => "i{$id}@e.com", 'is_active' => true]);
    $u->setRelation('userMemberships', new \Illuminate\Database\Eloquent\Collection());

    if ($withProfile) {
        $p = new Profile();
        $p->exists = true;
        $p->forceFill([
            'id' => $id,
            'user_id' => $id,
            'matri_id' => 'AM'.str_pad((string) $id, 6, '0', STR_PAD_LEFT),
            'gender' => 'male',
            'is_active' => true,
            'is_approved' => true,
        ]);
        $p->setRelation('user', $u);
        $u->setRelation('profile', $p);
    } else {
        $u->setRelation('profile', null);
    }

    return $u;
}

/**
 * Build an InterestController with the Fake service injected, plus an
 * optional pre-stubbed target profile for the send endpoint.
 */
function buildInterestController(?FakeInterestService $fake = null, ?Profile $stubTarget = null): InterestController
{
    $fake ??= new FakeInterestService();

    return new class($fake, app(ProfileAccessService::class), $stubTarget) extends InterestController {
        public function __construct(
            FakeInterestService $interests,
            ProfileAccessService $access,
            private ?Profile $stubbedTarget,
        ) {
            parent::__construct($interests, $access);
        }

        protected function findTargetByMatriId(string $matriId): ?Profile
        {
            return $this->stubbedTarget && $this->stubbedTarget->matri_id === $matriId
                ? $this->stubbedTarget
                : null;
        }
    };
}

function interestRequest(User $user, string $method = 'GET', array $body = [], string $path = '/api/v1/interests'): Request
{
    $r = Request::create($path, $method, $body);
    $r->setUserResolver(fn () => $user);

    return $r;
}

beforeEach(function () {
    createInterestsTable();
});

afterEach(function () {
    Schema::dropIfExists('interests');
});

/* ==================================================================
 |  /interests (index)
 | ================================================================== */

it('index returns 422 PROFILE_REQUIRED when viewer has no profile', function () {
    $user = buildInterestUser(100, withProfile: false);

    $response = buildInterestController()->index(interestRequest($user));

    expect($response->getStatusCode())->toBe(422);
    expect($response->getData(true)['error']['code'])->toBe('PROFILE_REQUIRED');
});

it('index returns envelope with data array + meta block', function () {
    $user = buildInterestUser(100);
    $controller = buildInterestController();

    $response = $controller->index(interestRequest($user, body: ['tab' => 'received']));
    $body = $response->getData(true);

    expect($response->getStatusCode())->toBe(200);
    expect($body)->toHaveKeys(['data', 'meta']);
    expect($body['meta'])->toHaveKeys(['page', 'per_page', 'total', 'last_page', 'tab']);
    expect($body['meta']['tab'])->toBe('received');
});

/* ==================================================================
 |  /interests/{interest} (show)
 | ================================================================== */

it('show returns 403 when viewer is neither sender nor receiver', function () {
    $stranger = buildInterestUser(999);
    $interest = persistInterest(['sender_profile_id' => 100, 'receiver_profile_id' => 200]);

    $response = buildInterestController()->show(interestRequest($stranger), $interest);

    expect($response->getStatusCode())->toBe(403);
    expect($response->getData(true)['error']['code'])->toBe('UNAUTHORIZED');
});

it('show returns toCard shape for the receiving party', function () {
    $receiver = buildInterestUser(200);
    $interest = persistInterest(['sender_profile_id' => 100, 'receiver_profile_id' => 200, 'status' => 'pending']);

    $response = buildInterestController()->show(interestRequest($receiver), $interest);
    $data = $response->getData(true)['data'];

    expect($response->getStatusCode())->toBe(200);
    expect($data)->toHaveKeys(['id', 'direction', 'status', 'is_starred', 'is_trashed', 'can_cancel', 'can_act', 'replies', 'created_at']);
    expect($data['direction'])->toBe('received');
    expect($data['can_act'])->toBeTrue();      // receiver + pending → can accept/decline
    expect($data['can_cancel'])->toBeFalse();  // not the sender
});

/* ==================================================================
 |  /profiles/{matriId}/interest (send)
 | ================================================================== */

it('send returns 422 PROFILE_REQUIRED when viewer has no profile', function () {
    $user = buildInterestUser(100, withProfile: false);
    $response = buildInterestController()->send(
        interestRequest($user, 'POST', path: '/api/v1/profiles/AM200000/interest'),
        'AM200000',
    );
    expect($response->getStatusCode())->toBe(422);
});

it('send returns 404 when target matri_id does not exist', function () {
    $sender = buildInterestUser(100);
    // No stub target → findTargetByMatriId returns null
    $response = buildInterestController(stubTarget: null)->send(
        interestRequest($sender, 'POST', path: '/api/v1/profiles/AM999999/interest'),
        'AM999999',
    );
    expect($response->getStatusCode())->toBe(404);
});

it('send happy path delegates to InterestService::send + returns interest card', function () {
    $sender = buildInterestUser(100);
    $target = buildInterestUser(200)->profile;
    $fake = new FakeInterestService();
    $controller = buildInterestController($fake, $target);

    $response = $controller->send(
        interestRequest($sender, 'POST', body: ['custom_message' => 'Hi'], path: '/api/v1/profiles/AM000200/interest'),
        'AM000200',
    );

    expect($response->getStatusCode())->toBe(201);
    expect($fake->calls[0]['method'])->toBe('send');
    expect($fake->calls[0]['args']['customMessage'])->toBe('Hi');
});

it('send maps service exception to 422 INVALID_INTEREST with message', function () {
    $sender = buildInterestUser(100);
    $target = buildInterestUser(200)->profile;
    $fake = new FakeInterestService();
    $fake->nextThrowable = new \RuntimeException('Daily interest limit reached.');
    $controller = buildInterestController($fake, $target);

    $response = $controller->send(
        interestRequest($sender, 'POST', path: '/api/v1/profiles/AM000200/interest'),
        'AM000200',
    );

    expect($response->getStatusCode())->toBe(422);
    expect($response->getData(true)['error']['code'])->toBe('INVALID_INTEREST');
    expect($response->getData(true)['error']['message'])->toBe('Daily interest limit reached.');
});

/* ==================================================================
 |  accept
 | ================================================================== */

it('accept returns 403 when viewer is not the receiver', function () {
    $stranger = buildInterestUser(999);
    $interest = persistInterest(['sender_profile_id' => 100, 'receiver_profile_id' => 200]);

    $response = buildInterestController()->accept(interestRequest($stranger, 'POST'), $interest);

    expect($response->getStatusCode())->toBe(403);
});

it('accept returns 422 when interest status is not pending', function () {
    $receiver = buildInterestUser(200);
    $interest = persistInterest([
        'sender_profile_id' => 100,
        'receiver_profile_id' => 200,
        'status' => 'accepted',  // already accepted
    ]);

    $response = buildInterestController()->accept(interestRequest($receiver, 'POST'), $interest);

    expect($response->getStatusCode())->toBe(422);
    expect($response->getData(true)['error']['code'])->toBe('INVALID_INTEREST');
});

it('accept happy path delegates to service.accept', function () {
    $receiver = buildInterestUser(200);
    $interest = persistInterest(['sender_profile_id' => 100, 'receiver_profile_id' => 200]);
    $fake = new FakeInterestService();

    $response = buildInterestController($fake)->accept(interestRequest($receiver, 'POST'), $interest);

    expect($response->getStatusCode())->toBe(200);
    expect($fake->calls[0]['method'])->toBe('accept');
});

/* ==================================================================
 |  decline
 | ================================================================== */

it('decline returns 403 when viewer is not the receiver', function () {
    $stranger = buildInterestUser(999);
    $interest = persistInterest(['sender_profile_id' => 100, 'receiver_profile_id' => 200]);

    $response = buildInterestController()->decline(interestRequest($stranger, 'POST'), $interest);

    expect($response->getStatusCode())->toBe(403);
});

it('decline happy path passes silent flag through to the service', function () {
    $receiver = buildInterestUser(200);
    $interest = persistInterest(['sender_profile_id' => 100, 'receiver_profile_id' => 200]);
    $fake = new FakeInterestService();

    $response = buildInterestController($fake)->decline(
        interestRequest($receiver, 'POST', body: ['silent' => true]),
        $interest,
    );

    expect($response->getStatusCode())->toBe(200);
    expect($fake->calls[0]['method'])->toBe('decline');
    expect($fake->calls[0]['args']['silent'])->toBeTrue();
});

/* ==================================================================
 |  cancel (24h window)
 | ================================================================== */

it('cancel returns 403 when viewer is not the sender', function () {
    $receiver = buildInterestUser(200);
    $interest = persistInterest(['sender_profile_id' => 100, 'receiver_profile_id' => 200]);

    $response = buildInterestController()->cancel(interestRequest($receiver, 'POST'), $interest);

    expect($response->getStatusCode())->toBe(403);
});

it('cancel returns 422 CANCEL_WINDOW_EXPIRED when older than the window', function () {
    $sender = buildInterestUser(100);
    // Created 25 hours ago — outside the 24h window.
    $interest = persistInterest([
        'sender_profile_id' => 100,
        'receiver_profile_id' => 200,
        'created_at' => Carbon::now()->subHours(25),
    ]);

    $response = buildInterestController()->cancel(interestRequest($sender, 'POST'), $interest);

    expect($response->getStatusCode())->toBe(422);
    expect($response->getData(true)['error']['code'])->toBe('CANCEL_WINDOW_EXPIRED');
});

it('cancel happy path within window delegates to service.cancel', function () {
    $sender = buildInterestUser(100);
    $interest = persistInterest([
        'sender_profile_id' => 100,
        'receiver_profile_id' => 200,
        'created_at' => Carbon::now()->subHours(2),  // within window
    ]);
    $fake = new FakeInterestService();

    $response = buildInterestController($fake)->cancel(interestRequest($sender, 'POST'), $interest);

    expect($response->getStatusCode())->toBe(200);
    expect($fake->calls[0]['method'])->toBe('cancel');
});

/* ==================================================================
 |  star + trash
 | ================================================================== */

it('star toggles is_starred_by_sender when viewer is the sender', function () {
    $sender = buildInterestUser(100);
    $interest = persistInterest([
        'sender_profile_id' => 100,
        'receiver_profile_id' => 200,
        'is_starred_by_sender' => false,
    ]);

    $response = buildInterestController()->star(interestRequest($sender, 'POST'), $interest);

    expect($response->getStatusCode())->toBe(200);
    expect($response->getData(true)['data']['is_starred'])->toBeTrue();
    expect($interest->fresh()->is_starred_by_sender)->toBeTrue();
});

it('star toggles is_starred_by_receiver when viewer is the receiver', function () {
    $receiver = buildInterestUser(200);
    $interest = persistInterest([
        'sender_profile_id' => 100,
        'receiver_profile_id' => 200,
        'is_starred_by_receiver' => false,
    ]);

    buildInterestController()->star(interestRequest($receiver, 'POST'), $interest);

    expect($interest->fresh()->is_starred_by_receiver)->toBeTrue();
    // Sender flag must remain untouched.
    expect($interest->fresh()->is_starred_by_sender)->toBeFalse();
});

it('star returns 403 when viewer is neither party', function () {
    $stranger = buildInterestUser(999);
    $interest = persistInterest(['sender_profile_id' => 100, 'receiver_profile_id' => 200]);

    $response = buildInterestController()->star(interestRequest($stranger, 'POST'), $interest);

    expect($response->getStatusCode())->toBe(403);
});

it('trash toggles the correct column based on viewer role', function () {
    $sender = buildInterestUser(100);
    $interest = persistInterest(['sender_profile_id' => 100, 'receiver_profile_id' => 200]);

    buildInterestController()->trash(interestRequest($sender, 'POST'), $interest);

    expect($interest->fresh()->is_trashed_by_sender)->toBeTrue();
    expect($interest->fresh()->is_trashed_by_receiver)->toBeFalse();
});

/* ==================================================================
 |  reply (chat)
 | ================================================================== */

it('reply returns 403 when viewer is neither party', function () {
    $stranger = buildInterestUser(999);
    $interest = persistInterest(['sender_profile_id' => 100, 'receiver_profile_id' => 200]);

    $response = buildInterestController()->reply(
        interestRequest($stranger, 'POST', body: ['message' => 'hi']),
        $interest,
    );

    expect($response->getStatusCode())->toBe(403);
});

it('reply happy path delegates to service.sendMessage', function () {
    $sender = buildInterestUser(100);
    $interest = persistInterest([
        'sender_profile_id' => 100,
        'receiver_profile_id' => 200,
        'status' => 'accepted',
    ]);
    $fake = new FakeInterestService();

    $response = buildInterestController($fake)->reply(
        interestRequest($sender, 'POST', body: ['message' => 'Hello there']),
        $interest,
    );

    expect($response->getStatusCode())->toBe(201);
    expect($fake->calls[0]['method'])->toBe('sendMessage');
    expect($fake->calls[0]['args']['message'])->toBe('Hello there');
});

it('reply maps service "Upgrade to a paid plan" exception to 422', function () {
    $sender = buildInterestUser(100);
    $interest = persistInterest([
        'sender_profile_id' => 100,
        'receiver_profile_id' => 200,
        'status' => 'accepted',
    ]);
    $fake = new FakeInterestService();
    $fake->nextThrowable = new \RuntimeException('Upgrade to a paid plan to send messages.');

    $response = buildInterestController($fake)->reply(
        interestRequest($sender, 'POST', body: ['message' => 'hi']),
        $interest,
    );

    expect($response->getStatusCode())->toBe(422);
    expect($response->getData(true)['error']['code'])->toBe('INVALID_INTEREST');
    expect($response->getData(true)['error']['message'])->toContain('Upgrade');
});

/* ==================================================================
 |  Constants + cancel window
 | ================================================================== */

it('exposes DEFAULT_PER_PAGE=20, MAX_PER_PAGE=50, cancelWindowHours=24', function () {
    $controller = app(InterestController::class);

    expect(InterestController::DEFAULT_PER_PAGE)->toBe(20);
    expect(InterestController::MAX_PER_PAGE)->toBe(50);
    expect($controller->cancelWindowHours())->toBe(24);
});

<?php

use App\Http\Controllers\InterestController;
use App\Models\Interest;
use App\Models\Profile;
use App\Models\User;
use App\Services\InterestService;
use App\Support\Analytics;
use App\Support\InterestStatusMap;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
| Send Interest was the 5th button in the profile panel, usually below the
| fold (owner's question, 7 Oct 2026). Now: right under the name, a fixed
| bar on phones, and a button on every profile card that sends in place.
| Shortlist is a star; the heart means interest.
*/

class EasyFindFakeInterestService extends InterestService
{
    public array $sent = [];
    public ?\Throwable $throw = null;

    public function __construct() {}

    public function send(Profile $sender, Profile $receiver, ?string $templateId, ?string $customMessage): Interest
    {
        if ($this->throw) {
            throw $this->throw;
        }
        $this->sent[] = [$sender->id, $receiver->id, $templateId];

        return new Interest();
    }
}

function easyFindRequest(array $data, bool $json): Request
{
    $request = Request::create('/interests/send/7', 'POST', $data);
    if ($json) {
        $request->headers->set('Accept', 'application/json');
    }
    $request->setLaravelSession(app('session.store'));
    app()->instance('request', $request);

    return $request;
}

function easyFindSignIn(): void
{
    $user = new User(['name' => 'Viewer']);
    $user->id = 5;
    $profile = new Profile(['gender' => 'male']);
    $profile->id = 50;
    $user->setRelation('profile', $profile);
    Auth::setUser($user);
}

it('sends from a card in place and answers in JSON, recording where it came from', function () {
    easyFindSignIn();
    $service = new EasyFindFakeInterestService();
    $receiver = new Profile(['gender' => 'female']);
    $receiver->id = 7;

    $response = (new InterestController($service))->send(easyFindRequest(['template_id' => 'msg_suitable', 'source' => 'card'], true), $receiver);

    expect($response->getStatusCode())->toBe(200)
        ->and($response->getData(true))->toBe(['ok' => true, 'message' => 'Interest sent'])
        ->and($service->sent)->toBe([[50, 7, 'msg_suitable']])
        ->and(Analytics::pull()[0]['params'])->toBe(['source' => 'card']);
});

it('explains a refused card send (e.g. the daily limit) without leaving the page', function () {
    easyFindSignIn();
    $service = new EasyFindFakeInterestService();
    $service->throw = new RuntimeException('You have reached your daily interest limit.');

    $response = (new InterestController($service))->send(easyFindRequest(['source' => 'card'], true), new Profile());

    expect($response->getStatusCode())->toBe(422)
        ->and($response->getData(true)['message'])->toBe('You have reached your daily interest limit.');
});

it('still redirects back for the ordinary profile-page form', function () {
    easyFindSignIn();
    $service = new EasyFindFakeInterestService();

    $response = (new InterestController($service))->send(easyFindRequest(['template_id' => 'msg_suitable'], false), new Profile());

    expect($response->isRedirect())->toBeTrue()
        ->and(Analytics::pull()[0]['params'])->toBe(['source' => 'profile']);
});

it('knows where the member stands with every profile on a page, in one query', function () {
    Schema::create('interests', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('sender_profile_id');
        $t->unsignedBigInteger('receiver_profile_id');
        $t->string('status');
        $t->timestamps();
    });
    DB::table('interests')->insert([
        ['sender_profile_id' => 50, 'receiver_profile_id' => 7, 'status' => 'pending'],   // I sent
        ['sender_profile_id' => 8, 'receiver_profile_id' => 50, 'status' => 'pending'],   // they sent
        ['sender_profile_id' => 9, 'receiver_profile_id' => 50, 'status' => 'accepted'],
        ['sender_profile_id' => 11, 'receiver_profile_id' => 50, 'status' => 'pending'],  // both ways:
        ['sender_profile_id' => 50, 'receiver_profile_id' => 11, 'status' => 'pending'],  // my sent wins
        ['sender_profile_id' => 50, 'receiver_profile_id' => 12, 'status' => 'expired'],  // can send again
        ['sender_profile_id' => 60, 'receiver_profile_id' => 61, 'status' => 'pending'],  // not mine
    ]);
    $viewer = new Profile();
    $viewer->id = 50;

    DB::enableQueryLog();
    $map = InterestStatusMap::for($viewer);
    InterestStatusMap::for($viewer);

    expect(count(DB::getQueryLog()))->toBe(1)
        ->and(array_keys($map))->toEqualCanonicalizing([7, 8, 9, 11])
        ->and($map[7]['direction'])->toBe('sent')
        ->and($map[8])->toMatchArray(['direction' => 'received', 'status' => 'pending'])
        ->and($map[9]['status'])->toBe('accepted')
        ->and($map[11]['direction'])->toBe('sent');

    Schema::dropIfExists('interests');
});

it('puts Send Interest under the name, keeps a phone bar, and uses a star for shortlist', function () {
    $page = file_get_contents(resource_path('views/profile/preview.blade.php'));
    $card = file_get_contents(resource_path('views/components/profile-card.blade.php'));
    $star = 'M11.48 3.499a.562.562 0 011.04 0';
    $heart = 'M21 8.25c0-2.485-2.099-4.5-4.688-4.5';

    // under the name, before the verification ticks and Print
    expect(strpos($page, 'id="profile-actions"'))->toBeGreaterThan(strpos($page, '$profile->matri_id }}</p>'))
        ->toBeLessThan(strpos($page, 'Mobile Number Verified'))
        ->and(substr_count($page, '<x-send-interest-modal'))->toBe(1)
        ->and($page)->toContain("\$dispatch('open-interest-modal', { source: 'mobile_bar' })")
        ->toContain('@media (min-width: 1024px) { .pv-action-bar, .pv-action-bar-spacer { display: none; }');

    // card: interest sent in place; shortlist is a star, the heart is interest
    expect($card)->toContain("JSON.stringify({ template_id: 'msg_suitable', source: 'card' })")
        ->toContain("'Accept': 'application/json'")
        ->and(substr($card, strpos($card, 'Shortlist star'), 1200))->toContain($star)->not->toContain($heart)
        ->and($page)->not->toContain('border-pink-300 text-pink-600');
    expect(file_get_contents(resource_path('views/components/send-interest-modal.blade.php')))->toContain($heart)
        ->toContain('x-on:open-interest-modal.window');
});

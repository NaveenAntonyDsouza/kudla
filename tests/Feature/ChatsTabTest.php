<?php

use App\Models\Interest;
use App\Models\Profile;
use App\Support\Chats;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
| Chats tab (owner's choice, 7 Oct 2026): a chat still opens only once an
| interest is accepted, but conversations get their own list and menu item
| with unread counts; Interests keeps the requests.
*/

beforeEach(function () {
    Schema::create('profiles', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('user_id')->nullable();
        $t->string('matri_id')->nullable();
        $t->softDeletes();
        $t->timestamps();
    });
    // the Chats list also loads each member's contact (WhatsApp shortcut)
    Schema::create('users', function (Blueprint $t) {
        $t->id();
        $t->string('name')->nullable();
        $t->string('phone')->nullable();
        $t->timestamps();
    });
    Schema::create('contact_info', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('profile_id');
        $t->string('primary_phone')->nullable();
        $t->string('whatsapp_number')->nullable();
        $t->timestamps();
    });
    Schema::create('profile_photos', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('profile_id');
        $t->boolean('is_primary')->default(false);
        $t->boolean('is_visible')->default(true);
        $t->string('approval_status')->default('approved');
        $t->timestamps();
    });
    Schema::create('interests', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('sender_profile_id');
        $t->unsignedBigInteger('receiver_profile_id');
        $t->string('template_id')->nullable();
        $t->text('custom_message')->nullable();
        $t->string('source', 20)->nullable();
        $t->string('status')->default('pending');
        $t->boolean('is_starred_by_sender')->default(false);
        $t->boolean('is_starred_by_receiver')->default(false);
        $t->boolean('is_trashed_by_sender')->default(false);
        $t->boolean('is_trashed_by_receiver')->default(false);
        $t->timestamp('cancelled_at')->nullable();
        $t->timestamp('sender_last_read_at')->nullable();
        $t->timestamp('receiver_last_read_at')->nullable();
        $t->timestamps();
    });
    Schema::create('interest_replies', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('interest_id');
        $t->unsignedBigInteger('replier_profile_id');
        $t->string('reply_type');
        $t->string('template_id')->nullable();
        $t->text('custom_message')->nullable();
        $t->boolean('is_silent_decline')->default(false);
        $t->timestamps();
    });

    foreach (range(1, 8) as $id) {
        DB::table('profiles')->insert(['id' => $id, 'matri_id' => 'KM10000'.$id]);
    }
    $at = fn (string $hm) => '2026-10-07 '.$hm.':00';
    DB::table('interests')->insert([
        // A: I (1) sent to 2, accepted; I last read at 10:30
        ['id' => 1, 'sender_profile_id' => 1, 'receiver_profile_id' => 2, 'status' => 'accepted', 'sender_last_read_at' => $at('10:30'), 'receiver_last_read_at' => null, 'is_trashed_by_receiver' => false, 'updated_at' => $at('09:00')],
        // B: 3 sent to me, I accepted; I've read everything
        ['id' => 2, 'sender_profile_id' => 3, 'receiver_profile_id' => 1, 'status' => 'accepted', 'sender_last_read_at' => null, 'receiver_last_read_at' => $at('12:00'), 'is_trashed_by_receiver' => false, 'updated_at' => $at('09:00')],
        // pending request from 4: an Interest, not a Chat
        ['id' => 3, 'sender_profile_id' => 4, 'receiver_profile_id' => 1, 'status' => 'pending', 'sender_last_read_at' => null, 'receiver_last_read_at' => null, 'is_trashed_by_receiver' => false, 'updated_at' => $at('09:00')],
        // accepted but I moved it to trash
        ['id' => 4, 'sender_profile_id' => 5, 'receiver_profile_id' => 1, 'status' => 'accepted', 'sender_last_read_at' => null, 'receiver_last_read_at' => null, 'is_trashed_by_receiver' => true, 'updated_at' => $at('09:00')],
        // someone else's chat
        ['id' => 5, 'sender_profile_id' => 6, 'receiver_profile_id' => 7, 'status' => 'accepted', 'sender_last_read_at' => null, 'receiver_last_read_at' => null, 'is_trashed_by_receiver' => false, 'updated_at' => $at('09:00')],
    ]);
    DB::table('interest_replies')->insert([
        ['interest_id' => 1, 'replier_profile_id' => 2, 'reply_type' => 'accept', 'template_id' => 'reply_accept_1', 'custom_message' => null, 'created_at' => $at('10:00')],
        ['interest_id' => 1, 'replier_profile_id' => 1, 'reply_type' => 'message', 'template_id' => null, 'custom_message' => 'Hello, thank you!', 'created_at' => $at('10:20')],
        ['interest_id' => 1, 'replier_profile_id' => 2, 'reply_type' => 'message', 'template_id' => null, 'custom_message' => 'Shall our parents   talk on Sunday?', 'created_at' => $at('11:00')],
        ['interest_id' => 2, 'replier_profile_id' => 1, 'reply_type' => 'accept', 'template_id' => 'reply_accept_2', 'custom_message' => null, 'created_at' => $at('11:30')],
        ['interest_id' => 4, 'replier_profile_id' => 5, 'reply_type' => 'message', 'template_id' => null, 'custom_message' => 'hi', 'created_at' => $at('11:40')],
        ['interest_id' => 5, 'replier_profile_id' => 6, 'reply_type' => 'message', 'template_id' => null, 'custom_message' => 'hi', 'created_at' => $at('11:40')],
    ]);
});

afterEach(function () {
    foreach (['interest_replies', 'interests', 'profile_photos', 'contact_info', 'users', 'profiles'] as $table) {
        Schema::dropIfExists($table);
    }
});

function chatsMe(): Profile
{
    return Profile::find(1);
}

function chatsFreshRequest(): void
{
    app()->instance('request', Request::create('/chats'));
}

it('lists only accepted, untrashed conversations, newest activity first, with unread counts', function () {
    $chats = Chats::forProfile(chatsMe());

    expect($chats->pluck('id')->all())->toBe([2, 1])             // B's accept 11:30 is newer than A's 11:00
        ->and($chats->firstWhere('id', 1)->unread_count)->toEqual(1)  // the 11:00 message, after I read at 10:30
        ->and($chats->firstWhere('id', 2)->unread_count)->toEqual(0); // my own accept doesn't count
});

it('counts conversations with something new for the menu, and interests waiting for a reply', function () {
    chatsFreshRequest();

    expect(Chats::unreadCount(chatsMe()))->toBe(1)
        ->and(Chats::pendingInterestsCount(chatsMe()))->toBe(1);
});

it('marks a chat read when the member opens it, without touching its date', function () {
    $chat = Interest::find(1);

    $chat->markReadBy(chatsMe());

    chatsFreshRequest();
    expect(Chats::unreadCount(chatsMe()))->toBe(0)
        ->and(DB::table('interests')->where('id', 1)->value('updated_at'))->toBe('2026-10-07 09:00:00')
        ->and(DB::table('interests')->where('id', 1)->value('receiver_last_read_at'))->toBeNull();
});

it('previews the last thing said, marking my own lines', function () {
    $chats = Chats::forProfile(chatsMe());

    expect(Chats::preview($chats->firstWhere('id', 1), chatsMe()))->toBe('Shall our parents talk on Sunday?')
        ->and(Chats::preview($chats->firstWhere('id', 2), chatsMe()))->toBe('You: I am happy to accept your interest. Please feel free to contact me.');
});

it('never breaks a page when the tables are missing', function () {
    Schema::dropIfExists('interest_replies');
    chatsFreshRequest();

    expect(Chats::unreadCount(chatsMe()))->toBe(0);
});

it('splits the menu into Interests and Chats, with phone tabs in the inbox', function () {
    $layout = file_get_contents(resource_path('views/components/layouts/app.blade.php'));
    $inbox = file_get_contents(resource_path('views/interests/inbox.blade.php'));
    $show = file_get_contents(resource_path('views/interests/show.blade.php'));

    expect(substr_count($layout, "route('chats.index')"))->toBe(2)    // desktop + phone menu
        ->and($layout)->not->toContain('>Messages</a>')
        ->and($inbox)->toContain('class="inbox-mobile-tabs"')
        ->toContain("@media (min-width:1024px){.inbox-mobile-tabs{display:none}}")
        ->and($show)->toContain("\$interest->status === 'accepted' ? route('chats.index') : route('interests.inbox')");
});

it('gives the app the same chats list and badge counts', function () {
    DB::table('profiles')->whereIn('id', [2, 3])->delete(); // cards need many tables; covered elsewhere
    $user = new App\Models\User(['name' => 'Me']);
    $user->id = 11;
    $user->setRelation('profile', chatsMe());
    $request = Request::create('/api/v1/chats');
    app()->instance('request', $request);
    Illuminate\Support\Facades\Auth::setUser($user);
    $api = new App\Http\Controllers\Api\V1\ChatController();

    $list = $api->index($request)->getData(true);
    $badges = $api->badges($request)->getData(true);

    expect(array_column($list['data'], 'interest_id'))->toBe([2, 1])
        ->and($list['data'][1])->toMatchArray(['last_message' => 'Shall our parents talk on Sunday?', 'last_message_from' => 'them', 'unread_count' => 1])
        ->and($list['data'][0])->toMatchArray(['last_message_from' => 'me', 'unread_count' => 0])
        ->and($list['data'][0]['last_message'])->not->toStartWith('You:')
        ->and($list['meta']['total'])->toBe(2)
        ->and($badges['data'])->toBe(['interests_pending' => 1, 'chats_unread' => 1]);
});

it('records where an interest was sent from, ignoring unknown places and unsaved interests', function () {
    Interest::recordSource(Interest::find(3), 'card');
    Interest::recordSource(Interest::find(4), 'somewhere');
    Interest::recordSource(new Interest(), 'app');

    expect(DB::table('interests')->where('id', 3)->value('source'))->toBe('card')
        ->and(DB::table('interests')->where('id', 4)->value('source'))->toBeNull()
        ->and(DB::table('interests')->whereNotNull('source')->count())->toBe(1);
});

it('shows chats and where interests were sent from on the admin engagement report', function () {
    $this->travelTo('2026-10-08 09:00:00');
    DB::table('interests')->where('id', 3)->update(['source' => 'card', 'created_at' => '2026-10-07 09:00:00']);
    DB::table('interests')->where('id', 1)->update(['source' => 'mobile_bar', 'created_at' => '2026-10-07 09:00:00']);
    DB::table('interests')->where('id', 2)->update(['created_at' => '2026-10-07 09:00:00']); // before tracking

    $page = new App\Filament\Pages\EngagementReports();
    (new ReflectionMethod($page, 'loadChatStats'))->invoke($page);

    expect($page->chatStats)->toMatchArray([
        'new_chats' => 2,          // two accept replies
        'active_chats' => 3,       // interests 1, 4, 5 have messages
        'messages' => 4,
        'members_chatting' => 4,   // profiles 1, 2, 5, 6
        'sent_total' => 3,
    ])->and($page->chatStats['by_source'])->toBe([
        'Profile page' => 0, 'Profile card' => 1, 'Phone action bar' => 1, 'Contact tab' => 0, 'Mobile app' => 0, 'Before tracking' => 1,
    ]);
});

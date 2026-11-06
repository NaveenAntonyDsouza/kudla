<?php

use App\Filament\Resources\InterestResource\Pages\ListInterests;
use App\Models\AdminActivityLog;
use App\Models\Interest;
use App\Models\InterestReply;
use App\Models\User;
use App\Services\InterestService;
use App\Services\MemberCreationService;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\PrivacyBench\Bench;

/*
| 6 Nov 2026 (KM100375 / KM100745): an interest and two days of chat disappeared. Sending
| an interest again used to delete the earlier declined / withdrawn / expired interest to
| the same member, and its messages with it; it is now opened again. Deleting an interest
| in the admin also deletes its messages: it now says so and is logged.
*/

beforeEach(function () {
    Bench::guard();
    Mail::fake();
    Http::fake();
    $make = fn (string $name, string $gender, string $phone) => app(MemberCreationService::class)->create(['full_name' => $name, 'gender' => $gender,
        'date_of_birth' => '1993-03-03', 'email' => strtolower(str_replace(' ', '-', $name)) . '@bench.test', 'phone' => $phone,
        'religion' => 'Hindu', 'caste' => 'Billava'], ['temp_password' => 'hist-pass-' . substr($phone, -4)])['profile'];
    $this->he = $make('History Groom', 'male', '9812700001');
    $this->she = $make('History Bride', 'female', '9812700002');
});

it('opens a declined, withdrawn or expired interest again when it is sent again, with its messages', function (string $ended) {
    $old = Interest::create(['sender_profile_id' => $this->he->id, 'receiver_profile_id' => $this->she->id, 'status' => $ended]);
    // ended 40 days ago: past the 30-day wait after a decline
    DB::table('interests')->where('id', $old->id)->update(['created_at' => now()->subDays(45), 'updated_at' => now()->subDays(40)]);
    foreach (['Good morning', 'Hello'] as $text) {
        InterestReply::create(['interest_id' => $old->id, 'replier_profile_id' => $this->he->id, 'reply_type' => 'message', 'custom_message' => $text]);
    }

    $again = app(InterestService::class)->send($this->he, $this->she, 'msg_suitable', null);

    expect($again->id)->toBe($old->id)
        ->and($again->fresh()->status)->toBe('pending')
        ->and($again->fresh()->created_at->isToday())->toBeTrue()
        ->and(InterestReply::where('interest_id', $old->id)->count())->toBe(2)
        ->and(Interest::where('sender_profile_id', $this->he->id)->where('receiver_profile_id', $this->she->id)->count())->toBe(1);
})->with(['declined', 'cancelled', 'expired']);

it('warns that deleting an interest deletes its messages, and logs who did it', function () {
    $staff = User::create(['name' => 'History Staff', 'email' => 'history-staff@bench.test', 'password' => bcrypt('x-history-pass-3'),
        'role' => 'admin', 'is_active' => true, 'staff_role_id' => DB::table('staff_roles')->where('slug', 'super_admin')->value('id'), 'email_verified_at' => now()]);
    $interest = Interest::create(['sender_profile_id' => $this->he->id, 'receiver_profile_id' => $this->she->id, 'status' => 'accepted']);
    InterestReply::create(['interest_id' => $interest->id, 'replier_profile_id' => $this->he->id, 'reply_type' => 'message', 'custom_message' => 'Hello']);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->actingAs($staff, 'admin');

    Livewire::test(ListInterests::class)
        ->mountTableAction('delete', $interest)
        ->assertMountedActionModalSee('This also deletes the 1 replies and chat messages in it')
        ->callMountedTableAction();

    $log = AdminActivityLog::where('action', 'interest_deleted')->sole();
    expect(Interest::find($interest->id))->toBeNull()
        ->and($log->admin_user_id)->toBe($staff->id)
        ->and($log->changes)->toMatchArray(['sender' => $this->he->matri_id, 'receiver' => $this->she->matri_id, 'status' => 'accepted', 'messages_deleted' => 1]);
});

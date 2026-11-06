<?php

use App\Filament\Resources\ContactSubmissionResource;
use App\Filament\Resources\ContactSubmissionResource\Pages\ListContactSubmissions;
use App\Filament\Resources\ContactSubmissionResource\Pages\ViewContactSubmission;
use App\Models\AdminActivityLog;
use App\Models\ContactSubmission;
use App\Models\SiteSetting;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\PrivacyBench\Bench;

/*
| Contact Inbox grouped by sender (6 Nov 2026). Kudla had 20 waiting messages from 11
| people (the same message 3-4 times within a minute), Crasta 76 from 17 (bots sending
| one link 5 times with a different phone number each time). The New tab shows one row
| per sender with how many wait and since when; Reply and Close answer all of the
| sender's waiting messages; the menu badge counts people.
*/

beforeEach(function () {
    Bench::guard();
    Http::fake();
    $this->staff = User::create(['name' => 'Inbox Staff', 'email' => 'inbox-staff@bench.test', 'password' => bcrypt('x-inbox-pass-3'),
        'role' => 'admin', 'is_active' => true, 'staff_role_id' => DB::table('staff_roles')->where('slug', 'super_admin')->value('id'), 'email_verified_at' => now()]);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->actingAs($this->staff, 'admin');

    // Asha wrote 3 times (one address, two letter cases), Ravi once, Mohan's old message was closed
    $this->asha = [
        inboxMessage('Asha', 'asha@bench.test', 'Payment not showing', '-5 days'),
        inboxMessage('Asha', 'Asha@Bench.test', 'Still waiting about my payment', '-3 days'),
        inboxMessage('Asha', 'asha@bench.test', 'Please call me about the payment', '-1 hour'),
    ];
    $this->ravi = inboxMessage('Ravi', 'ravi@bench.test', 'How do I hide my profile?', '-2 hours');
    $this->mohanOld = inboxMessage('Mohan', 'mohan@bench.test', 'An old question', '-30 days', 'closed');
});

function inboxMessage(string $name, string $email, string $text, string $ago, string $status = 'new'): ContactSubmission
{
    $at = now()->modify($ago);

    return ContactSubmission::findOrFail(DB::table('contact_submissions')->insertGetId([
        'name' => $name, 'email' => $email, 'subject' => 'Payment Issue', 'message' => $text, 'status' => $status,
        'ip_address' => '127.0.0.1', 'created_at' => $at, 'updated_at' => $at,
    ]));
}

it('shows one row per sender on the New tab, with how many wait and since when, and counts people', function () {
    $first = $this->asha[0]->created_at->displayTz()->format('j M Y');

    Livewire::test(ListContactSubmissions::class)
        ->assertCanSeeTableRecords([$this->asha[2], $this->ravi])
        ->assertCanNotSeeTableRecords([$this->asha[0], $this->asha[1], $this->mohanOld])
        ->assertSeeText('3 messages')
        ->assertSeeText("waiting since {$first} · OVERDUE");

    Livewire::test(ListContactSubmissions::class)
        ->set('activeTab', 'all')
        ->assertCanSeeTableRecords([...$this->asha, $this->ravi, $this->mohanOld]);

    expect(ContactSubmission::sendersWaiting())->toBe(2)
        ->and(ContactSubmissionResource::getNavigationBadge())->toBe('2')
        ->and(ContactSubmissionResource::getNavigationBadgeTooltip())->toBe('4 messages from 2 people');
});

it('answers all of a sender\'s waiting messages with one reply, and only theirs', function () {
    \Illuminate\Support\Facades\Mail::shouldReceive('raw')->once()
        ->withArgs(fn (string $text): bool => str_contains($text, 'We have fixed your payment.'));

    Livewire::test(ListContactSubmissions::class)
        ->mountTableAction('reply', $this->asha[2])
        ->assertMountedActionModalSee(['Reply to Asha', 'This reply answers all 3 messages waiting from this sender', 'Still waiting about my payment'])
        ->callMountedTableAction()
        ->assertHasFormErrors(['admin_reply' => 'required']);

    Livewire::test(ListContactSubmissions::class)
        ->callTableAction('reply', $this->asha[2], data: ['admin_reply' => 'We have fixed your payment.'])
        ->assertNotified('Reply sent to asha@bench.test');

    foreach ($this->asha as $message) {
        expect($message->fresh())
            ->status->toBe('replied')
            ->admin_reply->toBe('We have fixed your payment.')
            ->replied_at->not->toBeNull();
    }
    expect($this->ravi->fresh()->status)->toBe('new')
        ->and(AdminActivityLog::where('action', 'contact_replied')->sole())
        ->admin_user_id->toBe($this->staff->id)
        ->changes->toMatchArray(['messages' => 3]);
});

it('marks nothing as replied when the reply email cannot be sent', function () {
    \Illuminate\Support\Facades\Mail::shouldReceive('raw')->once()->andThrow(new RuntimeException('SMTP is down'));

    Livewire::test(ListContactSubmissions::class)
        ->callTableAction('reply', $this->asha[2], data: ['admin_reply' => 'Hello'])
        ->assertNotified('The reply could not be sent');

    foreach ($this->asha as $message) {
        expect($message->fresh()->status)->toBe('new');
    }
    expect(AdminActivityLog::where('action', 'contact_replied')->exists())->toBeFalse();
});

it('fills the reply from a quick reply template', function () {
    SiteSetting::setValue('canned_responses', json_encode([['label' => 'Payment received', 'body' => 'Your payment is confirmed.']]));

    Livewire::test(ListContactSubmissions::class)
        ->mountTableAction('reply', $this->ravi)
        ->assertMountedActionModalSee(['Their message:', 'How do I hide my profile?'])
        ->setTableActionData(['canned_response' => 'Payment received'])
        ->assertTableActionDataSet(['admin_reply' => 'Your payment is confirmed.']);
});

it('closes a sender\'s waiting messages together, and a replied message on its own', function () {
    Livewire::test(ListContactSubmissions::class)
        ->mountTableAction('close', $this->asha[2])
        ->assertMountedActionModalSee('Close all 3 messages from Asha?');

    Livewire::test(ListContactSubmissions::class)
        ->callTableAction('close', $this->asha[2])
        ->assertNotified('3 messages closed');

    foreach ($this->asha as $message) {
        expect($message->fresh()->status)->toBe('closed');
    }
    expect($this->ravi->fresh()->status)->toBe('new')
        ->and(AdminActivityLog::where('action', 'contact_closed')->sole()->changes)->toMatchArray(['messages' => 3])
        ->and(ContactSubmission::sendersWaiting())->toBe(1);

    $replied = inboxMessage('Mohan', 'mohan@bench.test', 'Thanks for the answer', '-2 days', 'replied');
    $waiting = inboxMessage('Mohan', 'mohan@bench.test', 'One more thing', '-1 hour');
    Livewire::test(ListContactSubmissions::class)
        ->set('activeTab', 'all')
        ->callTableAction('close', $replied)
        ->assertNotified('Inquiry closed');

    expect($replied->fresh()->status)->toBe('closed')
        ->and($waiting->fresh()->status)->toBe('new');
});

it('shows everything the sender sent on the message page, with Reply and Close there too', function () {
    Livewire::test(ViewContactSubmission::class, ['record' => $this->asha[0]->getKey()])
        ->assertSeeText('All messages from this sender')
        ->assertSeeText('Please call me about the payment')
        ->assertSeeText('Still waiting about my payment')
        ->assertSeeText('this message')
        ->assertActionVisible('reply')
        ->assertActionVisible('close');

    Livewire::test(ViewContactSubmission::class, ['record' => $this->ravi->getKey()])
        ->assertDontSeeText('All messages from this sender');
});

<?php

use App\Mail\InterestAcceptedMail;
use App\Mail\InterestReceivedMail;
use App\Services\InterestService;
use App\Support\MemberName;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\PrivacyBench\Bench;

/*
| Interest emails name the other member as "Priya D. (KM100123)" (owner, 20 Nov 2026), never their
| full surname, and never in the subject (subjects show on locked phones).
*/

beforeEach(function () {
    Mail::fake();
    Http::fake();
    $this->b = Bench::build();
});

it('names the sender of an interest by first name, initial and Matri ID, inside the email only', function () {
    $b = $this->b;
    $sender = $b->m['VS'];
    $sender->update(['full_name' => 'Benchina Testkumari Surnamewala']);
    $interest = app(InterestService::class)->send($sender, $b->m['TP'], null, null);

    $mail = new InterestReceivedMail($interest->fresh());
    $html = (string) $mail->render();

    expect($html)->toContain('Benchina S. (' . $sender->matri_id . ')')
        ->not->toContain('Surnamewala')
        ->and((string) $mail->envelope()->subject)->not->toContain('Benchina');
});

it('names the member in photo emails too, never the surname or in the subject', function () {
    $b = $this->b;
    $asker = $b->m['VS'];
    $owner = $b->m['TP'];
    $asker->update(['full_name' => 'Photoasker Hiddensurname']);
    $owner->update(['full_name' => 'Photoowner Secretfamily']);
    Mail::fake();                                  // only the emails from this request below
    $this->actingAs($asker->user)->post('/photo-requests/' . $owner->matri_id . '/send');
    $request = \App\Models\PhotoRequest::where('requester_profile_id', $asker->id)->where('target_profile_id', $owner->id)->first();
    if ($request && $request->status !== 'approved') {
        $this->actingAs($owner->user)->post('/photo-requests/' . $request->id . '/approve');
    }
    $mails = Mail::sent(fn (\Illuminate\Mail\Mailable $m) => true)->merge(Mail::queued(fn (\Illuminate\Mail\Mailable $m) => true))
        ->filter(fn ($m) => str_starts_with(class_basename($m), 'Photo'));
    expect($mails)->not->toBeEmpty();
    foreach ($mails as $mail) {
        $html = (string) $mail->render();
        $kind = class_basename($mail);
        $other = $kind === 'PhotoRequestApprovedMail' || $kind === 'PhotoAddedMail' ? $owner : $asker;
        $first = explode(' ', $other->full_name)[0];
        expect($html)->toContain($first . ' ' . ($other === $owner ? 'S.' : 'H.') . ' (' . $other->matri_id . ')')
            ->not->toContain($other === $owner ? 'Secretfamily' : 'Hiddensurname')   // the other member's surname
            ->and((string) $mail->envelope()->subject)->not->toContain($first);
    }
});

it('names who accepted, and falls back to the Matri ID when a profile has no name', function () {
    $b = $this->b;
    $accepter = $b->m['TP'];
    $accepter->update(['full_name' => 'Acceptina Lastnamebhat']);
    $interest = app(InterestService::class)->send($b->m['VS'], $accepter, null, null);

    $html = (string) (new InterestAcceptedMail($interest->fresh()))->render();
    expect($html)->toContain('Acceptina L. (' . $accepter->matri_id . ')')->not->toContain('Lastnamebhat');

    $accepter->full_name = '';
    expect(MemberName::withId($accepter))->toBe($accepter->matri_id);
});

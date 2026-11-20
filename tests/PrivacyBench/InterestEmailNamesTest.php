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

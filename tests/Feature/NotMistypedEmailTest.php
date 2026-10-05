<?php

use App\Rules\NotMistypedEmail;
use Illuminate\Support\Facades\Validator;

/*
| Sign-up catches mistyped email domains and suggests the right one
| (kudla's bounce list was full of gamil.com / gmail.coms / 315gmail.com).
*/

it('suggests the right address for common typos', function (string $typed, ?string $suggestion) {
    expect(NotMistypedEmail::suggestion($typed))->toBe($suggestion);
})->with([
    ['asha@gmial.com', 'asha@gmail.com'],
    ['Asha@Gamil.com', 'asha@gmail.com'],
    ['asha@gmail.coms', 'asha@gmail.com'],
    ['asha@gmail.co', 'asha@gmail.com'],
    ['poojaridinesh@315gmail.com', 'poojaridinesh315@gmail.com'],
    ['ravi@yahooo.com', 'ravi@yahoo.com'],
    ['asha@gmail.com', null],
    ['ravi@yahoo.co.in', null],
    ['info@kudlamatrimony.com', null],
]);

it('fails validation with the suggestion in the message', function () {
    $v = Validator::make(['email' => 'asha@gmial.com'], ['email' => ['required', 'email', new NotMistypedEmail]]);

    expect($v->fails())->toBeTrue()
        ->and($v->errors()->first('email'))->toContain('Did you mean asha@gmail.com?');
});

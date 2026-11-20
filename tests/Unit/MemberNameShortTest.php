<?php

use App\Support\MemberName;

/* "Priya D." for the shapes members really type (live names, 20 Nov 2026). */
it('shortens the names members really type', function (string $typed, string $shown) {
    expect(MemberName::short($typed))->toBe($shown);
})->with([
    ["Naveen Antony D'Souza", 'Naveen D.'],
    ['Naveen D Souza', 'Naveen D.'],
    ['Mary Jane D Cunha', 'Mary D.'],
    ['Dr. Priya Rao', 'Priya R.'],
    ['Smt. Shobha', 'Shobha'],
    ['Priya', 'Priya'],
    ['K. Ramesh', 'Ramesh K.'],
    ['K.Ramesh', 'Ramesh K.'],
    ['K R Ramesh', 'Ramesh K.'],
    ['K.R. Ramesh', 'Ramesh K.'],
    ['Ramesh K', 'Ramesh K.'],
    ['PRIYA RAO', 'Priya R.'],
    ['priya rao', 'Priya R.'],
    ['McDonald smith', 'McDonald S.'],
    ['Ann-Marie Pinto', 'Ann-Marie P.'],
    ['Anugraha 1460', 'Anugraha 1460'],
    ['priya@gmail.com', ''],
    ['Priya x@y.com Rao', 'Priya R.'],
    ['Priya.Shetty', 'Priya S.'],
    ['Priya, Rao', 'Priya R.'],
    ['Priya (Rao)', 'Priya R.'],
    ['Priya. Rao.', 'Priya R.'],
    ['Priya ..', 'Priya'],
    ["Naveen D' Souza", 'Naveen D.'],
    ['V C M', 'V C M'],
    ['', ''],
]);

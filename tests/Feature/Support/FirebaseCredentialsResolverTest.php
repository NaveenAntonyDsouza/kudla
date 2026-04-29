<?php

use App\Support\FirebaseCredentialsResolver;

it('returns null when the path is null', function () {
    expect(FirebaseCredentialsResolver::resolve(null))->toBeNull();
});

it('returns null when the path is empty string', function () {
    expect(FirebaseCredentialsResolver::resolve(''))->toBeNull();
});

it('passes Unix absolute paths through unchanged', function () {
    $abs = '/var/secrets/firebase-credentials.json';

    expect(FirebaseCredentialsResolver::resolve($abs))->toBe($abs);
});

it('passes Windows absolute paths through unchanged', function () {
    $backslash = 'C:\\secrets\\firebase-credentials.json';
    $forwardslash = 'D:/secrets/firebase-credentials.json';

    expect(FirebaseCredentialsResolver::resolve($backslash))->toBe($backslash);
    expect(FirebaseCredentialsResolver::resolve($forwardslash))->toBe($forwardslash);
});

it('wraps relative paths in base_path()', function () {
    $relative = 'storage/app/firebase-credentials.json';

    expect(FirebaseCredentialsResolver::resolve($relative))
        ->toBe(base_path($relative))
        ->toStartWith(base_path());
});

it('isAbsolute distinguishes Unix, Windows, and relative correctly', function () {
    expect(FirebaseCredentialsResolver::isAbsolute('/etc/foo'))->toBeTrue();
    expect(FirebaseCredentialsResolver::isAbsolute('C:\\Users\\a\\b'))->toBeTrue();
    expect(FirebaseCredentialsResolver::isAbsolute('D:/dev/foo'))->toBeTrue();
    expect(FirebaseCredentialsResolver::isAbsolute('storage/app/foo.json'))->toBeFalse();
    expect(FirebaseCredentialsResolver::isAbsolute('foo.json'))->toBeFalse();
    expect(FirebaseCredentialsResolver::isAbsolute('./foo'))->toBeFalse();
});

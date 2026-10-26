<?php

use App\Models\SiteSetting;
use App\Providers\GatewayConfigProvider;
use App\Support\SecretSetting;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
| Weekly review, 21 Oct 2026: the mailbox password was stored as plain text in
| site_settings. It is now encrypted with the app key, the mail settings still
| work, and the admin page never sends it to the browser.
*/

beforeEach(function () {
    Schema::dropIfExists('site_settings');
    Schema::create('site_settings', function (Blueprint $t) {
        $t->id();
        $t->string('key')->unique();
        $t->text('value')->nullable();
        $t->timestamps();
    });
    Cache::flush();
});

afterEach(fn () => Schema::dropIfExists('site_settings'));

it('stores a secret encrypted and reads it back, and still reads an old plain value', function () {
    SecretSetting::put('mail_password', 's3cret!pw');

    $stored = DB::table('site_settings')->where('key', 'mail_password')->value('value');
    expect($stored)->toStartWith('enc:')->not->toContain('s3cret')
        ->and(SecretSetting::reveal($stored))->toBe('s3cret!pw')
        ->and(SecretSetting::reveal('old-plain'))->toBe('old-plain')
        ->and(SecretSetting::reveal('enc:not-valid'))->toBe('');
});

it('gives the mailer the real password', function () {
    SiteSetting::setValue('mail_host', 'smtp.example.test');
    SecretSetting::put('mail_password', 's3cret!pw');

    (new GatewayConfigProvider(app()))->boot();

    expect(config('mail.mailers.smtp.password'))->toBe('s3cret!pw');
});

it('encrypts the saved password in place, once', function () {
    DB::table('site_settings')->insert(['key' => 'mail_password', 'value' => 'plain-pw', 'created_at' => now(), 'updated_at' => now()]);
    $migration = require database_path('migrations/2026_10_26_000002_encrypt_mail_password.php');

    $migration->up();
    $once = DB::table('site_settings')->where('key', 'mail_password')->value('value');
    $migration->up();

    expect($once)->toStartWith('enc:')
        ->and(DB::table('site_settings')->where('key', 'mail_password')->value('value'))->toBe($once)
        ->and(SecretSetting::reveal($once))->toBe('plain-pw');

    $migration->down();
    expect(DB::table('site_settings')->where('key', 'mail_password')->value('value'))->toBe('plain-pw');
});

it('never puts the saved password in the admin form', function () {
    $page = file_get_contents(app_path('Filament/Pages/GatewaySettings.php'));

    expect($page)->toContain("'mail_password' => '', // never sent to the browser")
        ->not->toContain("'mail_password' => \$settings['mail_password'] ?? ''")
        ->toContain("\\App\\Support\\SecretSetting::put(\$key, (string) \$value);");
});

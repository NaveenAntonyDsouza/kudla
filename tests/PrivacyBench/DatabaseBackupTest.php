<?php

use App\Console\Commands\BackupDatabase;
use Illuminate\Support\Facades\Log;
use Tests\PrivacyBench\Bench;

/*
| Nightly database backup (7 Nov 2026: no site had a backup it could be restored from).
*/

beforeEach(function () {
    Bench::guard();
    $this->dir = BackupDatabase::directory();
    $this->before = glob($this->dir . '/db-*.sql.gz') ?: [];
});

afterEach(function () {
    foreach (array_diff(glob($this->dir . '/db-*.sql.gz') ?: [], $this->before) as $f) {
        @unlink($f);
    }
});

it('writes a complete, gzip-valid copy of every table', function () {
    $this->artisan('db:backup')->assertSuccessful();
    $file = $this->dir . '/db-' . now()->format('Y-m-d') . '.sql.gz';
    $sql = implode('', gzfile($file));
    $tables = count(DB::select("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'"));

    expect($sql)->toContain('CREATE TABLE `profiles`')
        ->toContain('CREATE TABLE `interest_replies`')
        ->toContain("-- Dump completed: {$tables} tables")
        ->and(file_exists($file . '.part'))->toBeFalse();
});

it('keeps 14 daily and 6 monthly copies', function () {
    foreach (range(1, 40) as $i) {
        touch($this->dir . '/db-' . now()->subDays($i)->format('Y-m-d') . '.sql.gz');
    }
    foreach (range(1, 9) as $m) {
        touch($this->dir . '/db-' . now()->subMonthsNoOverflow($m)->startOfMonth()->format('Y-m-d') . '.sql.gz');
    }
    $this->artisan('db:backup')->assertSuccessful();

    $left = array_map('basename', array_diff(glob($this->dir . '/db-*.sql.gz') ?: [], $this->before));
    $firsts = array_filter($left, fn ($f) => str_ends_with(basename($f, '.sql.gz'), '-01'));
    // the newest 14 are kept whatever their date; older ones only if they are a first of the month
    expect(count($left))->toBeLessThanOrEqual(BackupDatabase::KEEP_DAILY + BackupDatabase::KEEP_MONTHLY)
        ->and(count(array_diff($left, $firsts)))->toBeLessThanOrEqual(BackupDatabase::KEEP_DAILY)
        ->and(in_array('db-' . now()->subDays(40)->format('Y-m-d') . '.sql.gz', $left, true))->toBeFalse()
        ->and(in_array('db-' . now()->format('Y-m-d') . '.sql.gz', $left, true))->toBeTrue();
});

it('reports a missing backup as an error, so the error alert emails the owner', function () {
    foreach (glob($this->dir . '/db-*.sql.gz') ?: [] as $f) {
        touch($f, now()->subDays(3)->timestamp);
    }
    Log::spy();
    $this->artisan('db:backup --check')->assertFailed();
    Log::shouldHaveReceived('error')->withArgs(fn ($m) => str_contains($m, 'Database backup missing'));
});

it('copies offsite only on the site that has the copy script set, and reports a missing script', function () {
    Log::spy();
    \App\Models\SiteSetting::setValue('offsite_backup_script', '');
    $this->artisan('db:offsite-copy')->assertSuccessful();
    Log::shouldNotHaveReceived('error');

    \App\Models\SiteSetting::setValue('offsite_backup_script', storage_path('no-such-script.sh'));
    $this->artisan('db:offsite-copy')->assertFailed();
    Log::shouldHaveReceived('error')->withArgs(fn ($m) => str_contains($m, 'Offsite backup copy') && str_contains($m, 'missing'));
});

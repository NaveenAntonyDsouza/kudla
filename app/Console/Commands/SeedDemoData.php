<?php

namespace App\Console\Commands;

use Database\Seeders\DemoDataSeeder;
use Illuminate\Console\Command;

class SeedDemoData extends Command
{
    protected $signature = 'matrimony:demo-seed
                            {--force : Allow running in production}';

    protected $description = 'Populate the database with realistic demo data for screenshots / fresh installs';

    public function handle(): int
    {
        if (app()->environment('production') && ! $this->option('force')) {
            $this->error('Refusing to seed demo data in production. Pass --force if you are sure.');
            return self::FAILURE;
        }

        // Skip confirmation when --force is set (for CI / scripts / non-interactive use)
        if (! $this->option('force')) {
            if (! $this->confirm('This will add ~50 demo profiles, 30 leads, 100 call logs, and related demo data. Continue?', true)) {
                $this->info('Aborted.');
                return self::SUCCESS;
            }
        }

        $this->info('Seeding demo data...');
        $seeder = new DemoDataSeeder();
        $seeder->setCommand($this);
        $seeder->run();

        return self::SUCCESS;
    }
}

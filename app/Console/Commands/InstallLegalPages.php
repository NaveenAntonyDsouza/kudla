<?php

namespace App\Console\Commands;

use App\Models\StaticPage;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

#[Signature('legal:install
    {variant : matrimony-in | dating-in | dating-us (folder in resources/legal)}
    {--about= : Site host whose About Us draft to use (resources/legal/about/<host>.html)}
    {--replace=* : Page slugs to overwrite if they already exist (default: only add missing pages)}
    {--dry-run : Show what would happen; change nothing}')]
#[Description('Install the legal pages (Privacy, Terms, Refund, Child Safety, Report Misuse, About) from resources/legal. Existing pages are kept unless named in --replace.')]
class InstallLegalPages extends Command
{
    /** slug => [title, sort_order] — same as the original StaticPageSeeder. */
    private const PAGES = [
        'privacy-policy' => ['Privacy Policy', 1],
        'terms-condition' => ['Terms of Service', 2],
        'about-us' => ['About Us', 3],
        'refund-policy' => ['Refund Policy', 4],
        'child-safety' => ['Child Safety Policy', 5],
        'report-misuse' => ['Report Misuse', 6],
    ];

    public function handle(): int
    {
        $variant = (string) $this->argument('variant');
        $dir = resource_path("legal/{$variant}");
        if (! in_array($variant, ['matrimony-in', 'dating-in', 'dating-us'], true) || ! is_dir($dir)) {
            $this->error("Unknown variant '{$variant}'.");
            return self::FAILURE;
        }

        $about = $this->option('about');
        $replace = array_filter((array) $this->option('replace'));
        $dryRun = (bool) $this->option('dry-run');
        $rows = [];

        foreach (self::PAGES as $slug => [$title, $order]) {
            $file = $slug === 'about-us'
                ? ($about ? resource_path("legal/about/{$about}.html") : null)
                : "{$dir}/{$slug}.html";
            if ($file === null) {
                $rows[] = [$slug, 'skipped (no --about)'];
                continue;
            }
            if (! is_file($file)) {
                $this->error("Missing draft: {$file}");
                return self::FAILURE;
            }

            $existing = StaticPage::where('slug', $slug)->first();
            if ($existing && ! in_array($slug, $replace, true)) {
                $rows[] = [$slug, 'kept (already exists)'];
                continue;
            }

            if (! $dryRun) {
                StaticPage::updateOrCreate(['slug' => $slug], [
                    'title' => $title,
                    'content' => trim(file_get_contents($file)),
                    'is_active' => true,
                    'is_system' => true,
                    'show_in_footer' => true,
                    'sort_order' => $order,
                ]);
                Cache::forget("static_page.{$slug}");
            }
            $rows[] = [$slug, $existing ? 'REPLACED' : 'created'];
        }

        $this->table(['Page', $dryRun ? 'Would be' : 'Result'], $rows);
        if ($dryRun) {
            $this->warn('DRY RUN — nothing changed.');
        }

        return self::SUCCESS;
    }
}

<?php

namespace App\Http\Middleware;

use App\Models\SiteSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * "App only" sites (Site Settings → Mobile App → App only, 24 Nov 2026, the Catholic dating app):
 * members use the app, so the website is one download page plus the information pages Google Play
 * asks for (privacy policy, terms, account deletion, contact). Website sign-up, sign-in and member
 * pages send visitors to the home page. The app API, the admin panel, password-reset links (the app's
 * "forgot password" emails one) and unsubscribe links keep working. Off on every other site.
 */
class AppOnlyMode
{
    public const SETTING = 'app_only';

    /** First part of the path that stays open. '' is the home page (the download page). */
    private const OPEN = [
        '', 'privacy-policy', 'terms-condition', 'about-us', 'contact-us', 'contact', 'page',
        'child-safety', 'refund-policy', 'report-misuse',   // Play asks dating apps for a child-safety page
        'forgot-password', 'reset-password', 'unsubscribe', 'logout',
        'livewire', 'storage', 'build', 'health', 'up', 'robots.txt', 'sitemap.xml', 'favicon.ico',
        '.well-known', 'api', 'filament',
    ];

    public static function on(): bool
    {
        try {
            return SiteSetting::getValue(self::SETTING, '0') === '1';
        } catch (\Throwable) {
            return false;
        }
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (! self::on()) {
            return $next($request);
        }

        $first = explode('/', trim($request->path(), '/'))[0];
        $admin = trim((string) config('app.admin_path', 'admin'), '/');
        if ($first === $admin || in_array($first, self::OPEN, true)) {
            return $next($request);
        }

        return $request->isMethod('GET') || $request->isMethod('HEAD')
            ? redirect('/')
            : abort(404);
    }
}

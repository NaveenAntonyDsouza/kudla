<?php

use App\Models\Profile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;

/*
| Phone bottom menu (owner's choice, 8 Oct 2026): 94% of signed-in members
| are on phones, and Interests / Chats were two taps away behind ☰.
*/

function mnavMember(int $pending = 0, int $unread = 0, string $routeName = 'dashboard'): void
{
    $user = new User(['name' => 'Member']);
    $user->id = 21;
    $profile = new Profile(['matri_id' => 'KM100021']);
    $profile->id = 21;
    $user->setRelation('profile', $profile);
    Auth::setUser($user);

    $request = Request::create('/x');
    $request->setRouteResolver(fn () => Route::getRoutes()->getByName($routeName));
    app()->instance('request', $request);
    // the badge counts are cached per request by App\Support\Chats
    $request->attributes->set('interests_pending.21', $pending);
    $request->attributes->set('chats_unread.21', $unread);
}

it('shows Home, Matches, Interests, Chats and Profile on phones only', function () {
    mnavMember();

    $html = Blade::render('<x-mobile-bottom-nav />');

    preg_match_all('/<span class="mnav-label">([^<]+)<\/span>/', $html, $m);
    expect($m[1])->toBe(['Home', 'Matches', 'Interests', 'Chats', 'Profile'])
        ->and($html)->toContain('.mnav, .mnav-spacer { display: none; }')
        ->toContain('@media (max-width: 767px)')
        ->toContain(route('chats.index'))
        ->toContain(route('profile.show'));
});

it('marks the page you are on and shows red counts for interests and chats', function () {
    mnavMember(pending: 3, unread: 1, routeName: 'chats.index');

    $html = Blade::render('<x-mobile-bottom-nav />');

    expect($html)->toMatch('/class="mnav-item mnav-on"\s+aria-current="page"\s*>.*?Chats/s')
        ->toContain('aria-label="3 new">3</span>')
        ->toContain('aria-label="1 new">1</span>')
        ->toContain(route('interests.inbox', ['tab' => 'received']));
});

it('counts search pages as Matches and keeps the menu off screen while typing', function () {
    mnavMember(routeName: 'search.index');

    $html = Blade::render('<x-mobile-bottom-nav />');

    expect($html)->toMatch('/mnav-on"\s+aria-current="page"\s*>.*?Matches/s')
        ->toContain('.mnav-typing .mnav { display: none; }')
        ->toContain("document.documentElement.classList.add('mnav-typing')");
});

it('stays out of the way where it would clash', function () {
    $page = file_get_contents(resource_path('views/profile/preview.blade.php'));
    $layout = file_get_contents(resource_path('views/components/layouts/app.blade.php'));

    expect($page)->toContain('.mnav, .mnav-spacer { display: none !important; }')   // profile action bar instead
        ->and($layout)->toContain('<x-mobile-bottom-nav />');

    Auth::logout();
    expect(trim(Blade::render('<x-mobile-bottom-nav />')))->toBe('');               // signed out: nothing
});

it('lets phone users switch search type, which was desktop-only', function () {
    $search = file_get_contents(resource_path('views/search/index.blade.php'));

    expect($search)->toContain('class="srch-mtabs" role="tablist"')
        ->toContain("@click=\"activeTab = 'keyword'\"")
        ->toContain("@click=\"activeTab = 'byid'\"")
        ->toContain('@media (min-width: 1024px) { .srch-mtabs { display: none; } }');
});

it('gives the login page its title back', function () {
    // (rendering the layout needs the theme tables; the prop is what was missing)
    $layout = file_get_contents(resource_path('views/components/layouts/auth.blade.php'));

    expect($layout)->toContain("@props(['maxWidth' => 'md', 'title' => null])")
        ->toContain("\$loginTitle = \$loginSeo?->meta_title ?: ((\$title ?? '') . ' | ' . \$siteName);")
        ->toContain('<title>{{ $loginTitle }}</title>');
});

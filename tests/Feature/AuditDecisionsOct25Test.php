<?php

use App\Models\Profile;
use App\Services\MemberErasure;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
| Owner's decisions on the 25 Oct 2026 audit:
| 1. "Delete Account" now keeps the privacy policy's promise: personal data is
|    erased 90 days later (members:erase-deleted), unless staff restore it.
| 2. Paid plans are "Coming soon" where no payment method is set up.
| 4. Sign-up asks for a tick to agree to the Privacy Policy and Terms, and
|    records when.
| 6. Multi-choice lists build their rows only when opened (pages were ~0.9 MB).
| Plus: the accept line is an invitation, not a warning.
*/

function erasureSchema(): void
{
    Schema::create('users', function (Blueprint $t) {
        $t->id();
        $t->string('name');
        $t->string('email')->nullable();
        $t->string('phone')->nullable();
        $t->string('password');
        $t->string('remember_token')->nullable();
        $t->json('notification_preferences')->nullable();
        $t->boolean('is_active')->default(true);
        $t->timestamps();
    });
    Schema::create('profiles', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('user_id');
        $t->string('matri_id');
        $t->string('full_name');
        $t->string('gender')->default('female');
        $t->date('date_of_birth');
        foreach (['creator_name', 'creator_contact_number', 'height', 'weight_kg', 'complexion', 'body_type', 'blood_group', 'mother_tongue', 'how_did_you_hear_about_us', 'deletion_reason'] as $c) {
            $t->string($c)->nullable();
        }
        $t->text('about_me')->nullable();
        $t->boolean('is_active')->default(true);
        $t->boolean('is_hidden')->default(false);
        $t->timestamp('account_deleted_at')->nullable();
        $t->timestamp('data_erased_at')->nullable();
        $t->softDeletes();
        $t->timestamps();
    });
    Schema::create('contact_info', fn (Blueprint $t) => [$t->id(), $t->unsignedBigInteger('profile_id'), $t->string('whatsapp_number')->nullable(), $t->timestamps()]);
    Schema::create('shortlists', fn (Blueprint $t) => [$t->id(), $t->unsignedBigInteger('profile_id'), $t->unsignedBigInteger('shortlisted_profile_id'), $t->timestamps()]);
    Schema::create('interests', fn (Blueprint $t) => [$t->id(), $t->unsignedBigInteger('sender_profile_id'), $t->unsignedBigInteger('receiver_profile_id'), $t->text('custom_message')->nullable(), $t->timestamps()]);
    Schema::create('interest_replies', fn (Blueprint $t) => [$t->id(), $t->unsignedBigInteger('interest_id'), $t->unsignedBigInteger('replier_profile_id'), $t->text('custom_message')->nullable(), $t->timestamps()]);
    Schema::create('profile_photos', fn (Blueprint $t) => [$t->id(), $t->unsignedBigInteger('profile_id'), $t->string('photo_url')->nullable(), $t->string('thumbnail_url')->nullable(), $t->string('storage_driver')->nullable(), $t->timestamps()]);
    Schema::create('admin_activity_log', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('admin_user_id')->nullable();
        $t->string('action');
        $t->string('model_type')->nullable();
        $t->unsignedBigInteger('model_id')->nullable();
        $t->json('changes')->nullable();
        $t->string('ip_address')->nullable();
        $t->timestamps();
    });
}

afterEach(function () {
    foreach (['users', 'profiles', 'contact_info', 'shortlists', 'interests', 'interest_replies', 'profile_photos', 'admin_activity_log'] as $t) {
        Schema::dropIfExists($t);
    }
});

it('erases a deleted member\'s personal data after 90 days, and only then', function () {
    erasureSchema();
    $member = function (int $id, ?string $deletedDaysAgo, bool $active = false) {
        DB::table('users')->insert(['id' => $id, 'name' => "Member $id", 'email' => "m$id@example.com", 'phone' => "98450000$id", 'password' => 'x']);
        DB::table('profiles')->insert(['id' => $id, 'user_id' => $id, 'matri_id' => "KM$id", 'full_name' => "Member $id", 'date_of_birth' => '1995-05-05',
            'about_me' => 'Call me on 9845000000', 'is_active' => $active, 'is_hidden' => ! $active,
            'deletion_reason' => $deletedDaysAgo !== null ? 'I am Married' : null,
            'account_deleted_at' => $deletedDaysAgo !== null ? now()->subDays((int) $deletedDaysAgo) : null]);
        DB::table('contact_info')->insert(['profile_id' => $id, 'whatsapp_number' => '9845000000']);
    };
    $member(11, '120');           // deleted 120 days ago → erase
    $member(12, '30');            // deleted 30 days ago → not yet
    $member(13, '200', true);     // restored by staff → never
    $member(14, null);            // ordinary member
    DB::table('shortlists')->insert([['profile_id' => 14, 'shortlisted_profile_id' => 11], ['profile_id' => 14, 'shortlisted_profile_id' => 12]]);
    DB::table('interests')->insert([['id' => 1, 'sender_profile_id' => 11, 'receiver_profile_id' => 14, 'custom_message' => 'Here is my number 98450 00000']]);
    DB::table('interest_replies')->insert(['interest_id' => 1, 'replier_profile_id' => 11, 'custom_message' => 'WhatsApp me']);

    $this->artisan('members:erase-deleted', ['--dry-run' => true])->expectsOutputToContain('not yet erased: 1.');
    expect(DB::table('profiles')->where('id', 11)->value('full_name'))->toBe('Member 11');

    $this->artisan('members:erase-deleted')->expectsOutputToContain('Erased: 1.');

    $p = DB::table('profiles')->where('id', 11)->first();
    $u = DB::table('users')->where('id', 11)->first();
    expect($p->full_name)->toBe('Deleted member')->and($p->about_me)->toBeNull()->and($p->data_erased_at)->not->toBeNull()->and($p->deleted_at)->not->toBeNull()
        ->and($p->matri_id)->toBe('KM11')
        ->and($u->name)->toBe('Deleted member')->and($u->email)->toBe('deleted-11@deleted.invalid')->and($u->phone)->toBeNull()
        ->and(DB::table('contact_info')->where('profile_id', 11)->count())->toBe(0)
        ->and(DB::table('shortlists')->where('shortlisted_profile_id', 11)->count())->toBe(0)
        ->and(DB::table('interests')->where('id', 1)->value('custom_message'))->toBeNull()
        ->and(DB::table('interest_replies')->where('replier_profile_id', 11)->value('custom_message'))->toBeNull()
        ->and(DB::table('admin_activity_log')->where('action', 'member_data_erased')->count())->toBe(1);

    // the others are untouched
    expect(DB::table('profiles')->whereIn('id', [12, 13, 14])->pluck('full_name')->all())->toBe(['Member 12', 'Member 13', 'Member 14'])
        ->and(DB::table('contact_info')->whereIn('profile_id', [12, 13, 14])->count())->toBe(3)
        ->and(DB::table('shortlists')->where('shortlisted_profile_id', 12)->count())->toBe(1);

    // second run: nothing left to do
    $this->artisan('members:erase-deleted')->expectsOutputToContain('not yet erased: 0.');
});

it('cancels the erasure when staff reactivate the account, and says 90 days when deleting', function () {
    erasureSchema();
    DB::table('users')->insert(['id' => 21, 'name' => 'X', 'password' => 'x']);
    DB::table('profiles')->insert(['id' => 21, 'user_id' => 21, 'matri_id' => 'KM21', 'full_name' => 'X', 'date_of_birth' => '1995-05-05', 'is_active' => false, 'deletion_reason' => 'Other Reasons', 'account_deleted_at' => now()->subDays(10)]);

    Profile::withTrashed()->find(21)->update(['is_active' => true]);
    expect(DB::table('profiles')->where('id', 21)->value('account_deleted_at'))->toBeNull();

    expect(file_get_contents(app_path('Http/Controllers/SettingsController.php')))->toContain("'account_deleted_at' => now()")
        ->and(file_get_contents(app_path('Http/Controllers/Api/V1/SettingsController.php')))->toContain("'account_deleted_at' => now()")
        ->and(file_get_contents(resource_path('views/settings/partials/delete-account.blade.php')))->toContain('permanently deleted after 90 days')
        ->and(file_get_contents(base_path('routes/console.php')))->toContain("Schedule::command('members:erase-deleted')");
});

it('shows paid plans as coming soon when no payment method is set up', function () {
    $index = file_get_contents(resource_path('views/membership/index.blade.php'));
    $home = file_get_contents(resource_path('views/pages/home/sections/plans.blade.php'));

    expect($index)->toContain('$comingSoon = \App\Support\PaidPlans::comingSoon();')
        ->toContain('@elseif($comingSoon)')
        ->toContain('Paid plans are coming soon')
        ->and($home)->toContain('@elseif($plansSoon)')
        ->and(file_get_contents(app_path('Support/PaidPlans.php')))->toContain('empty(app(PaymentGatewayManager::class)->getConfigured())');
});

it('asks new members to tick that they agree, and records when', function () {
    $rules = (new \App\Http\Requests\RegisterStep1Request())->rules();
    expect($rules['accept_terms'] ?? null)->toBe('accepted');

    \Illuminate\Support\Facades\View::share('errors', new \Illuminate\Support\ViewErrorBag()); // as every real page does
    $html = Blade::render('<x-terms-consent />');
    expect($html)->toContain('type="checkbox" name="accept_terms"')->toContain(' required')
        ->toContain(route('privacy'))->toContain(route('terms'));

    foreach (['auth/register-step1', 'pages/home/garland', 'pages/home/classic', 'pages/home'] as $view) {
        expect(file_get_contents(resource_path("views/{$view}.blade.php")))->toContain('<x-terms-consent')->not->toContain('By registering, you agree');
    }
    expect(file_get_contents(app_path('Http/Controllers/Auth/RegisterController.php')))->toContain("'terms_accepted_at' => now()")
        ->and(file_get_contents(app_path('Services/RegistrationService.php')))->toContain("'terms_accepted_at' => ! empty(\$data['accept_terms']) ? now() : null");
});

it('builds list rows only when a list is opened', function () {
    $html = Blade::render('<x-multi-select name="edu" label="Education" :options="$o" :grouped="true" />', ['o' => ['Bachelors' => ['B.Sc', 'B.Com'], 'Masters' => ['MBA/PGDM']]]);

    expect($html)->toContain('<template x-if="open">')
        ->toContain('x-for="(g, gi) in groupList"')
        ->toContain('MBA')                                        // the options travel once, as data
        ->not->toContain('selected.includes(\'MBA')              // no pre-rendered row per option
        ->not->toContain('groupMap');
});

it('invites rather than warns under the Accept button', function () {
    foreach (['interests/inbox', 'interests/show'] as $view) {
        expect(file_get_contents(resource_path("views/{$view}.blade.php")))
            ->toContain('Accept to connect: you can chat, call and WhatsApp each other.')
            ->not->toContain('Accepting shares your phone');
    }
});

it('calls the full search "Advanced Search" everywhere and offers With Photo Only to visitors too', function () {
    $public = file_get_contents(resource_path('views/search/public.blade.php'));
    $member = file_get_contents(resource_path('views/search/index.blade.php'));
    $layout = file_get_contents(resource_path('views/components/layouts/app.blade.php'));

    foreach ([$public, $member, $layout] as $view) {
        expect($view)->not->toContain('Partner Search')->not->toContain('Advance Search');
    }
    expect($member)->toContain('Advanced Search')
        ->and($public)->toContain('name="with_photo" value="1"')
        ->and(file_get_contents(app_path('Http/Controllers/SearchController.php')))
        ->toContain("COALESCE(NULLIF(profile_photo_privacy, ''), NULLIF(privacy_level, ''), 'visible_to_all') = 'visible_to_all'");
});

it('moved Advanced Search to /search/advanced-search and sends the old address there for good', function () {
    expect(route('search.advance', [], false))->toBe('/search/advanced-search');

    $this->get('/search/advance-search')->assertStatus(301)->assertRedirect('/search/advanced-search');
    $this->get('/search/advance-search?gender=female&with_photo=1')->assertStatus(301)
        ->assertRedirect('/search/advanced-search?gender=female&with_photo=1');
});

it('sends everyone who opens /search to Quick Search, and old /search links to their page', function () {
    $this->get('/search')->assertStatus(302)->assertRedirect('/search/quick-search');
    // old member links with a search in them now live at the shared addresses
    $this->get('/search?keyword=teacher')->assertStatus(301)->assertRedirect('/search/keyword-search?keyword=teacher');
    $this->get('/search?tab=byid&matri_id=KM100123')->assertStatus(301)->assertRedirect('/search/id-search?matri_id=KM100123');
    $this->get('/search?search=1&age_from=25')->assertStatus(301)->assertRedirect('/search/advanced-search?search=1&age_from=25');
    // a visitor opening a member's results link sees the visitor results
    $this->get('/search/advanced-search?search=1&age_from=25')->assertRedirect('/search/results?age_from=25');
    $this->get('/dashboard')->assertRedirect('/login'); // other member pages still ask to log in
});

it('lets a visitor save a search by logging in and coming back to it', function () {
    $this->get('/search/save?gender=female&age_from=25')->assertRedirect('/login')
        ->assertSessionHas('url.intended', route('search.advance', ['gender' => 'female', 'age_from' => 25, 'search' => 1]));
    expect(file_get_contents(resource_path('views/search/public-results.blade.php')))->toContain("route('search.save-later', request()->except(['page']))")->toContain('Save this search');
});

it('sorts by relevance, newest or recently active only, for visitors too', function () {
    $sort = file_get_contents(resource_path('views/search/_sort.blade.php'));
    expect($sort)->toContain("'recently_active' => 'Recently Active'")->not->toContain('age_low')->not->toContain('Age: Low')
        ->and(file_get_contents(resource_path('views/search/public-results.blade.php')))->toContain("@include('search._sort'")
        ->and(file_get_contents(resource_path('views/search/results.blade.php')))->toContain("@include('search._sort'")
        ->and(file_get_contents(app_path('Http/Controllers/SearchController.php')))->not->toContain("'age_low' =>");
});

it('shows the same Search menu to visitors and members', function () {
    $layout = file_get_contents(resource_path('views/components/layouts/app.blade.php'));
    $items = ["route('search.quick') }}", "route('search.advance') }}", "route('search.keyword') }}", "route('search.byid') }}", "route('discover.hub') }}"];

    foreach ($items as $item) {
        expect(substr_count($layout, $item))->toBeGreaterThanOrEqual(4); // desktop + phone, member + visitor
    }
    expect($layout)->not->toContain('>Id Search<')
        ->not->toContain("route('search.index', ['tab' => 'keyword'])")
        ->and(substr_count($layout, "route('saved-searches.index') }}\" class"))->toBe(4);   // Saved Searches in the menu too

    // the same address shows the member page to members (no redirect), the same sidebar for both
    $controller = file_get_contents(app_path('Http/Controllers/SearchController.php'));
    expect($controller)->toContain("return \$this->index(request(), ['keyword' => 'keyword', 'byid' => 'byid'][\$tab] ?? 'partner');")
        ->and(file_get_contents(resource_path('views/search/_nav.blade.php')))->toContain("'saved' => ['Saved Searches'")->toContain("'discover' => ['Discover Profiles'")
        ->and(file_get_contents(resource_path('views/search/public.blade.php')))->toContain("@guest\n                <div class=\"bg-(--color-primary-light)")
        ->and(file_get_contents(base_path('routes/web.php')))->toContain("->name('search.advance')->middleware('profile.complete.member')");
});

it('starts members at their own range in Quick Search and does not ask them who they are looking for', function () {
    $view = file_get_contents(resource_path('views/search/public.blade.php'));
    $quick = substr($view, strpos($view, "x-show=\"activeTab === 'partner'\""), 9000);

    expect($quick)->toContain("@guest\n                                <div class=\"float-field\">\n                                    <select name=\"gender\">")
        ->toContain("request('age_from', \$quickDefaults['age_from'] ?? 21)")
        ->toContain("request('height_to', \$quickDefaults['height_to'] ?? '')");

    $woman = (new Profile())->forceFill(['gender' => 'female', 'date_of_birth' => now()->subYears(28)->format('Y-m-d'), 'height' => '160 cm - 5 ft 03 inch']);
    expect(\App\Http\Controllers\SearchController::memberSearchDefaults($woman))
        ->toMatchArray(['age_from' => 28, 'age_to' => 38, 'height_from' => '160 cm - 5 ft 03 inch']);
});

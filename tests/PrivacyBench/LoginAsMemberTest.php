<?php

use App\Models\AdminActivityLog;
use App\Models\Interest;
use App\Models\LoginAsSession;
use App\Models\LoginHistory;
use App\Models\Profile;
use App\Models\ProfileView;
use App\Models\User;
use App\Support\LoginAs;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Tests\PrivacyBench\Bench;

/*
| "Login as member" (11 Nov 2026): a staff account opens the member site as one
| member sees it. The promises tested here, on the ten bench members:
|   - only with the staff right AND two-step sign-in, never a staff account;
|   - view only: every button is refused and nothing is written;
|   - no footprints: no profile view, contact view, "last active", login
|     history, email or analytics for the member;
|   - staff see what THAT member may see and nothing more (third members'
|     chats and contacts stay closed);
|   - every visit is recorded and ends on Exit, on time, or with the staff
|     account's admin sign-in.
*/

beforeEach(function () {
    Bench::guard();
    Http::fake();
    $this->bench = Bench::build();
    foreach ($this->bench->m as $p) {
        $p->forceFill(['onboarding_completed' => true])->save();
    }

    $role = fn (string $slug) => DB::table('staff_roles')->where('slug', $slug)->value('id');
    $staff = fn (string $name, string $slug, bool $twoStep) => tap(User::create([
        'name' => $name, 'email' => strtolower(str_replace(' ', '-', $name)).'@bench.test', 'password' => bcrypt('x-login-as-pass-7'),
        'role' => 'admin', 'is_active' => true, 'staff_role_id' => $role($slug), 'email_verified_at' => now(),
    ]), fn (User $u) => $twoStep ? $u->saveAppAuthenticationSecret('BENCHSECRETBENCH') : null)->fresh();

    $this->owner = $staff('Owner Two Step', 'super_admin', true);
    $this->ownerNoTwoStep = $staff('Owner Plain', 'super_admin', false);
    $this->manager = $staff('Manager Two Step', 'manager', true);

    // a request with the test's session, as inside the admin panel
    request()->setLaravelSession(app('session.store'));
    $this->visit = function (User $staff, Profile $member, string $reason = 'Member phoned: cannot see her matches'): LoginAsSession {
        auth('admin')->setUser($staff);

        return LoginAs::start($staff, $member, $reason);
    };
    $this->counts = fn (): array => [
        'profile_views' => ProfileView::count(),
        'contact_views' => DB::table('contact_views')->count(),
        'interests' => Interest::count(),
        'interest_replies' => DB::table('interest_replies')->count(),
        'notifications' => DB::table('notifications')->count(),
        'login_history' => LoginHistory::count(),
        'shortlists' => DB::table('shortlists')->count(),
        'blocked' => DB::table('blocked_profiles')->count(),
        'jobs' => DB::table('jobs')->count(),
    ];
});

it('is refused without the staff right, without two-step sign-in, and for staff accounts', function () {
    $t = $this->bench->m['T'];

    expect(LoginAs::refusal($this->owner, $t))->toBeNull()
        ->and(LoginAs::refusal($this->ownerNoTwoStep, $t))->toContain('two-step')
        ->and(LoginAs::refusal($this->manager, $t))->toContain('staff role')   // off for every role by default
        ->and(LoginAs::refusal($t->user, $this->bench->m['V']))->toContain('staff role') // a member is not staff
        ->and(LoginAs::refusal(null, $t))->toContain('staff role');

    // the right, switched on for the role, opens it (two-step still needed)
    DB::table('staff_role_permissions')->updateOrInsert(
        ['staff_role_id' => $this->manager->staff_role_id, 'permission_key' => LoginAs::PERMISSION],
        ['scope' => 'yes', 'created_at' => now(), 'updated_at' => now()],
    );
    expect(LoginAs::refusal($this->manager->fresh(), $t))->toBeNull();

    // never a staff account, never a deleted member
    $staffProfile = new Profile(['matri_id' => 'XX1']);
    $staffProfile->setRelation('user', $this->ownerNoTwoStep);
    expect(LoginAs::refusal($this->owner, $staffProfile))->toContain('Staff accounts');
    $t->delete();
    expect(LoginAs::refusal($this->owner, Profile::withTrashed()->find($t->id)))->toContain('deleted');

    expect(fn () => ($this->visit)($this->ownerNoTwoStep, $this->bench->m['V']))->toThrow(DomainException::class);
    expect(fn () => ($this->visit)($this->owner, $this->bench->m['V'], 'x'))->toThrow(DomainException::class, 'why');
    expect(LoginAsSession::count())->toBe(0)->and(auth('web')->check())->toBeFalse();
});

it('keeps the new right off for every seeded role except the owner\'s', function () {
    $defaults = (function () {
        return $this->defaultPermissions();
    })->call(new \Database\Seeders\StaffRolesSeeder);
    $on = collect($defaults)->filter(fn ($perms) => ($perms[LoginAs::PERMISSION] ?? 'no') === 'yes')->keys()->all();

    expect($on)->toBe(['super_admin'])
        ->and(config('permissions.permissions.'.LoginAs::PERMISSION.'.type'))->toBe('simple');
});

it('shows the member site as the member, records the visit, and leaves no footprint', function () {
    $t = $this->bench->m['T'];
    $va = $this->bench->m['VA']; // accepted interest with T: T may open his contact, and has not yet
    $before = ($this->counts)();
    $lastActive = DB::table('users')->where('id', $t->user_id)->value('last_login_at');
    DB::table('users')->where('id', $t->user_id)->update(['reengagement_level' => 2]);
    $completion = DB::table('profiles')->where('id', $t->id)->value('profile_completion_pct');

    $visit = ($this->visit)($this->owner, $t);

    $dashboard = (string) $this->get('/dashboard')->assertOk()->getContent();
    expect($dashboard)->toContain('Staff view of '.$t->matri_id)->toContain('view only')
        ->toContain('login-as/exit')
        ->not->toContain('posthog.init')->not->toContain('googletagmanager');

    $profile = (string) $this->get('/profile/'.$va->matri_id.'?tab=contact')->assertOk()->getContent();
    expect($profile)->toContain('Staff view of '.$t->matri_id);
    foreach (['/interests', '/chats', '/matches', '/views', '/notifications', '/settings', '/profile', '/membership-plans', '/search/quick-search'] as $page) {
        $this->get($page)->assertOk();
    }

    // nothing was written for the member or anyone she looked at
    expect(($this->counts)())->toBe($before)
        ->and(DB::table('users')->where('id', $t->user_id)->value('last_login_at'))->toBe($lastActive)
        ->and((int) DB::table('users')->where('id', $t->user_id)->value('reengagement_level'))->toBe(2)
        ->and(DB::table('profiles')->where('id', $t->id)->value('profile_completion_pct'))->toBe($completion)
        ->and(session(\App\Support\Analytics::SESSION_KEY))->toBeNull();

    // the visit itself is on record: who, whom, why, which pages
    $visit->refresh();
    $log = AdminActivityLog::where('action', 'login_as_started')->latest('id')->first();
    expect($visit->staff_user_id)->toBe($this->owner->id)
        ->and($visit->matri_id)->toBe($t->matri_id)
        ->and($visit->reason)->toBe('Member phoned: cannot see her matches')
        ->and($visit->pages_opened)->toBe(11)
        ->and(collect($visit->pages)->pluck('p')->all())->toContain('/dashboard', '/profile/'.$va->matri_id, '/chats')
        ->and($visit->isOpen())->toBeTrue()
        ->and($log->admin_user_id)->toBe($this->owner->id)
        ->and($log->model_id)->toBe($t->id)
        ->and($log->changes['reason'])->toBe($visit->reason);

    // control: the same page opened by the member herself does leave the footprints
    $this->post('/login-as/exit')->assertRedirect();
    app('auth')->forgetGuards();
    $this->actingAs($t->user->fresh(), 'web')->get('/profile/'.$va->matri_id.'?tab=contact')->assertOk();
    expect(ProfileView::count())->toBe($before['profile_views'] + 1)
        ->and(DB::table('contact_views')->count())->toBe($before['contact_views'] + 1);
});

it('refuses every button: nothing can be changed, sent, paid for or deleted', function () {
    $t = $this->bench->m['T'];
    $w = $this->bench->m['W'];
    $v = $this->bench->m['V'];
    $before = ($this->counts)();
    $row = fn () => [
        DB::table('users')->where('id', $t->user_id)->first(['password', 'email', 'phone', 'notification_preferences']),
        DB::table('profiles')->where('id', $t->id)->first(['is_hidden', 'is_active', 'deleted_at', 'about_me', 'height']),
        Interest::find($this->bench->interests['VS_T']->id)->status,
    ];
    $was = $row();
    $visit = ($this->visit)($this->owner, $t);

    $presses = [
        ['post', '/interests/send/'.$v->matri_id, ['message' => 'hello']],
        ['post', '/interests/'.$this->bench->interests['VS_T']->id.'/accept', []],
        ['post', '/interests/'.$this->bench->interests['VS_T']->id.'/decline', []],
        ['post', '/interests/'.$this->bench->interests['VA_T']->id.'/message', ['message' => 'from staff']],
        ['post', '/shortlist/'.$v->matri_id, []],
        ['post', '/block/'.$v->matri_id, []],
        ['post', '/profile/primary', ['about_me' => 'changed by staff', 'height' => '150', 'marital_status' => 'Unmarried', 'mother_tongue' => 'Tulu']],
        ['post', '/settings/password', ['current_password' => 'bench-pass-123', 'new_password' => 'staff-set-9', 'new_password_confirmation' => 'staff-set-9']],
        ['post', '/settings/hide', []],
        ['post', '/settings/delete', ['reason' => 'x']],
        ['post', '/settings/alerts', []],
        ['post', '/membership-plans/checkout', ['plan_id' => 1]],
        ['post', '/notifications/read-all', []],
        ['post', '/photo-requests/'.$v->matri_id.'/send', []],
        ['post', '/saved-searches', ['name' => 'by staff']],
        ['post', '/report/'.$v->matri_id, ['reason' => 'x']],
    ];
    foreach ($presses as [$method, $url, $data]) {
        $this->from('/dashboard')->{$method}($url, $data)->assertRedirect('/dashboard')->assertSessionHas('login_as_notice');
    }
    // the app-style (JSON) press gets a plain refusal
    $this->postJson('/shortlist/'.$w->matri_id)->assertStatus(403)->assertJsonFragment(['message' => \App\Http\Middleware\LoginAsViewOnly::NOTICE]);
    // one-tap sign-in links (as another member) are closed during a visit
    $this->get(\Illuminate\Support\Facades\URL::signedRoute('email.sign-in', ['user' => $v->user_id]))->assertRedirect();

    expect(($this->counts)())->toBe($before)
        ->and($row())->toEqual($was)
        ->and(auth('web')->id())->toBe($t->user_id)
        ->and($visit->fresh()->blocked_actions)->toBe(count($presses) + 2);

    // and the bar says so on the next page
    expect((string) $this->get('/dashboard')->getContent())->toContain('Staff view of '.$t->matri_id);
});

it('shows staff only what that member may see: third members stay closed', function () {
    // as V: T's chats with VA and with C, and every contact V has not been given, must not appear
    $v = $this->bench->m['V'];
    ($this->visit)($this->owner, $v);

    $problems = [];
    $pages = ['/dashboard', '/interests', '/chats', '/matches', '/views', '/shortlist', '/notifications', '/profile'];
    foreach (['T', 'TP', 'VA', 'C', 'W'] as $key) {
        $pages[] = '/profile/'.$this->bench->m[$key]->matri_id;
        $pages[] = '/profile/'.$this->bench->m[$key]->matri_id.'?tab=contact';
    }
    foreach ($pages as $page) {
        $res = $this->get($page);
        if ($res->getStatusCode() !== 200) {
            continue; // same-gender / hidden profiles are refused, as for the member
        }
        $problems = array_merge($problems, $this->bench->scan((string) $res->getContent(), $v, 'staff as V '.$page));
    }
    expect($problems)->toBe([]);

    // a hidden member's and a blocked pair's pages are refused to staff exactly as to the member
    $this->get('/profile/'.$this->bench->m['TH']->matri_id)->assertOk(); // TH wrote to V before hiding: V may see her
    ($this->visit)($this->owner, $this->bench->m['VB']);                 // T blocked VB
    expect($this->get('/profile/'.$this->bench->m['T']->matri_id)->getStatusCode())->toBe(404);
    // the interest between C and T is not V's or VB's to open
    expect($this->get('/interests/'.$this->bench->interests['C_T']->id)->getStatusCode())->toBeIn([403, 404]);
});

it('ends on Exit, on Logout, after the time limit, and with the staff sign-in', function () {
    $t = $this->bench->m['T'];
    $admin = '/'.trim((string) config('app.admin_path', 'admin'), '/');
    $token = DB::table('users')->where('id', $t->user_id)->value('remember_token');

    // Exit
    $visit = ($this->visit)($this->owner, $t);
    $this->get('/dashboard')->assertOk();
    $this->post('/login-as/exit')->assertRedirect($admin.'/users/'.$t->id);
    expect(auth('web')->check())->toBeFalse()
        ->and(auth('admin')->id())->toBe($this->owner->id)          // still signed in to the panel
        ->and(session(LoginAs::SESSION_KEY))->toBeNull()
        ->and($visit->fresh()->ended_how)->toBe('exit')
        ->and(AdminActivityLog::where('action', 'login_as_ended')->count())->toBe(1)
        // the member's own "remember me" on her phone is untouched
        ->and(DB::table('users')->where('id', $t->user_id)->value('remember_token'))->toBe($token);
    app('auth')->forgetGuards();
    $this->get('/dashboard')->assertRedirect(); // a visitor again: sent to sign in

    // the member menu's Logout ends the visit too
    $visit = ($this->visit)($this->owner, $t);
    $this->post('/logout')->assertRedirect($admin.'/users/'.$t->id);
    expect($visit->fresh()->ended_how)->toBe('exit')->and(auth('web')->check())->toBeFalse();
    app('auth')->forgetGuards();

    // the time limit
    $visit = ($this->visit)($this->owner, $t);
    $this->travel(LoginAs::MINUTES - 1)->minutes();
    $this->get('/dashboard')->assertOk();
    $this->travel(2)->minutes();
    $this->get('/dashboard')->assertRedirect($admin.'/users/'.$t->id);
    expect($visit->fresh()->ended_how)->toBe('expired')->and(auth('web')->check())->toBeFalse();
    $this->travelBack();
    app('auth')->forgetGuards();

    // the staff account signs out of the admin panel (or loses the right): the member site closes
    $visit = ($this->visit)($this->owner, $t);
    auth('admin')->logoutCurrentDevice();
    $this->get('/dashboard')->assertRedirect();
    expect($visit->fresh()->ended_how)->toBe('staff_signed_out')->and(auth('web')->check())->toBeFalse();
    app('auth')->forgetGuards();

    // opening a second member closes the first visit
    $first = ($this->visit)($this->owner, $t);
    $second = ($this->visit)($this->owner, $this->bench->m['V']);
    expect($first->fresh()->ended_how)->toBe('replaced')
        ->and($second->fresh()->isOpen())->toBeTrue()
        ->and(auth('web')->id())->toBe($this->bench->m['V']->user_id)
        ->and(LoginHistory::whereIn('user_id', [$t->user_id, $this->bench->m['V']->user_id])->count())->toBe(0);
});

it('never emails anyone because staff opened a page', function () {
    Route::middleware('web')->get('/__login-as-mail-probe', function () {
        \Illuminate\Support\Facades\Mail::raw('probe', fn ($m) => $m->to('probe@bench.test')->subject('probe'));

        return 'sent';
    });
    $sent = [];
    \Illuminate\Support\Facades\Event::listen(\Illuminate\Mail\Events\MessageSent::class, function ($e) use (&$sent) {
        $sent[] = $e->message->getSubject();
    });

    ($this->visit)($this->owner, $this->bench->m['T']);
    $this->get('/__login-as-mail-probe')->assertOk();
    expect($sent)->toBe([]);

    // the same page outside a visit does send (the probe works)
    $this->post('/login-as/exit');
    app('auth')->forgetGuards();
    $this->get('/__login-as-mail-probe')->assertOk();
    expect($sent)->toBe(['probe']);
});

it('leaves the admin panel working while a visit is open', function () {
    $t = $this->bench->m['T'];
    $admin = '/'.trim((string) config('app.admin_path', 'admin'), '/');
    ($this->visit)($this->owner, $t);

    // the panel is the staff account's own work: not view-only, not counted as a member page
    $this->actingAs($this->owner, 'admin')->get($admin.'/users')->assertOk();
    expect(LoginAs::active())->toBeFalse()
        ->and(LoginAsSession::latest('id')->first()->pages_opened)->toBe(0);

    // Audit Trail → Login as Member lists the visit, with its reason
    $page = (string) $this->get($admin.'/audit-trail/login-as-sessions')->assertOk()->getContent();
    expect($page)->toContain($t->matri_id)->toContain('Member phoned: cannot see her matches');
});

it('has the button on the members list only for accounts with the right', function () {
    \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('admin'));
    $t = $this->bench->m['T'];

    $this->actingAs($this->manager, 'admin');
    \Livewire\Livewire::test(\App\Filament\Resources\UserResource\Pages\ListUsers::class)
        ->assertTableActionHidden('loginAsMember', $t);

    $this->actingAs($this->owner, 'admin');
    \Livewire\Livewire::test(\App\Filament\Resources\UserResource\Pages\ListUsers::class)
        ->assertTableActionVisible('loginAsMember', $t)
        ->callTableAction('loginAsMember', $t, data: ['reason' => 'Member phoned: plan not showing'])
        ->assertRedirect('/dashboard');

    $visit = LoginAsSession::latest('id')->first();
    expect($visit->reason)->toBe('Member phoned: plan not showing')
        ->and($visit->staff_user_id)->toBe($this->owner->id)
        ->and($visit->member_user_id)->toBe($t->user_id);
});

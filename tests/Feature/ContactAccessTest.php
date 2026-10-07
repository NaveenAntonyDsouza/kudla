<?php

use App\Models\ContactInfo;
use App\Models\Interest;
use App\Models\Profile;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\InterestService;
use App\Services\ProfileAccessService;
use App\Support\ContactAccess;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schema;

/*
| View Contact made easy to find and use (owner's request, 7 Oct 2026):
| one answer for "can I see their phone / WhatsApp, and if not, why" across
| the profile panel, Contact tab, phone bar, chat page and Chats list.
| Numbers only ever leave the server in the OPEN state.
*/

class CaFakeAccess extends ProfileAccessService
{
    public bool $contact = false;
    public bool $access = true;

    public function __construct() {}

    public function canViewContact(Profile $viewer, Profile $target): bool
    {
        return $this->contact;
    }

    public function canAccess(Profile $viewer, Profile $target): bool
    {
        return $this->access;
    }
}

class CaFakeInterests extends InterestService
{
    public ?array $status = null;

    public function __construct() {}

    public function getStatus(Profile $viewer, Profile $other): ?array
    {
        return $this->status;
    }
}

beforeEach(function () {
    Schema::create('site_settings', function (Blueprint $t) {
        $t->id();
        $t->string('key')->unique();
        $t->text('value')->nullable();
        $t->timestamps();
    });
    SiteSetting::setValue('site_name', 'Kudla Matrimony');
    SiteSetting::setValue('whatsapp', '+91 77600 20186'); // the office number — must never stand in for a member's

    $this->access = new CaFakeAccess();
    $this->interests = new CaFakeInterests();
    app()->instance(ProfileAccessService::class, $this->access);
    app()->instance(InterestService::class, $this->interests);
    caFreshRequest();
});

afterEach(function () {
    Schema::dropIfExists('site_settings');
});

function caFreshRequest(): void
{
    $request = Request::create('/profile/x');
    $request->setLaravelSession(app('session.store'));
    app()->instance('request', $request);
}

function caProfile(int $id, string $matriId, array $contact = [], ?string $userPhone = null): Profile
{
    $p = new Profile(['matri_id' => $matriId, 'gender' => $id % 2 ? 'male' : 'female']);
    $p->id = $id;
    $p->setRelation('contactInfo', $contact ? new ContactInfo($contact) : null);
    $p->setRelation('socialMediaLink', null);
    $user = new User(['name' => 'Member '.$id, 'phone' => $userPhone]);
    $user->id = $id + 100;
    $p->setRelation('user', $user);

    return $p;
}

it('opens with tap-ready Call and WhatsApp links and a short intro using Matri IDs only', function () {
    $this->access->contact = true;
    $me = caProfile(1, 'KM100001');
    $them = caProfile(2, 'KM100002', ['primary_phone' => '98765 43210', 'whatsapp_number' => '99887 76655', 'contact_person' => 'Ramesh', 'contact_relationship' => 'Father', 'preferred_call_time' => 'Evening']);

    $r = ContactAccess::for($me, $them);

    expect($r['state'])->toBe(ContactAccess::OPEN)
        ->and($r['contact'])->toMatchArray([
            'tel' => 'tel:+919876543210',
            'phone_display' => '+91 98765 43210',
            'whatsapp_display' => '+91 99887 76655',
            'whatsapp_is_phone' => false,
            'person' => 'Ramesh', 'relationship' => 'Father', 'best_time' => 'Evening',
        ])
        ->and($r['contact']['whatsapp_link'])->toStartWith('https://wa.me/919988776655?text=')
        ->and(rawurldecode($r['contact']['whatsapp_link']))->toContain("I'm KM100001 from Kudla Matrimony")->toContain('KM100002')
        ->not->toContain('Member 1');
});

it('uses the mobile for WhatsApp when no separate number was given, and never the office number', function () {
    $this->access->contact = true;
    $me = caProfile(1, 'KM100001');

    $mobileOnly = ContactAccess::for($me, caProfile(2, 'KM100002', ['primary_phone' => '9876543210']));
    $nothing = ContactAccess::for($me, caProfile(4, 'KM100004', ['primary_phone' => null, 'whatsapp_number' => null]));
    $registeredPhone = ContactAccess::for($me, caProfile(6, 'KM100006', [], userPhone: '9123456780'));

    expect($mobileOnly['contact'])->toMatchArray(['whatsapp_is_phone' => true, 'whatsapp_display' => '+91 98765 43210'])
        ->and($nothing['contact'])->toMatchArray(['tel' => null, 'whatsapp_link' => null, 'phone_display' => null])
        ->and(json_encode($nothing))->not->toContain('7760020186')
        ->and($registeredPhone['contact']['tel'])->toBe('tel:+919123456780');
});

it('explains every locked state and never carries the numbers', function (?array $status, bool $canAccess, string $expected) {
    $this->access->access = $canAccess;
    $this->interests->status = $status;
    $them = caProfile(2, 'KM100002', ['primary_phone' => '9876543210', 'whatsapp_number' => '9988776655']);

    $r = ContactAccess::for(caProfile(1, 'KM100001'), $them);

    expect($r['state'])->toBe($expected)
        ->and($r['contact'])->toBeNull()
        ->and(ContactAccess::lockedReason($expected))->not->toBe('');
})->with([
    'no interest yet' => [null, true, ContactAccess::NO_INTEREST],
    'sent, waiting' => [['direction' => 'sent', 'status' => 'pending'], true, ContactAccess::SENT_PENDING],
    'they sent one' => [['direction' => 'received', 'status' => 'pending'], true, ContactAccess::RECEIVED_PENDING],
    'accepted, free plan' => [['direction' => 'sent', 'status' => 'accepted'], true, ContactAccess::NEEDS_PLAN],
    'declined' => [['direction' => 'sent', 'status' => 'declined'], true, ContactAccess::DECLINED],
    'blocked / hidden' => [null, false, ContactAccess::UNAVAILABLE],
]);

it('treats your own profile as yours', function () {
    $me = caProfile(1, 'KM100001', ['primary_phone' => '9876543210']);

    expect(ContactAccess::for($me, $me)['state'])->toBe(ContactAccess::OWN);
});

it('renders the Contact tab locked with one next step, and open with WhatsApp first', function () {
    $me = caProfile(1, 'KM100001');
    Auth::setUser($me->user->setRelation('profile', $me));
    $them = caProfile(2, 'KM100002', ['primary_phone' => '9876543210', 'whatsapp_number' => '9988776655', 'preferred_call_time' => 'Evening']);
    $them->setRelation('socialMediaLink', new App\Models\SocialMediaLink([
        'instagram_url' => 'https://www.instagram.com/asha.k/',
        'linkedin_url' => 'javascript:alert(1)', // never rendered as a link
    ]));
    $vars = ['profile' => $them, 'user' => $them->user, 'isOwn' => false, 'interestStatus' => null];

    $locked = Blade::render("@include('profile.preview.contact')", $vars);

    $this->access->contact = true;
    caFreshRequest();
    $open = Blade::render("@include('profile.preview.contact')", $vars);

    expect($locked)->toContain('Want to talk? Send an interest first')
        ->toContain("open-interest-modal', { source: 'contact_tab' }")
        ->toContain('Mobile number')->toContain('Best time to call')
        ->not->toContain('9876543210')->not->toContain('98765')->not->toContain('wa.me/91998')
        // social handles: only the name while locked, the link once connected
        ->toContain('&#128279; Instagram')->not->toContain('instagram.com/asha')
        ->not->toContain('javascript:')->not->toContain('LinkedIn')
        ->and($open)->toContain('href="https://www.instagram.com/asha.k/"')->toContain('>instagram.com/asha.k</a>')
        ->not->toContain('javascript:')
        ->toContain('https://wa.me/919988776655?text=')
        ->toContain('tel:+919876543210')
        ->toContain('Best time to call: <strong>Evening</strong>')
        ->toContain('Report a concern')
        ->and(strpos($open, 'cb-wa'))->toBeLessThan(strpos($open, 'cb-call'));
});

it('fits all four profile tabs on a phone and hides the help button next to a member\'s WhatsApp', function () {
    $page = file_get_contents(resource_path('views/profile/preview.blade.php'));
    $buttons = file_get_contents(resource_path('views/components/contact-buttons.blade.php'));

    expect($page)->toContain("'short' => 'Contact'")
        ->toContain('.pv-tab { position: relative; flex: 1 1 0;')
        ->toContain('x-on:show-contact-tab.window="showContactTab()"')
        ->toContain('<div class="sticky top-24" style="z-index: 10;">')
        ->and($buttons)->toContain('.wa-fab { display: none !important; }');
});

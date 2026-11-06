<?php

use App\Filament\Resources\AdvertisementResource\Pages\CreateAdvertisement;
use App\Filament\Resources\LeadResource\Pages\EditLead;
use App\Filament\Resources\LeadResource\Pages\ListLeads;
use App\Filament\Resources\LeadResource\RelationManagers\CallLogsRelationManager;
use App\Filament\Resources\MembershipPlanResource\Pages\CreateMembershipPlan;
use App\Filament\Resources\MembershipPlanResource\Pages\EditMembershipPlan;
use App\Filament\Resources\StaticPageResource\Pages\CreateStaticPage;
use App\Filament\Resources\StaticPageResource\Pages\EditStaticPage;
use App\Models\Lead;
use App\Models\MembershipPlan;
use App\Models\SiteSetting;
use App\Models\StaticPage;
use App\Models\User;
use App\Support\AdminModules;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\PrivacyBench\Bench;

/*
| Filament 5 leftovers, 6 Nov 2026: these admin forms still type-hinted Filament 3's
| Filament\Forms\Get / Filament\Forms\Set, which no longer exist. Filament hands the
| closures Filament\Schemas\Components\Utilities\Get / Set by parameter name, so PHP threw
| a TypeError as soon as one ran: typing a plan name or a page title, opening the
| advertisement form, opening a call log form. Each test changes the field and renders
| the form again.
*/

beforeEach(function () {
    Bench::guard();
    Mail::fake();
    Http::fake();
    $this->staff = User::create(['name' => 'Form Staff', 'email' => 'form-staff@bench.test', 'password' => bcrypt('x-staff-pass-5'),
        'role' => 'admin', 'is_active' => true, 'staff_role_id' => DB::table('staff_roles')->where('slug', 'super_admin')->value('id'), 'email_verified_at' => now()]);
    $this->actingAs($this->staff, 'admin');
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('fills the plan slug from the plan name on a new plan, and keeps the slug when a plan is renamed', function () {
    Livewire::test(CreateMembershipPlan::class)
        ->fillForm(['plan_name' => 'Gold Plus 6 Months'])
        ->assertFormSet(['slug' => 'gold-plus-6-months'])
        ->assertSuccessful();

    $plan = MembershipPlan::create(['plan_name' => 'Bench Silver', 'slug' => 'bench-silver', 'duration_months' => 3, 'price_inr' => 999]);
    Livewire::test(EditMembershipPlan::class, ['record' => $plan->getRouteKey()])
        ->fillForm(['plan_name' => 'Bench Silver Renamed'])
        ->assertFormSet(['plan_name' => 'Bench Silver Renamed', 'slug' => 'bench-silver'])
        ->assertSuccessful();
});

it('fills the page slug from the page title on a new page, and keeps the slug when a page is renamed', function () {
    Livewire::test(CreateStaticPage::class)
        ->fillForm(['title' => 'Bench Refund Policy'])
        ->assertFormSet(['slug' => 'bench-refund-policy'])
        ->assertSuccessful();

    $page = StaticPage::create(['slug' => 'bench-page', 'title' => 'Bench Page', 'content' => '<p>Bench</p>', 'is_active' => true]);
    Livewire::test(EditStaticPage::class, ['record' => $page->getRouteKey()])
        ->fillForm(['title' => 'Bench Page Renamed'])
        ->assertFormSet(['title' => 'Bench Page Renamed', 'slug' => 'bench-page'])
        ->assertSuccessful();
});

it('shows the image fields for an image ad and the code box for an HTML ad', function () {
    SiteSetting::setValue(AdminModules::ADVERTISEMENTS, '1');

    Livewire::test(CreateAdvertisement::class)
        ->assertSuccessful()
        ->assertFormSet(['type' => 'image'])
        ->assertFormFieldVisible('image_path')
        ->assertFormFieldVisible('click_url')
        ->assertFormFieldHidden('html_code')
        ->fillForm(['type' => 'html'])
        ->assertFormFieldHidden('image_path')
        ->assertFormFieldHidden('click_url')
        ->assertFormFieldVisible('html_code')
        ->assertSuccessful();
});

it('shows the follow-up date on a call log only when a follow-up is needed, from the leads list and from a lead', function () {
    SiteSetting::setValue(AdminModules::LEADS, '1');
    $lead = Lead::create(['full_name' => 'Bench Lead', 'phone' => '9812399001', 'source' => 'phone', 'status' => 'new']);

    // leads list: the "Add Call Log" button on a lead's row
    Livewire::test(ListLeads::class)
        ->mountAction(TestAction::make('addCallLog')->table($lead))
        ->assertFormFieldHidden('follow_up_date')
        ->fillForm(['follow_up_required' => true])
        ->assertFormFieldVisible('follow_up_date')
        ->assertSuccessful();

    // a lead's page: the Call Logs list, "New Call Log"
    Livewire::test(CallLogsRelationManager::class, ['ownerRecord' => $lead, 'pageClass' => EditLead::class])
        ->mountAction(TestAction::make('create')->table())
        ->assertFormFieldHidden('follow_up_date')
        ->fillForm(['follow_up_required' => true])
        ->assertFormFieldVisible('follow_up_date')
        ->assertSuccessful();
});

<?php

namespace App\Support;

use App\Models\SiteSetting;

/**
 * On/off switches for the admin tools a single-office site doesn't use yet
 * (Oct 2026: no site had a second branch, a lead, a call or a target).
 * Switching one off hides its menus, pages, dashboard widgets, form fields
 * and staff roles; the data and the code stay, so switching it back on in
 * Settings → Site Settings → Staff tools brings everything back as it was.
 *
 * A tool nobody uses yet starts off (not set = off); a tool that was already
 * working before it got a switch starts on, so adding the switch changed
 * nothing on the live sites (Nov 2026).
 */
class AdminModules
{
    /** Branches, Branch Payouts, branch fields/filters, Branch Manager + Branch Staff roles. */
    public const BRANCHES = 'module_branches_enabled';

    /** Leads, call logs, telecaller dashboard, Telecaller role. */
    public const LEADS = 'module_leads_enabled';

    /** Staff Targets, Staff Performance report, target progress widget. */
    public const STAFF_TARGETS = 'module_staff_targets_enabled';

    /** Advertisements (banner ads sold to businesses); no site had one in Oct 2026. */
    public const ADVERTISEMENTS = 'module_advertisements_enabled';

    /** Recommend Matches (staff suggest matches to a member: assisted matchmaking). */
    public const RECOMMENDATIONS = 'module_recommendations_enabled';

    /** Verification Calls: every new member is called once, shared out among staff (Nov 2026). New: starts off. */
    public const VERIFICATION_CALLS = 'module_verification_calls_enabled';

    /** Assisted service: a relationship manager works a paying member's profile (Nov 2026). */
    public const ASSISTED = 'module_assisted_enabled';

    /** Staff Activity / My Activity: what each staff member did, day by day (Nov 2026). */
    public const STAFF_ACTIVITY = 'module_staff_activity_enabled';

    /** Login as member: staff open the member site as a member, view only and recorded (Nov 2026). */
    public const LOGIN_AS_MEMBER = 'module_login_as_member_enabled';

    public static function verificationCalls(): bool
    {
        return SiteSetting::getValue(self::VERIFICATION_CALLS, '0') === '1';
    }

    public static function assisted(): bool
    {
        return SiteSetting::getValue(self::ASSISTED, '1') === '1';
    }

    public static function staffActivity(): bool
    {
        return SiteSetting::getValue(self::STAFF_ACTIVITY, '1') === '1';
    }

    public static function loginAsMember(): bool
    {
        return SiteSetting::getValue(self::LOGIN_AS_MEMBER, '1') === '1';
    }

    public static function branches(): bool
    {
        return SiteSetting::getValue(self::BRANCHES, '0') === '1';
    }

    public static function leads(): bool
    {
        return SiteSetting::getValue(self::LEADS, '0') === '1';
    }

    public static function staffTargets(): bool
    {
        return SiteSetting::getValue(self::STAFF_TARGETS, '0') === '1';
    }

    public static function advertisements(): bool
    {
        return SiteSetting::getValue(self::ADVERTISEMENTS, '0') === '1';
    }

    public static function recommendations(): bool
    {
        return SiteSetting::getValue(self::RECOMMENDATIONS, '0') === '1';
    }

    /**
     * Staff roles that only make sense with a switched-off tool, so they
     * aren't offered when adding staff. Staff who already hold one keep it.
     */
    public static function hiddenRoleSlugs(): array
    {
        return array_merge(
            self::branches() ? [] : ['branch_manager', 'branch_staff'],
            self::leads() ? [] : ['telecaller'],
        );
    }
}

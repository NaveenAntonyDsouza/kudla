<?php

namespace App\Support;

use App\Models\SiteSetting;

/**
 * On/off switches for the admin tools a single-office site doesn't use yet
 * (Oct 2026: no site had a second branch, a lead, a call or a target).
 * Switching one off hides its menus, pages, dashboard widgets, form fields
 * and staff roles; the data and the code stay, so switching it back on in
 * Settings → Site Settings → Staff tools brings everything back as it was.
 * Not set means off, so new sites start without them too.
 */
class AdminModules
{
    /** Branches, Branch Payouts, branch fields/filters, Branch Manager + Branch Staff roles. */
    public const BRANCHES = 'module_branches_enabled';

    /** Leads, call logs, telecaller dashboard, Telecaller role. */
    public const LEADS = 'module_leads_enabled';

    /** Staff Targets, Staff Performance report, target progress widget. */
    public const STAFF_TARGETS = 'module_staff_targets_enabled';

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

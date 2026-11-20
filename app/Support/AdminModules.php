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

    /** Payment funnel: records the steps towards paying and paywall moments; Money → Payment Funnel (18 Nov 2026, on by default). */
    public const PAYMENT_INSIGHTS = 'module_payment_insights_enabled';

    /*
     * Member-site changes from the 16–17 Nov 2026 weekly review. Built off by default, then
     * switched on for every site and made ON by default by the owner (18 Nov 2026): not set
     * means on, so a new site gets them too. Switching one off stores '0'. Except Last seen:
     * off by default, on for Kudla only (owner, 18 Nov 2026).
     */

    /** Profile cards and the full profile say when a member was last seen, including members not seen since tracking began. */
    public const LAST_SEEN = 'module_last_seen_enabled';

    /** An address the mail server refuses outright at sending time is marked as not reaching the member. */
    public const REFUSED_ADDRESSES = 'module_refused_addresses_enabled';

    /** "Add your photo" bar also on the member's own profile and above search results, with a close button (3 days). */
    public const PHOTO_BAR_MORE = 'module_photo_bar_more_enabled';

    /** "Add your photo" pop-up, once a day, for members with no photo. */
    public const PHOTO_POPUP = 'module_photo_popup_enabled';

    /** Members who never saved partner preferences get matches from their own age, religion and height (17 Nov 2026). */
    public const SUGGESTED_PREFS = 'module_suggested_preferences_enabled';

    /** Free members see other members as "Priya D."; paid members see the full name (owner 20 Nov 2026, like Chavara). */
    public const FULL_NAME_PAID = 'module_full_name_paid_enabled';

    /* Pop-ups (19 Nov 2026), one a day at most with the photo pop-up first (DailyPopup). New: off by default. */

    /** "Families are waiting for your reply" pop-up. */
    public const WAITING_POPUP = 'module_waiting_popup_enabled';

    /** Plans pop-up for free members, at most once every 4 days. */
    public const PLANS_POPUP = 'module_plans_popup_enabled';

    /** WhatsApp Channel pop-up, once per member. */
    public const CHANNEL_POPUP = 'module_channel_popup_enabled';

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

    public static function paymentInsights(): bool
    {
        return SiteSetting::getValue(self::PAYMENT_INSIGHTS, '1') === '1';
    }

    public static function lastSeen(): bool
    {
        return SiteSetting::getValue(self::LAST_SEEN, '0') === '1';
    }

    public static function refusedAddresses(): bool
    {
        return SiteSetting::getValue(self::REFUSED_ADDRESSES, '1') === '1';
    }

    public static function photoBarMore(): bool
    {
        return SiteSetting::getValue(self::PHOTO_BAR_MORE, '1') === '1';
    }

    public static function photoPopup(): bool
    {
        return SiteSetting::getValue(self::PHOTO_POPUP, '1') === '1';
    }

    public static function suggestedPreferences(): bool
    {
        return SiteSetting::getValue(self::SUGGESTED_PREFS, '1') === '1';
    }

    public static function fullNamePaidOnly(): bool
    {
        return SiteSetting::getValue(self::FULL_NAME_PAID, '1') === '1';
    }

    public static function waitingPopup(): bool
    {
        return SiteSetting::getValue(self::WAITING_POPUP, '0') === '1';
    }

    public static function plansPopup(): bool
    {
        return SiteSetting::getValue(self::PLANS_POPUP, '0') === '1';
    }

    public static function channelPopup(): bool
    {
        return SiteSetting::getValue(self::CHANNEL_POPUP, '0') === '1';
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

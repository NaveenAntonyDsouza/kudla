<?php

namespace App\Support;

/**
 * Default preview text for each email template: the line an inbox shows
 * next to the subject. Admins can change it per site in Email Templates.
 *
 * Used by EmailTemplateSeeder (new installs) and by the migration that added
 * the column, which fills it only where the preview text is still empty.
 * Placeholders work as in the subject; values are plain text here.
 */
final class EmailPreheaders
{
    public const DEFAULTS = [
        'interest-received' => '{{SENDER_MATRI_ID}} has shown interest in your profile. See their profile and reply.',
        'interest-accepted' => '{{ACCEPTER_MATRI_ID}} accepted your interest. You can now start a conversation.',
        'interest-declined' => 'An update on your interest, and more profiles that match you.',
        'interest-reminder' => 'Members are waiting for your reply. Answer them in a minute.',
        'photo-request-received' => '{{REQUESTER_MATRI_ID}} would like to see your photos.',
        'photo-request-approved' => 'Your photo request was approved. You can now see their photos.',
        'photo-upload-requested' => 'A member would like you to add a photo to your profile.',
        'photo-added' => 'A member you asked has added a photo.',
        'welcome' => 'Your {{MEMBER_ID_LABEL}}, three things to do this week, and how to stay safe.',
        'password-reset' => 'Use the link inside to set a new password.',
        'password-changed' => 'Your password was just changed. If this was not you, act now.',
        'profile-approved' => 'Your profile is live. Members can now find you.',
        'profile-rejected' => 'A small change is needed before your profile goes live.',
        'photo-approved' => 'Your photo is approved and now on your profile.',
        'photo-rejected' => 'Please upload a different photo. Here is why.',
        'membership-activated' => 'Your {{PLAN_NAME}} plan is active. Here is what you can do now.',
        'membership-expiring' => 'Your {{PLAN_NAME}} plan ends on {{EXPIRY_DATE}}. Renew to keep your benefits.',
        'membership-expiring-tomorrow' => 'Your {{PLAN_NAME}} plan ends tomorrow. Renew to keep your benefits.',
        'membership-expired' => 'Your {{PLAN_NAME}} plan has ended. Renew any time to pick up where you left off.',
        'payment-reminder' => 'Your {{PLAN_NAME}} plan is one step away.',
        'staff_created_member_welcome' => 'Your account is ready. Here is how to log in.',
        'reengagement-7day' => 'New members who match you have joined since your last visit.',
        'reengagement-14day' => 'Members who match you are waiting to hear from you.',
        'reengagement-30day' => 'Your matches are still here. Log in to see who has joined.',
        'registration-reminder' => 'Finish your profile in a few minutes and start seeing matches.',
        'photo-reminder' => 'Add a photo so the right people notice your profile.',
        'weekly-match-suggestions' => 'This week\'s matches, picked for your preferences.',
    ];
}

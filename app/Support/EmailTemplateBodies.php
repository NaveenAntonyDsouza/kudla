<?php

namespace App\Support;

/**
 * Email template bodies that are shared between EmailTemplateSeeder (new
 * installs) and migrations that upgrade a default body on live sites.
 *
 * A migration replaces a body only while it still equals the previous
 * default (the *_V1 constant), so an admin's own edits are never overwritten.
 * Never put a real person's name in these: they go to every member of every
 * site; the sign-off is always the site's team.
 */
final class EmailTemplateBodies
{
    /** The original welcome body, as seeded before October 2026. */
    public const WELCOME_V1 = '<h1>Welcome to {{SITE_NAME}}!</h1><p>Dear {{USER_NAME}},</p><p>Thank you for registering with {{SITE_NAME}}. Your Matri ID is <strong>{{MATRI_ID}}</strong>.</p><p>Here\'s what to do next:</p><ul><li>Complete your profile to get more visibility</li><li>Upload your photos</li><li>Set your partner preferences</li><li>Start browsing profiles</li></ul><p><a href="{{ACTION_URL}}" style="display:inline-block;padding:10px 24px;background:#8B1D91;color:#fff;text-decoration:none;border-radius:6px;">Complete Your Profile</a></p><p>If you have any questions, feel free to contact us.</p><p>Warm regards,<br>{{SITE_NAME}} Team</p>';

    public const WELCOME = '<h1>Welcome to {{SITE_NAME}}</h1>'
        . '<p>Dear {{USER_NAME}},</p>'
        . '<p>Thank you for joining {{SITE_NAME}}. Your {{MEMBER_ID_LABEL}} is <strong>{{MATRI_ID}}</strong>. You can log in with it, your email address or your mobile number.</p>'
        . '<h2 style="font-size:17px;margin:24px 0 8px;color:#1a1a1a;">How matching works</h2>'
        . '<p>We suggest members who fit the partner preferences you set, and we suggest you to members whose preferences you fit. Completed profiles with a photo are shown first, so the more you fill in, the more people see you.</p>'
        . '<h2 style="font-size:17px;margin:24px 0 8px;color:#1a1a1a;">Three things to do this week</h2>'
        . '<ul>'
        . '<li><strong>Add a clear, recent photo.</strong> You decide who can see it.</li>'
        . '<li><strong>Write a few lines about yourself and your family.</strong> Honest, specific details help the right people recognise a good match.</li>'
        . '<li><strong>Check your partner preferences.</strong> Keep your must-haves firm and the rest open, so good matches are not filtered out.</li>'
        . '</ul>'
        . '<p><a href="{{ACTION_URL}}" style="display:inline-block;padding:10px 24px;background:{{PRIMARY_COLOR}};color:#fff;text-decoration:none;border-radius:6px;">Complete your profile</a></p>'
        . '<h2 style="font-size:17px;margin:24px 0 8px;color:#1a1a1a;">Stay safe</h2>'
        . '<ul>'
        . '<li>Talk on {{SITE_NAME}} first, and share your phone number only when you are comfortable.</li>'
        . '<li>Never send money or bank details to anyone you meet online, whatever the reason.</li>'
        . '<li>Meet in a public place, and tell someone you trust where you are going.</li>'
        . '<li>Report any profile that seems wrong. Our team reviews every report.</li>'
        . '</ul>'
        . '<p>Please add this email address to your contacts so our emails reach your inbox.</p>'
        . '<p>Warm regards,<br>Team {{SITE_NAME}}</p>';

    /** The original interest-received body, as seeded before October 2026. */
    public const INTEREST_RECEIVED_V1 = '<h1>New Interest Received</h1><p>Dear {{RECEIVER_NAME}},</p><p><strong>{{SENDER_MATRI_ID}}</strong> has expressed interest in your profile on {{SITE_NAME}}.</p><p>Log in to view their profile and respond to the interest.</p><p><a href="{{ACTION_URL}}" style="display:inline-block;padding:10px 24px;background:#8B1D91;color:#fff;text-decoration:none;border-radius:6px;">View Interest</a></p><p>Wishing you the best in your search,<br>{{SITE_NAME}}</p>';

    /** Accept and Decline open the interest page with that reply chosen; the member confirms there. */
    public const INTEREST_RECEIVED = '<h1>New interest received</h1>'
        . '<p>Dear {{RECEIVER_NAME}},</p>'
        . '<p><strong>{{SENDER_MATRI_ID}}</strong> has expressed interest in your profile on {{SITE_NAME}}.</p>'
        . '<p style="margin:4px 0 16px;color:#4a4a4a;">{{SENDER_SUMMARY}}</p>'
        . '<p><a href="{{ACCEPT_URL}}" style="display:inline-block;padding:10px 24px;background:#15803d;color:#fff;text-decoration:none;border-radius:6px;margin:0 8px 8px 0;">Accept</a>'
        . '<a href="{{DECLINE_URL}}" style="display:inline-block;padding:9px 23px;background:#fff;color:#b91c1c;border:1px solid #b91c1c;text-decoration:none;border-radius:6px;margin:0 0 8px;">Decline</a></p>'
        . '<p style="font-size:13px;color:#6b7280;">You will see their profile and confirm your reply on {{SITE_NAME}}. <a href="{{ACTION_URL}}">View their profile</a></p>'
        . '<p>Wishing you the best in your search,<br>Team {{SITE_NAME}}</p>';

    public const INTEREST_REMINDER = '<h1>{{WAITING_LINE}}</h1>'
        . '<p>Dear {{USER_NAME}},</p>'
        . '<p>These members sent you an interest and haven\'t heard back yet. A reply, even a polite no, helps them move on, and it takes a minute.</p>'
        . '{{PENDING_LIST_HTML}}'
        . '<p><a href="{{INBOX_URL}}" style="display:inline-block;padding:10px 24px;background:{{PRIMARY_COLOR}};color:#fff;text-decoration:none;border-radius:6px;">See all interests</a></p>'
        . '<p>Warm regards,<br>Team {{SITE_NAME}}</p>'
        . '<hr style="border:none;border-top:1px solid #e5e7eb;margin:2rem 0 1rem;">'
        . '<p style="font-size:0.75rem;color:#6b7280;">Don\'t want emails about interests? <a href="{{UNSUBSCRIBE_URL}}" style="color:#6b7280;">Unsubscribe</a>.</p>';

    public const PAYMENT_REMINDER = '<h1>Your {{PLAN_NAME}} plan is one step away</h1>'
        . '<p>Dear {{USER_NAME}},</p>'
        . '<p>You started upgrading to the <strong>{{PLAN_NAME}}</strong> plan on {{SITE_NAME}}, but the payment wasn\'t completed.</p>'
        . '<p>If something went wrong, you can try again here:</p>'
        . '<p><a href="{{PLANS_URL}}" style="display:inline-block;padding:10px 24px;background:{{PRIMARY_COLOR}};color:#fff;text-decoration:none;border-radius:6px;">Complete your payment</a></p>'
        . '<p>If money left your account but your plan isn\'t active, reply to this email with the time of payment and we\'ll sort it out. {{HELP_LINE}}</p>'
        . '<p>Warm regards,<br>Team {{SITE_NAME}}</p>'
        . '<hr style="border:none;border-top:1px solid #e5e7eb;margin:2rem 0 1rem;">'
        . '<p style="font-size:0.75rem;color:#6b7280;">Don\'t want emails about plans and offers? <a href="{{UNSUBSCRIBE_URL}}" style="color:#6b7280;">Unsubscribe</a>.</p>';

    public const MEMBERSHIP_ENDING_TOMORROW = '<h1>Your plan ends tomorrow</h1>'
        . '<p>Dear {{USER_NAME}},</p>'
        . '<p>Your <strong>{{PLAN_NAME}}</strong> plan on {{SITE_NAME}} ends on {{EXPIRY_DATE}}. Renew now to keep your premium features without a break.</p>'
        . '<p><a href="{{ACTION_URL}}" style="display:inline-block;padding:10px 24px;background:{{PRIMARY_COLOR}};color:#fff;text-decoration:none;border-radius:6px;">Renew my plan</a></p>'
        . '<p>Warm regards,<br>Team {{SITE_NAME}}</p>';

    public const MEMBERSHIP_EXPIRED = '<h1>Your plan has ended</h1>'
        . '<p>Dear {{USER_NAME}},</p>'
        . '<p>Your <strong>{{PLAN_NAME}}</strong> plan on {{SITE_NAME}} has ended, so premium features are switched off. Your profile stays live and members can still find you.</p>'
        . '<p><a href="{{ACTION_URL}}" style="display:inline-block;padding:10px 24px;background:{{PRIMARY_COLOR}};color:#fff;text-decoration:none;border-radius:6px;">Renew my plan</a></p>'
        . '<p>Found your partner on {{SITE_NAME}}? We would love to hear your story. Just reply to this email.</p>'
        . '<p>Warm regards,<br>Team {{SITE_NAME}}</p>';

    public const PASSWORD_CHANGED = '<h1>Your password was changed</h1>'
        . '<p>Dear {{USER_NAME}},</p>'
        . '<p>The password for your {{SITE_NAME}} account was changed on {{CHANGED_AT}}.</p>'
        . '<p>If you made this change, you don\'t need to do anything.</p>'
        . '<p><strong>If you didn\'t</strong>, someone else may know your password. Reset it now, and let us know so we can check your account.</p>'
        . '<p><a href="{{FORGOT_URL}}" style="display:inline-block;padding:10px 24px;background:{{PRIMARY_COLOR}};color:#fff;text-decoration:none;border-radius:6px;">Reset your password</a></p>'
        . '<p>{{HELP_LINE}}</p>'
        . '<p>Regards,<br>Team {{SITE_NAME}}</p>';

    /**
     * Reminder #1, an hour after someone stops registering (registration-started).
     * Also reaches people whose address was typed by mistake — hence the line
     * telling them they can ignore it.
     */
    public const REGISTRATION_STARTED = '<h1>Thank you for joining {{SITE_NAME}}, {{USER_NAME}}</h1>'
        . '<p>Your profile has been created. Your {{MEMBER_ID_LABEL}} is <strong>{{MATRI_ID}}</strong> — keep it handy, you can log in with it.</p>'
        . '<p>{{STATUS_LINE}}</p>'
        . '<p style="text-align:center;margin:2rem 0;"><a href="{{LOGIN_URL}}" style="display:inline-block;padding:12px 32px;background:{{PRIMARY_COLOR}};color:#fff;text-decoration:none;border-radius:6px;font-weight:600;">Continue my profile</a></p>'
        . '{{CONFIRM_EMAIL_HTML}}'
        . '<p>You can log in with your email address, mobile number or {{MEMBER_ID_LABEL}} and your password. Forgot your password? <a href="{{FORGOT_URL}}">Reset it here</a>.</p>'
        . '<p>{{HELP_LINE}}</p>'
        . '<p>Warm regards,<br>Team {{SITE_NAME}}</p>'
        . '<hr style="border:none;border-top:1px solid #e5e7eb;margin:2rem 0 1rem;">'
        . '<p style="font-size:0.75rem;color:#6b7280;">You\'re receiving this because you started registering on {{SITE_NAME}}. Didn\'t sign up? Someone may have typed your address by mistake — you can ignore this email. <a href="{{UNSUBSCRIBE_URL}}" style="color:#6b7280;">Unsubscribe from reminders</a>.</p>';

    /** The hand-sent reminder of 2 Oct 2026 (mentions "recently fixed" registration problems). */
    public const REGISTRATION_REMINDER_V1 = '<h1>Hi {{USER_NAME}},</h1><p>Thank you for registering on {{SITE_NAME}}. {{STATUS_LINE}}</p><p>Completed profiles are shown first in search, so finishing yours helps the right people find you. It only takes a few minutes, and you\'ll continue right where you left off.</p><p style="text-align:center;margin:2rem 0;"><a href="{{LOGIN_URL}}" style="display:inline-block;padding:12px 32px;background:{{PRIMARY_COLOR}};color:#fff;text-decoration:none;border-radius:6px;font-weight:600;">Complete my profile</a></p><p>You can log in with your email address, mobile number or {{MEMBER_ID_LABEL}} (<strong>{{MATRI_ID}}</strong>) and your password. Forgot your password? <a href="{{FORGOT_URL}}">Reset it here</a>.</p><p>If something didn\'t work while you were filling in the form, we\'re sorry — we\'ve recently fixed some problems on our registration pages, so please give it another try. {{HELP_LINE}}</p><p>Warm regards,<br>Team {{SITE_NAME}}</p><hr style="border:none;border-top:1px solid #e5e7eb;margin:2rem 0 1rem;"><p style="font-size:0.75rem;color:#6b7280;">You\'re receiving this because you started registering on {{SITE_NAME}}. <a href="{{UNSUBSCRIBE_URL}}" style="color:#6b7280;">Unsubscribe from reminders</a>.</p>';

    /** Reminders #2 and #3 (automatic): confirm-email button, no one-off apology. */
    public const REGISTRATION_REMINDER = '<h1>Hi {{USER_NAME}},</h1><p>Thank you for registering on {{SITE_NAME}}. {{STATUS_LINE}}</p><p>Completed profiles are shown first in search, so finishing yours helps the right people find you. It only takes a few minutes, and you\'ll continue right where you left off.</p><p style="text-align:center;margin:2rem 0;"><a href="{{LOGIN_URL}}" style="display:inline-block;padding:12px 32px;background:{{PRIMARY_COLOR}};color:#fff;text-decoration:none;border-radius:6px;font-weight:600;">Complete my profile</a></p>{{CONFIRM_EMAIL_HTML}}<p>You can log in with your email address, mobile number or {{MEMBER_ID_LABEL}} (<strong>{{MATRI_ID}}</strong>) and your password. Forgot your password? <a href="{{FORGOT_URL}}">Reset it here</a>.</p><p>If something doesn\'t work while you\'re filling in the form, tell us and we\'ll help. {{HELP_LINE}}</p><p>Warm regards,<br>Team {{SITE_NAME}}</p><hr style="border:none;border-top:1px solid #e5e7eb;margin:2rem 0 1rem;"><p style="font-size:0.75rem;color:#6b7280;">You\'re receiving this because you started registering on {{SITE_NAME}}. <a href="{{UNSUBSCRIBE_URL}}" style="color:#6b7280;">Unsubscribe from reminders</a>.</p>';
}

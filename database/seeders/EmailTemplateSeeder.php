<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            // ── Existing (Interest) ──
            [
                'slug' => 'interest-received',
                'name' => 'Interest Received',
                'subject' => 'New Interest Received - {{SITE_NAME}}',
                'body_html' => '<h1>New Interest Received</h1><p>Dear {{RECEIVER_NAME}},</p><p><strong>{{SENDER_MATRI_ID}}</strong> has expressed interest in your profile on {{SITE_NAME}}.</p><p>Log in to view their profile and respond to the interest.</p><p><a href="{{ACTION_URL}}" style="display:inline-block;padding:10px 24px;background:#8B1D91;color:#fff;text-decoration:none;border-radius:6px;">View Interest</a></p><p>Wishing you the best in your search,<br>{{SITE_NAME}}</p>',
                'variables' => ['RECEIVER_NAME', 'SENDER_MATRI_ID', 'ACTION_URL', 'SITE_NAME'],
            ],
            [
                'slug' => 'interest-accepted',
                'name' => 'Interest Accepted',
                'subject' => 'Great News! Interest Accepted - {{SITE_NAME}}',
                'body_html' => '<h1>Great News! Interest Accepted</h1><p>Dear {{SENDER_NAME}},</p><p><strong>{{ACCEPTER_MATRI_ID}}</strong> has accepted your interest on {{SITE_NAME}}!</p><p>You can now start a conversation and get to know each other better.</p><p><a href="{{ACTION_URL}}" style="display:inline-block;padding:10px 24px;background:#8B1D91;color:#fff;text-decoration:none;border-radius:6px;">Start Chatting</a></p><p>Wishing you the best,<br>{{SITE_NAME}}</p>',
                'variables' => ['SENDER_NAME', 'ACCEPTER_MATRI_ID', 'ACTION_URL', 'SITE_NAME'],
            ],
            [
                'slug' => 'interest-declined',
                'name' => 'Interest Declined',
                'subject' => 'Interest Update - {{SITE_NAME}}',
                'body_html' => '<h1>Interest Update</h1><p>Dear {{SENDER_NAME}},</p><p>{{DECLINER_MATRI_ID}} has reviewed your interest but has decided not to proceed at this time.</p><p>Don\'t be discouraged! There are many compatible profiles waiting for you.</p><p><a href="{{ACTION_URL}}" style="display:inline-block;padding:10px 24px;background:#8B1D91;color:#fff;text-decoration:none;border-radius:6px;">Browse More Profiles</a></p><p>Wishing you the best in your search,<br>{{SITE_NAME}}</p>',
                'variables' => ['SENDER_NAME', 'DECLINER_MATRI_ID', 'ACTION_URL', 'SITE_NAME'],
            ],

            // ── Registration & Account ──
            [
                'slug' => 'welcome',
                'name' => 'Welcome Email',
                'subject' => 'Welcome to {{SITE_NAME}}! Your Journey Begins',
                'body_html' => '<h1>Welcome to {{SITE_NAME}}!</h1><p>Dear {{USER_NAME}},</p><p>Thank you for registering with {{SITE_NAME}}. Your Matri ID is <strong>{{MATRI_ID}}</strong>.</p><p>Here\'s what to do next:</p><ul><li>Complete your profile to get more visibility</li><li>Upload your photos</li><li>Set your partner preferences</li><li>Start browsing profiles</li></ul><p><a href="{{ACTION_URL}}" style="display:inline-block;padding:10px 24px;background:#8B1D91;color:#fff;text-decoration:none;border-radius:6px;">Complete Your Profile</a></p><p>If you have any questions, feel free to contact us.</p><p>Warm regards,<br>{{SITE_NAME}} Team</p>',
                'variables' => ['USER_NAME', 'MATRI_ID', 'ACTION_URL', 'SITE_NAME'],
            ],
            [
                'slug' => 'password-reset',
                'name' => 'Password Reset',
                'subject' => 'Reset Your Password - {{SITE_NAME}}',
                'body_html' => '<h1>Password Reset Request</h1><p>Dear {{USER_NAME}},</p><p>We received a request to reset your password for your {{SITE_NAME}} account.</p><p>Click the button below to set a new password. This link is valid for {{EXPIRY_MINUTES}} minutes.</p><p><a href="{{ACTION_URL}}" style="display:inline-block;padding:10px 24px;background:#8B1D91;color:#fff;text-decoration:none;border-radius:6px;">Reset Password</a></p><p>If you did not request a password reset, please ignore this email.</p><p>Regards,<br>{{SITE_NAME}} Team</p>',
                'variables' => ['USER_NAME', 'ACTION_URL', 'EXPIRY_MINUTES', 'SITE_NAME'],
            ],

            // ── Profile & Photo Moderation ──
            [
                'slug' => 'profile-approved',
                'name' => 'Profile Approved',
                'subject' => 'Your Profile is Now Live - {{SITE_NAME}}',
                'body_html' => '<h1>Profile Approved!</h1><p>Dear {{USER_NAME}},</p><p>Great news! Your profile ({{MATRI_ID}}) on {{SITE_NAME}} has been reviewed and approved.</p><p>Your profile is now visible to other members. Start browsing and sending interests!</p><p><a href="{{ACTION_URL}}" style="display:inline-block;padding:10px 24px;background:#8B1D91;color:#fff;text-decoration:none;border-radius:6px;">View Your Profile</a></p><p>Best wishes,<br>{{SITE_NAME}} Team</p>',
                'variables' => ['USER_NAME', 'MATRI_ID', 'ACTION_URL', 'SITE_NAME'],
            ],
            [
                'slug' => 'profile-rejected',
                'name' => 'Profile Needs Changes',
                'subject' => 'Profile Update Required - {{SITE_NAME}}',
                'body_html' => '<h1>Profile Update Required</h1><p>Dear {{USER_NAME}},</p><p>Your profile on {{SITE_NAME}} has been reviewed, and we need you to make some changes before it can go live.</p><p><strong>Reason:</strong> {{REASON}}</p><p>Please update your profile at the earliest to ensure it becomes visible to other members.</p><p><a href="{{ACTION_URL}}" style="display:inline-block;padding:10px 24px;background:#8B1D91;color:#fff;text-decoration:none;border-radius:6px;">Edit Your Profile</a></p><p>If you have questions, contact our support team.</p><p>Regards,<br>{{SITE_NAME}} Team</p>',
                'variables' => ['USER_NAME', 'REASON', 'ACTION_URL', 'SITE_NAME'],
            ],
            [
                'slug' => 'photo-approved',
                'name' => 'Photo Approved',
                'subject' => 'Your Photo Has Been Approved - {{SITE_NAME}}',
                'body_html' => '<h1>Photo Approved</h1><p>Dear {{USER_NAME}},</p><p>Your photo on {{SITE_NAME}} has been reviewed and approved. It is now visible on your profile.</p><p><a href="{{ACTION_URL}}" style="display:inline-block;padding:10px 24px;background:#8B1D91;color:#fff;text-decoration:none;border-radius:6px;">View Your Profile</a></p><p>Best wishes,<br>{{SITE_NAME}} Team</p>',
                'variables' => ['USER_NAME', 'ACTION_URL', 'SITE_NAME'],
            ],
            [
                'slug' => 'photo-rejected',
                'name' => 'Photo Rejected',
                'subject' => 'Photo Update Required - {{SITE_NAME}}',
                'body_html' => '<h1>Photo Not Approved</h1><p>Dear {{USER_NAME}},</p><p>Your recently uploaded photo on {{SITE_NAME}} could not be approved.</p><p><strong>Reason:</strong> {{REASON}}</p><p>Please upload a new photo following our photo guidelines.</p><p><a href="{{ACTION_URL}}" style="display:inline-block;padding:10px 24px;background:#8B1D91;color:#fff;text-decoration:none;border-radius:6px;">Upload New Photo</a></p><p>Regards,<br>{{SITE_NAME}} Team</p>',
                'variables' => ['USER_NAME', 'REASON', 'ACTION_URL', 'SITE_NAME'],
            ],

            // ── Membership ──
            [
                'slug' => 'membership-activated',
                'name' => 'Membership Activated',
                'subject' => 'Your {{PLAN_NAME}} Plan is Active - {{SITE_NAME}}',
                'body_html' => '<h1>Membership Activated!</h1><p>Dear {{USER_NAME}},</p><p>Thank you for upgrading to the <strong>{{PLAN_NAME}}</strong> plan on {{SITE_NAME}}.</p><p>Your plan is now active and valid until <strong>{{EXPIRY_DATE}}</strong>.</p><p>Enjoy your premium features:</p><ul><li>View contact details</li><li>Send unlimited interests</li><li>Get highlighted in search results</li></ul><p><a href="{{ACTION_URL}}" style="display:inline-block;padding:10px 24px;background:#8B1D91;color:#fff;text-decoration:none;border-radius:6px;">Start Exploring</a></p><p>Best wishes,<br>{{SITE_NAME}} Team</p>',
                'variables' => ['USER_NAME', 'PLAN_NAME', 'EXPIRY_DATE', 'ACTION_URL', 'SITE_NAME'],
            ],
            [
                'slug' => 'membership-expiring',
                'name' => 'Membership Expiring Soon',
                'subject' => 'Your Plan Expires Soon - {{SITE_NAME}}',
                'body_html' => '<h1>Your Plan is Expiring Soon</h1><p>Dear {{USER_NAME}},</p><p>Your <strong>{{PLAN_NAME}}</strong> plan on {{SITE_NAME}} will expire on <strong>{{EXPIRY_DATE}}</strong>.</p><p>Renew now to continue enjoying premium features without interruption.</p><p><a href="{{ACTION_URL}}" style="display:inline-block;padding:10px 24px;background:#8B1D91;color:#fff;text-decoration:none;border-radius:6px;">Renew Now</a></p><p>Regards,<br>{{SITE_NAME}} Team</p>',
                'variables' => ['USER_NAME', 'PLAN_NAME', 'EXPIRY_DATE', 'ACTION_URL', 'SITE_NAME'],
            ],

            // ── Staff Registered Member ──
            [
                'slug' => 'staff_created_member_welcome',
                'name' => 'Staff Created Member Welcome',
                'subject' => 'Welcome to {{SITE_NAME}} — Your Account is Ready',
                'body_html' => '<h1>Welcome to {{SITE_NAME}}!</h1><p>Dear {{USER_NAME}},</p><p>Your matrimony profile on {{SITE_NAME}} has been created by our staff.</p><p><strong>Your Profile Details:</strong></p><ul><li>Matri ID: <strong>{{MATRI_ID}}</strong></li><li>Login URL: <a href="{{LOGIN_URL}}">{{LOGIN_URL}}</a></li><li>Temporary Password: <strong>{{TEMP_PASSWORD}}</strong></li></ul><p style="color:#d97706;"><strong>⚠️ Important:</strong> Please log in and change your password immediately for security.</p><p><a href="{{LOGIN_URL}}" style="display:inline-block;padding:10px 24px;background:#8B1D91;color:#fff;text-decoration:none;border-radius:6px;">Login Now</a></p><p>Complete your profile, upload photos, and start your matrimony journey!</p><p>Best regards,<br>{{SITE_NAME}} Team</p>',
                'variables' => ['USER_NAME', 'MATRI_ID', 'TEMP_PASSWORD', 'LOGIN_URL', 'SITE_NAME'],
            ],

            // ── Re-engagement Emails (3 escalation levels) ──
            [
                'slug' => 'reengagement-7day',
                'name' => 'Re-engagement — 7 days inactive',
                'subject' => 'We miss you, {{USER_NAME}}! New matches await you on {{SITE_NAME}}',
                'body_html' => '<h1>We miss you, {{USER_NAME}}!</h1><p>It\'s been a week since you last visited {{SITE_NAME}}. A lot has happened in that time — new members have joined and new matches may be waiting for you.</p><p><strong>Here\'s what you can do in just 2 minutes:</strong></p><ul><li>✨ See fresh profile recommendations curated for you</li><li>💬 Check if anyone has shown interest in your profile</li><li>📝 Update your preferences for better matches</li></ul><p style="text-align:center;margin:2rem 0;"><a href="{{LOGIN_URL}}" style="display:inline-block;padding:12px 32px;background:#8B1D91;color:#fff;text-decoration:none;border-radius:6px;font-weight:600;">View Your Matches</a></p><p>Your perfect life partner could be just a login away.</p><p>Warm regards,<br>The {{SITE_NAME}} Team</p><hr style="border:none;border-top:1px solid #e5e7eb;margin:2rem 0 1rem;"><p style="font-size:0.75rem;color:#6b7280;">Don\'t want these emails? <a href="{{UNSUBSCRIBE_URL}}" style="color:#6b7280;">Unsubscribe with one click</a>.</p>',
                'variables' => ['USER_NAME', 'LOGIN_URL', 'UNSUBSCRIBE_URL', 'SITE_NAME'],
            ],
            [
                'slug' => 'reengagement-14day',
                'name' => 'Re-engagement — 14 days inactive',
                'subject' => 'Someone might be waiting to meet you, {{USER_NAME}}',
                'body_html' => '<h1>Hi {{USER_NAME}},</h1><p>Two weeks without a visit to {{SITE_NAME}} — and in that time, several new profiles have joined that match your preferences.</p><p><strong>What you might be missing:</strong></p><ul><li>🔔 Unread interests from potential matches</li><li>👀 New profile views that could turn into conversations</li><li>🎯 AI-curated match recommendations updated weekly</li></ul><p>Coming back takes less than a minute — and could change your life.</p><p style="text-align:center;margin:2rem 0;"><a href="{{LOGIN_URL}}" style="display:inline-block;padding:12px 32px;background:#8B1D91;color:#fff;text-decoration:none;border-radius:6px;font-weight:600;">Log In Now</a></p><p style="font-size:0.875rem;color:#374151;font-style:italic;">"Out of sight, out of mind" — don\'t let potential matches forget you\'re here.</p><p>Best,<br>The {{SITE_NAME}} Team</p><hr style="border:none;border-top:1px solid #e5e7eb;margin:2rem 0 1rem;"><p style="font-size:0.75rem;color:#6b7280;">Not interested in these reminders? <a href="{{UNSUBSCRIBE_URL}}" style="color:#6b7280;">Unsubscribe here</a>.</p>',
                'variables' => ['USER_NAME', 'LOGIN_URL', 'UNSUBSCRIBE_URL', 'SITE_NAME'],
            ],
            [
                'slug' => 'reengagement-30day',
                'name' => 'Re-engagement — 30 days inactive',
                'subject' => 'Last reminder, {{USER_NAME}} — is your search for a life partner still active?',
                'body_html' => '<h1>One last check-in, {{USER_NAME}}</h1><p>It\'s been 30 days since your last visit to {{SITE_NAME}}. We want to make sure you\'re finding what you\'re looking for.</p><p><strong>If you\'re still actively searching:</strong></p><ul><li>✅ Log back in and we\'ll show you the freshest matches</li><li>📸 A quick profile update can double your visibility</li><li>💡 Our support team is here if you need help</li></ul><p style="text-align:center;margin:2rem 0;"><a href="{{LOGIN_URL}}" style="display:inline-block;padding:12px 32px;background:#8B1D91;color:#fff;text-decoration:none;border-radius:6px;font-weight:600;">Yes, I\'m Still Searching</a></p><p><strong>If you\'ve found someone or are taking a break:</strong> Congratulations! You can safely ignore this email — we won\'t send any more reminders unless you log back in and become active again.</p><p style="font-size:0.875rem;color:#6b7280;">This is the last reminder we\'ll send in this cycle. We respect your time.</p><p>Warm wishes,<br>The {{SITE_NAME}} Team</p><hr style="border:none;border-top:1px solid #e5e7eb;margin:2rem 0 1rem;"><p style="font-size:0.75rem;color:#6b7280;"><a href="{{UNSUBSCRIBE_URL}}" style="color:#6b7280;">Unsubscribe from all emails</a></p>',
                'variables' => ['USER_NAME', 'LOGIN_URL', 'UNSUBSCRIBE_URL', 'SITE_NAME'],
            ],

            // ── Weekly Match Suggestions ──
            [
                'slug' => 'weekly-match-suggestions',
                'name' => 'Weekly Match Suggestions',
                'subject' => 'Your {{MATCH_COUNT}} matches this week, {{USER_NAME}}',
                'body_html' => '<div style="max-width:600px;margin:0 auto;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,sans-serif;color:#111827;"><h1 style="font-size:1.5rem;color:#111827;margin:0 0 0.5rem;">Hello {{USER_NAME}},</h1><p style="color:#374151;margin:0 0 1.5rem;">Here are your top matches for this week on {{SITE_NAME}}. Each one is scored against your preferences.</p>{{MATCH_CARDS_HTML}}<p style="text-align:center;margin:2rem 0;"><a href="{{MATCHES_URL}}" style="display:inline-block;padding:12px 32px;background:#8B1D91;color:#fff;text-decoration:none;border-radius:6px;font-weight:600;">See All Your Matches</a></p><p style="color:#374151;margin:1.5rem 0 0;">Log in regularly for fresh recommendations — we update matches as new members join.</p><p style="color:#374151;">Warm regards,<br>The {{SITE_NAME}} Team</p><hr style="border:none;border-top:1px solid #e5e7eb;margin:2rem 0 1rem;"><p style="font-size:0.75rem;color:#6b7280;text-align:center;">Don\'t want weekly match emails? <a href="{{UNSUBSCRIBE_URL}}" style="color:#6b7280;">Unsubscribe here</a>.</p></div>',
                'variables' => ['USER_NAME', 'MATCH_COUNT', 'MATCH_CARDS_HTML', 'MATCHES_URL', 'UNSUBSCRIBE_URL', 'SITE_NAME'],
            ],
        ];

        foreach ($templates as $template) {
            EmailTemplate::updateOrCreate(
                ['slug' => $template['slug']],
                $template,
            );
        }
    }
}

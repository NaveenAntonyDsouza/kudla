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
        ];

        foreach ($templates as $template) {
            EmailTemplate::updateOrCreate(
                ['slug' => $template['slug']],
                $template,
            );
        }
    }
}

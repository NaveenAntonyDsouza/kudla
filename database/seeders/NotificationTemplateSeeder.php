<?php

namespace Database\Seeders;

use App\Models\NotificationTemplate;
use Illuminate\Database\Seeder;

class NotificationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'slug' => 'interest-received',
                'name' => 'Interest Received',
                'title_template' => 'New Interest from {{SENDER_MATRI_ID}}',
                'body_template' => '{{SENDER_NAME}} ({{SENDER_MATRI_ID}}) has sent you an interest. View their profile and respond.',
                'variables' => ['SENDER_NAME', 'SENDER_MATRI_ID'],
            ],
            [
                'slug' => 'interest-accepted',
                'name' => 'Interest Accepted',
                'title_template' => '{{ACCEPTER_MATRI_ID}} accepted your interest!',
                'body_template' => 'Great news! {{ACCEPTER_NAME}} ({{ACCEPTER_MATRI_ID}}) has accepted your interest. You can now start a conversation.',
                'variables' => ['ACCEPTER_NAME', 'ACCEPTER_MATRI_ID'],
            ],
            [
                'slug' => 'interest-declined',
                'name' => 'Interest Declined',
                'title_template' => 'Interest Update',
                'body_template' => '{{DECLINER_MATRI_ID}} has reviewed your interest but decided not to proceed at this time. Don\'t be discouraged!',
                'variables' => ['DECLINER_MATRI_ID'],
            ],
            [
                'slug' => 'profile-viewed',
                'name' => 'Profile Viewed',
                'title_template' => '{{VIEWER_MATRI_ID}} viewed your profile',
                'body_template' => '{{VIEWER_NAME}} ({{VIEWER_MATRI_ID}}) viewed your profile. Check out their profile too!',
                'variables' => ['VIEWER_NAME', 'VIEWER_MATRI_ID'],
            ],
            [
                'slug' => 'profile-shortlisted',
                'name' => 'Profile Shortlisted',
                'title_template' => '{{USER_MATRI_ID}} shortlisted you',
                'body_template' => '{{USER_NAME}} ({{USER_MATRI_ID}}) has added you to their shortlist.',
                'variables' => ['USER_NAME', 'USER_MATRI_ID'],
            ],
            [
                'slug' => 'profile-approved',
                'name' => 'Profile Approved',
                'title_template' => 'Your profile is now live!',
                'body_template' => 'Your profile has been reviewed and approved. It is now visible to other members. Start browsing and sending interests!',
                'variables' => [],
            ],
            [
                'slug' => 'photo-approved',
                'name' => 'Photo Approved',
                'title_template' => 'Your photo has been approved',
                'body_template' => 'Your recently uploaded photo has been approved and is now visible on your profile.',
                'variables' => [],
            ],
            [
                'slug' => 'photo-rejected',
                'name' => 'Photo Rejected',
                'title_template' => 'Photo not approved',
                'body_template' => 'Your recently uploaded photo could not be approved. Reason: {{REASON}}. Please upload a new photo.',
                'variables' => ['REASON'],
            ],
            [
                'slug' => 'membership-activated',
                'name' => 'Membership Activated',
                'title_template' => 'Your {{PLAN_NAME}} plan is active!',
                'body_template' => 'Thank you for upgrading to {{PLAN_NAME}}. Your plan is active until {{EXPIRY_DATE}}. Enjoy premium features!',
                'variables' => ['PLAN_NAME', 'EXPIRY_DATE'],
            ],
            [
                'slug' => 'membership-expiring',
                'name' => 'Membership Expiring',
                'title_template' => 'Your plan expires on {{EXPIRY_DATE}}',
                'body_template' => 'Your {{PLAN_NAME}} plan will expire on {{EXPIRY_DATE}}. Renew now to continue enjoying premium features.',
                'variables' => ['PLAN_NAME', 'EXPIRY_DATE'],
            ],
            [
                'slug' => 'admin-recommendation',
                'name' => 'Admin Recommendation',
                'title_template' => 'Admin recommends {{RECOMMENDED_MATRI_ID}} for you',
                'body_template' => 'Our team recommends {{RECOMMENDED_NAME}} ({{RECOMMENDED_MATRI_ID}}) as a great match for you. {{ADMIN_NOTE}}',
                'variables' => ['RECOMMENDED_NAME', 'RECOMMENDED_MATRI_ID', 'ADMIN_NOTE'],
            ],
            [
                'slug' => 'admin-broadcast',
                'name' => 'Admin Broadcast',
                'title_template' => '{{TITLE}}',
                'body_template' => '{{MESSAGE}}',
                'variables' => ['TITLE', 'MESSAGE'],
            ],
        ];

        foreach ($templates as $template) {
            NotificationTemplate::updateOrCreate(
                ['slug' => $template['slug']],
                $template,
            );
        }
    }
}

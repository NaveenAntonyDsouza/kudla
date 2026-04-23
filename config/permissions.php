<?php

/*
|--------------------------------------------------------------------------
| Staff Permissions Configuration
|--------------------------------------------------------------------------
|
| Single source of truth for all staff-role permissions.
|
| Each permission has:
|   - label: Display label in admin UI
|   - type: 'scoped' = 3-option radio (All Members / Own Members / No)
|           'simple' = 2-option radio (Yes / No)
|   - category: Grouping for the UI (see 'categories' below)
|
| Scope values stored in DB:
|   - For 'scoped' type: 'all' | 'own' | 'none'
|   - For 'simple' type: 'yes' | 'no'
|
*/

return [
    'categories' => [
        'members' => '👥 Member Management',
        'vip_featured' => '⭐ VIP & Featured',
        'approvals' => '✅ Approvals & Documents',
        'leads' => '📞 Lead Management',
        'register' => '📝 Register on Behalf',
        'payments' => '💳 Membership & Payments',
        'communication' => '📧 Communication',
        'search' => '🔍 Search & Matching',
        'reports' => '📊 Reports',
        'settings' => '⚙️ Settings & System',
    ],

    'permissions' => [
        // ── Member Management (10 scoped) ──
        'view_member' => ['label' => 'View Member', 'type' => 'scoped', 'category' => 'members'],
        'edit_member' => ['label' => 'Edit Member', 'type' => 'scoped', 'category' => 'members'],
        'delete_member' => ['label' => 'Delete Member', 'type' => 'scoped', 'category' => 'members'],
        'approve_member' => ['label' => 'Approve Member', 'type' => 'scoped', 'category' => 'members'],
        'unapprove_member' => ['label' => 'Unapprove Member', 'type' => 'scoped', 'category' => 'members'],
        'suspend_member' => ['label' => 'Suspend Member', 'type' => 'scoped', 'category' => 'members'],
        'ban_member' => ['label' => 'Ban Member', 'type' => 'scoped', 'category' => 'members'],
        'toggle_active' => ['label' => 'Activate/Deactivate Member', 'type' => 'scoped', 'category' => 'members'],
        'add_note_to_member' => ['label' => 'Add Note to Member', 'type' => 'scoped', 'category' => 'members'],
        'view_member_notes' => ['label' => 'View Member Notes', 'type' => 'scoped', 'category' => 'members'],

        // ── VIP & Featured (2 simple) ──
        'mark_vip' => ['label' => 'Mark as VIP', 'type' => 'simple', 'category' => 'vip_featured'],
        'feature_profile' => ['label' => 'Feature Profile', 'type' => 'simple', 'category' => 'vip_featured'],

        // ── Approvals & Documents (6 scoped) ──
        'photo_approval' => ['label' => 'Approve/Reject Photos', 'type' => 'scoped', 'category' => 'approvals'],
        'photo_delete' => ['label' => 'Delete Photos', 'type' => 'scoped', 'category' => 'approvals'],
        'id_proof_approval' => ['label' => 'Approve/Reject ID Proof', 'type' => 'scoped', 'category' => 'approvals'],
        'id_proof_delete' => ['label' => 'Delete ID Proof', 'type' => 'scoped', 'category' => 'approvals'],
        'horoscope_approval' => ['label' => 'Approve/Reject Horoscope', 'type' => 'scoped', 'category' => 'approvals'],
        'horoscope_delete' => ['label' => 'Delete Horoscope', 'type' => 'scoped', 'category' => 'approvals'],

        // ── Lead Management (6 mixed) ──
        'view_lead' => ['label' => 'View Leads', 'type' => 'scoped', 'category' => 'leads'],
        'add_lead' => ['label' => 'Add Lead', 'type' => 'simple', 'category' => 'leads'],
        'edit_lead' => ['label' => 'Edit Lead', 'type' => 'scoped', 'category' => 'leads'],
        'delete_lead' => ['label' => 'Delete Lead', 'type' => 'scoped', 'category' => 'leads'],
        'assign_lead' => ['label' => 'Assign Lead to Staff', 'type' => 'simple', 'category' => 'leads'],
        'manage_call_log' => ['label' => 'Manage Call Log', 'type' => 'simple', 'category' => 'leads'],

        // ── Register on Behalf (2 simple) ──
        'register_on_behalf' => ['label' => 'Register Members on Behalf', 'type' => 'simple', 'category' => 'register'],
        'manage_bulk_import' => ['label' => 'Bulk Import Members (CSV)', 'type' => 'simple', 'category' => 'register'],

        // ── Membership & Payments (5 mixed) ──
        'edit_plan' => ['label' => 'Edit Membership Plans', 'type' => 'simple', 'category' => 'payments'],
        'renew_plan' => ['label' => 'Renew Member Plan', 'type' => 'scoped', 'category' => 'payments'],
        'view_payment_history' => ['label' => 'View Payment History', 'type' => 'scoped', 'category' => 'payments'],
        'refund_payment' => ['label' => 'Refund Payments', 'type' => 'simple', 'category' => 'payments'],
        'manage_coupons' => ['label' => 'Manage Discount Coupons', 'type' => 'simple', 'category' => 'payments'],
        'manage_payouts' => ['label' => 'Manage Branch Payouts', 'type' => 'scoped', 'category' => 'payments'],

        // ── Communication (3 simple) ──
        'send_bulk_email_sms' => ['label' => 'Send Bulk Email/SMS', 'type' => 'simple', 'category' => 'communication'],
        'send_broadcast' => ['label' => 'Send Broadcast Notifications', 'type' => 'simple', 'category' => 'communication'],
        'reply_contact' => ['label' => 'Reply Contact Inquiries', 'type' => 'simple', 'category' => 'communication'],

        // ── Search & Match (3 mixed) ──
        'advanced_search' => ['label' => 'Advanced Search', 'type' => 'simple', 'category' => 'search'],
        'match_making' => ['label' => 'Match Making', 'type' => 'scoped', 'category' => 'search'],
        'admin_recommendation' => ['label' => 'Recommend Matches', 'type' => 'simple', 'category' => 'search'],

        // ── Reports (4 simple) ──
        'view_user_reports' => ['label' => 'View User Reports', 'type' => 'simple', 'category' => 'reports'],
        'view_engagement_reports' => ['label' => 'View Engagement Reports', 'type' => 'simple', 'category' => 'reports'],
        'view_revenue_reports' => ['label' => 'View Revenue Reports', 'type' => 'simple', 'category' => 'reports'],
        'view_activity_log' => ['label' => 'View Activity Log', 'type' => 'simple', 'category' => 'reports'],

        // ── Settings & System (10 simple) ──
        'manage_content' => ['label' => 'Manage Content (Pages, FAQs, Stories)', 'type' => 'simple', 'category' => 'settings'],
        'manage_site_settings' => ['label' => 'Manage Site Settings', 'type' => 'simple', 'category' => 'settings'],
        'manage_seo_settings' => ['label' => 'Manage SEO Settings', 'type' => 'simple', 'category' => 'settings'],
        'manage_homepage_content' => ['label' => 'Manage Homepage Content', 'type' => 'simple', 'category' => 'settings'],
        'manage_gateway_settings' => ['label' => 'Manage Payment/SMS/Email Gateway', 'type' => 'simple', 'category' => 'settings'],
        'manage_staff' => ['label' => 'Manage Staff Users', 'type' => 'simple', 'category' => 'settings'],
        'manage_roles' => ['label' => 'Manage Staff Roles & Permissions', 'type' => 'simple', 'category' => 'settings'],
        'system_health' => ['label' => 'System Health & Maintenance', 'type' => 'simple', 'category' => 'settings'],
        'database_backup' => ['label' => 'Database Backup', 'type' => 'simple', 'category' => 'settings'],
        'manage_super_admins' => ['label' => 'Manage Super Admins', 'type' => 'simple', 'category' => 'settings'],
    ],
];

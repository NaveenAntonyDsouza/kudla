# Admin Panel — Comprehensive Plan
**Platform:** Anugraha Matrimony (White-Label Matrimony Platform)
**Framework:** Filament 5.4.3 (already installed)
**Target:** CodeCanyon / white-label customers who manage everything from Admin Panel

---

## Why This Matters for CodeCanyon
Customers buying a matrimony script on CodeCanyon expect:
1. **Zero coding** — everything manageable from admin panel
2. **Full control** — branding, content, plans, users, settings
3. **Professional dashboard** — stats, charts, recent activity
4. **One-click setup** — install, configure from admin, go live

---

## Admin Panel Structure

### 1. DASHBOARD (Home)
The first thing admin sees after login.

```
┌──────────────────────────────────────────────────┐
│ Dashboard                                         │
├──────────────────────────────────────────────────┤
│                                                   │
│ ┌────────┐ ┌────────┐ ┌────────┐ ┌────────┐     │
│ │ Total  │ │ Active │ │ New    │ │ Revenue│     │
│ │ Users  │ │ Users  │ │ Today  │ │ This   │     │
│ │ 1,234  │ │ 890    │ │ 12     │ │ Month  │     │
│ │        │ │        │ │        │ │ ₹45K   │     │
│ └────────┘ └────────┘ └────────┘ └────────┘     │
│                                                   │
│ ┌─────────────────┐ ┌─────────────────┐          │
│ │ Registration    │ │ Revenue         │          │
│ │ Chart (30 days) │ │ Chart (30 days) │          │
│ │ 📈              │ │ 📈              │          │
│ └─────────────────┘ └─────────────────┘          │
│                                                   │
│ Recent Registrations    │ Recent Payments          │
│ • John D. - 2 min ago  │ • AM100008 - ₹999       │
│ • Mary S. - 1 hr ago   │ • AM100005 - ₹1999      │
│ • Paul K. - 3 hr ago   │ • AM100003 - ₹3999      │
└──────────────────────────────────────────────────┘
```

**Stats Cards:**
- Total Users (all time)
- Active Users (not hidden/deleted)
- New Registrations Today
- Revenue This Month
- Total Interests Sent
- Active Subscriptions
- Pending ID Proofs
- Pending Profile Approvals

**Charts:**
- Registration trend (last 30 days)
- Revenue trend (last 30 days)
- Gender distribution (pie chart)
- Religion distribution (pie chart)

**Recent Activity:**
- Last 10 registrations
- Last 10 payments
- Last 10 interest messages

---

### 2. USER MANAGEMENT

#### 2a. All Users (Filament Table)
| Column | Filter | Sortable | Actions |
|--------|--------|----------|---------|
| ID | - | ✅ | - |
| Matri ID | Search | ✅ | Copy |
| Full Name | Search | ✅ | - |
| Gender | Filter | ✅ | - |
| Email | Search | - | - |
| Phone | Search | - | - |
| Religion | Filter | ✅ | - |
| Registration Date | Date Range | ✅ | - |
| Profile Completion | Range | ✅ | - |
| Status | Filter (Active/Hidden/Deleted) | ✅ | - |
| Verified | Filter (Phone/Email/ID) | - | - |
| Subscription | Filter (Free/Paid) | ✅ | - |
| Actions | - | - | View, Edit, Deactivate, Delete, Login As |

**Bulk Actions:**
- Activate selected
- Deactivate selected
- Send email to selected
- Export to CSV/Excel

#### 2b. User Detail Page
- All profile information (read-only with edit button)
- Photo gallery
- Verification status (Phone, Email, ID Proof)
- Subscription history
- Interest history (sent/received)
- Login history
- Activity log
- Admin notes

#### 2c. Login As User
- Admin can login as any user to debug issues
- Shows admin bar at top: "You are viewing as AM100008 [Return to Admin]"

---

### 3. PROFILE APPROVAL

#### 3a. Pending Approvals Queue
Profiles awaiting admin review before going live.

| Column | Details |
|--------|---------|
| Matri ID | Link to profile |
| Name | Full name |
| Photo | Thumbnail |
| Registered | Date/time |
| Completion | % |
| Actions | Approve / Reject (with reason) |

**Settings:**
- Auto-approve: ON/OFF (if ON, profiles go live immediately)
- Require photo before approval: ON/OFF
- Require ID proof before approval: ON/OFF

#### 3b. Rejected Profiles
List of rejected profiles with reason and option to re-approve.

---

### 4. ID PROOF VERIFICATION

#### 4a. Pending Verifications
| Column | Details |
|--------|---------|
| Matri ID | Link to profile |
| Document Type | Aadhaar/Passport/Voter ID/etc. |
| Document Number | Masked (last 4 visible) |
| Front Image | Click to view full |
| Back Image | Click to view full |
| Submitted | Date/time |
| Actions | Approve / Reject (with reason) |

#### 4b. Verified Users
List of all verified users with verification date and admin who verified.

#### 4c. Rejected Verifications
List with rejection reason and option for user to re-submit.

---

### 5. MEMBERSHIP & PAYMENTS

#### 5a. Subscription Plans Management
Full CRUD for membership plans — admin can create, edit, delete plans.

| Field | Type | Description |
|-------|------|-------------|
| Plan Name | Text | e.g., "Diamond Plus" |
| Slug | Auto | e.g., "diamond-plus" |
| Duration (months) | Number | e.g., 15 |
| Original Price | Number | e.g., 15000 (strikethrough) |
| Sale Price | Number | e.g., 12000 (displayed) |
| Daily Interest Limit | Number | e.g., 50 |
| View Contacts Limit | Number | e.g., 500 |
| Daily Contact Views | Number | e.g., 20 |
| Personalized Messages | Toggle | ON/OFF |
| Featured Profile | Toggle | ON/OFF |
| Priority Support | Toggle | ON/OFF |
| Is Popular | Toggle | Shows "POPULAR" badge |
| Sort Order | Number | Display order |
| Is Active | Toggle | Show/hide plan |

#### 5b. Payment History
| Column | Filter | Details |
|--------|--------|---------|
| Transaction ID | Search | Razorpay ID |
| User | Search | Matri ID + Name |
| Plan | Filter | Which plan |
| Amount | Range | Payment amount |
| Status | Filter | Paid/Pending/Failed/Refunded |
| Payment Date | Date Range | When paid |
| Expires | Date Range | Subscription expiry |
| Actions | - | View receipt, Refund, Extend |

#### 5c. Manual Subscription
Admin can manually activate a subscription for a user:
- Select user
- Select plan
- Set start/end dates
- Add admin note (e.g., "Complimentary for beta tester")

#### 5d. Revenue Reports
- Daily/Weekly/Monthly/Yearly revenue
- Revenue by plan
- Revenue by payment method
- Export to CSV

---

### 6. SITE SETTINGS (White-Label Configuration)

This is the **most important section for CodeCanyon customers**.

#### 6a. General Settings
| Setting | Type | Description |
|---------|------|-------------|
| Site Name | Text | e.g., "Anugraha Matrimony" |
| Tagline | Text | e.g., "Find Your Perfect Match" |
| Site Area | Text | e.g., "Karnataka's Christian Community" |
| Contact Email | Email | e.g., "info@anugrahamatrimony.com" |
| Contact Phone | Text | e.g., "+91 484 408 0333" |
| WhatsApp Number | Text | For chat support |
| Address | Textarea | Office address |
| Copyright Text | Text | Footer copyright |

#### 6b. Branding / Theme
| Setting | Type | Description |
|---------|------|-------------|
| Logo | File Upload | Header logo |
| Favicon | File Upload | Browser tab icon |
| Primary Color | Color Picker | e.g., #8B1D91 |
| Primary Hover | Color Picker | e.g., #6B1571 |
| Primary Light | Color Picker | e.g., #F3E8F7 |
| Secondary Color | Color Picker | e.g., #00BCD4 |
| Secondary Hover | Color Picker | e.g., #00ACC1 |
| Secondary Light | Color Picker | e.g., #E0F7FA |

**Live preview** — admin sees color changes in real-time before saving.

#### 6c. Homepage Content
| Setting | Type | Description |
|---------|------|-------------|
| Hero Title | Text | "Find Your Perfect Match in ..." |
| Hero Subtitle | Text | Tagline text |
| Stats: Members | Number | Shown on homepage |
| Stats: Marriages | Number | Shown on homepage |
| Stats: Years | Number | Shown on homepage |
| "Why Choose Us" items | Repeater | Title + description + icon |
| CTA Title | Text | "Register Free Today" |
| CTA Description | Text | Below CTA title |

#### 6d. Email Settings (SMTP)
| Setting | Type | Description |
|---------|------|-------------|
| Mail Driver | Select | smtp/sendmail/log |
| SMTP Host | Text | e.g., smtp.hostinger.com |
| SMTP Port | Number | e.g., 465 |
| SMTP Username | Text | e.g., info@... |
| SMTP Password | Password | Encrypted |
| Encryption | Select | ssl/tls/none |
| From Address | Email | Sender email |
| From Name | Text | Sender name |
| **Test Email** | Button | Send test email to admin |

#### 6e. SMS / OTP Gateway
| Setting | Type | Description |
|---------|------|-------------|
| SMS Provider | Select | Fast2SMS / Twilio / MSG91 / TextLocal / Custom |
| API Key | Text | Encrypted |
| API Secret | Password | Encrypted (if required) |
| Sender ID | Text | e.g., "ANUGRA" (6 chars for India) |
| OTP Length | Select | 4 / 6 digits |
| OTP Expiry (minutes) | Number | e.g., 10 |
| OTP Template | Text | "Your {{APP_NAME}} OTP is {{OTP}}. Valid for {{MINUTES}} minutes." |
| Enable Phone OTP | Toggle | ON/OFF |
| Enable WhatsApp OTP | Toggle | ON/OFF (via WhatsApp Business API) |
| WhatsApp Provider | Select | Twilio / Gupshup / Meta Business API |
| WhatsApp API Key | Text | Encrypted |
| **Send Test OTP** | Button | Send test OTP to admin's phone |

#### 6f. Payment Gateway
| Setting | Type | Description |
|---------|------|-------------|
| Gateway | Select | Razorpay / Stripe / PayPal |
| API Key | Text | Encrypted |
| API Secret | Password | Encrypted |
| Mode | Toggle | Test / Live |
| Currency | Select | INR / USD / EUR |
| **Test Payment** | Button | Process ₹1 test payment |

#### 6g. Registration Settings
| Setting | Type | Description |
|---------|------|-------------|
| Phone OTP Required | Toggle | Require phone verification |
| Email OTP Required | Toggle | Require email verification |
| Auto-Approve Profiles | Toggle | Skip manual approval |
| Matri ID Prefix | Text | e.g., "AM" (generates AM100001) |
| Default Interest Limit (Free) | Number | e.g., 5/day |
| Min Age | Number | e.g., 18 |
| Max Photos (Album) | Number | e.g., 9 |
| Max Photos (Family) | Number | e.g., 3 |

#### 6h. SEO Settings
| Setting | Type | Description |
|---------|------|-------------|
| Meta Title | Text | Homepage title tag |
| Meta Description | Textarea | Homepage meta description |
| Google Analytics ID | Text | e.g., GA-XXXXXXX |
| Facebook Pixel ID | Text | For ads tracking |
| OG Image | File Upload | Social sharing image |

---

### 7. CONTENT MANAGEMENT

#### 7a. Communities / Castes
CRUD for the community list shown in registration and home page.

| Field | Type |
|-------|------|
| Community Name | Text |
| Religion | Select |
| Sort Order | Number |
| Is Active | Toggle |

#### 7b. Reference Data Management
Admin can edit all dropdown options WITHOUT touching code:

**Location (cascading):**
- Country list (with phone codes, flag)
- State list (grouped by country)
- District list (grouped by state)

**Religion (cascading):**
- Religion list
- Denomination list (grouped by religion — e.g., Christian → Latin Catholic, Syrian Catholic)
- Diocese list (grouped by denomination)
- Caste list (grouped by religion — e.g., Hindu → Brahmin, Nair)
- Sub-caste list (grouped by caste)
- Muslim Sect list (grouped under Muslim)
- Muslim Community/Jamath list (grouped under Muslim)
- Jain Sect list (grouped under Jain)
- Nakshatra (Star) list
- Rasi (Zodiac) list
- Gotra/Gothram list

**Education & Profession (grouped):**
- Education qualifications (grouped by level — e.g., Engineering → B.Tech, M.Tech)
- Occupation categories (grouped by sector — e.g., IT → Software Engineer, Data Analyst)
- Employment categories
- Income ranges

**Lifestyle & Preferences:**
- Mother tongue / Language list
- Hobbies list
- Music genres list
- Books genres list
- Movie genres list
- Sports / Fitness list
- Cuisine list
- Diet options
- Cultural background options

**System:**
- Height list
- Weight list
- Interest message templates (send + accept + decline)
- "How did you hear about us" options

#### 7c. Static Pages
WYSIWYG editor for:
- Privacy Policy
- Terms of Service
- About Us
- Contact Us
- FAQ / Help
- Custom pages

#### 7d. Email Templates
Edit email content from admin:
- Interest Received email
- Interest Accepted email
- Interest Declined email
- Password Reset email
- Welcome email (after registration)
- Email OTP template
- New Match notification email
- Profile View notification email

#### 7e. SMS Templates (future)
- Phone OTP template
- Interest received SMS
- Interest accepted SMS

#### 7f. Notification Templates
Edit in-app notification messages:
- Interest received title/message
- Interest accepted title/message
- Interest declined title/message
- New message notification
- Profile view notification
- System/admin broadcast notification

---

### 8. INTEREST & MESSAGE MANAGEMENT

#### 8a. All Interests
| Column | Filter | Details |
|--------|--------|---------|
| Sender | Search | Matri ID |
| Receiver | Search | Matri ID |
| Status | Filter | Pending/Accepted/Declined/Cancelled |
| Message | Preview | First 50 chars |
| Date | Date Range | Created at |
| Actions | - | View conversation, Delete |

#### 8b. Reported Messages
Messages flagged by users for inappropriate content.

| Column | Details |
|--------|---------|
| Reported By | Matri ID |
| Reported User | Matri ID |
| Message Content | Full text |
| Reason | Harassment/Spam/Fake/Other |
| Date | When reported |
| Actions | Warn user / Block user / Dismiss report |

#### 8c. Broadcast Notifications
Admin can send notifications to:
- All users
- All male/female users
- Users by religion
- Users by state
- Users by subscription status (free/paid)
- Custom filter

Message fields: Title, Message, Link (optional)

---

### 9. PHOTO MANAGEMENT

#### 9a. Reported Photos
Photos flagged by system or users:
- Inappropriate content
- Not a real photo
- Group photo as profile

| Column | Details |
|--------|---------|
| Matri ID | Link to profile |
| Photo | Thumbnail (click to view full) |
| Type | Profile/Album/Family |
| Reason | Why flagged |
| Actions | Approve / Remove photo / Warn user |

#### 9b. Photos Without Faces (future)
Auto-detect photos without human faces using image analysis.

---

### 10. REPORTS & ANALYTICS

#### 9a. User Reports
- Registration by date (chart)
- Registration by gender (pie)
- Registration by religion (pie)
- Registration by state (map/bar)
- Profile completion distribution
- Active vs inactive users

#### 9b. Engagement Reports
- Interests sent per day (chart)
- Interest acceptance rate (%)
- Average response time
- Most active users
- Most viewed profiles

#### 9c. Revenue Reports
- Revenue by day/week/month (chart)
- Revenue by plan (pie)
- Active subscriptions count
- Subscription renewal rate
- Average revenue per user

#### 9d. Export
All reports exportable to CSV/Excel/PDF.

---

### 10. SYSTEM & MAINTENANCE

#### 10a. Activity Log
Track all admin actions:
- Who did what, when
- User approvals/rejections
- Setting changes
- Plan modifications

#### 10b. Error Log Viewer
View `storage/logs/laravel.log` from admin panel.

#### 10c. Cache Management
One-click buttons:
- Clear config cache
- Clear view cache
- Clear route cache
- Clear all caches

#### 10d. Database Backup
- Manual backup download
- Scheduled auto-backup (daily)

#### 10e. System Info
- PHP version
- Laravel version
- MySQL version
- Disk usage
- Last backup date

#### 10f. Scheduled Tasks / Cron Jobs
View and manage scheduled tasks:
- Expire old pending interests (after 30 days)
- Clean up unverified accounts (after 7 days)
- Send daily match emails
- Generate daily/weekly reports
- Database backup

#### 10g. Update / Migration
- Run pending migrations from admin
- Check for updates (if distributed via CodeCanyon)
- Version info

---

### 11. BLOCKED & REPORTED USERS

#### 11a. Blocked Users List
| Column | Details |
|--------|---------|
| Blocker | Matri ID |
| Blocked | Matri ID |
| Date | When blocked |
| Actions | Unblock (admin override) |

#### 11b. Reported Users
| Column | Details |
|--------|---------|
| Reported By | Matri ID |
| Reported User | Matri ID |
| Reason | Why reported |
| Date | When reported |
| Status | Pending / Reviewed / Action Taken |
| Actions | Warn / Suspend / Ban / Dismiss |

#### 11c. Banned Users
Permanently banned users with reason and ban date.

---

### 12. SHORTLIST & PROFILE VIEWS ANALYTICS

#### 12a. Most Shortlisted Profiles
Top profiles by shortlist count — useful for understanding what makes a good profile.

#### 12b. Most Viewed Profiles
Top profiles by view count.

#### 12c. Least Active Users
Users who registered but never completed profile or logged in again — for re-engagement campaigns.

---

## Admin Roles & Permissions

| Role | Permissions |
|------|------------|
| **Super Admin** | Full access to everything |
| **Admin** | All except: delete users permanently, system settings, database backup |
| **Moderator** | Profile approval, ID verification, message review |
| **Support** | View users (read-only), respond to queries |

Uses `spatie/laravel-permission` (already installed).

---

## Filament Resources to Create

| Resource | Model | Priority |
|----------|-------|----------|
| UserResource | User + Profile | HIGH |
| ProfileApprovalResource | Profile | HIGH |
| IdProofResource | IdProof | HIGH |
| SubscriptionResource | Subscription | HIGH |
| PlanResource | (new model) | HIGH |
| PaymentResource | Subscription | MEDIUM |
| InterestResource | Interest | MEDIUM |
| NotificationResource | Notification | LOW |
| CommunityResource | Community | MEDIUM |
| SiteSettingResource | SiteSetting | HIGH |
| ThemeSettingResource | ThemeSetting | HIGH |
| ActivityLogResource | (new model) | LOW |

**Filament Pages (custom):**
- Dashboard (stats + charts)
- Site Settings (tabs: General, Branding, Email, Payment, Registration, SEO)
- Reference Data Editor
- Static Page Editor
- Email Template Editor
- Reports (charts + exports)
- System Maintenance

---

## Estimated Build Time

| Section | Effort |
|---------|--------|
| Dashboard with charts | 3-4 hours |
| User Management | 3-4 hours |
| Profile Approval | 1-2 hours |
| ID Proof Verification | 1-2 hours |
| Membership Plans CRUD | 2-3 hours |
| Payment History + Manual Sub | 2-3 hours |
| Site Settings (all tabs) | 4-5 hours |
| Content Management + Reference Data | 4-5 hours |
| Interest & Message Management | 2-3 hours |
| Photo Management | 1-2 hours |
| Reports & Analytics | 3-4 hours |
| Blocked & Reported Users | 1-2 hours |
| Shortlist & Views Analytics | 1 hour |
| System & Maintenance | 2-3 hours |
| Roles & Permissions | 1-2 hours |
| Broadcast Notifications | 1-2 hours |
| **Total** | **~35-45 hours** |

---

## CodeCanyon Selling Points (Admin Features)

When listing on CodeCanyon, highlight these:
1. ✅ **Complete Admin Dashboard** with real-time stats & charts
2. ✅ **White-Label Ready** — change branding, colors, logo from admin
3. ✅ **No Coding Required** — all settings configurable from admin panel
4. ✅ **Plan Management** — create unlimited membership plans with custom features
5. ✅ **Payment Gateway** — Razorpay integrated, configurable from admin
6. ✅ **SMTP Config from Admin** — no .env editing needed
7. ✅ **Profile Approval** — manual or auto-approve modes
8. ✅ **ID Verification** — built-in document review system
9. ✅ **Content Management** — edit all pages, email templates, dropdown lists
10. ✅ **Reference Data Management** — edit all dropdowns (religions, castes, locations, etc.) from admin
11. ✅ **Multi-Role Admin** — Super Admin, Admin, Moderator, Support roles
12. ✅ **Export Reports** — CSV/Excel/PDF for all data
13. ✅ **Activity Logging** — track all admin actions
14. ✅ **Broadcast Notifications** — send announcements to all or filtered users
15. ✅ **Photo Moderation** — review and remove inappropriate photos
16. ✅ **User Reports & Bans** — handle complaints, warn/suspend/ban users
17. ✅ **Login As User** — debug user issues by viewing their account
18. ✅ **Scheduled Tasks** — auto-expire interests, cleanup, daily emails
19. ✅ **System Health** — PHP/MySQL versions, disk usage, error logs, cache management

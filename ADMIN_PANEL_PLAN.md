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
- Login history with IP address (see 2d)
- Activity log
- Admin notes

#### 2d. Login History with IP Address
Track every user login for security and analytics:

| Column | Details |
|--------|---------|
| User | Matri ID + Name |
| Login Date/Time | Timestamp |
| IP Address | e.g., 103.21.58.xx |
| Device | Desktop / Mobile / Tablet |
| Browser | Chrome / Safari / Firefox |
| Location (approx.) | City, State (from IP) |
| Status | Success / Failed |

**Admin actions:**
- Filter by user, date range, IP address
- Flag suspicious logins (multiple IPs, rapid location changes)
- Export login history to CSV

#### 2e. VIP / Featured Profile
Admin can mark profiles as VIP for premium visibility:

| Field | Type | Description |
|-------|------|-------------|
| Profile | Select | Matri ID |
| VIP Badge | Toggle | Show gold badge on profile |
| Featured | Toggle | Appear in "Featured Profiles" section |
| Featured Until | Date | Auto-remove after date |
| Priority Boost | Number | Higher = appears first in search results |
| Admin Note | Text | Why VIP (e.g., "Premium customer", "Brand ambassador") |

**Where VIP shows:**
- Gold "VIP" or "Featured" badge on profile card
- "Featured Profiles" carousel on homepage
- Boosted in search results (appears higher)
- Featured section on dashboard for opposite gender

#### 2c. Profile Link Sharing
Each profile gets a public shareable link:

- URL format: `https://yourdomain.com/profile/AM100008` (uses matri ID, not database ID)
- Shareable via WhatsApp, Email, Copy Link button on profile view
- Public link shows limited info (photo, age, religion, education, location) — no contact details
- Visitor must register/login to see full profile or send interest
- Share buttons: WhatsApp, Facebook, Email, Copy Link
- Admin setting: Enable/disable public profile sharing (toggle in Site Settings)

#### 2f. Profile Summary Card Download
Generate a downloadable image card with key profile info:

```
┌─────────────────────────────┐
│  [Photo]                    │
│  John D. (AM100008)         │
│  28 yrs, 5'10" | Male       │
│  Catholic | Latin Catholic   │
│  B.Tech, Software Engineer   │
│  Mangalore, Karnataka        │
│  ─────────────────────────── │
│  anugrahamatrimony.com       │
└─────────────────────────────┘
```

- Generated as PNG image (server-side using Intervention Image or similar)
- "Download Profile Card" button on profile view page
- Useful for parents sharing profiles offline (print/WhatsApp)
- Admin can customize card template (logo, colors, which fields to show)

#### 2g. Login As User
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

#### 5d. Discount Coupon Generation
Admin can create discount coupons for membership plans:

| Field | Type | Description |
|-------|------|-------------|
| Coupon Code | Text | e.g., "WELCOME50", "DIWALI2026" (auto-generate or manual) |
| Discount Type | Select | Percentage (%) / Fixed Amount (₹) |
| Discount Value | Number | e.g., 50 (for 50%) or 500 (for ₹500 off) |
| Applicable Plans | Multi-select | All plans / specific plans only |
| Min Purchase Amount | Number | e.g., ₹999 (optional) |
| Max Discount Cap | Number | e.g., ₹1000 max (for percentage coupons) |
| Usage Limit (total) | Number | e.g., 100 total uses |
| Usage Limit (per user) | Number | e.g., 1 per user |
| Valid From | Date | Start date |
| Valid Until | Date | Expiry date |
| Is Active | Toggle | Enable/disable |

**Coupon tracking:**
- Total times used
- Revenue impact (total discount given)
- Users who used the coupon
- Export coupon usage report

**Frontend integration:**
- "Have a coupon?" input on checkout/payment page
- Apply coupon → show discounted price with strikethrough original
- Coupon validation: expired, max usage reached, invalid code, plan not eligible

#### 5e. Revenue Reports
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

#### 6h. Social Links
Website-level social media links displayed in footer and contact page:

| Setting | Type | Description |
|---------|------|-------------|
| Facebook Page URL | URL | e.g., facebook.com/anugrahamatrimony |
| Instagram URL | URL | e.g., instagram.com/anugrahamatrimony |
| Twitter / X URL | URL | Optional |
| YouTube Channel URL | URL | Optional |
| LinkedIn URL | URL | Optional |
| WhatsApp Chat Link | URL | Direct WhatsApp chat (wa.me/91XXXXXXXXXX) |

Displayed in: footer icons, contact page, about page.

#### 6i. SEO Settings (Global + Per-Page)

**Global SEO:**
| Setting | Type | Description |
|---------|------|-------------|
| Default Meta Title | Text | Fallback title tag |
| Meta Title Suffix | Text | e.g., " | Anugraha Matrimony" (appended to all pages) |
| Default Meta Description | Textarea | Fallback meta description |
| Google Analytics ID | Text | e.g., GA-XXXXXXX |
| Google Tag Manager ID | Text | e.g., GTM-XXXXXX |
| Facebook Pixel ID | Text | For ads tracking |
| OG Image | File Upload | Default social sharing image |
| Robots.txt Content | Textarea | Editable robots.txt |
| Sitemap Auto-Generate | Toggle | Auto-generate XML sitemap |

**Per-Page SEO:**
Admin can set custom SEO for each page:

| Page | Meta Title | Meta Description | OG Image | Canonical URL |
|------|-----------|-----------------|----------|---------------|
| Home | ✏️ | ✏️ | ✏️ | Auto |
| Search | ✏️ | ✏️ | ✏️ | Auto |
| Login | ✏️ | ✏️ | - | Auto |
| Register | ✏️ | ✏️ | - | Auto |
| Happy Stories | ✏️ | ✏️ | ✏️ | Auto |
| Privacy Policy | ✏️ | ✏️ | - | Auto |
| Terms | ✏️ | ✏️ | - | Auto |
| About Us | ✏️ | ✏️ | ✏️ | Auto |
| Contact | ✏️ | ✏️ | - | Auto |
| Membership Plans | ✏️ | ✏️ | ✏️ | Auto |
| Custom Pages | ✏️ | ✏️ | ✏️ | Auto |

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

#### 7f. Happy Stories (Success Stories)
Showcase couples who found their match through the platform:

| Field | Type | Description |
|-------|------|-------------|
| Groom Name | Text | e.g., "John" |
| Bride Name | Text | e.g., "Mary" |
| Groom Matri ID | Select | Link to profile (optional) |
| Bride Matri ID | Select | Link to profile (optional) |
| Couple Photo | File Upload | Wedding/engagement photo |
| Marriage Date | Date | When they got married |
| Story | Rich Text | Their story (WYSIWYG editor) |
| Location | Text | e.g., "Mangalore, Karnataka" |
| Sort Order | Number | Display order |
| Is Active | Toggle | Show/hide on frontend |

**Frontend display:**
- Homepage: "Happy Stories" section with 3-4 featured stories
- Dedicated `/happy-stories` page with all stories (paginated)
- Each story card: couple photo, names, date, excerpt → click for full story

#### 7g. Testimonials
User reviews and feedback displayed on the website:

| Field | Type | Description |
|-------|------|-------------|
| User Name | Text | Display name |
| User Matri ID | Select | Link to profile (optional, for verified badge) |
| User Photo | File Upload | Or auto-pull from profile photo |
| Rating | Number | 1-5 stars |
| Testimonial Text | Textarea | What they said |
| Designation | Text | Optional (e.g., "Software Engineer, Bangalore") |
| Is Verified | Toggle | Verified user badge |
| Sort Order | Number | Display order |
| Is Active | Toggle | Show/hide |

**Frontend display:**
- Homepage: testimonial carousel (3-5 rotating)
- Dedicated `/testimonials` page (optional)
- Star rating display

#### 7h. Notification Templates
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

#### 8c. Admin Recommend Matches
Admin/staff can manually recommend profiles to users:

| Field | Type | Description |
|-------|------|-------------|
| For User | Select | Who receives the recommendation (Matri ID) |
| Recommended Profile | Select | Who is being recommended (Matri ID) |
| Admin Note | Text | "We think this profile is a great match because..." |
| Priority | Select | Normal / High (High shows as "Top Pick") |

**How it works:**
- Admin reviews two profiles and sees them as compatible
- Creates a recommendation → user gets a notification: "Admin recommends AM100042 for you"
- Recommended profiles appear in a dedicated "Admin Picks" section on the user's matches page
- User can send interest directly from the recommendation
- Track: recommendation sent, viewed, interest sent (conversion rate)

#### 8d. Partner Preference Match Count
Show compatibility percentage on every profile card and profile view:

**Match Score Display:**
- Profile cards show "85% Match" badge (green if >80%, yellow 60-80%, grey <60%)
- Profile view page shows detailed breakdown:
  ```
  Match Score: 85%
  ✅ Age Range (15%) — Within preference
  ✅ Religion (15%) — Same religion
  ✅ Mother Tongue (10%) — Same language
  ❌ Location (10%) — Different state
  ✅ Education (10%) — Matches preference
  ...
  ```
- Uses the MatchingService weights from NEXT_SESSION_PLAN.md

#### 8e. Broadcast Notifications
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

#### 10g. Installation Wizard & License Activation
First-time setup wizard when admin opens `/admin` for the first time:

**Step 1 — Environment Check:**
```
✅ PHP 8.3+
✅ MySQL 8.0+
✅ Required extensions: BCMath, Ctype, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML, cURL, GD/Imagick
✅ storage/ writable
✅ .env writable
```

**Step 2 — Database Setup:**
- Host, port, database name, username, password
- Test connection button
- Run migrations + seed reference data

**Step 3 — Admin Account:**
- Name, email, password for super admin

**Step 4 — Purchase Code Activation:**
| Field | Type | Description |
|-------|------|-------------|
| Purchase Code | Text | CodeCanyon purchase code (e.g., `a1b2c3d4-e5f6-7890-abcd-ef1234567890`) |
| Buyer Username | Auto | Fetched from Envato API after verification |
| License Type | Auto | Regular / Extended |
| Support Expiry | Auto | Date support expires |

**Verification flow:**
1. Customer enters purchase code
2. Script calls Envato API: `GET https://api.envato.com/v3/market/author/sale?code=PURCHASE_CODE`
3. API returns buyer info, license type, support dates
4. Script stores: purchase code, domain, activation date, license type in `site_settings`
5. Shows green checkmark: "License Activated — Thank you!"

**Step 5 — Site Settings:**
- Site name, logo, contact info (quick setup — editable later from Settings)

**After wizard:** redirect to admin dashboard, wizard never shows again.

#### 10h. Update System

**Phase 1 — Manual Update (ZIP Upload):**
Admin page: `System > Software Update`

```
┌─────────────────────────────────────────────┐
│ Software Update                              │
├─────────────────────────────────────────────┤
│                                              │
│ Current Version: v1.0.0                      │
│ License: Regular (Valid ✅)                   │
│ Support: Active until 2027-04-01             │
│                                              │
│ ┌──────────────────────────────────────┐     │
│ │  📁 Upload Update Package (.zip)     │     │
│ │  [Choose File] [Upload & Install]    │     │
│ └──────────────────────────────────────┘     │
│                                              │
│ Update Instructions:                         │
│ 1. Download latest version from CodeCanyon   │
│ 2. Upload the .zip file above                │
│ 3. Click "Upload & Install"                  │
│ 4. System will backup, extract, migrate      │
│                                              │
│ ── Update History ──                         │
│ v1.0.0 — Initial release (2026-05-01)        │
└─────────────────────────────────────────────┘
```

**Update process (what happens on "Upload & Install"):**
1. Verify purchase code is still valid (Envato API call)
2. Verify zip contains valid update (check for `update_manifest.json`)
3. Create automatic backup (database SQL dump + current files snapshot)
4. Extract zip to temp directory
5. Replace application files (app/, resources/, config/, routes/, database/migrations/)
6. Run `php artisan migrate` (new migrations only)
7. Run `php artisan db:seed --class=UpdateSeeder` (if exists — adds new reference data)
8. Clear all caches (`config:clear`, `view:clear`, `route:clear`, `cache:clear`)
9. Update version number in `site_settings`
10. Log update in activity log
11. Show changelog + "Update Complete!" message

**Safety measures:**
- Pre-update backup downloadable from admin
- Rollback button (restore from backup if update fails)
- Maintenance mode enabled during update, disabled after
- File permission checks before starting

**Phase 2 — One-Click Auto-Update (post-launch):**
Requires a separate **Update Server** (small Laravel app hosted on your VPS):

```
Customer Admin Panel              Your Update Server
────────────────────              ──────────────────
[Check for Updates] ──────────►  /api/v1/check
  POST: {                         - Verify purchase code
    purchase_code,                 - Compare versions
    current_version,               - Return: latest version,
    domain,                          changelog, download URL
    php_version
  }
                    ◄──────────  Response: {
                                   "update_available": true,
                                   "latest_version": "1.2.0",
                                   "changelog": "...",
                                   "download_url": "...",
                                   "min_php": "8.3"
                                 }

[Update Now] ─────────────────►  /api/v1/download
  POST: {purchase_code, domain}    - Verify again
                    ◄──────────    - Serve update.zip
  Auto-install (same as Phase 1 steps 3-10)
```

**Update Server features (separate app):**
- Upload new version zip + write changelog
- Track all customer installations (domain, version, last check)
- Analytics: how many on each version, update adoption rate
- Revoke pirated/refunded licenses
- Send update notification emails to customers

#### 10i. Version & Changelog Display
Admin can view full version history:

| Version | Date | Type | Changelog |
|---------|------|------|-----------|
| v1.2.0 | 2026-08-15 | Feature | Added Wedding Directory, Match Score display |
| v1.1.0 | 2026-07-01 | Feature | Added VIP profiles, Coupon system |
| v1.0.1 | 2026-05-15 | Bugfix | Fixed email template saving, mobile layout |
| v1.0.0 | 2026-05-01 | Release | Initial release |

Changelog stored in `update_manifest.json` inside each update zip:
```json
{
  "version": "1.2.0",
  "min_php": "8.3",
  "min_laravel": "13.0",
  "changelog": [
    {"type": "feature", "text": "Added Wedding Directory module"},
    {"type": "feature", "text": "Match compatibility score on profile cards"},
    {"type": "fix", "text": "Fixed email template not saving on some hosts"},
    {"type": "improvement", "text": "Optimized search query performance"}
  ],
  "migrations": true,
  "seeders": ["UpdateV120Seeder"]
}

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

### 13. FRANCHISE / BRANCH MANAGEMENT

For matrimony businesses operating across multiple locations with local branch offices.

#### 13a. Branches
| Field | Type | Description |
|-------|------|-------------|
| Branch Name | Text | e.g., "Mangalore Branch" |
| Branch Code | Text | e.g., "MNG" (unique) |
| Location | Text | City / Area |
| State | Select | State |
| Address | Textarea | Full address |
| Contact Phone | Text | Branch phone |
| Contact Email | Email | Branch email |
| Branch Manager | Select | From staff list |
| Is Active | Toggle | Enable/disable branch |

#### 13b. Branch Dashboard
Each branch sees only their own data:
- Users registered through this branch
- Interests facilitated by this branch
- Revenue generated by this branch
- Staff performance metrics

#### 13c. Branch Assignment
- Users can be assigned to a branch (during registration or by admin)
- Profiles show "Registered through: Mangalore Branch"
- Branch staff can only view/manage users assigned to their branch

#### 13d. Franchise Affiliate Link
Each franchise/branch gets a unique registration link for tracking:

- URL format: `https://yourdomain.com/register?ref=MNG` (branch code)
- Users who register via this link are auto-assigned to that branch
- Branch dashboard shows: "X users registered via your affiliate link"
- Franchise can share this link on their website, social media, printed materials
- QR code auto-generated for each affiliate link (for print/offline use)
- Tracking: clicks on link, registrations, conversions, revenue from referred users

#### 13e. Branch Revenue Tracking
| Metric | Description |
|--------|-------------|
| Total Registrations | Users registered via this branch |
| Paid Subscriptions | Revenue from branch users |
| Commission % | Branch earns X% of subscription revenue |
| Payout History | Monthly payouts to branch |

---

### 14. STAFF / TELECALLER MODULE

For staff members who assist users with registration and profile management.

#### 14a. Staff Management
| Field | Type | Description |
|-------|------|-------------|
| Staff Name | Text | Full name |
| Employee ID | Text | e.g., "EMP001" |
| Email | Email | Login email |
| Phone | Text | Contact number |
| Role | Select | Telecaller / Branch Staff / Branch Manager / Admin |
| Branch | Select | Assigned branch (if applicable) |
| Is Active | Toggle | Enable/disable |
| Joined Date | Date | Employment start |

#### 14b. Telecaller Dashboard (with Charts)
What each telecaller sees after login:
- My assigned leads (users to follow up)
- My registrations today / this week / this month
- My call log
- Pending follow-ups
- Performance metrics vs targets

**Dashboard Charts (Filament Widgets):**
- Registrations trend (line chart — last 30 days, my registrations)
- Calls made this week (bar chart — daily breakdown)
- Lead conversion funnel (pie chart — New → Contacted → Interested → Registered)
- Target progress (progress bar — registrations vs monthly target, revenue vs target)

#### 14c. Lead Management
| Field | Type | Description |
|-------|------|-------------|
| Lead Name | Text | Prospective user name |
| Phone | Text | Contact number |
| Email | Email | Optional |
| Source | Select | Walk-in / Phone inquiry / Website / Referral / Ad |
| Assigned To | Select | Staff member |
| Status | Select | New / Contacted / Interested / Registered / Not Interested / Follow-up |
| Follow-up Date | Date | Next follow-up |
| Notes | Textarea | Call notes |
| Converted | Toggle | Did they register? |
| Linked Profile | Select | Matri ID (after registration) |

#### 14d. Register on Behalf
Staff can register profiles for users who visit the branch:
- Fill registration form on behalf of user
- Upload photos on behalf
- Mark profile as "Registered by: Staff Name (Branch)"
- User receives credentials via SMS/Email

#### 14e. Call Log
| Field | Type | Description |
|-------|------|-------------|
| Staff | Auto | Who called |
| Lead/User | Select | Who was called |
| Call Type | Select | Outgoing / Incoming |
| Duration | Number | Minutes |
| Outcome | Select | Connected / No Answer / Busy / Interested / Not Interested |
| Notes | Textarea | Conversation summary |
| Follow-up Required | Toggle | Schedule next call |
| Follow-up Date | Date | When to call next |

#### 14f. Staff Performance Reports
| Metric | Description |
|--------|-------------|
| Registrations | Profiles created by this staff |
| Calls Made | Total calls today/week/month |
| Conversion Rate | Leads → Registrations % |
| Revenue Generated | Subscriptions from their users |
| Active Users | How many of their users are still active |
| Ranking | Staff leaderboard |

#### 14g. Targets & Incentives
Admin sets monthly targets per staff:
| Target | Type | Description |
|--------|------|-------------|
| Registration Target | Number | e.g., 50 registrations/month |
| Revenue Target | Amount | e.g., ₹50,000/month |
| Call Target | Number | e.g., 100 calls/day |
| Incentive Per Registration | Amount | e.g., ₹100 per registration |
| Incentive Per Subscription | % or Amount | e.g., 10% of subscription |

---

### 15. ADVERTISEMENT MANAGEMENT

Monetize ad spaces on the platform. Admin can manage ads without touching code.

#### 15a. Ad Spaces (Frontend Locations)
Pre-defined ad slots where ads appear on the website:

| Ad Space | Location | Size | Type |
|----------|----------|------|------|
| Homepage Banner | Below hero section | 728x90 (leaderboard) | Image / HTML |
| Homepage Sidebar | Right column on homepage | 300x250 (medium rectangle) | Image / HTML |
| Search Results | Between every 5th result | 728x90 | Image / HTML |
| Profile View Sidebar | Right sidebar on profile view | 300x600 (half page) | Image / HTML |
| Dashboard Sidebar | Right sidebar on dashboard | 300x250 | Image / HTML |
| Footer Banner | Above footer (all pages) | 728x90 | Image / HTML |
| Mobile Banner | Between content on mobile | 320x50 (mobile leaderboard) | Image / HTML |

#### 15b. Ad Management
| Field | Type | Description |
|-------|------|-------------|
| Ad Title | Text | Internal name (e.g., "Wedding Photography - March 2026") |
| Ad Space | Select | Which slot (from 15a) |
| Ad Type | Select | Image / HTML / Google AdSense |
| Image | File Upload | Banner image (for image type) |
| Click URL | URL | Where ad links to |
| HTML Code | Textarea | Custom HTML/JS (for HTML/AdSense type) |
| Advertiser Name | Text | Who placed the ad |
| Start Date | Date | When to start showing |
| End Date | Date | When to stop showing |
| Is Active | Toggle | Enable/disable |
| Priority | Number | Higher = shown first if multiple ads for same slot |

#### 15c. Ad Analytics
| Metric | Description |
|--------|-------------|
| Impressions | How many times ad was shown |
| Clicks | How many times ad was clicked |
| CTR | Click-through rate (%) |
| Revenue | If tracked (manual entry or per-click rate) |
| By Date | Daily impression/click chart |

#### 15d. Google AdSense Integration
- Admin can paste AdSense code for any ad space
- Toggle: "Use Google AdSense" per slot (overrides manual ads)
- Allows platform owners to monetize via Google AdSense without code changes

---

### 16. WEDDING DIRECTORY *(Low Priority — Phase 2)*

A vendor marketplace for wedding-related services. Separate module that adds value but is not core to matrimony.

#### 16a. Vendor Categories
Admin manages categories of wedding vendors:

| Field | Type | Description |
|-------|------|-------------|
| Category Name | Text | e.g., "Wedding Photography", "Catering", "Venues" |
| Icon | Select/Upload | Category icon |
| Sort Order | Number | Display order |
| Is Active | Toggle | Show/hide |

**Suggested categories:**
Wedding Photography, Videography, Catering, Wedding Venues/Halls, Wedding Planners, Florists, Bridal Makeup, Mehendi Artists, DJs/Music, Wedding Cards/Invitations, Jewellers, Bridal Wear, Groom Wear, Cake & Bakery, Travel & Honeymoon

#### 16b. Vendor Registration & Profile
Vendors register themselves (separate registration flow):

| Field | Type | Description |
|-------|------|-------------|
| Business Name | Text | e.g., "Royal Wedding Photography" |
| Category | Select | From 16a |
| Owner Name | Text | Contact person |
| Phone | Text | Business phone |
| Email | Email | Business email |
| Website | URL | Optional |
| Address | Textarea | Business address |
| City | Text | Service area |
| State | Select | State |
| Description | Rich Text | About the business |
| Logo | File Upload | Business logo |
| Portfolio Images | Multi-Upload | Up to 10 photos |
| Starting Price | Text | e.g., "₹25,000 onwards" |
| Is Verified | Toggle | Admin-verified badge |
| Is Active | Toggle | Show/hide listing |

#### 16c. Vendor Dashboard
Each vendor gets their own dashboard after login:
- Profile views count
- Inquiry count
- Inquiry list with user details
- Edit profile/photos
- Activity log

#### 16d. Browse & Search Vendors (Frontend)
- `/wedding-directory` — Browse all vendors by category
- `/wedding-directory?category=photography&city=mangalore` — Filter by category + city
- Each vendor card: logo, name, category, city, starting price, rating
- Vendor detail page: full profile, portfolio gallery, inquiry form
- "Similar Vendors" section on each vendor page

#### 16e. Vendor Inquiry
Users can send inquiries to vendors:

| Field | Type | Description |
|-------|------|-------------|
| User Name | Auto | From logged-in user |
| Phone | Auto | From profile |
| Event Date | Date | Wedding/event date |
| Message | Textarea | Requirements |
| Budget | Text | Optional |

Vendor receives email + in-dashboard notification for each inquiry.

#### 16f. Admin: Vendor Management
- Approve/reject vendor registrations
- View all vendor analytics
- Feature vendors (homepage showcase)
- Manage vendor categories

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
| PlanResource | MembershipPlan | HIGH |
| PaymentResource | Payment | MEDIUM |
| CouponResource | Coupon (new) | MEDIUM |
| InterestResource | Interest | MEDIUM |
| AdminRecommendationResource | AdminRecommendation (new) | MEDIUM |
| VipProfileResource | Profile (scoped) | MEDIUM |
| LoginHistoryResource | LoginHistory (new) | LOW |
| NotificationResource | Notification | LOW |
| CommunityResource | Community | MEDIUM |
| SiteSettingResource | SiteSetting | HIGH |
| ThemeSettingResource | ThemeSetting | HIGH |
| HappyStoryResource | HappyStory (new) | MEDIUM |
| TestimonialResource | Testimonial (new) | MEDIUM |
| AdvertisementResource | Advertisement (new) | MEDIUM |
| ActivityLogResource | ActivityLog (new) | LOW |
| BranchResource | Branch (new) | LOW |
| StaffResource | Staff (new) | LOW |
| LeadResource | Lead (new) | LOW |
| VendorCategoryResource | VendorCategory (new) | LOW (Phase 2) |
| VendorResource | Vendor (new) | LOW (Phase 2) |
| VendorInquiryResource | VendorInquiry (new) | LOW (Phase 2) |

**Filament Pages (custom):**
- Dashboard (stats + charts)
- Site Settings (tabs: General, Branding, Email, Payment, Registration, SEO, Social Links)
- Reference Data Editor
- Static Page Editor
- Email Template Editor
- SEO Manager (per-page SEO)
- Reports (charts + exports)
- System Maintenance
- Staff/Telecaller Dashboard
- Branch/Franchise Dashboard

---

## Estimated Build Time

| Section | Effort |
|---------|--------|
| 1. Dashboard with charts | 3-4 hours |
| 2. User Management (+ login history, VIP, profile sharing, card download) | 5-6 hours |
| 3. Profile Approval | 1-2 hours |
| 4. ID Proof Verification | 1-2 hours |
| 5. Membership Plans + Coupons | 3-4 hours |
| 5e. Revenue Reports | 1-2 hours |
| 6. Site Settings (General, Branding, Email, SMS, Payment, Registration, Social, SEO) | 5-6 hours |
| 7. Content Management (Reference Data, Static Pages, Email Templates, Happy Stories, Testimonials) | 5-6 hours |
| 8. Interest & Match Management (+ Admin Recommend, Match Score) | 3-4 hours |
| 9. Photo Management | 1-2 hours |
| 10. Reports & Analytics | 3-4 hours |
| 10 (System). System & Maintenance (logs, cache, backup) | 2-3 hours |
| 10g. Installation Wizard + License Activation | 3-4 hours |
| 10h. Update System (Phase 1 — zip upload) | 3-4 hours |
| 10h. Update Server (Phase 2 — auto-update, separate app) | 4-6 hours |
| 11. Blocked & Reported Users | 1-2 hours |
| 12. Shortlist & Views Analytics | 1 hour |
| 13. Franchise / Branch Management (+ Affiliate Link) | 3-4 hours |
| 14. Staff / Telecaller Module (+ Charts) | 4-5 hours |
| 15. Advertisement Management | 2-3 hours |
| 16. Wedding Directory (Phase 2) | 6-8 hours |
| Roles & Permissions | 1-2 hours |
| Broadcast Notifications | 1-2 hours |
| **Total (Core — without Wedding Dir & Auto-Update Server)** | **~56-66 hours** |
| **Total (with Wedding Directory)** | **~62-74 hours** |
| **Total (everything including Auto-Update Server)** | **~66-80 hours** |

---

## CodeCanyon Selling Points (Admin Features)

When listing on CodeCanyon, highlight these:
1. ✅ **Complete Admin Dashboard** with real-time stats & charts
2. ✅ **White-Label Ready** — change branding, colors, logo from admin
3. ✅ **No Coding Required** — all settings configurable from admin panel
4. ✅ **Plan Management** — create unlimited membership plans with custom features
5. ✅ **Discount Coupons** — generate promo codes with percentage/fixed discounts, usage limits, expiry
6. ✅ **Payment Gateway** — Razorpay integrated, configurable from admin
7. ✅ **SMTP Config from Admin** — no .env editing needed
8. ✅ **Profile Approval** — manual or auto-approve modes
9. ✅ **ID Verification** — built-in document review system
10. ✅ **Content Management** — edit all pages, email templates, dropdown lists
11. ✅ **Reference Data Management** — edit all dropdowns (religions, castes, locations, etc.) from admin
12. ✅ **Multi-Role Admin** — Super Admin, Admin, Moderator, Support roles
13. ✅ **Export Reports** — CSV/Excel/PDF for all data
14. ✅ **Activity Logging** — track all admin actions
15. ✅ **Broadcast Notifications** — send announcements to all or filtered users
16. ✅ **Photo Moderation** — review and remove inappropriate photos
17. ✅ **User Reports & Bans** — handle complaints, warn/suspend/ban users
18. ✅ **Login As User** — debug user issues by viewing their account
19. ✅ **Scheduled Tasks** — auto-expire interests, cleanup, daily emails
20. ✅ **System Health** — PHP/MySQL versions, disk usage, error logs, cache management
21. ✅ **VIP / Featured Profiles** — admin-promoted premium visibility
22. ✅ **Admin Recommend Matches** — manually curate match suggestions for users
23. ✅ **Match Compatibility Score** — show percentage match on every profile
24. ✅ **Happy Stories & Testimonials** — showcase success stories on homepage
25. ✅ **Profile Link Sharing** — shareable public profile links via WhatsApp/email
26. ✅ **Profile Summary Card** — downloadable profile image for offline sharing
27. ✅ **Login History with IP** — security tracking of all user logins
28. ✅ **Per-Page SEO** — custom meta title/description for every page
29. ✅ **Social Links Management** — connect Facebook, Instagram, YouTube, WhatsApp
30. ✅ **Advertisement Management** — manage ad banners + Google AdSense integration
31. ✅ **Franchise / Branch System** — multi-location management with commission tracking
32. ✅ **Franchise Affiliate Links** — unique registration links with QR codes per branch
33. ✅ **Staff / Telecaller Module** — lead management, call logs, performance tracking
34. ✅ **Wedding Directory** — vendor marketplace with categories, search, inquiries (Phase 2)
35. ✅ **Installation Wizard** — guided setup with environment check, DB config, license activation
36. ✅ **Purchase Code Verification** — Envato API integration for license validation
37. ✅ **One-Click Updates** — upload zip or auto-update from admin panel with backup + rollback
38. ✅ **Version & Changelog** — full update history visible in admin

---

## Excluded Features (Consciously Omitted)

Features offered by some competitors that we chose **not** to include, with reasoning:

| Feature | Reason for Exclusion |
|---------|---------------------|
| Special case search (IIT/IIM) | Too niche — our keyword search already covers this. Users can search by education/college name |
| Custom code injection (Plugin) | Security risk for white-label customers. Admin shouldn't inject arbitrary JS. Use Google Analytics/Tag Manager fields instead |
| User → Admin direct notification reply | Overcomplicates the notification system. Users can use Contact Us page or WhatsApp support instead |
| Staff pending task assignment | Adds complexity without clear value. Lead management + follow-up dates already cover task tracking |
| Franchise payout request flow | Too finance-heavy for an MVP. Commission tracking is enough — payouts handled offline via bank transfer |

These can be reconsidered in future versions based on customer demand.

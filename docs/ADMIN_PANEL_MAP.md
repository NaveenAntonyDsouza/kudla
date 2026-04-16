# Admin Panel — Complete Visual Map
**Platform:** Anugraha / Kudla Matrimony (White-Label)
**Framework:** Filament 5.4.3 (Laravel 13)
**Total Sections:** 11 | **Total Pages:** 35+

---

## Navigation Sidebar

```
 DASHBOARD
    Dashboard                          [Stats, Charts, Recent Activity]

 USER MANAGEMENT
    Users                              [All profiles — view, edit, filters]
    Profile Approvals                  [Pending → Approve / Reject]
    VIP / Featured Profiles            [Mark profiles as featured]
    Login History                      [IP, device, browser, location]
    Bulk Import (CSV)                  [Upload CSV → bulk create profiles]
    Deleted / Deactivated Users        [View, reactivate, permanent delete]

 VERIFICATION
    ID Verification              [3]  [Pending proofs → Approve / Reject]
    Reported Users                     [View reports → Warn / Suspend / Ban]
    Reported Photos                    [Flagged photos → Approve / Remove]
    Blocked Users                      [View all blocks → Unblock option]

 MEMBERSHIP & PAYMENTS
    Membership Plans                   [CRUD: plans, pricing, features]
    Subscriptions                      [All active/expired subscriptions]
    Payment History                    [Razorpay transactions log]
    Discount Coupons                   [Create/manage coupons]

 INTERESTS & MATCHING
    All Interests                      [View all sent/received interests]
    Reported Messages                  [Flagged messages → Warn / Block]
    Match Weight Config                [Adjust 12-criteria scoring weights]
    Broadcast Notifications            [Send bulk notifications to users]

 CONTENT MANAGEMENT
    Communities / Castes               [CRUD: name, religion, sort order]
    Reference Data                     [Edit all dropdowns: religion, caste, etc.]
    Static Pages                       [WYSIWYG editor for policy pages]
    Email Templates                    [Edit transactional email content]
    SMS Templates                      [Edit OTP/notification SMS text]
    Success Stories                    [Approve/manage user submissions]
    Testimonials                       [Manage testimonial cards]

 REPORTS & ANALYTICS
    User Reports                       [Registration trends, demographics]
    Engagement Reports                 [Interest stats, response rates]
    Revenue Reports                    [Income by plan, daily/monthly]

 SUPPORT
    Contact Inbox                [5]  [Manage contact form submissions]

 SITE SETTINGS                        [General, branding, SMTP, SMS, etc.]

 STAFF & TELECALLER
    Staff Members                      [CRUD: name, role, branch]
    Leads                              [Lead management with status flow]
    Call Logs                          [Track calls, outcomes, follow-ups]
    Targets & Performance              [Monthly targets, leaderboard]

 FRANCHISE / BRANCHES
    Branches                           [CRUD: name, location, manager]
    Branch Revenue                     [Revenue tracking per branch]

 ADVERTISEMENTS
    Ad Spaces                          [Pre-defined ad locations on site]
    Manage Ads                         [Upload ads, set schedule, track]

 SYSTEM
    Activity Log                       [Track all admin actions]
    Error Logs                         [View Laravel error logs]
    Cache Management                   [One-click clear all caches]
    Database Backup                    [Manual + scheduled backups]
    System Info                        [PHP, Laravel, MySQL versions]
```

---

## Detailed Page Breakdown

### DASHBOARD
```
 Dashboard
 +------------------------------------------------------------------+
 |                                                                    |
 |  [ Total Users ]  [ Active ]  [ New Today ]  [ Revenue/Month ]   |
 |  [ Interests  ]  [ Active Subs ]  [ Pending ID ]  [ Pending App ]|
 |                                                                    |
 |  +--- Registration Trend (30 days line chart) ---+                |
 |  +--- Revenue Trend (30 days bar chart) ---------+                |
 |  +--- Gender Pie ---+  +--- Religion Pie --------+                |
 |                                                                    |
 |  Recent Registrations (last 10)                                   |
 |  Recent Payments (last 10)                                        |
 |  Upcoming Follow-ups (profiles with follow-up date today/overdue) |
 +------------------------------------------------------------------+
```

### USER MANAGEMENT
```
 Users (List Page)
 +------------------------------------------------------------------+
 | [Search: name/email/phone/matriID]                                |
 | Filters: Gender | Religion | Status | Subscription | Completion % |
 |                                                                    |
 | # | MatriID | Name  | Gender | Religion | Reg Date | Status | Act |
 |---|---------|-------|--------|----------|----------|--------|-----|
 | 1 | AM10001 | John  | Male   | Catholic | 10 Apr   | Active | ... |
 | 2 | AM10002 | Mary  | Female | Catholic | 09 Apr   | Active | ... |
 |                                                                    |
 | Bulk: [Activate] [Deactivate] [Export CSV] [Send Email]           |
 +------------------------------------------------------------------+

 Users (Detail / View Page)
 +------------------------------------------------------------------+
 | [Photo] AM10001 — John Doe                    [Edit] [Login As]  |
 |------------------------------------------------------------------|
 | Tabs:                                                             |
 | [Profile] [Photos] [Subscription] [Interests] [Notes] [Activity] |
 |                                                                    |
 | Profile Tab:                                                      |
 |   Personal: Name, DOB, Age, Gender, Height, Complexion...        |
 |   Religious: Religion, Denomination, Caste, Diocese...            |
 |   Education: Qualification, Occupation, Income...                 |
 |   Family: Father, Mother, Siblings...                             |
 |   Location: Native, Residing, Address...                          |
 |   Contact: Phone, WhatsApp, Email, Reference...                   |
 |   Partner Prefs: Age range, Religion, Education...                |
 |                                                                    |
 | Notes Tab:                                                        |
 |   [Add Note] [Follow-up Date: ___]                                |
 |   10 Apr — Admin: "Called, interested in Gold plan"               |
 |   08 Apr — Admin: "Father enquired about profiles"               |
 |                                                                    |
 | Subscription Tab:                                                 |
 |   Current: Gold (expires 15 Jul 2026)                             |
 |   History: Free → Gold (10 Apr) → ...                             |
 |   [Extend] [Change Plan] [Manual Activate]                       |
 +------------------------------------------------------------------+

 Profile Approvals
 +------------------------------------------------------------------+
 | Pending Profiles (new registrations awaiting review)              |
 |                                                                    |
 | # | MatriID | Name  | Photo | Registered | Completion | Actions  |
 |---|---------|-------|-------|------------|------------|----------|
 | 1 | AM10050 | Alex  | [img] | 10 Apr     | 45%        | [A] [R] |
 |                                                                    |
 | [A] = Approve    [R] = Reject (with reason)                      |
 +------------------------------------------------------------------+

 VIP / Featured Profiles
 +------------------------------------------------------------------+
 | # | MatriID | Name  | VIP Badge | Featured | Until    | Priority |
 |---|---------|-------|-----------|----------|----------|----------|
 | 1 | AM10001 | John  |    ON     |   ON     | 30 Jun   |    5     |
 |                                                                    |
 | [Add VIP Profile] → Select user, set badge, featured date, note  |
 +------------------------------------------------------------------+

 Login History
 +------------------------------------------------------------------+
 | Filters: User | Date Range | IP | Status (Success/Failed)        |
 |                                                                    |
 | # | User    | Date/Time        | IP           | Device  | Status  |
 |---|---------|------------------|--------------|---------|---------|
 | 1 | AM10001 | 10 Apr, 9:30 AM  | 103.21.58.xx | Mobile  | Success |
 | 2 | AM10002 | 10 Apr, 8:15 AM  | 49.36.12.xx  | Desktop | Failed  |
 +------------------------------------------------------------------+

 Bulk Import (CSV)
 +------------------------------------------------------------------+
 | Step 1: [Download CSV Template]                                   |
 | Step 2: [Upload Filled CSV]                                       |
 | Step 3: Preview — Valid (green) / Errors (red with reason)        |
 | Step 4: [Import X Profiles] [Skip Errors]                        |
 |                                                                    |
 | Options: [ ] Send credentials via Email/SMS                       |
 |          [ ] Auto-approve profiles                                |
 |          [Branch: _____ ]                                         |
 +------------------------------------------------------------------+

 Deleted / Deactivated Users
 +------------------------------------------------------------------+
 | # | MatriID | Name  | Reason          | By    | Date   | Actions |
 |---|---------|-------|-----------------|-------|--------|---------|
 | 1 | AM10099 | Ravi  | Found someone   | Self  | 05 Apr | [React] |
 | 2 | AM10045 | Priya | Fake profile     | Admin | 03 Apr | [View]  |
 |                                                                    |
 | [React] = Reactivate    [View] = View archived profile            |
 +------------------------------------------------------------------+
```

### VERIFICATION
```
 ID Verification                                              [3]
 +------------------------------------------------------------------+
 | Pending Verifications                                             |
 |                                                                    |
 | # | MatriID | Doc Type | Doc No.    | Front | Back | Actions     |
 |---|---------|----------|------------|-------|------|-------------|
 | 1 | AM10001 | Aadhaar  | XXXX-1234  | [img] | [img]| [A] [R]   |
 |                                                                    |
 | [A] = Approve    [R] = Reject (with reason)                      |
 | Tabs: [Pending] [Verified] [Rejected]                            |
 +------------------------------------------------------------------+

 Reported Users
 +------------------------------------------------------------------+
 | # | Reported By | Reported User | Reason        | Date   | Action |
 |---|-------------|---------------|---------------|--------|--------|
 | 1 | AM10001     | AM10055       | Fake profile  | 10 Apr | [...]  |
 |                                                                    |
 | Actions: [Warn] [Suspend] [Ban] [Dismiss]                        |
 +------------------------------------------------------------------+

 Reported Photos
 +------------------------------------------------------------------+
 | # | MatriID | Photo    | Type    | Reason     | Actions          |
 |---|---------|----------|---------|------------|------------------|
 | 1 | AM10055 | [thumb]  | Profile | Not real   | [Keep] [Remove]  |
 +------------------------------------------------------------------+

 Blocked Users
 +------------------------------------------------------------------+
 | # | Blocker  | Blocked  | Date    | Action                       |
 |---|----------|----------|---------|------------------------------|
 | 1 | AM10001  | AM10055  | 10 Apr  | [Unblock]                    |
 +------------------------------------------------------------------+
```

### MEMBERSHIP & PAYMENTS
```
 Membership Plans
 +------------------------------------------------------------------+
 | # | Plan     | Duration | Price  | Strike | Interests | Contact  |
 |---|----------|----------|--------|--------|-----------|----------|
 | 1 | Free     | Lifetime |   0    |   -    |    5/day  |   No     |
 | 2 | Silver   | 1 month  |  999   | 1,499  |   10/day  |   Yes    |
 | 3 | Gold     | 3 months | 2,999  | 3,999  |   20/day  |   Yes    |
 | 4 | Diamond  | 6 months | 4,999  | 6,999  |   50/day  |   Yes    |
 | 5 | Dia Plus | 12 months| 7,999  | 11,999 |   50/day  |   Yes    |
 |                                                                    |
 | [Create Plan] [Edit] [Toggle Active]                              |
 +------------------------------------------------------------------+

 Subscriptions
 +------------------------------------------------------------------+
 | Filters: Status (Active/Expired/All) | Plan | Expiring in 7 days  |
 |                                                                    |
 | # | User    | Plan   | Started  | Expires  | Status  | Actions   |
 |---|---------|--------|----------|----------|---------|-----------|
 | 1 | AM10001 | Gold   | 10 Apr   | 10 Jul   | Active  | [Extend]  |
 |                                                                    |
 | [Manual Activate] → Select user, plan, dates                      |
 +------------------------------------------------------------------+

 Payment History
 +------------------------------------------------------------------+
 | # | Txn ID       | User    | Plan  | Amount | Status | Date      |
 |---|--------------|---------|-------|--------|--------|-----------|
 | 1 | pay_Abc123   | AM10001 | Gold  | 2,999  | Paid   | 10 Apr    |
 |                                                                    |
 | Filters: Status (Paid/Failed/Refunded) | Date Range | Plan       |
 | [Export CSV]                                                      |
 +------------------------------------------------------------------+

 Discount Coupons
 +------------------------------------------------------------------+
 | # | Code     | Type | Value | Plans     | Uses | Valid Until | Act |
 |---|----------|------|-------|-----------|------|-------------|-----|
 | 1 | LAUNCH50 | %    | 50%   | Gold,Dia  | 3/50 | 30 Jun      | ON  |
 | 2 | FLAT500  | INR  | 500   | All       | 0/100| 31 Dec      | ON  |
 |                                                                    |
 | [Create Coupon] → Code, type, value, plans, limits, dates        |
 +------------------------------------------------------------------+
```

### INTERESTS & MATCHING
```
 All Interests
 +------------------------------------------------------------------+
 | Filters: Status | Date Range | Sender/Receiver search             |
 |                                                                    |
 | # | Sender  | Receiver | Status   | Message Preview  | Date      |
 |---|---------|----------|----------|------------------|-----------|
 | 1 | AM10001 | AM10002  | Accepted | "We find your..."| 10 Apr    |
 | 2 | AM10003 | AM10004  | Pending  | "My family and.."| 09 Apr    |
 |                                                                    |
 | Actions: [View Conversation] [Delete]                             |
 +------------------------------------------------------------------+

 Reported Messages
 +------------------------------------------------------------------+
 | # | Reported By | From    | Message Content      | Reason | Act   |
 |---|-------------|---------|----------------------|--------|-------|
 | 1 | AM10002     | AM10055 | "Send me your num.." | Harass | [...]  |
 |                                                                    |
 | Actions: [Warn Sender] [Block Sender] [Dismiss]                   |
 +------------------------------------------------------------------+

 Match Weight Config
 +------------------------------------------------------------------+
 | Criteria               | Weight | Enabled |  Total must = 100%   |
 |------------------------|--------|---------|                       |
 | Religion               |  15%   |   ON    |                       |
 | Age Range              |  15%   |   ON    |                       |
 | Denomination / Caste   |  10%   |   ON    |                       |
 | Mother Tongue          |  10%   |   ON    |                       |
 | Education              |  10%   |   ON    |                       |
 | Occupation             |  10%   |   ON    |                       |
 | Height Range           |   8%   |   ON    |                       |
 | Native Location        |   8%   |   ON    |                       |
 | Working Location       |   5%   |   ON    |                       |
 | Marital Status         |   5%   |   ON    |                       |
 | Diet                   |   2%   |   ON    |                       |
 | Family Status          |   2%   |   ON    |                       |
 | Horoscope (Nakshatra)  |   0%   |   OFF   |                       |
 |                                                                    |
 | [Save Weights] [Reset to Default]                                 |
 +------------------------------------------------------------------+

 Broadcast Notifications
 +------------------------------------------------------------------+
 | Send To:  ( ) All Users                                           |
 |           ( ) All Male / All Female                               |
 |           ( ) By Religion: [___]                                  |
 |           ( ) By State: [___]                                     |
 |           ( ) By Subscription: Free / Paid                        |
 |                                                                    |
 | Title:   [________________________________]                       |
 | Message: [________________________________]                       |
 | Link:    [________________________________] (optional)            |
 |                                                                    |
 | [Send Notification]    Preview: "Will reach ~2,450 users"         |
 +------------------------------------------------------------------+
```

### CONTENT MANAGEMENT
```
 Communities / Castes
 +------------------------------------------------------------------+
 | # | Community Name | Religion  | Sort | Active | Actions          |
 |---|----------------|-----------|------|--------|------------------|
 | 1 | Roman Catholic | Christian |  1   |  ON    | [Edit] [Delete]  |
 | 2 | Bunts          | Hindu     |  1   |  ON    | [Edit] [Delete]  |
 |                                                                    |
 | [Add Community] → Name, Religion (select), Sort Order, Active     |
 +------------------------------------------------------------------+

 Reference Data (Cascading Dropdowns)
 +------------------------------------------------------------------+
 | Tabs: [Religion] [Location] [Education] [Lifestyle] [System]      |
 |                                                                    |
 | Religion Tab:                                                     |
 |   Religions:     [Hindu] [Christian] [Muslim] [Jain] [+Add]      |
 |   Denominations: Catholic → [Roman Catholic] [Syrian Catholic]..  |
 |   Castes:        Hindu → [Brahmin] [Bunt] [Billava] [+Add]       |
 |   Sub-castes:    Bunt → [Shetty] [Hegde] [Rai] [+Add]            |
 |   Muslim Sects:  [Sunni] [Shia] [Beary] [+Add]                   |
 |   Jain Sects:    [Digambar] [Svetambara] [+Add]                  |
 |   Nakshatra:     [Ashwini] [Bharani] [+Add]                      |
 |   Rashi:         [Aries] [Taurus] [+Add]                         |
 |   Gotra:         [Atri] [Bharadvaja] [+Add]                      |
 |                                                                    |
 | Location Tab:                                                     |
 |   Countries:     [India] [USA] [UAE] [+Add]                      |
 |   States:        India → [Karnataka] [Kerala] [+Add]              |
 |   Districts:     Karnataka → [DK] [Udupi] [+Add]                 |
 |                                                                    |
 | Education Tab:                                                    |
 |   Qualifications: [B.Tech] [MBBS] [MBA] [+Add]                   |
 |   Occupations:    [Software Engineer] [Doctor] [+Add]             |
 |   Income Ranges:  [< 2 Lakh] [2-5 Lakh] [+Add]                   |
 |                                                                    |
 | Lifestyle Tab:                                                    |
 |   Mother Tongues: [Kannada] [Tulu] [Konkani] [+Add]              |
 |   Hobbies:        [Reading] [Cooking] [+Add]                      |
 |   Diet:           [Veg] [Non-Veg] [Eggetarian] [+Add]            |
 |                                                                    |
 | System Tab:                                                       |
 |   Height values:  [4'0"] [4'1"] ... [7'0"] [+Add]                |
 |   Interest templates: [We find your profile...] [+Add]            |
 +------------------------------------------------------------------+

 Static Pages (WYSIWYG)
 +------------------------------------------------------------------+
 | # | Page Title      | URL              | Last Updated | Actions   |
 |---|-----------------|------------------|--------------|-----------|
 | 1 | Privacy Policy  | /privacy-policy  | 05 Apr       | [Edit]    |
 | 2 | Terms           | /terms-condition | 05 Apr       | [Edit]    |
 | 3 | About Us        | /about-us        | 05 Apr       | [Edit]    |
 | 4 | Refund Policy   | /refund-policy   | 05 Apr       | [Edit]    |
 |                                                                    |
 | Editor: Rich text (bold, italic, headings, links, images, tables) |
 +------------------------------------------------------------------+

 Email Templates
 +------------------------------------------------------------------+
 | # | Template Name      | Subject            | Last Edited | Act   |
 |---|--------------------|--------------------|-------------|-------|
 | 1 | Interest Received  | New Interest from.. | 05 Apr      | [Edit]|
 | 2 | Interest Accepted  | Great news! ...    | 05 Apr      | [Edit]|
 | 3 | Welcome Email      | Welcome to ...     | 05 Apr      | [Edit]|
 | 4 | Password Reset     | Reset your pass... | 05 Apr      | [Edit]|
 | 5 | Membership Expiry  | Your plan expir... | 10 Apr      | [Edit]|
 |                                                                    |
 | Variables: {{user_name}}, {{matri_id}}, {{site_name}}, etc.       |
 +------------------------------------------------------------------+

 SMS Templates
 +------------------------------------------------------------------+
 | # | Template Name      | Content                       | Actions  |
 |---|--------------------|------------------------------ |----------|
 | 1 | Phone OTP          | Your OTP is {{otp}}. Valid... | [Edit]   |
 | 2 | Interest Received  | {{matri_id}} sent you an...  | [Edit]   |
 +------------------------------------------------------------------+

 Success Stories
 +------------------------------------------------------------------+
 | Tabs: [Pending Approval] [Published] [All]                        |
 |                                                                    |
 | # | Couple Names | Location  | Date    | Status  | Actions       |
 |---|-------------|-----------|---------|---------|---------------|
 | 1 | John & Mary | Mangalore | Mar 2026| Pending | [Approve][Rej]|
 | 2 | Ravi & Priya| Bangalore | Jan 2026| Visible | [Edit] [Hide] |
 |                                                                    |
 | [Add Story] → Names, photo, story, date, location, display order  |
 +------------------------------------------------------------------+

 Testimonials
 +------------------------------------------------------------------+
 | # | User Name | Rating | Testimonial Preview     | Active | Act   |
 |---|-----------|--------|-------------------------|--------|-------|
 | 1 | John D.   | *****  | "Great platform, foun.."| ON     | [Edit]|
 |                                                                    |
 | [Add Testimonial] → Name, MatriID, photo, rating, text, order    |
 +------------------------------------------------------------------+
```

### REPORTS & ANALYTICS
```
 User Reports
 +------------------------------------------------------------------+
 | Date Range: [01 Jan 2026] to [10 Apr 2026]     [Apply] [Export]  |
 |                                                                    |
 | +--- Registrations per Day (line chart, 30 days) ----+            |
 | +--- Gender Distribution (pie) ---+                               |
 | +--- Religion Distribution (pie) ---+                             |
 | +--- Top States (bar chart) ---+                                  |
 |                                                                    |
 | Profile Completion:                                               |
 |   0-25%: 120 users | 25-50%: 340 | 50-75%: 280 | 75-100%: 450   |
 |                                                                    |
 | Active vs Inactive: 980 active / 220 inactive                     |
 +------------------------------------------------------------------+

 Engagement Reports
 +------------------------------------------------------------------+
 | +--- Interests Sent per Day (line chart) ---+                     |
 |                                                                    |
 | Acceptance Rate:       42%                                        |
 | Avg Response Time:     18 hours                                   |
 | Most Viewed Profiles:  AM10001 (245 views), AM10023 (198 views)   |
 | Most Shortlisted:      AM10002 (67 times), AM10015 (54 times)     |
 | Least Active Users:    150 users (registered but never logged in) |
 +------------------------------------------------------------------+

 Revenue Reports
 +------------------------------------------------------------------+
 | +--- Revenue per Month (bar chart) ---+                           |
 | +--- Revenue by Plan (pie chart) -----+                           |
 |                                                                    |
 | This Month:    Rs 45,500 (15 subscriptions)                       |
 | Last Month:    Rs 38,200 (12 subscriptions)                       |
 | Total Revenue: Rs 2,45,000                                        |
 |                                                                    |
 | Active Subscriptions: 45                                          |
 | Renewal Rate:         35%                                         |
 | Avg Revenue/User:     Rs 850                                      |
 |                                                                    |
 | [Export CSV] [Export PDF]                                          |
 +------------------------------------------------------------------+
```

### SUPPORT
```
 Contact Inbox                                                 [5]
 +------------------------------------------------------------------+
 | Filters: Status (New/In Progress/Replied/Closed) | Assigned To    |
 |                                                                    |
 | # | Name  | Email        | Subject       | Date   | Status | Act  |
 |---|-------|--------------|---------------|--------|--------|------|
 | 1 | Ravi  | ravi@gm..    | Can't login   | 10 Apr | New    | [..] |
 | 2 | Priya | priya@gm..   | Refund request| 09 Apr | Open   | [..] |
 |                                                                    |
 | View → Full message + Reply thread                                |
 |   [Reply] (sends email via SMTP + saves in thread)                |
 |   [Assign to: ___] [Internal Note] [Close]                       |
 |   [Canned Responses: "Thank you for...", "We will look into..."]  |
 +------------------------------------------------------------------+
```

### SITE SETTINGS
```
 Site Settings (Single Page with Tabs)
 +------------------------------------------------------------------+
 | Tabs: [General] [Branding] [Email] [SMS] [Payment] [Registration] |
 |       [Social] [SEO]                                              |
 |                                                                    |
 | General:                                                          |
 |   Site Name:        [Kudla Matrimony     ]                        |
 |   Tagline:          [Find Your Perfect...]                        |
 |   Contact Email:    [info@kudlamatr...   ]                        |
 |   Contact Phone:    [+91 98765 43210     ]                        |
 |   WhatsApp:         [+91 98765 43210     ]                        |
 |   Address:          [Mangalore, Karnataka]                        |
 |                                                                    |
 | Branding:                                                         |
 |   Logo:             [Upload]  [Preview]                           |
 |   Favicon:          [Upload]  [Preview]                           |
 |   Primary Color:    [#8B1D91] [Color Picker]                      |
 |   Secondary Color:  [#00BCD4] [Color Picker]                      |
 |                                                                    |
 | Social:                                                           |
 |   Facebook:         [https://facebook.com/...]                    |
 |   Instagram:        [https://instagram.com/...]                   |
 |   YouTube:          [https://youtube.com/...]                     |
 |   Twitter/X:        [https://x.com/...]                           |
 |                                                                    |
 | SEO:                                                              |
 |   Default Meta Title:  [Kudla Matrimony - ...]                    |
 |   Meta Description:    [Trusted matrimony...]                     |
 |   Google Analytics ID: [G-XXXXXXXXX]                              |
 |   Facebook Pixel ID:   [XXXXXXXXX]                                |
 |   OG Image:            [Upload]                                   |
 |                                                                    |
 | [Save Settings]                                                   |
 +------------------------------------------------------------------+
```

### STAFF & TELECALLER
```
 Staff Members
 +------------------------------------------------------------------+
 | # | Name     | Role       | Branch    | Email        | Active     |
 |---|----------|------------|-----------|--------------|------------|
 | 1 | Ramesh   | Telecaller | Mangalore | ramesh@..    | ON         |
 | 2 | Suresh   | Br.Manager | Udupi     | suresh@..    | ON         |
 |                                                                    |
 | [Add Staff] → Name, ID, Email, Phone, Role, Branch, Password     |
 +------------------------------------------------------------------+

 Leads
 +------------------------------------------------------------------+
 | Filters: Status | Assigned To | Source | Follow-up Due             |
 |                                                                    |
 | # | Name  | Phone      | Source  | Assigned | Status    | Follow  |
 |---|-------|------------|---------|----------|-----------|---------|
 | 1 | Kumar | 9876543210 | Walk-in | Ramesh   | Contacted | 12 Apr  |
 | 2 | Anita | 9123456789 | Phone   | Suresh   | New       | -       |
 |                                                                    |
 | Status flow: New → Contacted → Interested → Registered → Closed  |
 | [Convert to Profile] → Pre-fills registration form                |
 +------------------------------------------------------------------+

 Call Logs
 +------------------------------------------------------------------+
 | # | Staff  | Lead/User | Type     | Duration | Outcome   | Notes  |
 |---|--------|-----------|----------|----------|-----------|--------|
 | 1 | Ramesh | Kumar     | Outgoing | 5 min    | Interested| "Will."|
 |                                                                    |
 | [Log Call] → Lead, Type, Duration, Outcome, Notes, Follow-up     |
 +------------------------------------------------------------------+

 Targets & Performance
 +------------------------------------------------------------------+
 | Staff: Ramesh (Telecaller, Mangalore)    Month: April 2026        |
 |                                                                    |
 | Registration Target:  50    Done: 23    [=======>        ] 46%    |
 | Revenue Target:       50K   Done: 18K   [====>           ] 36%    |
 | Call Target:          100/day Done: 8    [========>       ] 80%    |
 |                                                                    |
 | Leaderboard:                                                      |
 | #1 Suresh — 35 registrations | #2 Ramesh — 23 | #3 Meena — 18   |
 +------------------------------------------------------------------+
```

### FRANCHISE / BRANCHES
```
 Branches
 +------------------------------------------------------------------+
 | # | Branch Name | Code | Location  | Manager | Users | Active     |
 |---|-------------|------|-----------|---------|-------|------------|
 | 1 | Mangalore   | MNG  | Mangalore | Suresh  | 450   | ON         |
 | 2 | Udupi       | UDP  | Udupi     | Ramesh  | 230   | ON         |
 |                                                                    |
 | [Add Branch] → Name, Code, Location, Phone, Email, Manager       |
 | Affiliate Link: kudlamatrimony.com/register?ref=MNG  [Copy] [QR] |
 +------------------------------------------------------------------+

 Branch Revenue
 +------------------------------------------------------------------+
 | Branch: Mangalore (MNG)              Period: April 2026           |
 |                                                                    |
 | Total Registrations:  450                                         |
 | Paid Subscriptions:   45                                          |
 | Revenue:              Rs 1,25,000                                 |
 | Commission (10%):     Rs 12,500                                   |
 |                                                                    |
 | +--- Monthly Revenue Trend (bar chart) ---+                       |
 +------------------------------------------------------------------+
```

### ADVERTISEMENTS
```
 Ad Spaces
 +------------------------------------------------------------------+
 | # | Space Name           | Size      | Location     | Active      |
 |---|----------------------|-----------|--------------|-------------|
 | 1 | Homepage Banner      | 728x90    | Below hero   | ON          |
 | 2 | Search Sidebar       | 300x250   | Search page  | ON          |
 | 3 | Profile Sidebar      | 300x600   | Profile view | OFF         |
 | 4 | Mobile Banner        | 320x50    | All pages    | ON          |
 +------------------------------------------------------------------+

 Manage Ads
 +------------------------------------------------------------------+
 | # | Title     | Space          | Type  | Advertiser | Dates      |
 |---|-----------|----------------|-------|------------|------------|
 | 1 | Jewellers | Homepage Banner| Image | Sri Gold   | Apr-Jun    |
 |                                                                    |
 | [Create Ad] → Title, Space, Type (Image/HTML/AdSense),           |
 |               Image upload, Click URL, Start/End dates, Priority  |
 |                                                                    |
 | Analytics: Impressions: 12,450 | Clicks: 234 | CTR: 1.88%        |
 +------------------------------------------------------------------+
```

### SYSTEM
```
 Activity Log
 +------------------------------------------------------------------+
 | # | Admin    | Action                  | Target      | Date       |
 |---|----------|-------------------------|-------------|------------|
 | 1 | Admin    | Approved profile        | AM10050     | 10 Apr     |
 | 2 | Admin    | Changed plan price      | Gold: 2999  | 09 Apr     |
 | 3 | Admin    | Rejected ID proof       | AM10033     | 09 Apr     |
 +------------------------------------------------------------------+

 Cache Management
 +------------------------------------------------------------------+
 | [Clear Config Cache]    Last cleared: 10 Apr, 2:30 PM             |
 | [Clear View Cache]      Last cleared: 10 Apr, 2:30 PM             |
 | [Clear Route Cache]     Last cleared: 10 Apr, 2:30 PM             |
 | [Clear All Caches]                                                |
 +------------------------------------------------------------------+

 Database Backup
 +------------------------------------------------------------------+
 | [Create Backup Now]                                               |
 |                                                                    |
 | Auto Backup: [ON]  Schedule: Daily at 2:00 AM                     |
 |                                                                    |
 | # | Filename              | Size   | Date       | Actions        |
 |---|----------------------|--------|------------|----------------|
 | 1 | backup_2026-04-10.zip | 45 MB  | 10 Apr     | [Download]     |
 | 2 | backup_2026-04-09.zip | 44 MB  | 09 Apr     | [Download]     |
 +------------------------------------------------------------------+

 System Info
 +------------------------------------------------------------------+
 | PHP Version:      8.3.30                                          |
 | Laravel Version:  13.2.0                                          |
 | Filament Version: 5.4.3                                           |
 | MySQL Version:    8.0.36                                          |
 | Disk Usage:       12.4 GB / 100 GB                                |
 | Last Backup:      10 Apr 2026, 2:00 AM                            |
 | Cron Status:      Running (last run: 10 Apr, 8:00 AM)             |
 +------------------------------------------------------------------+
```

---

## Build Status (Updated April 12, 2026)

```
  COMPLETED                           PENDING (to build)
  ----------------------------------  -----------------------------------
  Dashboard (10 widgets, lazy+cache)  Verification (ID Proofs) — enhance
  User Management (card list,         Membership & Payments — enhance
    9 tabs, 14 filters, 8-tab view,   Communities / Castes CRUD
    9-section edit, admin notes)      Reference Data Management
  Site Settings (core)                Sub-caste cascading dropdown
  ID Verification (basic resource)    Discount Coupons
  Memberships (basic resource)        VIP / Featured Profiles
                                      Login History
                                      Bulk CSV Import
                                      Deleted Users (separate page)
                                      All Interests (view/manage)
                                      Reported Messages
                                      Match Weight Config
                                      Broadcast Notifications
                                      Success Stories (admin approve)
                                      Testimonials
                                      Email/SMS Templates
                                      Reports & Analytics
                                      Contact Inbox (support)
                                      Staff & Telecaller
                                      Franchise / Branches
                                      Advertisements
                                      Activity Log
                                      Cache Management
                                      Database Backup
                                      System Info
```

---

## Build Order (Current Plan)

```
Phase 1 — Active Development (for kudlamatrimony.com)
  1. [DONE] Dashboard
  2. [DONE] User Management
  3. [NEXT] Verification (ID Proofs) — enhance
  4. Membership & Payments — enhance
  5. Content Management (reference data, sub-caste dropdown)
  6. Interest & Match Management
  7. Moderation (photos, reports, blocks)
  8. Reports & Analytics
  9. Site Settings — enhancement

Phase 2 — CodeCanyon Features
  10. System (install wizard, updater)
  11. Franchise / Branches
  12. Staff / Telecaller
  13. Advertisements
  14. Wedding Directory (Phase 2)
```

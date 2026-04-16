# Manual Testing Checklist — Anugraha / Kudla Matrimony
**Test on:** https://kudlamatrimony.com (or localhost)
**Tester:** _____________ | **Date:** _______________

> Mark each item: ✅ Pass | ❌ Fail | ⏭ Skipped
> Note any bugs in the "Notes" column

---

## 1. PUBLIC PAGES (No Login Required)

| # | Test | Status | Notes |
|---|------|--------|-------|
| 1.1 | Homepage loads — hero, registration form, search widget, stats, how it works, why choose us, community browse, CTA | | |
| 1.2 | Homepage registration form has: country code selector, OTP hint, "Already have account? LOGIN" link, age calculator | | |
| 1.3 | `/about-us` loads | | |
| 1.4 | `/faq` loads — accordion expands/collapses | | |
| 1.5 | `/privacy-policy` loads | | |
| 1.6 | `/terms-condition` loads | | |
| 1.7 | `/refund-policy` loads | | |
| 1.8 | `/child-safety` loads | | |
| 1.9 | `/contact-us` loads — form + contact sidebar | | |
| 1.10 | `/contact-us` form submission works | | |
| 1.11 | `/membership-plans` loads — shows all plans, features, pricing | | |
| 1.12 | `/success-stories` loads | | |
| 1.13 | `/report-misuse` loads | | |
| 1.14 | `/blog` loads | | |
| 1.15 | `/demograph` loads | | |
| 1.16 | `/sitemap.xml` loads valid XML | | |
| 1.17 | `/robots.txt` loads, has sitemap reference | | |
| 1.18 | Footer shows: site links, social media links, copyright | | |
| 1.19 | Mobile hamburger menu works | | |
| 1.20 | Logo shows from admin settings | | |

---

## 2. REGISTRATION (5 Steps)

### Step 1: Account Creation
| # | Test | Status | Notes |
|---|------|--------|-------|
| 2.1 | Form shows: Full Name, Gender (Male/Female buttons), DOB + Age calc, Phone (country code), Email, Password | | |
| 2.2 | Submit with all valid data → goes to Step 2 | | |
| 2.3 | ❌ Submit with age < 18 → error | | |
| 2.4 | ❌ Submit with duplicate phone → error | | |
| 2.5 | ❌ Submit with duplicate email → error | | |
| 2.6 | ❌ Submit with password < 6 chars → error | | |
| 2.7 | ❌ Submit with empty gender → error | | |
| 2.8 | Password show/hide toggle works | | |
| 2.9 | "Already have an account? LOGIN" link works | | |
| 2.10 | Homepage hero form also submits to Step 1 correctly | | |

### Step 2: Physical & Religious Info
| # | Test | Status | Notes |
|---|------|--------|-------|
| 2.11 | Height, Complexion, Body Type, Physical Status, Marital Status dropdowns populate | | |
| 2.12 | Religion dropdown → shows correct denomination/caste fields per religion | | |
| 2.13 | Christian → shows Denomination, Diocese, Parish fields | | |
| 2.14 | Hindu → shows Caste, Gotra, Nakshatra, Rashi, Manglik fields | | |
| 2.15 | Jathakam file upload (JPG/PNG/PDF, max 2MB) | | |
| 2.16 | ❌ Wrong file format for jathakam → error | | |
| 2.17 | Family status field works | | |
| 2.18 | Submit → goes to Step 3 | | |

### Step 3: Education & Profession
| # | Test | Status | Notes |
|---|------|--------|-------|
| 2.19 | Education, Occupation, Income dropdowns populate | | |
| 2.20 | Working Country → State → District cascading works | | |
| 2.21 | Submit → goes to Step 4 | | |

### Step 4: Location & Contact
| # | Test | Status | Notes |
|---|------|--------|-------|
| 2.22 | Native Country → State → District cascading works | | |
| 2.23 | Custodian Name, Relation, Communication Address fields | | |
| 2.24 | Submit → goes to Step 5 | | |

### Step 5: Profile Creation Details
| # | Test | Status | Notes |
|---|------|--------|-------|
| 2.25 | Created By dropdown (Self, Parent, Sibling, Friend) | | |
| 2.26 | Submit → redirects based on verification settings | | |

### Verification (if enabled)
| # | Test | Status | Notes |
|---|------|--------|-------|
| 2.27 | Phone OTP page shows if phone verification enabled | | |
| 2.28 | OTP sent to phone | | |
| 2.29 | Correct OTP → verified | | |
| 2.30 | ❌ Wrong OTP → error | | |
| 2.31 | Resend OTP works (30s cooldown) | | |
| 2.32 | Email OTP page shows if email verification enabled | | |
| 2.33 | Email OTP sent | | |
| 2.34 | Correct email OTP → verified | | |
| 2.35 | Registration complete page shows → redirect to dashboard | | |

---

## 3. LOGIN & AUTH

| # | Test | Status | Notes |
|---|------|--------|-------|
| 3.1 | Login with email + password works | | |
| 3.2 | Login with phone + OTP works | | |
| 3.3 | ❌ Wrong password → error | | |
| 3.4 | ❌ Non-existent email → error | | |
| 3.5 | Forgot password → sends reset link | | |
| 3.6 | Reset password with valid token works | | |
| 3.7 | Logout works | | |
| 3.8 | Protected routes redirect to login when not authenticated | | |

---

## 4. DASHBOARD

| # | Test | Status | Notes |
|---|------|--------|-------|
| 4.1 | Profile sidebar shows: photo, name, matri_id, verification badges, completion % | | |
| 4.2 | Profile completion bar shows correct percentage | | |
| 4.3 | **If < 80%:** Shows completion CTA + sections checklist | | |
| 4.4 | Recommended matches section shows (if partner prefs set) | | |
| 4.5 | Stats bar: Interest Sent, Accepted, Profile Views, Pending Received, Shortlisted | | |
| 4.6 | Mutual matches section shows | | |
| 4.7 | **FREE user:** "Who Viewed Me" shows count + "Upgrade" CTA (no profile cards) | | |
| 4.8 | **PREMIUM user:** "Who Viewed Me" shows actual profile cards | | |
| 4.9 | Newly joined profiles section | | |
| 4.10 | Discover profiles section (6 categories) | | |
| 4.11 | **Unapproved profile:** Shows "Pending Approval" amber banner | | |
| 4.12 | Quick links: View & Edit Profile, Manage Photos | | |

---

## 5. PROFILE VIEW & EDIT

### View Own Profile
| # | Test | Status | Notes |
|---|------|--------|-------|
| 5.1 | `/profile` shows own profile with all 9 sections | | |
| 5.2 | Each section expandable/collapsible (accordion) | | |
| 5.3 | Edit button on each section works | | |
| 5.4 | `/profile/preview` shows how others see your profile | | |

### Edit Profile (9 Sections)
| # | Test | Status | Notes |
|---|------|--------|-------|
| 5.5 | **Primary:** Edit weight, blood group, mother tongue, complexion, body type, about me | | |
| 5.6 | **Religious:** Edit religion-specific fields, jathakam upload | | |
| 5.7 | **Education:** Edit education, occupation, income, working location | | |
| 5.8 | **Family:** Edit father/mother details, siblings count, family status | | |
| 5.9 | **Location:** Edit native/residing location, residency status | | |
| 5.10 | **Contact:** Edit phones, WhatsApp, email, addresses, reference person | | |
| 5.11 | **Hobbies/Lifestyle:** Edit diet, drinking, smoking, hobbies arrays | | |
| 5.12 | **Social:** Edit Facebook, Instagram, LinkedIn, YouTube, Website URLs | | |
| 5.13 | **Partner Prefs:** Edit all preference fields, cascading dropdowns | | |
| 5.14 | Profile completion % updates after edits | | |

### View Other's Profile
| # | Test | Status | Notes |
|---|------|--------|-------|
| 5.15 | Other profile loads with all public info | | |
| 5.16 | Match score + badge shows (if partner prefs set) | | |
| 5.17 | **Contact section — FREE + no accepted interest:** "Locked" message, upgrade CTA | | |
| 5.18 | **Contact section — PREMIUM + no accepted interest:** "Send interest first" message | | |
| 5.19 | **Contact section — PREMIUM + accepted interest:** Full contacts visible | | |
| 5.20 | **Contact section — FREE + accepted interest:** "Upgrade" message | | |
| 5.21 | Interest send button/status shows correctly | | |
| 5.22 | Shortlist (heart) button works | | |
| 5.23 | Report button shows → opens report form | | |
| 5.24 | Block button works (with confirmation) | | |
| 5.25 | Print profile link opens print-friendly page | | |
| 5.26 | Photo privacy: blurred photos for non-accepted users | | |

---

## 6. PHOTO MANAGEMENT

| # | Test | Status | Notes |
|---|------|--------|-------|
| 6.1 | `/manage-photos` loads with tabs: Profile, Album, Family, Archived | | |
| 6.2 | Upload profile photo (JPG) → watermark applied, shows as primary | | |
| 6.3 | Upload album photo (PNG) → watermark applied | | |
| 6.4 | Upload family photo (WebP) → watermark applied | | |
| 6.5 | ❌ Upload over limit (>9 album, >3 family) → error | | |
| 6.6 | ❌ Upload wrong format (PDF, BMP) → error | | |
| 6.7 | ❌ Upload over 5MB → error | | |
| 6.8 | Set photo as primary | | |
| 6.9 | Archive photo (soft delete) | | |
| 6.10 | Restore archived photo | | |
| 6.11 | Permanently delete photo | | |
| 6.12 | Photo privacy setting: visible_to_all / interest_accepted / hidden | | |
| 6.13 | **Verify watermark:** Download/view uploaded photo — site name visible as watermark | | |

---

## 7. SEARCH & DISCOVER

### Search
| # | Test | Status | Notes |
|---|------|--------|-------|
| 7.1 | `/search` loads with all filter dropdowns | | |
| 7.2 | Quick search with age + gender works | | |
| 7.3 | Advanced search with 5+ filters returns correct results | | |
| 7.4 | Keyword search finds by name/matri_id/occupation | | |
| 7.5 | ID search with valid matri_id → shows profile | | |
| 7.6 | ❌ ID search with invalid ID → "not found" | | |
| 7.7 | "Load Partner Preferences" button fills search form | | |
| 7.8 | Save search → appears in saved searches | | |
| 7.9 | Load saved search works | | |
| 7.10 | Delete saved search works | | |
| 7.11 | Pagination works (20 per page) | | |
| 7.12 | **Search results:** Diamond/Diamond+ profiles appear first (highlighted) | | |
| 7.13 | **Search results:** Then other premium profiles, then free | | |
| 7.14 | **Guest search:** Public search pages work without login | | |
| 7.15 | **Approved filter:** Unapproved profiles do NOT appear in search | | |

### Discover
| # | Test | Status | Notes |
|---|------|--------|-------|
| 7.16 | `/discover` hub shows all 13 categories | | |
| 7.17 | Click category → shows subcategories | | |
| 7.18 | Click subcategory → shows filtered profiles | | |
| 7.19 | Direct-filter categories (e.g., Kannadiga Matrimony) skip to results | | |
| 7.20 | Guest can browse discover pages | | |

---

## 8. INTEREST SYSTEM

### Send Interest
| # | Test | Status | Notes |
|---|------|--------|-------|
| 8.1 | Send interest with template message → success | | |
| 8.2 | **FREE user:** Send interest with custom message → blocked with upgrade message | | |
| 8.3 | **PREMIUM user:** Send interest with custom message → success | | |
| 8.4 | ❌ Send to same gender → error | | |
| 8.5 | ❌ Send to blocked profile → error | | |
| 8.6 | ❌ Send when daily limit reached → error with upgrade message | | |
| 8.7 | ❌ Re-send after decline within 30 days → error with days remaining | | |
| 8.8 | ❌ Send when other person already sent you interest → error "check received" | | |

### Interest Inbox
| # | Test | Status | Notes |
|---|------|--------|-------|
| 8.9 | Inbox loads with tabs: All, Received, Sent, Starred, Trash | | |
| 8.10 | Filters work: interest_received, interest_sent, i_accepted, etc. | | |
| 8.11 | Counts in sidebar are correct | | |
| 8.12 | Click interest → opens conversation thread | | |

### Accept / Decline / Cancel
| # | Test | Status | Notes |
|---|------|--------|-------|
| 8.13 | **Receiver:** Accept with template → status changes to Accepted | | |
| 8.14 | **Receiver:** Accept with custom reply | | |
| 8.15 | **Receiver:** Decline with template → status changes to Declined | | |
| 8.16 | **Receiver:** Decline silently (no notification to sender) | | |
| 8.17 | **Sender:** Cancel pending interest | | |
| 8.18 | Star/Unstar interest toggle | | |
| 8.19 | Move to trash | | |

### Chat/Messaging (Accepted Interests)
| # | Test | Status | Notes |
|---|------|--------|-------|
| 8.20 | **PREMIUM user:** Message input shows, can send messages | | |
| 8.21 | **FREE user:** Message input hidden, shows "Upgrade to Premium to chat" CTA | | |
| 8.22 | ❌ **FREE user:** Even if they somehow POST → backend blocks with error | | |
| 8.23 | Messages appear in conversation thread chronologically | | |
| 8.24 | Notifications sent on new message | | |

---

## 9. WHO VIEWED MY PROFILE

| # | Test | Status | Notes |
|---|------|--------|-------|
| 9.1 | `/views` loads with tabs: Viewed By Others, Profiles I Viewed | | |
| 9.2 | **FREE user — Viewed By tab:** Shows count + "Upgrade to See Viewers" button, NO profiles | | |
| 9.3 | **PREMIUM user — Viewed By tab:** Shows full profile list | | |
| 9.4 | "Profiles I Viewed" tab works for all users (no premium gate) | | |
| 9.5 | Profile view tracking works (visit someone's profile → appears in their views) | | |

---

## 10. SHORTLIST & BLOCK

| # | Test | Status | Notes |
|---|------|--------|-------|
| 10.1 | Shortlist (heart) on profile card → adds to shortlist | | |
| 10.2 | Un-shortlist → removes from shortlist | | |
| 10.3 | `/shortlist` page shows all shortlisted profiles | | |
| 10.4 | Block profile → confirm dialog → blocked | | |
| 10.5 | Blocked profile doesn't appear in search | | |
| 10.6 | `/blocked` page shows blocked profiles | | |
| 10.7 | Unblock works | | |
| 10.8 | Ignored profiles don't appear in search | | |

---

## 11. PHOTO REQUESTS

| # | Test | Status | Notes |
|---|------|--------|-------|
| 11.1 | Send photo request to profile with hidden photos | | |
| 11.2 | ❌ Send to self → error | | |
| 11.3 | ❌ Duplicate request → info message | | |
| 11.4 | `/photo-requests` shows Received + Sent tabs | | |
| 11.5 | Approve request → requester can see photos | | |
| 11.6 | Ignore request | | |
| 11.7 | Notification sent on request received/approved | | |

---

## 12. MEMBERSHIP & PAYMENT

| # | Test | Status | Notes |
|---|------|--------|-------|
| 12.1 | `/membership-plans` shows all plans with features comparison | | |
| 12.2 | Current plan highlighted (if subscribed) | | |
| 12.3 | "Free During Launch" badge shows when price = 0 or 1 | | |
| 12.4 | Click Upgrade → Razorpay checkout opens | | |
| 12.5 | Complete payment → redirects back with success | | |
| 12.6 | After payment: `isPremium()` returns true | | |
| 12.7 | ❌ Failed payment → handles gracefully | | |
| 12.8 | Previous membership deactivated on new purchase | | |

---

## 13. SETTINGS

| # | Test | Status | Notes |
|---|------|--------|-------|
| 13.1 | `/settings` loads all sections | | |
| 13.2 | **Visibility:** Change "Show Profile To" (All / Premium / Matches) | | |
| 13.3 | **Visibility:** Toggle same religion/denomination/mother tongue | | |
| 13.4 | **Alerts:** Toggle each email notification preference | | |
| 13.5 | **Hide Profile:** Toggle hide → profile hidden from search | | |
| 13.6 | **Change Password:** With correct current password → success | | |
| 13.7 | ❌ **Change Password:** Wrong current password → error | | |
| 13.8 | ❌ **Change Password:** New password < 6 chars → error | | |
| 13.9 | **Delete Profile:** Select reason → confirm → account deactivated, logged out | | |

---

## 14. REPORT PROFILE

| # | Test | Status | Notes |
|---|------|--------|-------|
| 14.1 | Report button visible on other's profile page | | |
| 14.2 | Click → opens report form with 7 reason options | | |
| 14.3 | Select reason + optional description → submit → success message | | |
| 14.4 | ❌ Report own profile → error | | |
| 14.5 | ❌ Duplicate pending report → info message | | |

---

## 15. SUCCESS STORIES

| # | Test | Status | Notes |
|---|------|--------|-------|
| 15.1 | `/success-stories` shows approved stories (or empty state) | | |
| 15.2 | "Share Your Story" button shows (logged in only) | | |
| 15.3 | Submit form: couple names, location, date, story, photo | | |
| 15.4 | Success message: "pending admin approval" | | |
| 15.5 | Homepage shows top 3 approved stories (if any exist) | | |

---

## 16. ID PROOF

| # | Test | Status | Notes |
|---|------|--------|-------|
| 16.1 | `/submit-id-proof` shows upload form | | |
| 16.2 | Select type (Aadhaar/Passport/etc), number, upload front + back | | |
| 16.3 | Submit → status shows "Pending" | | |
| 16.4 | ❌ Wrong file format → error | | |
| 16.5 | ❌ File > 5MB → error | | |
| 16.6 | Delete uploaded proof | | |

---

## 17. NOTIFICATIONS

| # | Test | Status | Notes |
|---|------|--------|-------|
| 17.1 | Bell icon shows unread count | | |
| 17.2 | Click bell → dropdown with recent notifications | | |
| 17.3 | `/notifications` shows full list | | |
| 17.4 | Mark single notification as read | | |
| 17.5 | Mark all as read | | |
| 17.6 | Interest sent → receiver gets notification | | |
| 17.7 | Interest accepted → sender gets notification | | |
| 17.8 | Photo request → target gets notification | | |
| 17.9 | Email notifications respect alert preferences | | |

---

## 18. SEO & META

| # | Test | Status | Notes |
|---|------|--------|-------|
| 18.1 | Homepage: meta description present, og:title, og:description | | |
| 18.2 | Canonical URL set on each page | | |
| 18.3 | Structured data (WebSite + Organization) in page source | | |
| 18.4 | `/sitemap.xml` returns valid XML with 30+ URLs | | |
| 18.5 | `/robots.txt` disallows private routes, allows public | | |
| 18.6 | Favicon shows from admin settings | | |

---

## 19. SUBSCRIPTION ENFORCEMENT SUMMARY

| # | Test | Status | Notes |
|---|------|--------|-------|
| 19.1 | **FREE:** Max 5 interests/day → 6th blocked | | |
| 19.2 | **FREE:** Cannot send custom/personalized message | | |
| 19.3 | **FREE:** Cannot chat in accepted interests | | |
| 19.4 | **FREE:** Cannot view contacts (even with accepted interest) | | |
| 19.5 | **FREE:** Who Viewed Me shows count only | | |
| 19.6 | **PREMIUM + no interest:** Cannot view contacts | | |
| 19.7 | **PREMIUM + accepted interest:** CAN view contacts | | |
| 19.8 | **PREMIUM:** CAN chat, send custom messages | | |
| 19.9 | **PREMIUM:** Who Viewed Me shows full profiles | | |
| 19.10 | **Diamond/Diamond+:** Profiles appear first in search | | |

---

## 20. MOBILE RESPONSIVENESS

| # | Test | Status | Notes |
|---|------|--------|-------|
| 20.1 | Homepage — hero + form stacks vertically | | |
| 20.2 | Navigation hamburger menu works | | |
| 20.3 | Search filters stack properly | | |
| 20.4 | Profile cards grid → single column on mobile | | |
| 20.5 | Interest inbox readable on mobile | | |
| 20.6 | Dashboard sidebar collapses properly | | |
| 20.7 | Registration form usable on mobile | | |
| 20.8 | Country code phone dropdown works on mobile | | |

---

## 21. PROFILE APPROVAL

| # | Test | Status | Notes |
|---|------|--------|-------|
| 21.1 | **Auto-approve ON:** New registration → profile immediately visible in search | | |
| 21.2 | **Auto-approve OFF:** New registration → profile NOT in search, dashboard shows "Pending Approval" banner | | |
| 21.3 | Admin approves profile → profile appears in search | | |
| 21.4 | Unapproved profiles hidden from: search, discover, homepage featured, matches | | |

---

## Bug Log

| # | Page/Feature | Bug Description | Severity | Screenshot |
|---|-------------|-----------------|----------|------------|
| | | | | |
| | | | | |
| | | | | |
| | | | | |
| | | | | |

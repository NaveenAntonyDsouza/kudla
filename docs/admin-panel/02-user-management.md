# 2. User Management

## 2a. All Users (Filament Table)

| Column | Filter | Sortable | Actions |
|--------|--------|----------|---------|
| ID | - | Yes | - |
| Matri ID | Search | Yes | Copy |
| Full Name | Search | Yes | - |
| Gender | Filter | Yes | - |
| Email | Search | - | - |
| Phone | Search | - | - |
| Religion | Filter | Yes | - |
| Registration Date | Date Range | Yes | - |
| Profile Completion | Range | Yes | - |
| Status | Filter (Active/Hidden/Deleted) | Yes | - |
| Verified | Filter (Phone/Email/ID) | - | - |
| Subscription | Filter (Free/Paid) | Yes | - |
| Actions | - | - | View, Edit, Deactivate, Delete, Login As |

**Bulk Actions:**
- Activate selected
- Deactivate selected
- Send email to selected
- Export to CSV/Excel

## 2b. User Detail Page

- All profile information (read-only with edit button)
- Photo gallery
- Verification status (Phone, Email, ID Proof)
- Subscription history
- Interest history (sent/received)
- Login history with IP address (see 2d)
- Activity log
- Admin notes

## 2c. Profile Link Sharing

Each profile gets a public shareable link:

- URL format: `https://yourdomain.com/profile/AM100008` (uses matri ID, not database ID)
- Shareable via WhatsApp, Email, Copy Link button on profile view
- Public link shows limited info (photo, age, religion, education, location) — no contact details
- Visitor must register/login to see full profile or send interest
- Share buttons: WhatsApp, Facebook, Email, Copy Link
- Admin setting: Enable/disable public profile sharing (toggle in Site Settings)

## 2d. Login History with IP Address

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

## 2e. VIP / Featured Profile

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

## 2f. Profile Summary Card Download

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

## 2g. Bulk Profile Import (CSV)

Admin can import multiple profiles at once via CSV upload:

**Import flow:**
1. Download CSV template (with column headers)
2. Fill in profile data (name, email, phone, DOB, gender, religion, etc.)
3. Upload CSV → system validates each row
4. Preview: shows valid rows (green) and errors (red with reason)
5. Confirm → bulk create profiles + send credentials via email/SMS

| Setting | Type | Description |
|---------|------|-------------|
| CSV File | File Upload | .csv or .xlsx |
| Assign to Branch | Select | Optional — assign all imported profiles to a branch |
| Registered By | Auto | Admin name / Staff name |
| Send Credentials | Toggle | Email login details to each imported user |
| Auto-Approve | Toggle | Skip approval queue for imported profiles |

**Error handling:**
- Duplicate email/phone → skip with warning
- Missing required fields → skip row, report error
- Invalid data format → highlight and suggest fix
- Export error report as CSV

**Use cases:**
- Franchise migrating profiles from old system
- Admin adding demo/test profiles
- Bulk onboarding from offline registration drives

## 2h. Login As User

- Admin can login as any user to debug issues
- Shows admin bar at top: "You are viewing as AM100008 [Return to Admin]"

# 2. User Management — COMPLETED

## 2a. All Users (Card-Style List)

Each profile displayed as a full-width card with 4 rows of info + action buttons.

### Card Layout
- **Row 1:** Name (Matri ID) | Plan Badge | Approved/Pending Status
- **Row 2:** Gender | Age | Phone | Email | Education | Occupation
- **Row 3:** Religion/Denomination | Location | Marital Status | Mother Tongue | Income | Created By
- **Row 4:** Profile % | Registered Date+Time (x ago) | Last Login Date+Time (x ago) | Notes Count | ID Verified

### 9 Tab Filters
All Members | Pending Approval (with count badge) | Incomplete (<60%) | Premium | Free Users | Expiring Soon (7 days) | Recent (7 days) | Inactive (30+ days) | Blocked/Deactivated

### 14 Sidebar Filters
Gender, Religion, Membership Plan, Profile Completion Range, Marital Status, Active Status, Approved, ID Verified, Has Photo, Hidden, Created By, Registration Date Range, Last Login Date Range, Native State

### Row Actions
View | Edit | WhatsApp (opens wa.me) | Quick Approve | Add Note (modal with text + follow-up date) | Activate/Deactivate

### Bulk Actions
Approve Selected | Activate Selected | Deactivate Selected | Export

## 2b. View Profile (8 Tabs)

Header section with photo, matri ID, name, gender, completion %, status badges (Approved, Active, ID Verified), plan badge with expiry date.

| Tab | Content |
|-----|---------|
| Personal | DOB, Age, Marital Status, Mother Tongue, Height, Weight, Complexion, Body Type, Blood Group, Physical Status, Created By, About Me |
| Account & Contact | Email, Phone, Email/Phone Verified, Last Login, Registered, WhatsApp, Custodian, Preferred Call Time, Reference, Communication Address |
| Religious | All 15 religion-specific fields (denomination, diocese, caste, gotra, nakshatra, rashi, muslim sect, jain sect, etc.) |
| Education & Career | 12 fields (education, college, occupation, employer, income, working location) |
| Family | Parents (4-column: name, occupation, house name, native place) + Siblings (married/unmarried/priest/nun) + Assets + About Family |
| Location | Native Country/State/District, Residing Country, Residency Status, PIN Code |
| Lifestyle & Social | Diet, Smoking, Drinking, Cultural Background + Instagram, Facebook, LinkedIn links |
| Admin Notes | All notes with follow-up dates, overdue badge (red), added by, timestamp. Badge shows note count on tab. |

### Header Actions
Edit | WhatsApp | Approve | Add Note | Activate/Deactivate

## 2c. Edit Profile (9 Sections)

All fields from 7 related tables editable in organized sections:

1. **Personal Information** (3-col) — Matri ID (readonly), Name, Gender, DOB, Marital Status, Mother Tongue, Height, Weight, Complexion, Body Type, Blood Group, Physical Status, About Me
2. **Account & Contact** (3-col) — Email, Phone, WhatsApp, Custodian, Preferred Call Time, Communication Address, PIN, Reference
3. **Religious Information** (3-col) — All 16 religion-specific fields
4. **Education & Career** (3-col) — All 12 fields
5. **Family Details** (3-col) — Father/Mother details, Siblings counts, Assets, About Family
6. **Location** (3-col) — Native/Residing country/state/district, Residency Status, PIN
7. **Lifestyle** (3-col, collapsed) — Diet, Smoking, Drinking, Cultural Background
8. **Social Media** (3-col, collapsed) — Instagram, Facebook, LinkedIn URLs
9. **Status & Admin Controls** (4-col) — Active, Approved, ID Verified, Hidden toggles

Saves to: profiles, users, religious_info, education_details, family_details, location_info, contact_info, lifestyle_info, social_media_links

## 2d. Admin Notes

- Add note with optional follow-up date from list page (row action) or view page (header action)
- Notes stored in `profile_notes` table with admin_user_id
- Upcoming Follow-ups widget on dashboard shows overdue items
- Note count badge visible on list cards and view page tab

## Deferred to Phase 2 (CodeCanyon / Staff Module)

| Feature | Reason |
|---------|--------|
| **VIP / Featured Profiles** | Needs new DB column + search ranking change. Not needed until 1000+ profiles. |
| **Login History** | Needs new table + middleware to log every login with IP/device/browser. Build with Staff/Telecaller module. |
| **Bulk CSV Import** | Complex feature (template download, validation, preview, error handling). Build with Staff/Telecaller module. |

## Technical Notes
- Card layout uses Filament `Tables\Columns\Layout\Split` and `Stack`
- `contentGrid(['default' => 1])` for full-width cards
- Plan badge uses `getStateUsing()` with `activeMembership()` method
- Notes count via `->withCount('profileNotes')` on Eloquent query
- Religion and Native State filters use manual `->options()` with `whereNotNull()` to avoid NULL crash
- Table polls every 60 seconds for live updates

# 7. Interest & Match Management

## 7a. All Interests

| Column | Filter | Details |
|--------|--------|---------|
| Sender | Search | Matri ID |
| Receiver | Search | Matri ID |
| Status | Filter | Pending/Accepted/Declined/Cancelled |
| Message | Preview | First 50 chars |
| Date | Date Range | Created at |
| Actions | - | View conversation, Delete |

## 7b. Reported Messages

Messages flagged by users for inappropriate content.

| Column | Details |
|--------|---------|
| Reported By | Matri ID |
| Reported User | Matri ID |
| Message Content | Full text |
| Reason | Harassment/Spam/Fake/Other |
| Date | When reported |
| Actions | Warn user / Block user / Dismiss report |

## 7c. Admin Recommend Matches

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

## 7d. Partner Preference Match Count

Show compatibility percentage on every profile card and profile view:

**Match Score Display:**
- Profile cards show "85% Match" badge (green if >80%, yellow 60-80%, grey <60%)
- Profile view page shows detailed breakdown:
  ```
  Match Score: 85%
  Age Range (15%) — Within preference
  Religion (15%) — Same religion
  Mother Tongue (10%) — Same language
  Location (10%) — Different state
  Education (10%) — Matches preference
  ...
  ```
- Uses the MatchingService weights (configurable from admin, see 7e)

## 7e. Match Score Weight Configuration

Admin can customize the matching algorithm weights from the admin panel:

| Criteria | Default Weight | Configurable |
|----------|---------------|--------------|
| Age Range | 15% | Yes |
| Religion | 15% | Yes |
| Denomination / Caste | 10% | Yes |
| Education | 10% | Yes |
| Occupation | 10% | Yes |
| Mother Tongue | 10% | Yes |
| Height Range | 10% | Yes |
| Location (native) | 10% | Yes |
| Marital Status | 5% | Yes |
| Family Status | 5% | Yes |
| Horoscope (Nakshatra/Rasi) | 0% (off) | Yes |

**Admin controls:**
- Slider or number input for each weight (must total 100%)
- "Reset to Default" button
- Enable/disable individual criteria (set weight to 0%)

### Horoscope / Kundli Matching

When enabled (weight > 0%), the matching engine includes astrological compatibility:

| Setting | Type | Description |
|---------|------|-------------|
| Enable Horoscope Matching | Toggle | Include in match score |
| Horoscope Weight | Number | % weight in overall score (e.g., 10%) |
| Match Type | Select | Nakshatra-based / Rasi-based / Both |
| Compatible Pairs | Table | Admin-editable compatibility chart (which Nakshatra matches which) |
| Show on Profile | Toggle | Display horoscope compatibility on profile view |

**How it works:**
- Uses Nakshatra (birth star) compatibility grid — traditional 10-point Guna matching simplified
- Admin can edit the compatibility chart (e.g., Ashwini + Bharani = Compatible)
- Score: Compatible = full points, Neutral = half points, Incompatible = 0
- Shows on profile: "Horoscope: Compatible" / "Partially Compatible" / "Not Checked"

## 7f. Broadcast Notifications

Admin can send notifications to:
- All users
- All male/female users
- Users by religion
- Users by state
- Users by subscription status (free/paid)
- Custom filter

Message fields: Title, Message, Link (optional)

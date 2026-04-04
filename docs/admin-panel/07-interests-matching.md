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

**Scoring logic:**
- Only criteria where the user HAS set a preference are scored
- Unset preferences are skipped — weights redistribute proportionally
- Each criteria scores 0 (no match) or 1 (match)
- Final score = (weighted sum of matched criteria) / (sum of weights for set criteria) x 100%

**Example:** User sets only Age, Religion, Education (total weight = 40).
Candidate matches Age + Religion but not Education → score = 30/40 = 75%.

**Badge display:**
- 80%+ = Green badge "Great Match"
- 60-79% = Yellow badge "Good Match"
- 40-59% = Grey badge "Partial Match"
- Below 40% = No badge (still shown unless filtered out)

**Profile view breakdown:**
```
Match Score: 85%
✅ Religion (15) — Same religion
✅ Age Range (15) — Within preference
✅ Mother Tongue (10) — Same language
✅ Education (10) — Matches preference
❌ Location (8) — Different state
✅ Occupation (10) — Matches preference
...
```

## 7e. Match Score Weight Configuration

Admin can customize the matching algorithm weights from the admin panel:

| Criteria | Default Weight | Match Logic | Configurable |
|----------|---------------|-------------|--------------|
| Religion | 15 | Profile religion IN preferred religions | Yes |
| Age Range | 15 | Profile age BETWEEN age_from AND age_to | Yes |
| Denomination / Caste | 10 | Profile denomination/caste IN preferred list | Yes |
| Mother Tongue | 10 | Profile mother_tongue IN preferred list | Yes |
| Education | 10 | Profile education IN preferred education levels | Yes |
| Occupation | 10 | Profile occupation IN preferred occupations | Yes |
| Height Range | 8 | Profile height BETWEEN height_from AND height_to | Yes |
| Location (native) | 8 | Profile native_state IN preferred states/countries | Yes |
| Location (working) | 5 | Profile working_country IN preferred working countries | Yes |
| Marital Status | 5 | Profile marital_status IN preferred list | Yes |
| Diet | 2 | Profile diet IN preferred diet | Yes |
| Family Status | 2 | Profile family_status IN preferred list | Yes |
| Horoscope (Nakshatra/Rasi) | 0 (off) | Nakshatra compatibility chart lookup | Yes |

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

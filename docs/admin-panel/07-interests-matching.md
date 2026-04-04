# 8. Interest & Match Management

## 8a. All Interests

| Column | Filter | Details |
|--------|--------|---------|
| Sender | Search | Matri ID |
| Receiver | Search | Matri ID |
| Status | Filter | Pending/Accepted/Declined/Cancelled |
| Message | Preview | First 50 chars |
| Date | Date Range | Created at |
| Actions | - | View conversation, Delete |

## 8b. Reported Messages

Messages flagged by users for inappropriate content.

| Column | Details |
|--------|---------|
| Reported By | Matri ID |
| Reported User | Matri ID |
| Message Content | Full text |
| Reason | Harassment/Spam/Fake/Other |
| Date | When reported |
| Actions | Warn user / Block user / Dismiss report |

## 8c. Admin Recommend Matches

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

## 8d. Partner Preference Match Count

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
- Uses the MatchingService weights from NEXT_SESSION_PLAN.md

## 8e. Broadcast Notifications

Admin can send notifications to:
- All users
- All male/female users
- Users by religion
- Users by state
- Users by subscription status (free/paid)
- Custom filter

Message fields: Title, Message, Link (optional)

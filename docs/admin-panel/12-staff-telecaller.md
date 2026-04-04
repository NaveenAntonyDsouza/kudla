# 12. Staff / Telecaller Module

For staff members who assist users with registration and profile management.

## 12a. Staff Management

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

## 12b. Telecaller Dashboard (with Charts)

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

## 12c. Lead Management

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

## 12d. Register on Behalf

Staff can register profiles for users who visit the branch:
- Fill registration form on behalf of user
- Upload photos on behalf
- Mark profile as "Registered by: Staff Name (Branch)"
- User receives credentials via SMS/Email

## 12e. Call Log

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

## 12f. Staff Performance Reports

| Metric | Description |
|--------|-------------|
| Registrations | Profiles created by this staff |
| Calls Made | Total calls today/week/month |
| Conversion Rate | Leads → Registrations % |
| Revenue Generated | Subscriptions from their users |
| Active Users | How many of their users are still active |
| Ranking | Staff leaderboard |

## 12g. Targets & Incentives

Admin sets monthly targets per staff:

| Target | Type | Description |
|--------|------|-------------|
| Registration Target | Number | e.g., 50 registrations/month |
| Revenue Target | Amount | e.g., ₹50,000/month |
| Call Target | Number | e.g., 100 calls/day |
| Incentive Per Registration | Amount | e.g., ₹100 per registration |
| Incentive Per Subscription | % or Amount | e.g., 10% of subscription |

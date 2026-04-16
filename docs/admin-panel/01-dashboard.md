# 1. Dashboard (Home) — COMPLETED

The first thing admin sees after login. All 10 widgets built with lazy loading + caching.

## Stats Cards (8 cards, cached 5 min)

- Total Users (all time)
- Active Users (not hidden/deleted)
- New Registrations Today
- Revenue This Month (paise -> rupees conversion)
- Total Interests Sent
- Active Subscriptions
- Pending ID Proofs
- Pending Profile Approvals

## Charts (all with 6 time-period tabs: 7d/30d/3m/6m/1y/All)

- **Registration Chart** — Line chart, optimized to single grouped query
- **Revenue Chart** — Bar chart, green color, paise->rupees conversion
- **Plan Sales Chart** — Bar chart, shows plan name + count + revenue
- **Gender Distribution** — Doughnut chart
- **Religion Distribution** — Doughnut chart (caches final array, not Eloquent Collection)
- **Caste/Denomination Chart** — Horizontal bar with 5 filter tabs (All/Hindu/Christian/Muslim/Jain), top 15 communities

## Tables

- **Recent Registrations** — Last 10, with matri_id, name, date
- **Recent Payments** — Last 10, with matri_id, plan badge (color-coded), amount (paise->rupees), payment ID
- **Upcoming Follow-ups** — Profiles with follow-up date <= today, overdue badge (red)

## Technical Notes
- All widgets use `protected static bool $isLazy = true;` for AJAX lazy loading
- Heavy widgets cached for 5 minutes to prevent dashboard timeout
- Cache stores final arrays, NOT Eloquent Collections (serialization gotcha)
- `php.ini` max_execution_time set to 120 seconds

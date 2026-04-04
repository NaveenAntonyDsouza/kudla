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

## Plan Documents

| # | Section | File | Priority |
|---|---------|------|----------|
| 1 | Dashboard | [01-dashboard.md](01-dashboard.md) | HIGH |
| 2 | User Management | [02-user-management.md](02-user-management.md) | HIGH |
| 3 | Profile Approval + ID Verification | [03-approvals.md](03-approvals.md) | HIGH |
| 4 | Membership, Payments & Coupons | [04-membership.md](04-membership.md) | HIGH |
| 5 | Site Settings (White-Label) | [05-site-settings.md](05-site-settings.md) | HIGH |
| 6 | Content Management | [06-content-management.md](06-content-management.md) | HIGH |
| 7 | Interest & Match Management | [07-interests-matching.md](07-interests-matching.md) | MEDIUM |
| 8 | Moderation, Support & Reports | [08-moderation.md](08-moderation.md) | MEDIUM |
| 9 | Reports & Analytics | [09-reports-analytics.md](09-reports-analytics.md) | MEDIUM |
| 10 | System, Installation & Updates | [10-system.md](10-system.md) | HIGH |
| 11 | Franchise / Branch Management | [11-franchise.md](11-franchise.md) | LOW |
| 12 | Staff / Telecaller Module | [12-staff-telecaller.md](12-staff-telecaller.md) | LOW |
| 13 | Advertisement Management | [13-advertisements.md](13-advertisements.md) | MEDIUM |
| 14 | Wedding Directory (Phase 2) | [14-wedding-directory.md](14-wedding-directory.md) | LOW |
| 15 | Implementation Summary | [15-implementation.md](15-implementation.md) | — |

---

## Quick Stats

- **15 sections** covering every admin function (files 01-14 + implementation)
- **41 CodeCanyon selling points**
- **27 Filament resources** to build

---

## Admin Roles & Permissions

| Role | Permissions |
|------|------------|
| **Super Admin** | Full access to everything |
| **Admin** | All except: delete users permanently, system settings, database backup |
| **Moderator** | Profile approval, ID verification, message review |
| **Support** | View users (read-only), respond to queries |

Uses `spatie/laravel-permission` (already installed).

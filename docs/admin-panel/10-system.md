# 10. System & Maintenance

## 10a. Activity Log

Track all admin actions:
- Who did what, when
- User approvals/rejections
- Setting changes
- Plan modifications

## 10b. Error Log Viewer

View `storage/logs/laravel.log` from admin panel.

## 10c. Cache Management

One-click buttons:
- Clear config cache
- Clear view cache
- Clear route cache
- Clear all caches

## 10d. Database Backup

- Manual backup download
- Scheduled auto-backup (daily)

## 10e. System Info

- PHP version
- Laravel version
- MySQL version
- Disk usage
- Last backup date

## 10f. Scheduled Tasks / Cron Jobs

View and manage scheduled tasks:
- Expire old pending interests (after 30 days)
- Clean up unverified accounts (after 7 days)
- Send daily match emails
- Generate daily/weekly reports
- Database backup

---

## 10g. Installation Wizard & License Activation

First-time setup wizard when admin opens `/admin` for the first time:

**Step 1 — Environment Check:**
```
PHP 8.3+
MySQL 8.0+
Required extensions: BCMath, Ctype, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML, cURL, GD/Imagick
storage/ writable
.env writable
```

**Step 2 — Database Setup:**
- Host, port, database name, username, password
- Test connection button
- Run migrations + seed reference data

**Step 3 — Admin Account:**
- Name, email, password for super admin

**Step 4 — Purchase Code Activation:**

| Field | Type | Description |
|-------|------|-------------|
| Purchase Code | Text | CodeCanyon purchase code (e.g., `a1b2c3d4-e5f6-7890-abcd-ef1234567890`) |
| Buyer Username | Auto | Fetched from Envato API after verification |
| License Type | Auto | Regular / Extended |
| Support Expiry | Auto | Date support expires |

**Verification flow:**
1. Customer enters purchase code
2. Script calls Envato API: `GET https://api.envato.com/v3/market/author/sale?code=PURCHASE_CODE`
3. API returns buyer info, license type, support dates
4. Script stores: purchase code, domain, activation date, license type in `site_settings`
5. Shows green checkmark: "License Activated — Thank you!"

**Step 5 — Site Settings:**
- Site name, logo, contact info (quick setup — editable later from Settings)

**After wizard:** redirect to admin dashboard, wizard never shows again.

---

## 10h. Update System

### Phase 1 — Manual Update (ZIP Upload)

Admin page: `System > Software Update`

```
┌─────────────────────────────────────────────┐
│ Software Update                              │
├─────────────────────────────────────────────┤
│                                              │
│ Current Version: v1.0.0                      │
│ License: Regular (Valid)                     │
│ Support: Active until 2027-04-01             │
│                                              │
│ ┌──────────────────────────────────────┐     │
│ │  Upload Update Package (.zip)        │     │
│ │  [Choose File] [Upload & Install]    │     │
│ └──────────────────────────────────────┘     │
│                                              │
│ Update Instructions:                         │
│ 1. Download latest version from CodeCanyon   │
│ 2. Upload the .zip file above                │
│ 3. Click "Upload & Install"                  │
│ 4. System will backup, extract, migrate      │
│                                              │
│ -- Update History --                         │
│ v1.0.0 — Initial release (2026-05-01)        │
└─────────────────────────────────────────────┘
```

**Update process (what happens on "Upload & Install"):**
1. Verify purchase code is still valid (Envato API call)
2. Verify zip contains valid update (check for `update_manifest.json`)
3. Create automatic backup (database SQL dump + current files snapshot)
4. Extract zip to temp directory
5. Replace application files (app/, resources/, config/, routes/, database/migrations/)
6. Run `php artisan migrate` (new migrations only)
7. Run `php artisan db:seed --class=UpdateSeeder` (if exists — adds new reference data)
8. Clear all caches (`config:clear`, `view:clear`, `route:clear`, `cache:clear`)
9. Update version number in `site_settings`
10. Log update in activity log
11. Show changelog + "Update Complete!" message

**Safety measures:**
- Pre-update backup downloadable from admin
- Rollback button (restore from backup if update fails)
- Maintenance mode enabled during update, disabled after
- File permission checks before starting

### Phase 2 — One-Click Auto-Update (post-launch)

Requires a separate **Update Server** (small Laravel app hosted on your VPS):

```
Customer Admin Panel              Your Update Server
────────────────────              ──────────────────
[Check for Updates] ──────────►  /api/v1/check
  POST: {                         - Verify purchase code
    purchase_code,                 - Compare versions
    current_version,               - Return: latest version,
    domain,                          changelog, download URL
    php_version
  }
                    ◄──────────  Response: {
                                   "update_available": true,
                                   "latest_version": "1.2.0",
                                   "changelog": "...",
                                   "download_url": "...",
                                   "min_php": "8.3"
                                 }

[Update Now] ─────────────────►  /api/v1/download
  POST: {purchase_code, domain}    - Verify again
                    ◄──────────    - Serve update.zip
  Auto-install (same as Phase 1 steps 3-10)
```

**Update Server features (separate app):**
- Upload new version zip + write changelog
- Track all customer installations (domain, version, last check)
- Analytics: how many on each version, update adoption rate
- Revoke pirated/refunded licenses
- Send update notification emails to customers

---

## 10i. Version & Changelog Display

Admin can view full version history:

| Version | Date | Type | Changelog |
|---------|------|------|-----------|
| v1.2.0 | 2026-08-15 | Feature | Added Wedding Directory, Match Score display |
| v1.1.0 | 2026-07-01 | Feature | Added VIP profiles, Coupon system |
| v1.0.1 | 2026-05-15 | Bugfix | Fixed email template saving, mobile layout |
| v1.0.0 | 2026-05-01 | Release | Initial release |

Changelog stored in `update_manifest.json` inside each update zip:
```json
{
  "version": "1.2.0",
  "min_php": "8.3",
  "min_laravel": "13.0",
  "changelog": [
    {"type": "feature", "text": "Added Wedding Directory module"},
    {"type": "feature", "text": "Match compatibility score on profile cards"},
    {"type": "fix", "text": "Fixed email template not saving on some hosts"},
    {"type": "improvement", "text": "Optimized search query performance"}
  ],
  "migrations": true,
  "seeders": ["UpdateV120Seeder"]
}
```

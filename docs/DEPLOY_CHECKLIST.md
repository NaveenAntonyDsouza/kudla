# Deploy Checklist — MatrimonyTheme → Live Production

**Scope:** Generic runbook for deploying accumulated changes to live (kudlamatrimony.com or any host).

**Estimated total time:** 45-75 minutes end-to-end (first time). 20-30 min once you know the routine.

**Risk level:** Medium — schema changes, new composer deps, file overwrites. Follow every step in order.

**First successful deploy:** April 23, 2026 (51 users, 50 profiles, 5 paid subscriptions preserved with zero data loss)

---

## ⚠️ Lessons learned from past deploys (READ FIRST)

These bit us once — don't let them bite again:

1. **`deploy-build.ps1` must stay ASCII-only.** Windows PowerShell 5.1 reads `.ps1` files as system codepage, so em-dashes (`—`) inside strings break parsing. Use plain hyphens (`-`).

2. **The PowerShell script uses .NET ZipArchive** (not `Compress-Archive`). PS 5.1's `Compress-Archive` creates ZIPs with backslash entry names that Linux can't extract correctly (entries appear as flat files like `app\Console\Commands\X.php` instead of nested folders). Don't switch back.

3. **Hostinger File Manager → Extract** wants a destination folder name. **Leave it blank or use `.`** — typing anything (e.g., the ZIP basename) extracts into a subfolder. Also **check "Overwrite existing files"** — without it, modified files won't replace the old versions on disk (only NEW files get added).

4. **Renamed migrations create orphans.** When a migration file is renamed locally (e.g., `add_anugraha_fields...` → `add_extended_profile_fields...`), the old file STILL exists on the server after extract because extraction never deletes. Manually `rm` the old file BEFORE running `php artisan migrate`, or migrate will try to re-run it and fail with "column already exists". Verify with: `ls database/migrations/ | grep -E "PATTERN"`.

5. **Engagement email switches default to `'1'` (enabled)** when SiteSetting key doesn't exist. After a deploy that adds these features for the first time, they auto-enable and immediately email all users. **ALWAYS run the pre-flight SQL** in `PRODUCTION_DB_CLEANUP.sql` Part 0 to disable them, then re-enable gradually after verification.

6. **`config:cache` requires no closures in any config file.** If `php artisan config:cache` fails with `Closure::__set_state()`, find the closure in `config/X.php` and move it to a service class. We did this for `config/discover.php` → `App\Services\DiscoverConfigService`. Site works without config:cache (slightly slower) but production should always have it.

7. **`composer install --no-dev` removes 30+ dev packages** with cosmetic "Could not scan for classes" warnings. Safe to ignore — composer scans packages it just removed (race condition during dev cleanup).

8. **`filament:upgrade` runs automatically** as a composer post-install hook and republishes Filament JS/CSS to `public/`. Don't be surprised by the long output.

9. **The two-line carousel HTML bug** (`<div class="carousel-card flex-shrink-0 px-3"` missing closing `>`) was a pre-existing bug that browsers tolerated but Alpine.js couldn't bind. After our deploy + fix, it works. **Always smoke-test the success stories carousel** (auto-rotation should work).

10. **Carbon v3 changed `diffInDays()` to return float.** Display code that does `$lastLogin->diffInDays(now()) . 'd ago'` now shows `2.5020910621759d ago`. Cast `(int)` for clean output. Same applies to other `diffInX()` methods in views.

11. **Refactoring a data shape? Grep ALL consumers, not just controllers.** The discover service refactor (April 23) moved closures from `config/discover.php` into `DiscoverConfigService` and changed the shape from `'subcategories' => fn()` to `'subcategories_source' => 'methodName'`. I updated `DiscoverController::resolveSubcategories()` but missed `resources/views/pages/home/classic.blade.php` line 512 which ALSO resolved subcategories inline in a Blade `@php` block. Result: 500 error on every homepage visit 20 minutes after deploy. Recovery required a hotfix ZIP.
    - **Rule**: before committing ANY refactor that changes a key/property/shape accessed via `config()`, array access, or method chain, run a comprehensive grep: `grep -rn "property_name" app/ resources/ routes/ config/ database/` and update EVERY site that reads it. Blade `@php` blocks count.

12. **Fresh clones need `npm run build` before using `deploy-build.ps1`.** The `/public/build` path is gitignored (with two legacy files grandfathered in). On a fresh clone, the compiled CSS doesn't exist — `deploy-build.ps1` will bundle an empty `public/build/` or stale legacy files. Your deploy ZIP then ships a site with no CSS.
    - **Rule**: step 1 of EVERY pre-deploy is `npm run build`. Confirm new hashes in `public/build/assets/` before running `deploy-build.ps1`.

13. **Use tiny hotfix ZIPs for post-deploy bugs — don't full-redeploy.** When the homepage crashed 20 min after deploy on April 23, recovery was:
    - Identify broken files (2 Blade files).
    - Build `hotfix-YYYYMMDD-HHMM.zip` via PHP ZipArchive containing ONLY: the fixed files + any build artifacts that hadn't uploaded yet (manifest + CSS).
    - Upload to project root in File Manager.
    - Right-click → Extract → **Check Overwrite existing files** → current directory (no subfolder).
    - SSH: `php artisan view:clear` to flush stale compiled Blade.
    - Total recovery: ~5 minutes vs ~30 minutes for a full redeploy.
    - Use this same pattern for any future post-deploy bugs.

14. **Smoke test must include DevTools Network tab check for expected CSS hash.** If you rebuild CSS but forget to upload `manifest.json`, browsers fetch the manifest → follow the old hash → serve an old CSS file → page looks partially-broken with no obvious error. Or: if manifest points to a new CSS file you forgot to upload, page loads with no CSS (styles missing). Both are silent failures.
    - **Rule**: as part of smoke test, open DevTools → Network → filter `.css` → hard refresh → confirm the CSS file name being served matches the one your local `public/build/manifest.json` references.

---

## PRE-DEPLOY (on your local machine) — 10-15 min

### 1. Verify local state is clean
```bash
# You should be on the committed branch you want to deploy
git status                    # should be clean (or have only intended staged changes)
git log --oneline -5          # review recent commits
```

### 2. Build production assets locally
```bash
npm run build
# Should complete in ~10s, generate new CSS + JS with hashes
ls -la public/build/assets/   # verify new files exist
```

### 3. Run the test suites one last time (optional but recommended)
```bash
php artisan test              # if you have PHPUnit tests
# Or just spot-check: homepage + admin panel load
php artisan serve             # http://localhost:8000
```

### 4. Build the ZIP

**INCLUDE in the ZIP:**
```
app/
bootstrap/
config/
database/
public/                       (including the new public/build/)
resources/
routes/
storage/framework/            (empty structure only — see step 4b)
storage/app/public/           (only demo-avatars/ if you want demo data on live — otherwise skip)
composer.json
composer.lock
package.json
package-lock.json
artisan
.htaccess                     (project root, if present)
```

**EXCLUDE from the ZIP (critical):**
```
.env                          ← live .env has real Razorpay keys + DB creds
.env.example, .env.production ← optional, not needed on server
vendor/                       ← composer install on server instead
node_modules/                 ← never upload
.git/                         ← never upload
storage/app/public/branding/  ← has live logo/favicon uploads
storage/app/public/photos/    ← has user photos
storage/app/public/id-proofs/ ← has sensitive ID docs
storage/app/public/jathakam/  ← has user documents
storage/logs/*                ← server has its own logs
storage/framework/cache/*     ← will regenerate
storage/framework/sessions/*  ← has live session data
storage/framework/views/*     ← compiled views, regenerate
public/storage                ← this is a symlink, not a real dir
tests/                        ← not needed in production
docs/                         ← optional, not needed for runtime
.phpunit.*                    ← dev only
.github/                      ← if present
README.md                     ← optional (small file, OK either way)
*.log
storage/app/test_*.php        ← any leftover test scripts
```

**Suggested zip command** (from project root):
```bash
# Windows / Git Bash / WSL
zip -r deploy-$(date +%Y%m%d-%H%M).zip \
    app bootstrap config database public resources routes \
    composer.json composer.lock package.json package-lock.json \
    artisan \
    -x "node_modules/*" -x ".git/*" -x "vendor/*" \
    -x "storage/app/public/photos/*" -x "storage/app/public/branding/*" \
    -x "storage/logs/*" -x "tests/*" -x "docs/*" \
    -x ".env*"
```

Rename to something obvious like `deploy-2026-04-23.zip`.

### 5. Run `docs/PRODUCTION_DB_CLEANUP.sql` Part 2 audit locally first
```sql
-- Open PRODUCTION_DB_CLEANUP.sql and run the SELECT queries (Part 2 step 2a + 2c) against
-- your LOCAL database first to see what you'd find on live. Know what to expect.
```

---

## BACKUP (on live server) — 5-10 min

### 6. Backup the live database
**Option A (admin panel):** Log in to admin → Settings → Database Backup → Download. Save the `.sql` file somewhere safe.

**Option B (SSH):**
```bash
cd /home/u000000/public_html    # your actual path
mysqldump -u DB_USER -p DB_NAME > backup-$(date +%Y%m%d-%H%M).sql
```

### 7. Backup the current code (in case rollback is needed)
```bash
cd /home/u000000/
tar -czf public_html-backup-$(date +%Y%m%d-%H%M).tar.gz public_html
```

---

## DEPLOY (on live server) — 20-30 min

### 8. Enable maintenance mode
```bash
cd /home/u000000/public_html
php artisan down --message="Upgrading — back in a few minutes"
```

Users visiting the site see a "Be right back" page during the deploy.

### 9. Upload the ZIP
Use Hostinger File Manager or FTP/SFTP to upload `deploy-2026-04-23.zip` into `public_html/` (or a `deploys/` subdirectory).

### 10. Extract the ZIP, overwriting existing files
```bash
cd /home/u000000/public_html
# If your zip extracts into a subfolder, cd into it and sync
unzip -o deploy-2026-04-23.zip

# Or for Hostinger File Manager: use "Extract" on the zip
```

**Verify the excluded directories are NOT overwritten:**
```bash
ls -la storage/app/public/        # should still have branding/, photos/, etc.
ls -la .env                       # should still exist with live values
```

### 11. Run the database migration rename SQL (CRITICAL — before any migrate)
```bash
# Connect to MySQL
mysql -u DB_USER -p DB_NAME

# Then in mysql prompt:
UPDATE migrations
SET migration = '2026_03_31_161609_add_extended_profile_fields_to_tables'
WHERE migration = '2026_03_31_161609_add_anugraha_fields_to_tables';

# Verify (should return 1 row):
SELECT migration FROM migrations WHERE migration LIKE '%extended_profile_fields%';

EXIT;
```

Alternatively, run `docs/PRODUCTION_DB_CLEANUP.sql` Part 1 in phpMyAdmin.

### 12. (Optional) Audit + clean any stale `anugraha` email references
Open `docs/PRODUCTION_DB_CLEANUP.sql` Part 2. Run the SELECT queries. If you find rows, customize the UPDATEs with your real email and run them.

Example found:
```sql
SELECT `key`, `value` FROM site_settings WHERE `value` LIKE '%anugraha%';
-- Returns: ('email', 'info@anugrahamatrimony.com'), ...

UPDATE site_settings
SET `value` = 'info@kudlamatrimony.com'
WHERE `key` = 'email' AND `value` LIKE '%anugraha%';
```

### 13. Install composer dependencies on server
```bash
cd /home/u000000/public_html
composer install --no-dev --optimize-autoloader
```

This will:
- Install `league/flysystem-aws-s3-v3` + `aws/aws-sdk-php` (new dependencies)
- Update other packages if `composer.lock` has changed
- Optimize the autoloader

**Watch for warnings.** If any package fails to install, STOP and investigate.

### 14. Run database migrations
```bash
php artisan migrate --force
```

Expected migrations to run (all accumulated since last deploy):
- `2026_04_14_*` suspension columns, notification templates, admin recommendations
- `2026_04_16_*` static pages, coupons, advertisements, last_login, login_history
- `2026_04_17_*` staff_roles, leads, call_logs
- `2026_04_18_*` staff_targets
- `2026_04_19_*` branches, branch_payouts, affiliate_clicks, bulk_imports, photo storage driver
- `2026_04_22_*` fonts on theme_settings

**If migrate fails**, the SQL rename (step 11) may have been missed. Run step 11 then retry.

### 15. Verify symlink for public/storage
```bash
ls -la public/storage
# Should show: lrwxrwxrwx ... public/storage -> /home/u000000/public_html/storage/app/public
```

If the symlink is broken or missing:
```bash
rm public/storage          # remove if exists
ln -s ../storage/app/public public/storage
```

### 16. Clear caches + optimize
```bash
php artisan optimize:clear       # clears: config, view, event, route caches
php artisan config:cache         # re-cache config
php artisan route:cache          # re-cache routes
php artisan view:cache           # re-cache views
```

### 17. Set proper file permissions
```bash
chmod -R 775 storage bootstrap/cache
```

### 18. Exit maintenance mode
```bash
php artisan up
```

---

## SMOKE TEST (live) — 5-10 min

### 19. Public homepage
- [ ] Visit kudlamatrimony.com — homepage loads, no errors
- [ ] Browser DevTools console — no 404s on CSS/JS/images
- [ ] CSS hash loaded is `app-4erY1nPZ.css` (or whatever the latest build produced)
- [ ] Hero image loads, registration form renders
- [ ] Stats section shows numbers
- [ ] Testimonials carousel works
- [ ] Community browse links work
- [ ] Responsive test: narrow browser to 375px — layout stacks properly

### 20. Admin panel
- [ ] Login to /admin works with your existing super admin credentials
- [ ] Dashboard loads, widgets show data
- [ ] Navigate: Users, Leads, Staff Targets, Branches, Theme & Branding, Photo Storage, Storage Settings
- [ ] Theme & Branding page shows font + color + logo controls
- [ ] Photo Storage page shows 4 drivers (Local, Cloudinary, R2, AWS S3)
- [ ] Click "Test Connection" on currently-active storage driver — should return success

### 21. Core flows
- [ ] Create a test profile (or use existing test account) — register, login
- [ ] Search profiles — results return
- [ ] Send interest to a test profile — goes through
- [ ] Admin: approve a pending profile — updates
- [ ] Admin: Database Backup — download works

### 22. Check the error logs for 5 min
```bash
tail -f storage/logs/laravel.log
```
Hit a few pages while watching. Should see no uncaught exceptions.

---

## ROLLBACK (if something goes wrong) — 5-10 min

### 23. Emergency rollback
```bash
cd /home/u000000/public_html
php artisan down

# Restore code
cd /home/u000000/
rm -rf public_html
tar -xzf public_html-backup-YYYYMMDD-HHMM.tar.gz

# Restore database (if schema changed)
mysql -u DB_USER -p DB_NAME < backup-YYYYMMDD-HHMM.sql

# Clear caches
cd public_html
php artisan optimize:clear

# Exit maintenance mode
php artisan up
```

---

## POST-DEPLOY — 5 min

### 24. Update your local `main` branch to reflect deployed state
```bash
git tag deploy-2026-04-23
git push --tags     # optional — nice for future reference
```

### 25. Delete the uploaded ZIP from the server (saves disk)
```bash
rm /home/u000000/public_html/deploy-2026-04-23.zip
```

### 26. Update `docs/FEATURE_STATUS.md` with deploy date
Note "Deployed: April 23, 2026" at top.

### 27. Monitor for 24h
Keep an eye on:
- `storage/logs/laravel.log` — any new exceptions
- User-reported issues via Contact form
- Payment flow (Razorpay webhook)
- Scheduled cron jobs (re-engagement emails, profile nudges)

---

## Common issues + fixes

| Symptom | Cause | Fix |
|---------|-------|-----|
| `500 error` on homepage | Permissions on storage/bootstrap/cache | `chmod -R 775 storage bootstrap/cache` |
| CSS not loading | Wrong build hash in manifest | Re-upload `public/build/` directory |
| `Class "League\Flysystem\AwsS3V3\AwsS3V3Adapter" not found` | `composer install` didn't run | Re-run `composer install --no-dev` |
| Photos 404 | `public/storage` symlink broken | `ln -s ../storage/app/public public/storage` |
| Migration "column already exists" | SQL rename (step 11) was skipped | Run step 11, then `php artisan migrate` again |
| "Class App\Models\X not found" | Stale autoload | `composer dump-autoload -o` |
| Admin panel empty/blank | Filament asset cache | `php artisan filament:assets` |

---

## For future deploys — consider git-based deployment

After this ZIP deploy, set up git on the server so next time you can:
```bash
cd /home/u000000/public_html
git pull                          # 10 seconds vs 10 minutes
composer install --no-dev -o      # if composer.lock changed
php artisan migrate --force
php artisan optimize:clear
```

Hostinger hPanel → Advanced → Git supports this. Connect your GitHub/GitLab repo, enable auto-deploy or manual pull. Much less error-prone than ZIP.

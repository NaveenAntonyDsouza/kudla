-- ============================================================================
-- Post-brand-purge DB cleanup for live production database
-- ============================================================================
--
-- Run this ONCE on the live MySQL database BEFORE your next deployment
-- that includes the brand-purged codebase.
-- ============================================================================

-- ----------------------------------------------------------------------------
-- PART 1: Migration rename (REQUIRED)
-- ----------------------------------------------------------------------------
--
-- The migration file was renamed from:
--   2026_03_31_161609_add_anugraha_fields_to_tables.php
-- to:
--   2026_03_31_161609_add_extended_profile_fields_to_tables.php
--
-- Without this UPDATE, Laravel would think the renamed file is a NEW
-- migration and try to run it on next `php artisan migrate`, which would
-- fail because the columns already exist.

UPDATE migrations
SET migration = '2026_03_31_161609_add_extended_profile_fields_to_tables'
WHERE migration = '2026_03_31_161609_add_anugraha_fields_to_tables';

-- Verify (should return 1 row):
-- SELECT migration FROM migrations WHERE migration LIKE '%extended_profile_fields%';


-- ----------------------------------------------------------------------------
-- PART 2: Audit live site_settings for stale anugraha email references
-- ----------------------------------------------------------------------------
--
-- The code purge did NOT touch the live DB's `site_settings` table
-- (by design — to preserve your Kudla Matrimony live branding).
--
-- However, some rows may still have `info@anugrahamatrimony.com` left over
-- from initial seeding months ago. Audit and fix before CodeCanyon launch.

-- Step 2a: Find any rows containing "anugraha" in value column
SELECT `key`, `value`
FROM site_settings
WHERE `value` LIKE '%anugraha%' COLLATE utf8mb4_general_ci;

-- Step 2b: If rows found above, update them. EXAMPLE for the email setting
-- (replace 'info@kudlamatrimony.com' with your actual contact email):
--
-- UPDATE site_settings
-- SET `value` = 'info@kudlamatrimony.com'
-- WHERE `key` = 'email' AND `value` LIKE '%anugraha%';

-- Step 2c: Check admin user email (seeded long ago as admin@anugrahamatrimony.com)
SELECT id, email, name
FROM users
WHERE email LIKE '%anugraha%' COLLATE utf8mb4_general_ci;

-- If found, update the super admin email to something you control:
-- UPDATE users
-- SET email = 'admin@kudlamatrimony.com'
-- WHERE email = 'admin@anugrahamatrimony.com';


-- ----------------------------------------------------------------------------
-- PART 3: After running the above, deploy the new code
-- ----------------------------------------------------------------------------
--
-- Once the above is done, you can safely deploy the brand-purged codebase
-- and run:
--   php artisan migrate
--   php artisan config:clear
--   php artisan view:clear
--   php artisan cache:clear
--
-- The renamed migration will be recognized as already-run and skipped.
-- All Anugraha-era defaults in seeders are now MatrimonyTheme — but seeders
-- only run on fresh installs (`php artisan db:seed`), not on existing
-- databases. Your live DB's site_settings continue to drive the live site.

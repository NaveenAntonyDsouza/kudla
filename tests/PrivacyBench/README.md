# Privacy test-member bench

Ten test members whose private details are unique "canary" values (phones, emails,
address, reference contact, creator's number, ID proof, staff note, horoscope file,
photos, interest messages), with interests, chats, blocks, views, shortlists and photo
requests between them, all made through the app's own code. Every website page, app
answer, email and visitor page is opened as each kind of member and scanned for other
members' canaries and for members the viewer has no reason to see.
Built in the privacy check of 4 Nov 2026; see `Bench.php` for the rules.

It is **not** part of the normal suite and never touches a live database: it needs the
local MySQL database `matrimony_privacy_bench` and refuses any other. Each test rolls
back its own members.

## Set up once (or after new migrations)

```bash
php artisan tinker --execute='DB::statement("CREATE DATABASE IF NOT EXISTS matrimony_privacy_bench CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");'
DB_DATABASE=matrimony_privacy_bench php artisan migrate:fresh --force
for s in RoleSeeder StaffRolesSeeder CommunitySeeder MembershipPlanSeeder SiteSettingsSeeder ThemeSettingsSeeder EmailTemplateSeeder NotificationTemplateSeeder StaticPageSeeder FaqSeeder; do DB_DATABASE=matrimony_privacy_bench php artisan db:seed --class=$s --force; done
```

## Run (about 2 minutes)

```bash
php vendor/bin/pest -c phpunit.privacy-bench.xml
```

Run it before deploying any change to who may see what (profile access, lists,
contact details, photos, interests, emails, the app's answers).

# Anugraha Matrimony — Tech Stack

## Core Framework
- **Laravel 13.2.0** — PHP full-stack framework
- **PHP 8.3.30** — Server-side language (GD extension enabled for image processing)

## Frontend
- **Blade** — Laravel's templating engine (server-rendered HTML)
- **Tailwind CSS 4.2.2** — Utility-first CSS framework (CSS-first config, no tailwind.config.js)
- **Alpine.js 3.15.10** — Lightweight JavaScript framework (loaded via Livewire)
- **Livewire 4.2.3** — Dynamic UI components (Alpine.js delivery + asset injection)

## Database
- **MySQL 8.0** — Relational database
- **Eloquent ORM** — Laravel's database abstraction layer
- **20+ tables** — Normalized schema with separate tables per profile section (religious_info, education_details, family_details, location_info, contact_info, lifestyle_info, etc.)

## Authentication & Authorization
- **Laravel built-in Auth** — Session-based authentication
- **Hash (bcrypt)** — Password hashing
- **Spatie Laravel Permission 6.25** — Role-based access control (HasRoles trait on User model)
- **OTP Service** — Phone/Email verification (stub — SMS provider pending)

## File Storage & Media
- **Laravel Filesystem** — Local disk storage (`storage/app/public/`)
- **Public disk** — Photos (profile/album/family), ID proofs, Jathakam uploads
- **WatermarkService** — GD library-based diagonal text watermark on uploaded photos
- **Photo privacy** — 3 modes (visible_to_all, interest_accepted, hidden) with CSS blur

## Payment Gateway
- **Razorpay** — Payment processing (Indian payments)
- **HTTP Client** — Direct API integration via `Http::post()` (no SDK package)
- **Subscription model** — Two tables: `subscriptions` (Razorpay audit, amount in paise) + `user_memberships` (feature access)
- **Plans** — Free, Silver, Gold, Diamond, Diamond Plus

## Email
- **Laravel Mail** — Email sending via SMTP
- **Hostinger SMTP** — `smtp.hostinger.com:465` (SSL)
- **Markdown Mailables** — Interest notifications, membership expiry reminders

## Search & Matching
- **MySQL queries** — Standard Eloquent `where`/`whereIn` (no external search engine)
- **ProfileQueryFilters trait** — Shared base query with `->approved()` scope, privacy filters, religion/denomination/mother-tongue preference matching
- **Search ranking** — 3-tier ordering: Diamond (highlighted) → Premium → Recently Active → Newest
- **Quick Search** — Filter by religion, caste, denomination, age range, location
- **Advanced Search** — 15+ filters (education, income, marital status, height, complexion, etc.)
- **Discover pages** — Category-based browsing (by religion, caste, denomination, location, mother tongue)
- **Sort options** — Relevance (default), Newest First, Recently Active, Age (Low/High)
- **MatchingService** — Weighted scoring algorithm (religion 15%, age 10%, location 10%, education 10%, etc.)

## Chat & Notifications
- **Interest Messaging** — Database-stored chat between matched profiles (premium only)
- **In-app Notifications** — Database-driven (`notifications` table), displayed in header bell icon
- **Email Notifications** — Membership expiry reminders (3-day warning + expiry day), interest notifications
- **Scheduled Tasks** — `membership:expiry-reminders` runs daily at 8 AM via Laravel Scheduler + Hostinger Cron
- **Real-time:** Not implemented — standard page refresh. WebSockets (Laravel Reverb) planned for future

## SEO
- **Dynamic meta tags** — Per-page title, description, canonical URL, Open Graph, Twitter Cards
- **Structured data** — Organization + WebSite JSON-LD schema
- **Dynamic XML sitemap** — `/sitemap.xml` with 30+ URLs
- **robots.txt** — Configured with Disallow for private routes
- **.htaccess** — HTTPS redirect, www→non-www, GZIP compression, browser caching (1 month for static assets)

## Admin Panel
- **Filament 5.4.3** — Admin panel framework
  - **Dashboard:** 10 widgets (stats, charts, tables) with lazy loading + 5-min caching
  - **User Management:** Card-style list, 9 tab filters, 14 sidebar filters, 5 row actions, tabbed view (8 tabs) + sectioned edit (9 sections)
  - **Site Settings:** Dynamic configuration (site name, logo, contact info, profile ID prefix)
  - **ID Verification:** Resource page (pending enhancement)
  - **Memberships:** Resource page (pending enhancement)

## Build Tools
- **Vite 8.0** — Asset bundling (CSS + JS)
- **laravel-vite-plugin 3.0** — Laravel integration
- **@tailwindcss/vite** — Tailwind CSS Vite plugin
- **@tailwindcss/forms** — Form styling plugin
- **npm** — Package manager

## Hosting & Deployment
- **Hostinger Business** — Shared hosting
- **LiteSpeed** — Web server
- **SSH** — Remote access for deployments
- **Manual FTP upload** — File-by-file deployment (no CI/CD)
- **Domains:** anugrahamatrimony.com, kudlamatrimony.com (white-label)
- **Cron:** Hostinger hPanel → Cron Jobs (crontab not available via SSH)

## PHP Packages (composer.json)

| Package | Version | Purpose |
|---------|---------|---------|
| `laravel/framework` | ^13.0 | Core framework |
| `filament/filament` | ^5.4 | Admin panel |
| `livewire/livewire` | ^4.2 | Dynamic UI components + Alpine.js |
| `spatie/laravel-permission` | ^6.25 | Role-based access control |
| `phpoffice/phpspreadsheet` | ^5.6 | Excel file generation |
| `laravel/tinker` | ^3.0 | REPL for debugging |

**Dev only:**

| Package | Purpose |
|---------|---------|
| `barryvdh/laravel-debugbar` | Debug toolbar |
| `laravel/pint` | Code formatting |
| `laravel/pail` | Real-time log viewer |
| `phpunit/phpunit` | Testing framework |

## npm Packages (package.json)

| Package | Version | Purpose |
|---------|---------|---------|
| `tailwindcss` | ^4.2.2 | CSS framework |
| `@tailwindcss/forms` | ^0.5.11 | Form element styling |
| `alpinejs` | ^3.15.10 | JavaScript interactivity (loaded via Livewire) |
| `axios` | ^1.11.0 | HTTP client for AJAX requests |
| `vite` | ^8.0.0 | Build tool |
| `laravel-vite-plugin` | ^3.0.0 | Laravel Vite integration |
| `concurrently` | ^9.0.1 | Run multiple dev processes |

## Should Adopt Later (when scaling)

| Package | When to Adopt | Why |
|---------|--------------|-----|
| **Meilisearch** | 1000+ profiles | Fast full-text search, typo-tolerant, Laravel Scout compatible |
| **Cloudinary** | When storage exceeds hosting limit | Image CDN, optimization, transformations |
| **Razorpay SDK** | When adding webhooks, refunds | Better error handling, webhook verification |
| **Laravel Reverb** | When real-time chat is needed | WebSocket server for live messaging |
| **Redis** | 500+ concurrent users | Session/cache/queue performance |
| **Fast2SMS** | For actual phone OTP | Currently OTP is stub only |
| **Laravel Horizon** | When using Redis queues | Queue monitoring dashboard |

## Filament 5 Gotchas

| Issue | Solution |
|-------|----------|
| `Section`, `Tabs`, `Grid` not in `Forms\Components` | Use `Filament\Schemas\Components\*` |
| `Tab` not in `Resources\Components` | Use `Filament\Schemas\Components\Tabs\Tab` |
| `Split` layout doesn't exist in schemas | Use `Section` with grid columns |
| `$navigationIcon` type error | Must be `BackedEnum\|string\|null` |
| `$navigationGroup` type error | Must be `\UnitEnum\|string\|null` (not `?string`) |
| RelationManager `$icon` type error | Must be `BackedEnum\|string\|null` |
| Custom Page `$view` property | Must be non-static: `protected string $view` (not `static`) |
| Widget timeout on dashboard | Add `protected static bool $isLazy = true;` |
| Filament assets not loading | Set `inject_assets = true` in Livewire config |
| `SelectFilter` crashes on NULL DB values | Use manual `->options()` with `whereNotNull()` |
| Cache serialization error | Cache final arrays, not Eloquent Collections |

## Architecture Decisions

| Decision | Reason |
|----------|--------|
| Laravel over Next.js | Full-stack PHP is simpler for solo developer, no API layer needed |
| Blade over React | Server-rendered, SEO-friendly, no hydration issues |
| Alpine.js over React | Lightweight (15KB vs 100KB+), works with server-rendered HTML |
| MySQL over PostgreSQL/Supabase | Standard hosting support, no vendor lock-in |
| Local storage over Cloudinary | Simpler for MVP, no external dependency |
| Razorpay HTTP over SDK | Fewer dependencies, direct control |
| White-label via SiteSetting | Single codebase serves multiple matrimony portals |

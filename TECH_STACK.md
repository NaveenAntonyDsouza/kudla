# Anugraha Matrimony — Tech Stack

## Core Framework
- **Laravel 13.2** — PHP full-stack framework
- **PHP 8.3** — Server-side language

## Frontend
- **Blade** — Laravel's templating engine (server-rendered HTML)
- **Tailwind CSS 4** — Utility-first CSS framework (CSS-first config)
- **Alpine.js 3.15** — Lightweight JavaScript framework (loaded via Livewire)
- **Livewire 3** — Dynamic UI components (used for Alpine.js delivery + asset injection)

## Database
- **MySQL 8.0** — Relational database
- **Eloquent ORM** — Laravel's database abstraction layer

## Authentication
- **Laravel built-in Auth** — Session-based authentication
- **Hash (bcrypt)** — Password hashing
- **OTP Service** — Phone/Email verification via OTP

## File Storage
- **Laravel Filesystem** — Local disk storage (`storage/app/public/`)
- **Public disk** — Photos, ID proofs, Jathakam uploads

## Payment Gateway
- **Razorpay** — Payment processing (Indian payments)
- **HTTP Client** — Direct API integration (no SDK package)

## Email
- **Laravel Mail** — Email sending via SMTP
- **Hostinger SMTP** — `smtp.hostinger.com:465` (SSL)
- **Markdown Mailables** — Email templates for interest notifications

## Admin Panel
- **Filament 5.4** — Admin panel framework (installed, not fully configured)

## Build Tools
- **Vite 8** — Asset bundling (CSS + JS)
- **@tailwindcss/vite** — Tailwind CSS Vite plugin
- **@tailwindcss/forms** — Form styling plugin
- **npm** — Package manager

## Hosting & Deployment
- **Hostinger Business** — Shared hosting
- **LiteSpeed** — Web server
- **SSH** — Remote access for deployments
- **Domain:** anugrahamatrimony.com

## Key PHP Packages (composer.json)
- `laravel/framework` — Core framework
- `filament/filament` — Admin panel
- `livewire/livewire` — Dynamic components + Alpine.js
- `laravel/sanctum` — API authentication (available, not actively used)

## Key npm Packages (package.json)
- `alpinejs` — JavaScript interactivity (installed but loaded via Livewire)
- `axios` — HTTP client for AJAX requests
- `vite` — Build tool
- `tailwindcss` — CSS framework
- `@tailwindcss/forms` — Form element styling

## Planned but NOT Used (from planning/tech-stack.json)

| Planned | What We Used Instead | Reason |
|---------|---------------------|--------|
| `spatie/laravel-permission` | Manual role checks | Only admin/user roles needed for MVP |
| `razorpay/razorpay` SDK | HTTP API with `Http::post()` | Fewer dependencies, direct control |
| `resend/resend-laravel` | Laravel Mail + Hostinger SMTP | Already have Hostinger email, no extra cost |
| `cloudinary-community/cloudinary-laravel` | Local file storage | Simpler for MVP, no external dependency |
| `pestphp/pest` | Manual testing + curl | Tests deferred for post-launch |
| Cloudinary (image hosting) | `storage/app/public/` | Local storage sufficient for <100 users |
| Fast2SMS (phone OTP) | OTP service stub | SMS integration pending |

## Should Adopt Later (when scaling)

| Package | When to Adopt | Why |
|---------|--------------|-----|
| **Cloudinary** | When photos exceed hosting storage or need CDN | Image optimization, CDN delivery, transformation |
| **Razorpay SDK** | When adding webhooks, refunds, subscriptions | Better error handling, webhook verification |
| **Spatie Permissions** | When adding moderators, support staff | Role-based access control |
| **Pest** | Before major feature additions | Automated testing prevents regressions |
| **Resend** | If Hostinger SMTP becomes unreliable | Better deliverability, templates, analytics |
| **Fast2SMS** | For actual phone OTP sending | Currently OTP is stub only |
| **Redis** | When user count exceeds 500+ | Session/cache performance improvement |

## Architecture Decisions
1. **Laravel over Next.js** — Full-stack PHP is simpler for a solo developer, no API layer needed
2. **Blade over React** — Server-rendered HTML is faster, SEO-friendly, no hydration issues
3. **Alpine.js over React** — Lightweight (15KB vs 100KB+), works with server-rendered HTML
4. **MySQL over Supabase** — Standard hosting support, no vendor lock-in
5. **Local storage over Cloudinary** — Simpler for MVP, no external dependency
6. **Razorpay HTTP API over SDK** — Fewer dependencies, direct control

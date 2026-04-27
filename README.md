# MatrimonyTheme

A comprehensive, white-label matrimony platform built with Laravel. Designed for CodeCanyon distribution.

## Tech Stack

- **Backend:** Laravel 13.2, PHP 8.3, MySQL 8
- **Frontend:** Tailwind CSS 4.2, Alpine.js (via Livewire 4.2)
- **Admin Panel:** Filament 5.4
- **Payments:** Razorpay
- **Hosting:** Compatible with shared hosting (tested on Hostinger)

See [docs/TECH_STACK.md](docs/TECH_STACK.md) for full version details.

## Features

- 5-step registration with phone/email OTP
- 4-step onboarding wizard
- Profile management (9 editable sections, 4-tab preview)
- Photo management (profile, album, family) with multi-driver storage (Local / Cloudinary / R2 / S3)
- Advanced search (partner preferences, keyword, matri ID)
- Interest system with chat messaging
- Shortlist / Block / Who Viewed
- Membership plans with Razorpay payment
- In-app + email notifications
- Profile visibility preferences
- ID proof verification
- Staff & telecaller module (leads, call logs, monthly targets, incentives)
- Franchise / branch management with affiliate links and commission tracking
- Theme & branding customization (8 preset palettes, custom colors, 10 curated Google Fonts + custom)
- Admin-editable email templates with brand colors auto-injected
- Mobile responsive

See [docs/FEATURE_STATUS.md](docs/FEATURE_STATUS.md) for complete feature audit.

## Documentation

| Document | Description |
|----------|-------------|
| [docs/TECH_STACK.md](docs/TECH_STACK.md) | Technology versions and dependencies |
| [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md) | Deployment guide |
| [docs/FEATURE_STATUS.md](docs/FEATURE_STATUS.md) | Complete feature audit |
| [docs/NEXT_SESSION_PLAN.md](docs/NEXT_SESSION_PLAN.md) | Roadmap and next priorities |
| [docs/admin-panel/](docs/admin-panel/README.md) | Admin panel plan (complete — shipped) |
| [docs/mobile-app/](docs/mobile-app/README.md) | Mobile app plan — API layer (Sanctum) + Flutter native app (current priority) |
| [docs/SCALING_GUIDE.md](docs/SCALING_GUIDE.md) | When and how to optimize (caching, CDN, queues, indexing) |
| [docs/MOBILE_APP_PLAN.md](docs/MOBILE_APP_PLAN.md) | High-level mobile overview — superseded by `docs/mobile-app/` for implementation |
| [docs/.env.production](docs/.env.production) | Production environment template |

## Quick Start

```bash
# Install dependencies
composer install
npm install

# Configure environment
cp .env.example .env
php artisan key:generate

# Database
php artisan migrate --seed

# Build assets
npm run build

# Serve
php artisan serve
```

Default super admin credentials after seeding:
- Email: `admin@example.com`
- Password: `password`

**Change these immediately after first login.**

## Demo Data

To populate the database with realistic demo content (50 profiles, 30 leads, 100 call logs, subscriptions, staff targets, interests, etc.) — useful for screenshots, exploring the admin panel, or development:

```bash
php artisan matrimony:demo-seed
```

All demo entities are tagged for safe removal:
- Demo users: email ends with `@demo.local`
- Demo branch: code prefix `DEMO-`
- Demo testimonials: couple names prefixed `[Demo]`

To remove everything demo-related without touching real data:

```bash
php artisan matrimony:demo-clean
```

## License

Proprietary. All rights reserved.

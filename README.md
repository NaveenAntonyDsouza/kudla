# Anugraha Matrimony

A comprehensive, white-label matrimony platform built with Laravel. Designed for CodeCanyon distribution.

**Live:** [anugrahamatrimony.com](https://anugrahamatrimony.com)

## Tech Stack

- **Backend:** Laravel 13.2, PHP 8.3, MySQL 8
- **Frontend:** Tailwind CSS 4.2, Alpine.js (via Livewire 4.2)
- **Admin Panel:** Filament 5.4
- **Payments:** Razorpay
- **Hosting:** Hostinger shared hosting

See [docs/TECH_STACK.md](docs/TECH_STACK.md) for full version details.

## Features

- 5-step registration with phone/email OTP
- 4-step onboarding wizard
- Profile management (9 editable sections, 4-tab preview)
- Photo management (profile, album, family)
- Advanced search (partner preferences, keyword, matri ID)
- Interest system with chat messaging
- Shortlist / Block / Who Viewed
- Membership plans with Razorpay payment
- In-app + email notifications
- Profile visibility preferences
- ID proof verification
- Mobile responsive

See [docs/FEATURE_STATUS.md](docs/FEATURE_STATUS.md) for complete feature audit.

## Documentation

| Document | Description |
|----------|-------------|
| [docs/TECH_STACK.md](docs/TECH_STACK.md) | Technology versions and dependencies |
| [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md) | Hostinger deployment guide (12 steps) |
| [docs/FEATURE_STATUS.md](docs/FEATURE_STATUS.md) | Complete feature audit |
| [docs/NEXT_SESSION_PLAN.md](docs/NEXT_SESSION_PLAN.md) | Roadmap and next priorities |
| [docs/admin-panel/](docs/admin-panel/README.md) | Admin panel plan (15 sections, 41 selling points) |
| [docs/SCALING_GUIDE.md](docs/SCALING_GUIDE.md) | When and how to optimize (caching, CDN, queues, indexing) |
| [docs/MOBILE_APP_PLAN.md](docs/MOBILE_APP_PLAN.md) | Flutter mobile app plan (future phase) |
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

## License

Proprietary. All rights reserved.

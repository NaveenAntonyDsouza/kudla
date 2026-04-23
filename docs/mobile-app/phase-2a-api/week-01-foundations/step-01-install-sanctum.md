# Step 1 — Install Laravel Sanctum

## Goal
Install Sanctum, run migrations, add `HasApiTokens` to the User model. After this step, the app can issue and validate API tokens, but no endpoints use them yet.

## Prerequisites
- [ ] Laravel 13.x application (confirmed via `php artisan --version`)
- [ ] PHP 8.3+ (`php -v`)
- [ ] Working database connection (current MySQL on Hostinger or local dev DB)
- [ ] You're on branch `phase-2-mobile` (`git checkout -b phase-2-mobile` if not yet)

## Procedure

### 1. Install package

```bash
composer require laravel/sanctum
```

Expected output: Laravel discovers the `SanctumServiceProvider` automatically, creates `config/sanctum.php`.

### 2. Run install-api artisan command

```bash
php artisan install:api
```

This does three things:
- Creates `routes/api.php` with default scaffolding (`api` prefix, `throttle:api` middleware)
- Adds the `api` middleware group in `bootstrap/app.php` via `->withRouting(api: ...)`
- Ensures `config/sanctum.php` is published

When it prompts "Would you like to install Laravel Sanctum?" — answer **No** if asked (we already installed above). If prompted for Passport, answer **No**.

### 3. Run migrations

```bash
php artisan migrate
```

Expected: `personal_access_tokens` table created.

### 4. Add `HasApiTokens` trait to User model

Edit `app/Models/User.php`:

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;    // ← ADD THIS
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasRoles, Notifiable;    // ← ADD HasApiTokens

    // ... rest of model unchanged
}
```

### 5. Configure Sanctum token expiry

Edit `config/sanctum.php`:

```php
// Find the 'expiration' line:
'expiration' => env('SANCTUM_EXPIRATION', 60 * 24 * 90),  // 90 days in minutes
```

Default is `null` (never expire). 90-day TTL balances "users stay logged in" vs "stale tokens cleaned up."

Add to `.env`:
```
SANCTUM_EXPIRATION=129600
```

(129600 = 60 × 24 × 90)

### 6. Clear config cache (if prod-deployed)

```bash
php artisan config:clear
```

## Verification

- [ ] `php artisan route:list --path=sanctum` shows Sanctum's CSRF route
- [ ] `php artisan migrate:status` shows `personal_access_tokens` as migrated
- [ ] Tinker test:
  ```bash
  php artisan tinker
  >>> $user = \App\Models\User::first();
  >>> $token = $user->createToken('test');
  >>> echo $token->plainTextToken;
  ```
  Should print a token like `1|abc...xyz`.
- [ ] Delete the test token: `>>> $user->tokens()->delete();`

## Common issues

| Issue | Fix |
|-------|-----|
| `Class "Laravel\Sanctum\HasApiTokens" not found` | Composer autoload cache — run `composer dump-autoload` |
| `install:api` fails on Laravel < 11 | This project is Laravel 13, so shouldn't happen. Check `composer show laravel/framework` |
| Migration fails with SQLSTATE | Check DB connection in `.env`. Hostinger DB credentials may have rotated |
| `personal_access_tokens` already exists | Previous install attempt — run `php artisan migrate:status`, then `php artisan migrate:rollback` if needed |

## Commit

```bash
git add composer.json composer.lock config/sanctum.php bootstrap/app.php app/Models/User.php routes/api.php database/migrations/
git commit -m "phase-2a wk-01: step-01 install Laravel Sanctum"
```

## Next step
→ [step-02-api-routes-skeleton.md](step-02-api-routes-skeleton.md)

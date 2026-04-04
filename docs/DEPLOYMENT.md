# Deployment Guide — Anugraha Matrimony on Hostinger

## Prerequisites
- Hostinger Business plan with SSH access
- Domain: anugrahamatrimony.com pointed to Hostinger
- MySQL database created in hPanel

---

## Step 1: Create MySQL Database

1. Login to **Hostinger hPanel**
2. Go to **Databases > MySQL Databases**
3. Create a new database:
   - Database name: `anugraha_matrimony`
   - Username: `anugraha_user`
   - Password: (generate a strong password, save it)
4. Note down: database name, username, password

---

## Step 2: Upload Files

### Option A: Using Git (Recommended)
1. SSH into Hostinger: `ssh u123456@your-server-ip -p 65002`
2. Navigate to web root: `cd public_html`
3. Clone the repo: `git clone <your-repo-url> .`

### Option B: Using File Manager / FTP
1. Zip the entire project folder (excluding `node_modules`, `.env`)
2. Upload to `public_html/` via hPanel File Manager
3. Extract the zip

### Option C: Using hPanel Git Deployment
1. Push your code to GitHub/GitLab
2. In hPanel > Advanced > Git, connect your repo

---

## Step 3: Configure Environment

1. SSH into server
2. Copy production env:
   ```bash
   cp .env.production .env
   ```
3. Edit `.env` with your actual values:
   ```bash
   nano .env
   ```
   - Set DB_DATABASE, DB_USERNAME, DB_PASSWORD (from Step 1)
   - Set MAIL_PASSWORD (your Hostinger email password)
   - Set RAZORPAY keys (live keys for production)
4. Generate app key:
   ```bash
   php artisan key:generate
   ```

---

## Step 4: Install Dependencies

```bash
cd public_html
composer install --optimize-autoloader --no-dev
```

(Node.js/npm is NOT needed on server — assets are pre-built in `public/build/`)

---

## Step 5: Run Migrations

```bash
php artisan migrate --force
```

---

## Step 6: Publish Livewire Assets (IMPORTANT)

```bash
php artisan livewire:publish --assets
```

This copies Livewire/Alpine.js files to `public/vendor/livewire/`. Without this, Alpine.js won't load and interactive features (tabs, dropdowns, modals) will be broken.

**Run this after every `composer install` or `composer update`.**

---

## Step 7: Create Storage Link

```bash
php artisan storage:link
```

This creates `public/storage` symlink pointing to `storage/app/public` (for uploaded photos).

---

## Step 8: Set Permissions

```bash
chmod -R 775 storage bootstrap/cache
```

---

## Step 9: Configure Document Root

**Important:** Laravel's entry point is `public/index.php`, not the project root.

In Hostinger hPanel:
1. Go to **Websites > Manage > File Manager**
2. If your project is at `public_html/`, you need to point the domain to `public_html/public/`

### Method: Create .htaccess in project root

Create `public_html/.htaccess`:
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

---

## Step 10: Optimize for Production

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

---

## Step 11: Seed Initial Data (if needed)

```bash
php artisan db:seed
```

---

## Step 12: Test

1. Visit https://anugrahamatrimony.com
2. Register a new account
3. Test login, search, interests
4. Test payment (use Razorpay test mode first, then switch to live)

---

## Post-Deployment

### Enable HTTPS
- Hostinger provides free SSL via hPanel > Security > SSL

### Set up Cron Job (for scheduled tasks)
In hPanel > Advanced > Cron Jobs:
```
* * * * * cd /home/u123456/public_html && php artisan schedule:run >> /dev/null 2>&1
```

### Monitor Errors
Check `storage/logs/laravel.log` for any errors.

### Email Configuration
Once `.env` has correct MAIL settings, emails will be sent for:
- Interest received/accepted/declined
- Password reset links

---

## Troubleshooting

| Issue | Fix |
|-------|-----|
| 500 error | Check `storage/logs/laravel.log`, ensure permissions on `storage/` |
| CSS/JS not loading | Run `php artisan storage:link`, check `public/build/` exists |
| Database error | Verify `.env` DB credentials match hPanel database |
| Email not sending | Check MAIL_HOST (smtp.hostinger.com), MAIL_PORT (465), MAIL_ENCRYPTION (ssl) |
| Photos not uploading | Check `storage/app/public/` permissions, verify `public/storage` symlink |
| Razorpay SSL error | Remove `withoutVerifying()` from MembershipController (production has proper SSL) |

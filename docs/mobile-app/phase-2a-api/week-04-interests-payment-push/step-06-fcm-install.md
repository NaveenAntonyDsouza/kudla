# Step 6 — FCM Install (`kreait/laravel-firebase`)

## Goal
Install Firebase SDK for Laravel, configure service account credentials.

## Prerequisites
- [ ] Firebase service account JSON (from Firebase Console → Project Settings → Service Accounts → Generate New Private Key)

## Procedure

### 1. Install package

```bash
composer require kreait/laravel-firebase
```

### 2. Publish config

```bash
php artisan vendor:publish --tag=firebase-config
```

### 3. Place service account JSON

Download service account key from Firebase Console → Project Settings → Service Accounts → Generate New Private Key.

Save to `storage/app/firebase-credentials.json` (do NOT commit).

Add to `.gitignore`:

```
storage/app/firebase-credentials.json
```

### 4. Configure `.env`

```
FIREBASE_CREDENTIALS=storage/app/firebase-credentials.json
```

### 5. Test connectivity

```bash
php artisan tinker
>>> use Kreait\Laravel\Firebase\Facades\Firebase;
>>> Firebase::messaging();
# Should return a Messaging instance, no exception
```

### 6. Commit (without credentials)

```bash
git add composer.json composer.lock config/firebase.php .gitignore .env.example
# Add to .env.example:  FIREBASE_CREDENTIALS=storage/app/firebase-credentials.json

git commit -m "phase-2a wk-04: step-06 FCM — install kreait/laravel-firebase"
```

## Verification

- [ ] Tinker call succeeds
- [ ] `firebase-credentials.json` is in `.gitignore`
- [ ] `.env.example` documents the env var

## Next step
→ [step-07-notification-push-dispatch.md](step-07-notification-push-dispatch.md)

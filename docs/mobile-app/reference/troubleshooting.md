# Troubleshooting

Common issues + fixes. Add to this file whenever you hit a non-obvious problem.

---

## Laravel / API

### Migration fails after OTP channel backfill

**Symptom:** `SQLSTATE[23000]: Integrity constraint violation: destination cannot be null`
**Fix:** migration must do UPDATE first, then ALTER COLUMN to NOT NULL. See step-01 wk-02.

### Sanctum token works in tests but not via curl

**Symptom:** Pest tests pass with Bearer token; curl returns 401.
**Likely causes:**
- Token has trailing whitespace (shell escaping)
- Token expired (SANCTUM_EXPIRATION)
- Bearer prefix missing
**Debug:** `php artisan tinker` → `PersonalAccessToken::findToken(<plain-text>)` — should return the model

### Exception handler returning HTML

**Symptom:** 500 error returns Laravel's whoops HTML, not envelope JSON.
**Cause:** `bootstrap/app.php` `withExceptions` block not registered OR `$request->is('api/*')` check returning false
**Fix:** ensure `ForceJsonResponse` middleware runs BEFORE the handler, confirm route has `/api/` prefix (Laravel adds it automatically)

### Filament admin broken after routes/api.php added

**Symptom:** `/admin` returns 404 after API work.
**Fix:** clear all caches: `php artisan optimize:clear && php artisan config:clear && php artisan route:clear`

### Scribe generate hangs

**Symptom:** `php artisan scribe:generate` hangs indefinitely.
**Cause:** Scribe trying to make HTTP calls to get example responses.
**Fix:** in `config/scribe.php`, set `'response_calls.methods' => []` to disable auto-calls and rely on `@response` annotations

### Razorpay webhook receives request but subscription stays pending

**Symptom:** Razorpay dashboard shows webhook delivered but our DB shows `status=pending`.
**Causes:**
- Signature verification failing (check webhook secret in site_settings)
- `X-Razorpay-Signature` header missing (check Laravel's trusted proxies / Cloudflare stripping headers)
**Debug:** temporarily log `$signature`, `$expected`, and `$payload` to laravel.log and compare

### FCM push silently fails

**Symptom:** `NotificationService::dispatch()` runs without error, no push arrives.
**Causes:**
- Device's `fcm_token` expired (Firebase rotates them) — check `is_active=false` on device row
- Quiet hours active — check user's `notification_preferences.quiet_hours_*`
- Credentials JSON missing / wrong path — check FIREBASE_CREDENTIALS env
**Debug:** `php artisan tinker` → `Firebase::messaging()->validateRegistrationTokens(['<token>'])` returns which tokens are alive

### Queue worker not processing jobs on Hostinger

**Symptom:** Jobs stack up in `jobs` table.
**Fix:** confirm cron is active:
```
crontab -l | grep queue:work
```
Should show:
```
* * * * * cd /home/u562383594/.../public_html && /usr/bin/php artisan queue:work --stop-when-empty --max-time=55 >> /dev/null 2>&1
```

---

## Flutter / Android

### `flutter run` error: Gradle plugin not found

**Cause:** Flutter version + AGP version mismatch.
**Fix:** upgrade both: `flutter upgrade`, then in `android/build.gradle` bump `com.android.application` plugin version.

### Firebase `google-services.json` not found

**Symptom:** Build fails with "File google-services.json is missing."
**Fix:** place file at exact path: `flutter-app/android/app/google-services.json`

### Razorpay SDK crashes on Android 14+

**Cause:** ProGuard stripping Razorpay classes.
**Fix:** add to `android/app/proguard-rules.pro`:
```
-keep class com.razorpay.** { *; }
-dontwarn com.razorpay.**
```

### Deep link opens browser, not app

**Cause:** App Links not verified.
**Debug:**
```bash
adb shell pm verify-app-links --re-verify com.books.KudlaMatrimony
adb shell pm get-app-links com.books.KudlaMatrimony
```
**Fix:**
- Check `assetlinks.json` serves with `Content-Type: application/json`
- Check SHA-256 fingerprint matches your signing key
- Check `autoVerify="true"` in manifest

### `flutter_secure_storage` returns null on fresh install

**Expected** — first-run state. No bug.

### Release build works locally but crashes on Play Store tester

**Causes:**
- Minified code stripped something we need (add to ProGuard rules)
- API URL still pointing to localhost (check `--dart-define=FLAVOR=prod`)
- FCM credentials are test-project (production needs real keys)

### Image upload succeeds but photo doesn't show

**Cause:** `storage_driver` mismatch — DB says `cloudinary` but file uploaded to `public`.
**Fix:** check `PhotoStorageService::store()` picks the correct driver from `SiteSetting::active_storage_driver`

### Biometric prompts every app launch

**Expected behaviour** if the user enabled it — the whole point.
**If they want to disable:** Settings → App → Biometric login → Off.

### Chat doesn't receive new messages (polling broken)

**Causes:**
- Timer not resumed on foreground
- Polling endpoint returning 401 (token expired mid-session)
- Network offline (offline banner should show)

### "Unauthenticated" after relogin

**Cause:** New token stored but Dio interceptor still has old token cached.
**Fix:** tokens should be fetched per-request from secure storage, not cached in interceptor

---

## Play Store

### Upload rejected: "APK signed with wrong key"

**Cause:** keystore mismatch — you used a different `.jks` than the one Google expects.
**Fix:** since we use Play App Signing, this shouldn't happen. If it does, check `key.properties` points to the correct upload keystore (not the old one from a different project).

### "This app contains 0 activities"

**Cause:** `MainActivity` not declared in manifest.
**Fix:** ensure `android/app/src/main/AndroidManifest.xml` has:
```xml
<activity android:name=".MainActivity" ...>
  <intent-filter>
    <action android:name="android.intent.action.MAIN" />
    <category android:name="android.intent.category.LAUNCHER" />
  </intent-filter>
</activity>
```

### Crashlytics not receiving crashes

**Causes:**
- ProGuard mapping files not uploaded → crashes appear as obfuscated
- Firebase plugin not initialized — check `Firebase.initializeApp()` in `main()`
**Fix upload mapping:** `flutter build appbundle --obfuscate --split-debug-info=build/symbols`, then upload `build/symbols/` to Firebase

---

## Generic debugging flow

When something's broken and you don't know why:

1. **Check logs** — `tail -f storage/logs/laravel.log` on server; `flutter logs` locally
2. **Isolate** — can you reproduce with curl? If yes, backend issue. If no, Flutter issue
3. **Diff** — what changed since last working state? `git log --since="1 day ago"`
4. **Revert** — if git shows a suspect commit, `git diff HEAD~1 HEAD` it
5. **Ask** — paste symptom + log snippet + "I tried X, Y, Z" to Claude

Don't "fix" by trying random things. Understand first, fix once.

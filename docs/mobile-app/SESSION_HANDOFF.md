# Phase 2b — Session Handoff

**Last updated:** 2026-04-30 (Week 1 closed)
**Audience:** A fresh Claude session (or human collaborator) picking up Phase 2b Flutter mobile-app work.

This doc is the **first thing to read** in a new session. It links to everything else.

---

## Where we are

**Phase 2b Week 1 — CLOSED 2026-04-30.**
- Tag `mobile-v0.1.0-week-01-scaffold` at commit `4fdcc4d` on https://github.com/NaveenAntonyDsouza/kudla-mobile (private)
- 19 commits delivered; full breakdown in [`phase-2b-flutter/week-01-scaffold/week-01-acceptance.md`](phase-2b-flutter/week-01-scaffold/week-01-acceptance.md)
- FCM round-trip proof passed: production tinker → Firebase → Xiaomi 220333QBI received "Phase 2b Week 1 proof" push at 02:21 UTC
- 4-of-5 documented acceptance criteria met. Deep links explicitly deferred to Week 2.

**Phase 2b Week 2 — starts Monday 2026-05-04.** Auth flows. Plan: [`phase-2b-flutter/week-02-auth-ui/README.md`](phase-2b-flutter/week-02-auth-ui/README.md). 13 sub-steps + acceptance.

---

## Working directories (cheat sheet)

| What | Path |
|---|---|
| **Flutter project** | `D:\matrimony\platform\flutter-app\` |
| Laravel backend | `D:\matrimony\platform\matrimony-platform\` |
| Local scratch (gitignored) | `D:\matrimony\scratch\` |
| FVM cache | `D:\dev\fvm\` (NOT `~/fvm/`) |
| Gradle cache | `D:\dev\gradle\` (NOT `~/.gradle\`) |
| Pub cache | `D:\dev\pub\` (NOT `~/AppData\Local\Pub\`) |
| Mobile repo | https://github.com/NaveenAntonyDsouza/kudla-mobile (private) |
| Backend repo | https://github.com/NaveenAntonyDsouza/kudla (public) |

---

## Toolchain state (verified 2026-04-30)

- Flutter **3.41.8** pinned via FVM. `D:\dev\fvm\default\bin\` is on Windows User PATH so bare `flutter` and `dart` work.
- Dart 3.11.5 (bundled).
- Android: AGP 8.x, NDK 28.2, Java 17, target SDK 36, min SDK 21, package `com.books.KudlaMatrimony`.
- Firebase CLI 14.15.2 (`npm install -g firebase-tools`); flutterfire_cli 1.3.2 (`dart pub global activate flutterfire_cli`). Active firebase account: `naveendsouza1993@gmail.com` (Naveen has 2 Google accounts; both have access to `kudla-matrimony-e3d63`).
- Test device: Xiaomi 220333QBI, Android 13 (API 33), serial `116b1ae7`.

Bash aliases for Windows shims (in `~/.bash_profile`): `fvm='fvm.bat'`, `flutterfire='flutterfire.bat'`, `firebase='firebase.cmd'`. **Git Bash doesn't auto-resolve `.bat`/`.cmd` extensions** — without these aliases bare `fvm`/`flutterfire`/`firebase` fail.

---

## Production state (2026-04-30)

- Live: https://kudlamatrimony.com (Hostinger Premium, LiteSpeed)
- SSH alias: `kudla-prod` → `91.108.107.86:65002` user `u562383594`. Key auth via `~/.ssh/hostinger_kudla` (registered in Hostinger SSH Access panel). **No password needed.**
- Latest deploy tag: `deploy-2026-04-28-v2`
- Firebase Admin SDK boots cleanly on prod (`storage/app/firebase-credentials.json` uploaded chmod 600; `FIREBASE_CREDENTIALS` set in `.env`).
- The `App\Support\FirebaseCredentialsResolver` patch (commit `5309b5f`) wraps relative paths in `base_path()` — committed, **not yet deployed**. After deploy, prod `.env` can revert to relative path.

**Open prod chore:** `git pull` on prod + `php artisan config:clear` to deploy commits since `deploy-2026-04-28-v2`. See "Reproducibility — Backend deploy" below.

---

## Hard-won gotchas (will bite again if forgotten)

1. **MIUI install retries** — `INSTALL_FAILED_USER_RESTRICTED`. Fix: phone Settings → Additional Settings → Developer Options → toggle "Install via USB" + "USB debugging (Security settings)". MIUI silently resets these; check first when an install fails.

2. **`fvm.bat` exit-code masks gradle failures** — auto-completion notifications mark builds as exit 0 even when underlying gradle fails. Always grep output for `BUILD FAILED` or `BUILD SUCCESSFUL` to know real result.

3. **Firebase CLI / flutterfire_cli need cmd.exe (not Git Bash) for interactive prompts.** Symptom: `Cannot run login in non-interactive mode`. Use cmd.exe for `firebase login`, `firebase login --reauth`, `flutterfire configure`. Non-interactive ops (`firebase projects:list`) work in Git Bash too.

4. **Sanctum tokens have a `|` separator** that gets eaten by Windows shell → cmd.exe → .bat handoff. Pass `--dart-define` as **two separate values** (id + hash), reassemble in app. Pattern lives in archived debug code.

5. **Relative `FIREBASE_CREDENTIALS` paths break for web context** because PHP-FPM CWD is `public/`, not project root. Fixed by `App\Support\FirebaseCredentialsResolver` (already in place — wraps relative paths in `base_path()`).

6. **First fresh build after dependency churn is slow** — ~48 min the first time, ~3-5 min incremental afterwards. Be patient on the first build, don't kill it.

7. **AGP 8.x compat checklist**: many older Flutter plugins lack `namespace=` in their gradle. Symptom: `Namespace not specified...`. Fix: bump to maintained version, use a fork, or drop. Don't monkey-patch pub-cache (lost on `pub cache clean`). Currently dropped: `flutter_app_badger` 1.5.0 → revisit `app_badge_plus` Week 6+.

8. **flutter_local_notifications needs core library desugaring**. Already enabled in `android/app/build.gradle.kts`: `isCoreLibraryDesugaringEnabled = true` + `coreLibraryDesugaring("com.android.tools:desugar_jdk_libs:2.1.4")`.

Memory files capture all of this — see `~/.claude/projects/D--matrimony-platform-matrimony-platform/memory/`.

---

## What Week 2 needs from Naveen before starting

**Design screenshots** are the long pole. Per [`week-02-auth-ui/README.md`](phase-2b-flutter/week-02-auth-ui/README.md):

1. **Login screen** — all 3 tabs: email+password, phone+OTP, email+OTP
2. **Forgot password screen**
3. **Reset password screen**
4. **Biometric enrol bottom sheet**
5. **Biometric unlock screen**

Drop screenshots anywhere accessible (e.g., `D:\matrimony\scratch\week-02-screenshots\`). Don't need polished Figma — pencil sketch works, photo of paper works, screenshot of similar matrimony app works. The implementation plan adapts to whatever's provided.

If Naveen says "let's iterate without screenshots", proceed step-by-step from the `week-02-auth-ui/` step files, asking for one screen at a time.

---

## Recipes (copy-paste ready)

### FCM regression check (re-runs the Week 1 acceptance test)

Full recipe in [`phase-2b-flutter/week-01-scaffold/week-01-acceptance.md` § Reproducibility](phase-2b-flutter/week-01-scaffold/week-01-acceptance.md). Summary:

```bash
# 1. Mint test user + Sanctum token on prod
ssh kudla-prod
cd domains/kudlamatrimony.com/public_html
# (use API or tinker — both documented in the acceptance doc)

# 2. Run Flutter with token split into two env vars (avoids shell pipe-eating)
fvm flutter run -d 116b1ae7 \
  --dart-define=DEBUG_BEARER_ID=<id> \
  --dart-define=DEBUG_BEARER_HASH=<hash>
# Note: the debug FCM register button was stripped at Week 1 acceptance.
# To re-test, restore from commit dc3e2aa..4fdcc4d range.
```

### Backend deploy (when commits accumulate on `kudla.git` main)

```bash
ssh kudla-prod << 'EOF'
cd domains/kudlamatrimony.com/public_html
git pull origin main
composer install --no-dev --optimize-autoloader --ignore-platform-req=ext-redis
php artisan migrate --force
php artisan config:clear
php artisan view:clear
php artisan cache:clear
EOF
```

(Hostinger Premium quirk: `--ignore-platform-req=ext-redis` always required. See [`project_kudla_production.md`](../../../.claude/projects/D--matrimony-platform-matrimony-platform/memory/project_kudla_production.md) memory if path resolved.)

### New Flutter feature — first build after big dep changes

```bash
cd "/d/matrimony/platform/flutter-app/"
fvm flutter clean
fvm flutter pub get
fvm flutter analyze     # ~30-60 sec; static check before committing
fvm flutter build apk --debug   # ~5-15 min; full validation
fvm flutter run -d 116b1ae7     # ~30 sec to install + launch on Xiaomi
```

---

## Memory files (auto-load in fresh session)

Located at `C:\Users\Lenovo\.claude\projects\D--matrimony-platform-matrimony-platform\memory\`:

- `MEMORY.md` — index of the others
- `project_kudla_production.md` — SSH alias, deploy procedure, .htaccess rules, Firebase Admin path
- `project_phase_2b_kickoff.md` — locked decisions (Option C, Android-only, package name, Play App Signing state)
- `project_phase_2b_progress.md` — per-week state ("Week 1 CLOSED" as of 2026-04-30)
- `feedback_collaboration_style.md` — paste-and-verify rhythm, todo-list usage, security-issue surfacing pattern

A fresh session reads these automatically. No need to re-load manually.

---

## Open follow-ups (across both repos, ordered by priority)

1. **Deploy `5309b5f` (FirebaseCredentialsResolver) to prod** — see "Backend deploy" recipe above. After deploy, edit prod `.env` to use `FIREBASE_CREDENTIALS=storage/app/firebase-credentials.json` (the relative form documented in `.env.example`). 5 minutes total.
2. **Design screenshots for Week 2 auth flows** — Naveen.
3. **Find original Play Store upload keystore** for `com.books.KudlaMatrimony` (or plan for Play Console "Reset upload key" 24-48h turnaround) — needed at Week 12 launch, not before.
4. **`google_fonts` package** — `theme.heading_font: "Playfair Display"` and `body_font: "Inter"` are received from API but not yet rendered (no font files bundled). Material default (Roboto) shows instead. Plumbing is already in place in `lib/core/theme/app_theme.dart` — flip a switch when adding `google_fonts` to deps.
5. **Real logo asset** — placeholder (heart-on-rounded-square) used. Naveen has the flat PNG; bundle as `assets/logo/kudla.png` in Week 2.
6. **`app_badge_plus`** (or maintained fork) for icon badge counts — Week 6+ when notifications screen lands.

---

## How a fresh session should kick off

1. Read this doc (you are here).
2. Memory files auto-load — confirm they include `project_phase_2b_progress.md`.
3. Open `phase-2b-flutter/week-02-auth-ui/README.md`.
4. Ask Naveen: "Do you have the Week 2 screenshots ready? Where are they?"
5. From there, proceed step-by-step per his answer.

That's it. The hard context-building from Week 1 is already encoded in commits, memory, and this doc. No re-derivation needed.

# Step 1 — Pre-Launch Checklist

## Goal
Verify everything is ready for Internal Testing submission.

## Checklist

### Technical

- [ ] Signed `app-release.aab` exists in `build/app/outputs/bundle/release/`
- [ ] AAB size < 100 MB (target < 50 MB)
- [ ] Release build tested on physical device (not just emulator)
- [ ] ProGuard rules prevent Razorpay + Firebase crashes
- [ ] Crashlytics initialized; test crash appears in Firebase console
- [ ] API endpoints pointing to production (`APP_URL=https://kudlamatrimony.com`)
- [ ] Razorpay production key set (NOT test key)
- [ ] FCM production credentials on server
- [ ] Queue worker cron active on Hostinger
- [ ] Webhook URL configured in Razorpay dashboard
- [ ] Deep link verification: `adb shell pm verify-app-links --re-verify com.books.KudlaMatrimony` returns verified

### Play Store Assets

- [ ] App icon 512×512 PNG
- [ ] Adaptive icon foreground + background in `res/mipmap-anydpi-v26/`
- [ ] Feature graphic 1024×500 PNG
- [ ] 8 phone screenshots 1080×1920
- [ ] Short description (≤ 80 chars)
- [ ] Full description (≤ 4000 chars) — draft in design doc §15.10
- [ ] Privacy Policy URL: `https://kudlamatrimony.com/privacy-policy`
- [ ] Content rating survey complete → PEGI 12 / ESRB Teen
- [ ] Target audience: 18+
- [ ] App category: Lifestyle → Matrimonial
- [ ] Data safety form filled (declare: name, email, phone, photos collected)
- [ ] Developer name + address + email visible on listing

### Legal

- [ ] Privacy policy covers mobile app (photos uploaded, location opt-in, etc.)
- [ ] Refund policy live
- [ ] Child safety policy live (per Google Play policy for matrimony apps)

### Access

- [ ] Play Console access for any team members
- [ ] Keystore backed up in 2 locations (e.g., local + cloud vault)
- [ ] Firebase project shared with team

### Documentation

- [ ] `docs/mobile-app/CHANGELOG.md` updated with v1.0.0 release notes
- [ ] Admin-facing help article ("How to download the new app")
- [ ] Support team briefed on new app's features + common questions

## Verification

All above ✅ → proceed to Step 2.

Any ❌ → fix before going live. Most items are quick — don't rush critical path ones (keystore backup, content rating).

## Next step
→ [step-02-internal-testing.md](step-02-internal-testing.md)

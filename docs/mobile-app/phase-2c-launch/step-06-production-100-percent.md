# Step 6 — Production 100% Rollout

## Goal
Full rollout. Every user receives the new app as an update.

## Duration
**Ongoing from here.**

## Procedure

### 1. Bump to 100%

Play Console → Production → active release → "Edit rollout percentage" → 100%

### 2. Announce

- Email full user base:
  ```
  Subject: The new Kudla Matrimony app is here!

  Hi {name},

  Today we're launching our brand new mobile app — built native,
  packed with features, much faster.

  Update (or install) from Play Store: https://play.google.com/...

  Highlights:
  • Push notifications — never miss an interest
  • Biometric login
  • Native, modern UX

  Your account works instantly — just log in with your existing credentials.

  Thanks for being part of Kudla Matrimony.
  ```

- Social media posts (Facebook, Instagram)
- Homepage banner: "New app available" → Play Store link
- WhatsApp status update

### 3. Webview app migration banner

Existing webview app users should be prompted to upgrade.

Add to kudlamatrimony.com homepage (mobile-only):
```html
<div class="app-banner">
  <img src="/new-app-icon.png" />
  <div>
    <h3>New Kudla app is here!</h3>
    <p>Faster, native, with push notifications</p>
    <a href="https://play.google.com/store/apps/details?id=com.books.KudlaMatrimony">Update Now</a>
  </div>
</div>
```

Since package name = `com.books.KudlaMatrimony` (same as webview), Play Store will show "Update" button, not "Install".

### 4. Monitor ongoing

Daily for first week:
- Uninstall rate — target < 10%
- Reviews — target ≥ 4.3 average
- DAU — target ≥ 80% of webview DAU

Weekly thereafter.

## Exit criteria

- [ ] 100% rolled out
- [ ] Announcement sent to all channels
- [ ] Webview-to-native migration rate > 70% in first 2 weeks
- [ ] No P0 bugs surfaced
- [ ] Retention metrics match or exceed webview baseline

## Next step
→ [step-07-monitoring-playbook.md](step-07-monitoring-playbook.md)

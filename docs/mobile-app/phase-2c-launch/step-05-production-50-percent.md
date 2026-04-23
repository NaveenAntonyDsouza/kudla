# Step 5 — Production 50% Rollout

## Goal
Bump rollout to 50% of users. Half the webview user base now on native app.

## Duration
**3 days.**

## Procedure

### 1. Bump rollout %

Play Console → Production → active release → "Edit rollout percentage" → 50%

### 2. Monitor (same as Step 4, with higher scale)

- Crashlytics — expect more crashes due to more users; rate should stay ≥ 99%
- Play Console ANR, install/uninstall
- Review rate doubles, watch median score

### 3. Address scale-surfaced issues

Common issues at scale:
- Rare device-specific crashes (certain Android OEMs)
- Slow network in certain regions
- Memory pressure on low-RAM devices

Fix or document each. Don't halt for minor issues (< 1% affected).

### 4. Communications

Day 3: if all metrics green, prepare comms for 100% rollout announcement:
- Email to full user base
- Webview app banner: "Download the new native app for better experience" (see step-08)

## Exit criteria

- [ ] Crash-free rate ≥ 99% sustained for 3 days
- [ ] No new P0 bugs
- [ ] Average session length ≥ baseline webview × 2
- [ ] Payment success rate sustained

## Next step
→ [step-06-production-100-percent.md](step-06-production-100-percent.md)

# Step 8 — User Migration Communications

## Goal
Move existing webview users to the native app as quickly as possible without frustrating them. Communication is the lever.

## Channels

### Email (transactional)

**Day 0 (launch):**
- Subject: "Big news: Kudla Matrimony has a new native app"
- CTA: "Update on Play Store"
- Key points: feature highlights, seamless login

**Day 7 (non-updaters):**
- Subject: "You haven't updated yet — here's why it matters"
- CTA: "Update now for push notifications and faster search"

**Day 30 (still webview):**
- Subject: "The webview app is getting phased out"
- CTA: "Upgrade before {date} to continue receiving updates"

### In-app (webview — existing HTML)

Add a top banner on web + webview:
- Dismissible "New app available" banner
- Floating "Update App" button on mobile web

### Push (after first native app install)

First welcome push:
- Title: "Welcome to Kudla!"
- Body: "Tap to explore your new home screen"

### WhatsApp (existing customer list)

Templated message to opt-in users:
- "We're live with a new app! Download: {link}"

### Social

- Facebook post with video walkthrough (15s)
- Instagram reel showing new features
- Google My Business update

## Support scripts

Support team (Filament admin) + WhatsApp helpdesk trained:
- "Can you login on the new app?" → walk through login
- "I'm getting a blank screen" → check Android version ≥ 5.0
- "I can't find the register button" → first screen is login; register link at bottom
- "My membership isn't showing" → ensure same email used

## Metrics

Track migration rate:
- Week 1: 30% of DAU on new app
- Week 2: 50% on new app
- Week 4: 70% on new app
- Week 8: 90% on new app

## When to deprecate webview

Once 90% of DAU is on native (~week 8):

1. Announce deprecation with 30-day notice
2. Add "This webview app will stop working on {date}" banner
3. Actually deprecate: webview app redirects to "Download new app" page
4. Remove old webview app listing from Play Store (Play Console → App content → unpublish)

## Next step
→ [step-09-post-launch-iteration.md](step-09-post-launch-iteration.md)

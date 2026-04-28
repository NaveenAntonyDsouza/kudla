# FCM Round-Trip Quickstart

You don't need a Flutter app to verify FCM works end-to-end. This guide walks the **fastest path** — under 15 minutes — using a tiny HTML page as the "device". When Phase 2b Flutter starts, the same Firebase project drops in unchanged.

---

## What you're testing

```
┌──────────────┐   1. registerToken     ┌──────────────────┐
│  Test page   │ ─────────────────────▶ │  Laravel API     │
│  (browser)   │                        │  POST /devices   │
└──────┬───────┘                        └─────────┬────────┘
       │                                          │
       │ 5. push received                         │ 4. trigger
       │    (Service Worker)                      │    (e.g. send
       ▼                                          ▼     interest)
┌─────────────────────┐      3. dispatch    ┌──────────────────┐
│  Firebase FCM       │ ◀──────────────────▶ │ NotificationService │
│  (Google's servers) │                      │  → Firebase SDK     │
└─────────────────────┘                      └──────────────────┘
```

You already have:
- ✅ Firebase project (`kudla-matrimony-e3d63`)
- ✅ Service-account JSON at `storage/app/firebase-credentials.json`
- ✅ Laravel API wired (verified in Pest + the auth round-trip in step-15)

You need to add:
- A **Web App** to the Firebase project (3 clicks)
- A **VAPID key** for web push (1 click)
- One small HTML file served from localhost (copy-paste below)

---

## Step 1 — Add a Web App to the Firebase project

1. Open https://console.firebase.google.com/project/kudla-matrimony-e3d63/overview
2. Click the **`</>`** (web) icon under "Get started by adding Firebase to your app"
3. Nickname: `Kudla FCM Test Page`. **Don't** check "Firebase Hosting" (we're using localhost).
4. Click **Register app**.
5. Copy the **`firebaseConfig`** object that appears — you need 6 values:
   ```js
   apiKey, authDomain, projectId, storageBucket, messagingSenderId, appId
   ```
   Save these in a scratch file. You'll paste them into the HTML below.
6. Click **Continue to console** — skip the SDK install screen.

## Step 2 — Generate a VAPID key (for web push)

1. In the same Firebase Console, gear icon → **Project Settings** → **Cloud Messaging** tab
2. Scroll to **Web configuration** → **Web Push certificates**
3. Click **Generate key pair**
4. Copy the **Key pair** value (a long base64 string starting with `B…`). This is your VAPID public key. Save it next to your `firebaseConfig`.

## Step 3 — Save the test page

Create a folder anywhere — say `D:\matrimony\fcm-test\`. Put **two files** in it:

### `index.html`

```html
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Kudla FCM Test</title>
  <style>
    body { font-family: system-ui; max-width: 720px; margin: 40px auto; padding: 0 16px; }
    pre { background: #f4f4f4; padding: 12px; border-radius: 6px; overflow-x: auto; }
    button { padding: 10px 20px; font-size: 16px; cursor: pointer; }
    .token { word-break: break-all; }
  </style>
</head>
<body>
  <h1>Kudla FCM Test</h1>
  <p>Click the button to ask your browser for permission to receive notifications,
     then copy the FCM token below and register it via the Laravel API.</p>

  <button id="btn">Get FCM Token</button>
  <h3>Your FCM token:</h3>
  <pre id="token" class="token">(click button)</pre>
  <h3>Incoming push messages:</h3>
  <pre id="messages">(none yet)</pre>

  <script type="module">
    import { initializeApp } from "https://www.gstatic.com/firebasejs/10.13.0/firebase-app.js";
    import { getMessaging, getToken, onMessage } from "https://www.gstatic.com/firebasejs/10.13.0/firebase-messaging.js";

    // ⚠ PASTE YOUR firebaseConfig FROM STEP 1 HERE:
    const firebaseConfig = {
      apiKey:            "PASTE_HERE",
      authDomain:        "kudla-matrimony-e3d63.firebaseapp.com",
      projectId:         "kudla-matrimony-e3d63",
      storageBucket:     "kudla-matrimony-e3d63.appspot.com",
      messagingSenderId: "PASTE_HERE",
      appId:             "PASTE_HERE",
    };

    // ⚠ PASTE YOUR VAPID PUBLIC KEY FROM STEP 2 HERE:
    const VAPID_KEY = "PASTE_HERE";

    const app = initializeApp(firebaseConfig);
    const messaging = getMessaging(app);

    document.getElementById("btn").addEventListener("click", async () => {
      try {
        const reg = await navigator.serviceWorker.register("/firebase-messaging-sw.js");
        const token = await getToken(messaging, { vapidKey: VAPID_KEY, serviceWorkerRegistration: reg });
        document.getElementById("token").textContent = token || "(no token — denied?)";
      } catch (e) {
        document.getElementById("token").textContent = "ERROR: " + e.message;
      }
    });

    onMessage(messaging, (payload) => {
      const el = document.getElementById("messages");
      el.textContent = JSON.stringify(payload, null, 2) + "\n\n" + el.textContent;
    });
  </script>
</body>
</html>
```

### `firebase-messaging-sw.js` (next to `index.html`, NOT in a subfolder)

```js
// Service Worker that receives FCM messages when the page is in the background.
importScripts("https://www.gstatic.com/firebasejs/10.13.0/firebase-app-compat.js");
importScripts("https://www.gstatic.com/firebasejs/10.13.0/firebase-messaging-compat.js");

firebase.initializeApp({
  apiKey:            "PASTE_HERE",       // ← same as index.html
  authDomain:        "kudla-matrimony-e3d63.firebaseapp.com",
  projectId:         "kudla-matrimony-e3d63",
  storageBucket:     "kudla-matrimony-e3d63.appspot.com",
  messagingSenderId: "PASTE_HERE",
  appId:             "PASTE_HERE",
});

const messaging = firebase.messaging();

messaging.onBackgroundMessage((payload) => {
  self.registration.showNotification(
    payload.notification?.title || "Kudla",
    {
      body: payload.notification?.body || "",
      data: payload.data || {},
    },
  );
});
```

## Step 4 — Serve the test page on localhost

Service Workers require either HTTPS or localhost. The simplest server:

```bash
# In the fcm-test/ folder
python -m http.server 5500
# OR if you have Node:  npx serve -p 5500
```

Open http://localhost:5500 in Chrome. Click **Get FCM Token**. Allow notifications when prompted. Copy the token from the page.

## Step 5 — Register the token via the Laravel API

You need a Sanctum token for an authenticated user. The smoke run earlier minted one in `/tmp/smoke-token`; if that's gone, register a fresh user (matri-id and password are throwaway):

```bash
TOKEN=$(curl -s -X POST http://127.0.0.1:8000/api/v1/auth/register/step-1 \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "full_name": "FCM Tester",
    "gender": "male",
    "date_of_birth": "1995-04-12",
    "phone": "9999988887",
    "email": "fcm-test@example.com",
    "password": "fcm-test-pwd-12"
  }' | jq -r '.data.token')
echo "$TOKEN"
```

Then register the FCM token from your browser:

```bash
FCM_TOKEN="<paste-the-long-token-from-the-browser-here>"

curl -s -X POST http://127.0.0.1:8000/api/v1/devices \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d "{
    \"fcm_token\": \"$FCM_TOKEN\",
    \"platform\": \"android\",
    \"app_version\": \"1.0.0\",
    \"device_model\": \"Web FCM Test Page\"
  }" | jq .
```

Expected: `{"success": true, "data": {"device_id": <number>}}` with HTTP 201.

## Step 6 — Trigger a push

Open `php artisan tinker` from the project root:

```bash
cd D:/matrimony/platform/matrimony-platform
php artisan tinker
```

In the tinker prompt — note two things:
1. `NotificationService::send()` takes 6 positional arguments (User, type, title, message, fromProfileId, data), NOT a single config array.
2. The `notifications.type` column is an ENUM. Only these values are accepted: `interest_received`, `interest_accepted`, `interest_declined`, `profile_view`, `system`. Anything else throws `QueryException: Data truncated for column 'type'`.

```php
$user = \App\Models\User::where('email', 'fcm-test@example.com')->first();

app(\App\Services\NotificationService::class)->send(
    $user,
    'system',                                                    // must be one of the 5 enum values
    'FCM round-trip test',                                       // title
    'If you see this in the browser, the loop is closed.',       // message
    null,                                                        // fromProfileId (null for system msgs)
    ['source' => 'fcm-quickstart-guide']                         // data
);
```

**Expected within 2-5 seconds:** the browser tab shows the JSON payload under "Incoming push messages", and a system notification appears in the OS notification center.

If the tab is in the background or closed, the **Service Worker** picks it up and the OS shows the notification — verifying the full background-push path.

---

## Troubleshooting

**"Notification permission was denied."** Chrome only asks once. Reset via the lock icon in the address bar → Site settings → Notifications → Allow → reload.

**"messaging/no-vapid-key"** in the JS console — you forgot to paste the VAPID key (Step 2) into `index.html`. The `firebase-messaging-sw.js` does NOT need the VAPID key.

**Tinker says `Firebase factory failed: cURL error 60: SSL certificate problem`** on Windows — your local PHP doesn't have a CA bundle. This is a Windows-only sandbox issue and has no production impact (Linux servers have proper CA paths). To fix locally:
1. Download https://curl.se/ca/cacert.pem
2. Save to e.g. `D:\applications\xampp\php\extras\ssl\cacert.pem`
3. Edit `D:\applications\xampp\php\php.ini`, find the `[curl]` section, set:
   ```
   curl.cainfo = "D:\applications\xampp\php\extras\ssl\cacert.pem"
   openssl.cafile = "D:\applications\xampp\php\extras\ssl\cacert.pem"
   ```
4. Restart Apache + retry tinker.

**No notification arrives but tinker returned no error.** Check `storage/logs/laravel.log` for the dispatch line. Common causes:
- Token was rotated by Firebase between registration and send (rare). Re-click "Get FCM Token" and re-register.
- The user's `notification_preferences` opt-out the type. Check `users.notification_preferences` JSON in MySQL.
- The device row's `is_active` flag is false. Check `devices` table.

**`POST /devices` returns 401 UNAUTHENTICATED.** Your bearer token expired (Sanctum token TTL is 90 days by default). Re-run Step 5 to mint a fresh one.

---

## When Phase 2b Flutter starts

The Flutter app will replace this HTML page exactly:
1. `firebase_messaging.getToken()` → same FCM token shape (different value).
2. Same `POST /api/v1/devices` call you just made.
3. Same `NotificationService::send` dispatches to it.

The Firebase project, service-account JSON, and server-side wiring stay unchanged. The Web app you registered in Step 1 just becomes a sibling of the eventual Android + iOS apps under "All apps" in Project Settings.

---

## Cleanup when done

To remove the test device after verifying:

```bash
# Find the device id from Step 5's response.
curl -X DELETE -H "Authorization: Bearer $TOKEN" http://127.0.0.1:8000/api/v1/devices/<DEVICE_ID>
```

Or to drop the whole test user:

```php
// In tinker
\App\Models\User::where('email', 'fcm-test@example.com')->delete();
```

The Web App you added to Firebase Console can stay — it costs nothing and is useful for any future browser-based testing.

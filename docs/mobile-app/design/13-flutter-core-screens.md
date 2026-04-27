# 13. Flutter — Core Screens

The feature body of the app. Covers: dashboard, search, discover, profile view, my profile edit, photo manager, interests inbox, chat thread, notifications.

Each screen follows the pattern from `12-flutter-auth-onboarding.md`. Design screenshots needed per screen — ping me when ready to build each.

---

## 13.1 Dashboard

**Purpose:** landing screen after login. Summarises activity, surfaces matches, prompts next action.

**Entry points:** Splash (authenticated), bottom nav "Home" tab, any deep link with empty path.

**API calls:**
- `GET /api/v1/dashboard` (single call returns all sections)
- `GET /api/v1/notifications/unread-count` (for bell badge, polled every 30s while focused)

**State:**
- `dashboardProvider` (AsyncValue<DashboardData>) — refreshed on pull-to-refresh + on app resume
- `unreadCountProvider` — polled, consumed by app shell

**Sections (in order):**
1. **Top bar:** logo, search icon (→ `/search`), notification bell with unread badge (→ `/notifications`)
2. **CTA banner** (conditional):
   - If `profile_completion_pct < 80` → "Complete your profile" with progress bar → `/profile`
   - Else if `!email_verified` → "Verify email" → `/verify/email`
   - Else if `!is_premium` → "Upgrade to Gold — 50% off" → `/membership`
   - Else: hide banner
3. **Stats row:** 4 pill counts — Interests received, Profile views, Shortlisted, Mutual matches. Tapping → respective screen
4. **Recommended Matches** horizontal carousel — up to 10 `ProfileCard` widgets, "See all →" → `/matches/my`
5. **Mutual Matches** carousel
6. **Recent Views** carousel (premium — else "Upgrade to see who viewed you")
7. **Newly Joined** carousel
8. **Discover teasers** — 2×2 grid of category tiles with counts → `/discover/{cat}`

**States:**
- Loading (initial): shimmer skeleton for each section
- Error: error view with retry — keeps stats/carousel position stable if cached data exists
- Empty: each section handles "no data yet" inline (e.g. "Complete your partner preferences to see matches")

**Navigation out:**
- Search icon → `/search`
- Bell → `/notifications`
- CTA → various
- Stats → respective feature
- Profile card tap → `/profiles/{matriId}`
- "See all" → feature-specific full list

**Design placeholder:** needs screenshot showing section ordering, card shape, carousel style.

---

## 13.2 Search Screen

**Purpose:** advanced partner search with 15+ filters, keyword, or matri-ID lookup.

**Entry points:** dashboard top-bar search icon, bottom nav "Search" tab.

**UI layout:**
- Top tab bar: "Partner Search" / "Keyword" / "By ID"
- Filter sheet opens from bottom (slide-up modal with all filter categories)
- Active filter chips displayed horizontally above results
- Results list (scrollable, paginated, pull-to-refresh)
- Sort dropdown top-right

**API calls:**
- `GET /api/v1/search` (partner filters) — debounced 500ms
- `GET /api/v1/search/keyword?q=...`
- `GET /api/v1/search/id/{matriId}`
- `GET /api/v1/search/saved` on tab-switch to view saved searches
- `POST /api/v1/search/saved` to save current filter set
- Reference data calls (`/reference/religions`, etc.) for filter dropdowns — cached 24h

**State:**
- `searchFiltersProvider` (filter object, serialisable so we can persist last used)
- `searchResultsProvider` (AsyncValue<PaginatedList<ProfileCard>>)
- `savedSearchesProvider`

**Filter categories (sections in bottom sheet):**
- Age range (RangeSlider 18–60)
- Height range (RangeSlider cm)
- Religion (multi-select chips)
- Caste (multi-select chips, cascades from religion)
- Sub-caste (cascades from caste)
- Education (multi-select)
- Occupation (multi-select)
- Income range (chips)
- Marital status (chips)
- Mother tongue (chips)
- Native state / country (cascading dropdown)
- Residing country (chips)
- Complexion (chips)
- Body type (chips)
- Family status (chips)
- Diet / Drinking / Smoking (chips)
- Manglik (3-option radio)
- "Only with photo" / "Only verified" / "Only premium" toggles

**Sort options:**
- Relevance (default)
- Newest first
- Recently active
- Age low → high
- Age high → low
- Match score (only when results include it)

**Navigation out:**
- Profile card → `/profiles/{matriId}`
- "Save this search" → bottom sheet to name it → stored
- "Saved searches" icon → list of saved searches with "Run" action

**States:**
- Loading (initial): shimmer list
- Loading (next page): spinner at bottom during scroll
- Empty: "No profiles match. Try loosening filters." + "Clear filters" action
- Error: retry button

**Design placeholder:** screenshot of filter sheet + results list + active chip bar.

---

## 13.3 Discover Hub

**Purpose:** SEO/engagement surface. Browse by community categories without needing to set up a search.

**Entry points:** dashboard discover teasers, bottom nav "More" → Discover, deep link `/discover`.

**API calls:**
- `GET /api/v1/discover` (hub)
- `GET /api/v1/discover/{category}` (category subcategory list)
- `GET /api/v1/discover/{category}/{slug}` (results)

**UI layout:**
- Grid of 13 category tiles with icon + label + profile count
- Tap category → next screen

**States:** loading (shimmer grid), error (retry), no empty state (always has 13 tiles).

**Design placeholder:** tile grid.

---

## 13.4 Discover Category

**Purpose:** within a category, browse subcategories (e.g. NRI Matrimony → list of countries).

**Entry:** `/discover/{category}`.

**UI:** list or grid of subcategories with counts. Tap → results screen.

**Special case (direct_filter categories):** no subcategory step — server returns results directly. UI renders results list instead of category list.

**Design placeholder:** list/grid of subcategories.

---

## 13.5 Discover Results

**Purpose:** profile list for a specific category/subcategory.

**Entry:** `/discover/{category}/{slug}`.

**UI:** same profile list as search results, header shows "NRI Matrimony > UAE".

**Public vs authenticated:** same screen either way; anonymous users get truncated detail per server gates. If user taps a card while unauthenticated → deep link to `/login` with return URL to that profile.

---

## 13.6 Profile View (other user)

**Purpose:** the conversion surface — user reads someone's profile and decides to send interest.

**Entry points:** any profile card tap, match result, notification deep link, deep link URL.

**API calls:**
- `GET /api/v1/profiles/{matriId}` — primary data source
- `POST /api/v1/profiles/{matriId}/shortlist` — toggle from this screen
- `POST /api/v1/profiles/{matriId}/interest` — send interest
- `POST /api/v1/profiles/{matriId}/photo-request` — if photos gated
- `GET /api/v1/matches/score/{matriId}` — if score not included in response (rare)

**UI layout:**
- Hero: primary photo (tappable → full-screen gallery with album + family photos)
- Sticky action bar at bottom: "Send Interest" / "Shortlist" (heart toggle) / "Share" / "•••" menu (block, report, ignore)
- Tabbed body (swipeable):
  - **About** — primary info, about_me, match score breakdown chart
  - **Family** — family details, siblings, about_family
  - **Preferences** — partner preferences (what they're looking for)
  - **Background** — religion, education, occupation, location
- If contact unlocked (premium + accepted interest): "Contact" tab is visible with phone/email/WhatsApp buttons (tap to launch dialer/mail/WhatsApp via `url_launcher`)

**Gated states:**
- Contact tab hidden or shows "Premium to view" CTA
- Photos blurred with "Request photos" overlay button
- Whole profile might redirect to 404 if blocked/suspended/hidden — show "Profile not available" screen

**State:**
- `profileViewProvider(matriId)` — single source
- `interestStatusProvider(matriId)` — updates after send/accept/decline
- `shortlistStatusProvider(matriId)` — updates after toggle

**Navigation out:**
- "Send Interest" → shows template picker bottom sheet → submits → success toast
- "Shortlist" → toggles, heart animates
- "Share" → system share sheet with formatted link `https://kudlamatrimony.com/profile/AM100042`
- "•••" → bottom sheet (Block, Report, Ignore — confirmations before each)
- Contact action → launches dialer/mail/WhatsApp intent (counts toward daily contact view limit)

**Empty/error/loading:**
- Loading: shimmer hero + shimmer text blocks
- Error: "Couldn't load profile" with retry
- Blocked/Hidden/Suspended: "This profile isn't available" with back button

**Design placeholder:** hero + tabs + action bar. Biggest screen — need detailed screenshot.

---

## 13.7 My Profile (view + edit)

**Purpose:** authenticated user views + edits their own profile.

**Entry:** bottom nav "Profile" tab, dashboard CTA "Complete profile".

**UI layout:**
- Header: own primary photo + name + matri_id + badges (verified, premium, VIP, featured)
- Profile completion pct bar with "Complete" → `/profile/complete` (jumps to first incomplete section)
- Accordion with 9 sections (same as web):
  - Primary, Religious, Education, Family, Location, Contact, Hobbies, Social, Partner Preferences
- Each section:
  - Summary of current values
  - Edit button → opens section editor sheet
- Photos row → "Manage photos" → `/photos`
- ID Proof row → "Submit ID Proof" or "Verified" badge → `/settings?section=id-proof`
- Preview button → opens preview sheet (matches what others see)

**API calls:**
- `GET /api/v1/profile/me` — section data
- `PUT /api/v1/profile/me/{section}` — save edits
- `POST /api/v1/profile/me/jathakam` — upload jathakam PDF/image

**Section editors:** reusable per-section forms. Content identical to registration step forms (same fields) but only edits one section at a time.

**State:** `myProfileProvider` (AsyncValue<MyProfileData>). Refetched after each section save.

**Navigation out:** sections stay on same screen (accordion). "Manage photos" / "Share profile" / "Preview" open respective screens.

**Design placeholder:** accordion layout + section editor sheet.

---

## 13.8 Photo Manager

**Purpose:** upload, arrange, set primary, delete, manage privacy of profile photos.

**Entry:** My Profile → "Manage photos", or dashboard CTA "Add photos".

**UI layout:**
- Tab bar: Profile (1) / Album (9) / Family (3)
- Active tab shows grid of current photos
- Empty cell (+) triggers file picker → crop → upload
- Long-press photo → action sheet: Set primary (profile tab), Delete, Restore (if archived)
- Switch tab at top: "Active" / "Pending" / "Rejected" / "Archived"
- Top-right: "Privacy settings" gear → bottom sheet with 3 toggles (gated_premium, show_watermark, blur_non_premium)

**API calls:**
- `GET /api/v1/photos` on mount
- `POST /api/v1/photos` (multipart) — upload
- `POST /api/v1/photos/{id}/primary` — set primary
- `DELETE /api/v1/photos/{id}` — archive
- `POST /api/v1/photos/{id}/restore` — restore
- `POST /api/v1/photos/privacy` — privacy toggles

**Upload flow:**
1. Tap "+" → `image_picker` opens gallery
2. User selects photo → `image_cropper` for rectangle crop (aspect ratio per type: profile 4:5, album free)
3. `flutter_image_compress` if > 2 MB (target < 2 MB before upload)
4. `POST /photos` multipart with progress indicator
5. Success → refresh grid, new photo shown with "Pending approval" tag if auto-approve is off

**State:** `photosProvider` — single fetch, invalidated on upload/delete/etc.

**Navigation out:** back to My Profile.

**Empty:** "Add a photo to get 3× more interest responses" with primary upload button.

**Design placeholder:** grid + upload sheet + privacy sheet.

---

## 13.9 Interests Inbox

**Purpose:** primary engagement surface — review received interests, track sent ones, continue chats.

**Entry:** bottom nav "Interests" tab, notification deep links.

**UI layout:**
- Top tab bar: Received / Sent / Accepted / Declined / Trash
- Each tab shows list of interest cards
- Interest card:
  - Avatar + name + age + location
  - Timestamp / "Active 2h ago" badge
  - Last message preview (if accepted and chat exists)
  - Status chip (Pending / Accepted / Declined / Expired)
  - Unread dot if new reply
- Tap card → `/interests/{id}` (thread view)
- Swipe left → Star / Trash (based on tab)
- Long press → action sheet

**API calls:**
- `GET /api/v1/interests?tab=received&page=1`
- `POST /api/v1/interests/{id}/accept|decline|cancel|star|trash` — inline quick actions

**State:**
- `interestsProvider(tab)` — paginated list per tab
- `interestCountsProvider` — for tab badges

**Inline actions (without opening thread):**
- Received tab: "Accept" / "Decline" buttons on each card
- Sent tab: "Cancel" button if within 24h window

**Empty states per tab:**
- Received: "No interests yet. Your profile is visible to matches — stay patient."
- Sent: "You haven't sent any interests. Browse matches to start."
- Accepted: "No accepted interests yet."

**Navigation out:** card tap → thread; top search icon → `/search`.

**Design placeholder:** tab bar + card list. Key screen.

---

## 13.10 Chat Thread (Interest Detail)

**Purpose:** view full interest history + reply if accepted.

**Entry:** Interests inbox card tap, notification deep link.

**UI layout:**
- Header: back button, other party's avatar + name + online status, "View profile" icon
- If status=pending (received): Accept + Decline buttons at top
- If status=pending (sent): Cancel button (if within 24h)
- If status=accepted: chat thread below
- Message list (scroll up for older, auto-scroll on new message)
- Input bar at bottom (disabled if status != accepted OR user not premium)
  - Text input + send button
  - If non-premium: "Upgrade to chat" CTA instead of input bar

**API calls:**
- `GET /api/v1/interests/{id}` on mount (full thread)
- `GET /api/v1/interests/{id}/messages/since/{lastId}` every 10s while screen focused (polling)
- `POST /api/v1/interests/{id}/messages` on send
- `POST /accept` / `/decline` / `/cancel` inline

**Polling logic:**
```dart
Timer.periodic(const Duration(seconds: 10), (t) {
  if (!screenFocused) { t.cancel(); return; }
  ref.read(chatMessagesProvider(interestId).notifier).fetchSince();
});
```

On app backgrounded → pause polling. On resume → immediate fetch + resume polling. On leave screen → cancel timer.

**State:**
- `interestDetailProvider(id)` — thread metadata
- `chatMessagesProvider(id)` — message list, appendable via polling

**Navigation out:**
- Header "View profile" → `/profiles/{matriId}`
- Back → inbox

**Empty state:** accepted with no replies yet — input enabled, body shows "No messages yet. Say hi 👋"

**Design placeholder:** WhatsApp-style bubble thread. Screenshot needed.

---

## 13.11 Notifications Screen

**Purpose:** full list of notifications (matches bell icon).

**Entry:** bell icon from any screen, deep link `/notifications`.

**UI layout:**
- Top: "Mark all read" button (when unread > 0)
- List of notifications:
  - Icon (per type from §9.1 catalogue)
  - Title, body, relative time
  - Unread dot
  - Tap → deep link in `data.deep_link`
- Pagination on scroll
- Pull-to-refresh

**API calls:**
- `GET /api/v1/notifications?page=N`
- `POST /api/v1/notifications/{id}/read` on tap
- `POST /api/v1/notifications/read-all`

**Empty:** "No notifications yet. Go find matches!"

**Design placeholder:** list. Simple.

---

## 13.12 Matches (My / Mutual)

**Purpose:** browse curated matches.

**Entry:** dashboard "See all" on Recommended or Mutual, deep link.

**UI:** same as search results but pre-filtered server-side. Top segment control: "My Matches" / "Mutual". Uses shared `ProfileCard` widget. Pull-to-refresh.

**API calls:**
- `GET /api/v1/matches/my`
- `GET /api/v1/matches/mutual`

**Design placeholder:** list. Can reuse search results layout.

---

## 13.13 Shortlist / Who Viewed / Blocked / Ignored

These four are nearly-identical list screens. Share one widget `ProfileListScreen` with a `data source` parameter.

| Screen | Endpoint | Title | Empty copy |
|--------|----------|-------|-----------|
| Shortlist | `GET /shortlist` | "Shortlisted" | "Tap the heart on profiles to shortlist." |
| Who Viewed | `GET /views?tab=viewed_by` | "Who Viewed Me" | "Nobody's viewed you yet. Complete your profile to boost visibility." |
| I Viewed | `GET /views?tab=i_viewed` | "Profiles I Viewed" | "You haven't viewed anyone yet." |
| Blocked | `GET /blocked` | "Blocked" | "You haven't blocked anyone." (Unblock action on card) |
| Ignored | `GET /ignored` | "Ignored" | "No ignored profiles." (Un-ignore action on card) |

**Entry:** "More" tab menu.

**Navigation out:** card → `/profiles/{matriId}`.

**Design placeholder:** shared list UI. Screenshot for ProfileListScreen layout.

---

## 13.14 Shared Widget — ProfileCard

**Used by:** dashboard carousels, search results, matches, shortlist, discover results, interests inbox.

**Variants:**
- Full: photo + name + 3 lines of details + match score badge + action icons (heart, interest send)
- Compact: photo + name + 1 line of details (for carousels)
- Inbox: adds last message preview + status chip

**Props:**
```dart
class ProfileCard extends ConsumerWidget {
  final ProfileCardDto profile;
  final ProfileCardVariant variant;
  final VoidCallback? onTap;
  final VoidCallback? onShortlist;
  final VoidCallback? onSendInterest;
  final Widget? trailingAction;
}
```

Implements: tap → navigate to profile, blurred photo handling, badges.

**Design placeholder:** one screenshot per variant.

---

## 13.15 Build Checklist

- [ ] `DashboardScreen` with all sections + pull-to-refresh
- [ ] `SearchScreen` with filter sheet, tabs, saved searches
- [ ] `DiscoverHubScreen`, `DiscoverCategoryScreen`, `DiscoverResultsScreen`
- [ ] `ProfileViewScreen` with tabbed body + sticky action bar + gallery
- [ ] `MyProfileScreen` with 9-section accordion + section editor sheet
- [ ] `PhotoManagerScreen` with 3 tabs + upload + crop + privacy sheet
- [ ] `InterestsScreen` with 5 tabs + inline actions + counts
- [ ] `InterestThreadScreen` with polling chat + accept/decline/cancel inline
- [ ] `NotificationsScreen` with mark-read + pagination
- [ ] `MatchesScreen` with my/mutual segment
- [ ] `ProfileListScreen` (shared) for shortlist, views, blocked, ignored
- [ ] `ProfileCard` widget with 3 variants + blurred-photo handling
- [ ] Shared `EmptyState`, `ErrorView`, `LoadingSkeleton` widgets
- [ ] All screens have pull-to-refresh where list-based
- [ ] All async operations handle loading/error/empty

**Screens needing screenshots (you'll provide):**
1. Dashboard
2. Search (results + filter sheet)
3. Discover hub
4. Discover category
5. Discover results
6. Profile view (other user) — hero + each tab
7. My Profile (accordion collapsed + expanded)
8. Photo manager (grid + upload sheet + privacy sheet)
9. Interests inbox (each tab)
10. Chat thread (accepted state)
11. Chat thread (pending received — Accept/Decline buttons)
12. Notifications
13. Matches (My + Mutual)
14. Shortlist / Who Viewed / Blocked / Ignored (one representative screenshot — rest follow pattern)
15. ProfileCard (all 3 variants)

**Acceptance:** after §12 + §13 ships, the app covers 100% of the web's user-facing read/write flows. Still missing: membership purchase, settings, polish (§14 + §15).

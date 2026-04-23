# Scaling Guide — MatrimonyTheme

When to optimize, what to change, and how. Each section is independent — do them when needed, not all at once.

---

## 1. Caching (Any Scale — Do First)

**Current:** Dashboard widgets cached 5 min, site settings cached 1 hour, reference data not cached
**Quick wins with zero risk:**

### What to cache

| Data | TTL | Status |
|------|-----|--------|
| Admin dashboard widgets | 5 min | Done |
| Site settings | 1 hour | Done |
| Reference data (dropdown lists) | 24 hours | Not done |
| Discover category counts | 1 hour | Not done |
| Profile completion % | On update | Done (stored in column) |
| Search result counts per tab | 5 min | Not done |

### Steps for reference data
```php
// In a helper or config:
$religions = Cache::remember('ref_religions', 86400, fn () => config('reference_data.religion_list'));
$castes = Cache::remember('ref_castes', 86400, fn () => config('reference_data.caste_list'));
```

### Cache gotcha (learned the hard way)
Never cache Eloquent Collections — they cause `__PHP_Incomplete_Class` on deserialization. Always cache the final array/scalar:
```php
// BAD: Cache::remember('data', 300, fn () => Model::all());
// GOOD: Cache::remember('data', 300, fn () => Model::pluck('name', 'id')->toArray());
```

---

## 2. Email Queue (500+ Daily Emails)

**Current:** Emails sent synchronously (blocks the request 1-3 seconds each)
**Problem:** Interest notifications, match alerts, expiry reminders — each blocks the user's request

### Steps
1. Set `QUEUE_CONNECTION=database` in `.env`
2. Run `php artisan queue:table && php artisan migrate`
3. Change `Mail::send()` to `Mail::queue()` in notification services
4. Set up queue worker via cron (shared hosting can't run persistent workers):
   ```
   * * * * * cd /path/to/project && php artisan queue:work --stop-when-empty --max-time=55
   ```

### Hostinger note
Use `--stop-when-empty --max-time=55` so each cron run processes pending jobs and exits before the next minute's cron fires.

---

## 3. Database Indexing (5K+ Users)

**Current:** Basic indexes on primary keys and foreign keys
**Problem at scale:** Search queries with multiple WHERE clauses get slow

### Indexes to add
```sql
-- Search: gender + active + approved (most common query)
ALTER TABLE profiles ADD INDEX idx_search_base (gender, is_active, is_approved, is_hidden, deleted_at);

-- Search: religion-based filtering
ALTER TABLE religious_info ADD INDEX idx_religion_denom (religion, denomination);
ALTER TABLE religious_info ADD INDEX idx_caste (caste);

-- Search: location filtering
ALTER TABLE location_info ADD INDEX idx_native (native_state, native_district);

-- Interests: inbox/sent queries
ALTER TABLE interests ADD INDEX idx_receiver_status (receiver_profile_id, status, created_at);
ALTER TABLE interests ADD INDEX idx_sender_status (sender_profile_id, status, created_at);

-- Profile views: who viewed me
ALTER TABLE profile_views ADD INDEX idx_viewed (viewed_profile_id, created_at);

-- Admin: user management filters
ALTER TABLE profiles ADD INDEX idx_completion (profile_completion_pct);
ALTER TABLE profiles ADD INDEX idx_created (created_at);
```

### When to trigger
- Search page takes more than 500ms
- Admin user list loads slowly
- Run `EXPLAIN` on slow queries to verify index usage

---

## 4. Photo CDN (1K+ Users Uploading Photos)

**Current:** Photos stored on local disk (`storage/app/public/`), watermarked via GD
**Problem at scale:** Shared hosting disk fills up (user reported storage becoming full on Hostinger)

### Recommended: Cloudinary
| Aspect | Detail |
|--------|--------|
| Free tier | 25GB storage, 25GB bandwidth/month |
| Laravel package | `cloudinary-labs/cloudinary-laravel` (verified compatible) |
| Benefits | Auto-optimization, responsive images, CDN delivery, transformations |

### Steps
1. `composer require cloudinary-labs/cloudinary-laravel`
2. Set `CLOUDINARY_URL` in `.env`
3. Update `PhotoController::upload()` to store on Cloudinary instead of local disk
4. WatermarkService can be replaced by Cloudinary overlay transformation
5. Migrate existing photos with a one-time script

### Alternative: AWS S3 + CloudFront
Better for production scale (~$5/mo per 50GB) but more setup effort.

---

## 5. Search Optimization (10K+ Profiles)

**Current:** Standard Eloquent `where`/`whereIn` queries
**Problem at scale:** Keyword search and multi-filter queries get slow

### Phase 1: MySQL FULLTEXT (quick, free)
```sql
ALTER TABLE profiles ADD FULLTEXT idx_profile_search (full_name, about_me);
ALTER TABLE education_details ADD FULLTEXT idx_edu_search (occupation, occupation_detail, employer_name, college_name);
```

Update SearchController:
```php
// Before: $q->where('full_name', 'LIKE', "%{$keyword}%")
// After:  $q->whereRaw('MATCH(full_name, about_me) AGAINST(? IN BOOLEAN MODE)', [$keyword])
```

### Phase 2: Meilisearch (10K+ profiles)
- Open-source, self-hosted, typo-tolerant
- Laravel Scout compatible: `composer require laravel/scout meilisearch/meilisearch-php`
- Requires VPS (can't run on shared hosting)
- Sub-50ms search across 100K+ profiles

---

## 6. Match Score Caching (10K+ Users)

**Current:** Scores calculated on-the-fly in PHP (~10-50ms for 500 candidates)
**Problem at scale:** With 10K+ profiles, scoring 5,000+ candidates gets slow (1-2 seconds)
**Solution:** Pre-calculate scores into `match_scores` table

### What exists already
- `match_scores` table — created and migrated (currently empty)
- `app/Models/MatchScore.php` — model ready with relationships
- `app/Services/MatchingService.php` — service with 12-criteria weighted scoring

### Steps
1. Create `app/Jobs/RecalculateMatchScores.php` — background job
2. Dispatch on: profile update, preference update, new registration
3. Update `MatchingService::getMatches()` to query pre-calculated scores
4. Initial run: loop all profiles, dispatch job for each

### What does NOT change
- Controllers, views, routes — same interfaces
- Profile card badges — same data format

---

## 7. Session & Auth (10K+ Concurrent Users)

**Current:** Database sessions (`SESSION_DRIVER=database`)
**Problem at scale:** Thousands of session rows slow down queries

### Upgrade path
1. **Database** (current) — fine for up to 10K concurrent users
2. **Redis** — much faster, requires VPS: `SESSION_DRIVER=redis`
3. Also move cache to Redis: `CACHE_DRIVER=redis`

---

## 8. Hosting Upgrade Path

| Users | Hosting | Monthly Cost | What You Get |
|-------|---------|-------------|--------------|
| 0-5K | Hostinger Business (current) | ~₹200 | Shared hosting, LiteSpeed, SSH |
| 5K-20K | Hostinger VPS | ~₹500 | Root access, Redis, queue workers, Meilisearch |
| 20K-50K | DigitalOcean/AWS | ~$24-50 | Dedicated resources, auto-scaling |
| 50K+ | Multi-server | ~$100+ | App + DB + Redis + CDN separated |

### Signs you've outgrown shared hosting
- Persistent queue workers needed
- Redis needed for sessions/cache
- 503 errors during peak hours
- Cron jobs timing out
- Disk storage full (photos)

---

## 9. Real-time Features (When Adding Live Chat)

**Current:** Standard HTTP request/response, page refresh for updates
**When needed:** 500+ daily active users actively chatting, users complaining about refresh-to-see-messages
**Estimated timeline:** Not needed for 12-18 months minimum. Page-refresh notifications are fine for early-stage portals.

### What real-time enables

| Feature | Without (current) | With Real-time |
|---------|-------------------|----------------|
| New interest | Seen on next page load | Instant popup notification |
| Chat messages | Refresh to see new messages | Messages appear instantly (WhatsApp-style) |
| Online status | "Last active 2 hrs ago" | Green dot "Online now" |
| Profile viewed | Notification on next visit | Instant "Someone is viewing you" |
| Payment status | Redirect after payment | Instant confirmation without redirect |

### Laravel Reverb (recommended)
- First-party Laravel WebSocket server (built by Laravel team)
- Free, self-hosted, requires VPS (won't work on shared hosting)
- Works with Laravel Echo + Pusher.js on frontend

### Implementation Steps
1. `composer require laravel/reverb`
2. `php artisan reverb:install`
3. Configure `.env`:
   ```
   BROADCAST_CONNECTION=reverb
   REVERB_APP_ID=your-app-id
   REVERB_APP_KEY=your-app-key
   REVERB_APP_SECRET=your-app-secret
   REVERB_HOST=your-domain.com
   REVERB_PORT=8080
   ```
4. Install frontend packages:
   ```
   npm install laravel-echo pusher-js
   ```
5. Configure Echo in `resources/js/echo.js`
6. Enable broadcasting in `config/broadcasting.php`
7. Create broadcast events:
   - `NewMessageReceived` — instant chat delivery
   - `InterestReceived` / `InterestAccepted` / `InterestDeclined`
   - `ProfileViewed`
   - `PaymentSuccessful` / `PaymentFailed`
   - `ProfileVerified` — ID proof approval notification
8. Set up channels:
   - `private-user.{userId}` — personal notifications
   - `private-chat.{conversationId}` — user-to-user messaging
   - `presence-online` — online/offline status (optional)
9. Run Reverb server:
   - Development: `php artisan reverb:start`
   - Production: `php artisan reverb:start --host=0.0.0.0 --port=8080`
   - Use **Supervisor** to keep Reverb running in production
10. Queue worker for broadcasting:
    - `php artisan queue:work` (Redis driver recommended)
    - Supervisor for production persistence

### Prerequisites
- **VPS hosting** (Reverb can't run on shared hosting — no persistent process)
- **Redis** (recommended for broadcast queue performance)
- **Supervisor** (keeps Reverb + queue worker running after server restart)

### Alternative: Pusher (if staying on shared hosting)
- Hosted service, works without VPS
- Free tier: 200K messages/day, 100 concurrent connections
- Easier setup but third-party dependency, data on Pusher's servers

---

## 10. Python Microservice for ML Matching (50K+ Users)

**Current:** 12-criteria weighted scoring in PHP (fast, rule-based)
**When needed:** 50K+ users where behavioral/ML matching adds value

### Architecture
```
User → Laravel (web app) ──HTTP──→ Python FastAPI (ML engine) → JSON scores
                                   ├── Collaborative filtering
                                   ├── NLP personality matching
                                   └── Photo quality scoring
```

### When NOT to use Python
- Under 50K users — PHP calculates scores in milliseconds
- Rule-based matching (age, religion, caste) — PHP handles this perfectly

### When to consider Python
- "Users who liked profile A also liked B" (collaborative filtering)
- NLP analysis of "About Me" for personality compatibility
- Match prediction based on historical acceptance patterns

### Integration
- FastAPI on same server (port 8001)
- Laravel calls via `Http::get('http://localhost:8001/recommend/123')`
- Python reads from same MySQL database (read-only)
- Results cached in `match_scores` table

---

## Priority Order

| Priority | Optimization | Trigger | Effort |
|----------|-------------|---------|--------|
| 1 | Caching (reference data, counts) | Now | 1 hour |
| 2 | Email queue | 500+ daily emails | 2 hours |
| 3 | Database indexing | Queries >500ms | 30 min |
| 4 | Photo CDN (Cloudinary) | Disk filling up | 3 hours |
| 5 | Search optimization (FULLTEXT) | Keyword search >1s | 1 hour |
| 6 | Match score caching | 10K+ profiles | 4 hours |
| 7 | Real-time (Reverb/Pusher) | 500+ DAU chatting (12-18 months away) | 1-2 days |
| 8 | Session → Redis | 10K+ concurrent | 30 min (needs VPS) |
| 9 | Hosting upgrade (VPS) | Shared hosting limits | 1 day |
| 10 | Python ML microservice | 50K+ users | 1 week |

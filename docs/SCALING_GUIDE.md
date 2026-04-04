# Scaling Guide — Anugraha Matrimony

When to optimize, what to change, and how. Each section is independent — do them when needed, not all at once.

---

## 1. Match Score Caching (10K+ users)

**Current:** Scores calculated on-the-fly in PHP (~10-50ms for 500 candidates)
**Problem at scale:** With 10K+ profiles, fetching and scoring 5,000+ candidates gets slow (1-2 seconds)
**Solution:** Pre-calculate scores into `match_scores` table, query directly

### When to trigger
- `/matches` page takes more than 1-2 seconds
- Active profiles exceed 10,000

### What exists already
- `match_scores` table — created and migrated (currently empty)
- `app/Models/MatchScore.php` — model ready with relationships
- `app/Services/MatchingService.php` — service with clean method signatures

### Steps (30-minute task)
1. Create `app/Jobs/RecalculateMatchScores.php`:
   ```php
   // For a single profile: recalculate scores against all opposite-gender profiles
   // Insert/update rows in match_scores table
   // Dispatch this job when:
   //   - User updates their profile
   //   - User updates partner preferences
   //   - New user registers
   //   - Admin changes match weights
   ```
2. Update `MatchingService::getMatches()`:
   ```php
   // Before (v1): fetch 500 candidates, score in PHP
   // After (v2): SELECT FROM match_scores WHERE profile_id = X ORDER BY score DESC
   ```
3. Add job dispatch in `ProfileController::update()` and `PartnerPreference` save logic
4. Run initial calculation for all existing users: `php artisan tinker` → loop all profiles, dispatch job

### Files involved
| File | Status | Action |
|------|--------|--------|
| `app/Models/MatchScore.php` | Exists | No change needed |
| `database/migrations/2026_04_04_125409_create_match_scores_table.php` | Migrated | No change needed |
| `app/Services/MatchingService.php` | Exists | Change internal methods |
| `app/Jobs/RecalculateMatchScores.php` | Create | New background job |
| `app/Http/Controllers/ProfileController.php` | Exists | Add job dispatch on save |
| `config/queue.php` | Exists | Ensure queue driver is set (database or redis) |

### What does NOT change
- `MatchController.php` — same method calls
- All views — same data format
- Routes — same URLs
- Profile card badges — same props

---

## 2. Database Indexing (5K+ users)

**Current:** Basic indexes on profiles table
**Problem at scale:** Search queries with multiple WHERE clauses get slow

### Indexes to add
```sql
-- Matching engine: speed up candidate fetching
ALTER TABLE profiles ADD INDEX idx_gender_active_hidden (gender, is_active, is_hidden);

-- Search: speed up religion-based filtering
ALTER TABLE religious_info ADD INDEX idx_religion_denom (religion, denomination);

-- Search: speed up location filtering
ALTER TABLE location_info ADD INDEX idx_native_state_district (native_state, native_district);

-- Interests: speed up inbox queries
ALTER TABLE interests ADD INDEX idx_receiver_status_created (receiver_profile_id, status, created_at);
ALTER TABLE interests ADD INDEX idx_sender_status_created (sender_profile_id, status, created_at);
```

### When to trigger
- Search page takes more than 500ms
- Dashboard loads slowly

---

## 3. Photo CDN (1K+ users uploading photos)

**Current:** Photos stored on local disk (`storage/app/public/`)
**Problem at scale:** Shared hosting disk fills up, slow image loading

### Options (choose one)
| Option | Cost | Effort | Best For |
|--------|------|--------|----------|
| Cloudinary | Free up to 25GB | Low (already in tech stack) | Quick fix |
| AWS S3 + CloudFront | ~$5/mo per 50GB | Medium | Production scale |
| Hostinger file manager | ₹0 (included) | None | Current hosting |

### Steps for Cloudinary
1. `composer require cloudinary-labs/cloudinary-laravel`
2. Set `CLOUDINARY_URL` in `.env`
3. Update `PhotoController` to upload to Cloudinary instead of local disk
4. Migrate existing photos with a one-time script

---

## 4. Email Queue (500+ daily emails)

**Current:** Emails sent synchronously (blocks the request until sent)
**Problem at scale:** Interest notifications, match alerts — each takes 1-3 seconds to send

### Steps
1. Set `QUEUE_CONNECTION=database` in `.env`
2. Run `php artisan queue:table && php artisan migrate`
3. Change `Mail::send()` to `Mail::queue()` in NotificationService
4. Set up queue worker: `php artisan queue:work` (or cron on shared hosting)

### Hostinger note
Shared hosting can't run persistent queue workers. Use `php artisan queue:work --stop-when-empty` in a cron job every minute instead.

---

## 5. Search Optimization (10K+ profiles)

**Current:** `LIKE '%keyword%'` for keyword search (full table scan)
**Problem at scale:** Keyword search gets progressively slower

### Options
| Approach | Effort | Best For |
|----------|--------|----------|
| MySQL FULLTEXT index | Low | Simple keyword search |
| Laravel Scout + Meilisearch | Medium | Advanced search with facets |
| Algolia | Low (hosted) | Best UX, costs $$ |

### Quick fix: MySQL FULLTEXT (already partially done)
```sql
-- Already exists on profiles:
-- FULLTEXT idx_search (full_name, about_me)

-- Add to education_details:
ALTER TABLE education_details ADD FULLTEXT idx_edu_search (occupation, occupation_detail, employer_name, college_name);
```

Then in `SearchController`, change `LIKE` to `MATCH ... AGAINST`:
```php
// Before
$q->where('full_name', 'LIKE', "%{$keyword}%")

// After
$q->whereRaw('MATCH(full_name, about_me) AGAINST(? IN BOOLEAN MODE)', [$keyword])
```

---

## 6. Caching (any scale — quick win)

### What to cache
| Data | TTL | Method |
|------|-----|--------|
| Reference data (dropdown lists) | 24 hours | `Cache::remember()` in config helper |
| Dashboard stats (total users, interests) | 5 minutes | `Cache::remember()` in DashboardController |
| Profile completion percentage | On update | Already stored in `profile_completion_pct` column |
| Site settings (site name, colors) | 1 hour | `Cache::remember()` in SiteSetting model |
| Discover category subcategories | 1 hour | `Cache::remember()` in discover config |

### Steps
```php
// In DashboardController:
$interestStats = Cache::remember("interest_stats_{$profile->id}", 300, function () use ($profile) {
    return [
        'sent' => $profile->sentInterests()->count(),
        // ...
    ];
});
```

---

## 7. Session & Auth (10K+ concurrent users)

**Current:** File-based sessions (`SESSION_DRIVER=file`)
**Problem at scale:** Thousands of session files slow down disk I/O

### Steps
1. Switch to database sessions: `SESSION_DRIVER=database`
2. Run `php artisan session:table && php artisan migrate`
3. Or if you add Redis: `SESSION_DRIVER=redis` (fastest)

---

## 8. Hosting Upgrade Path

| Users | Current Hosting | Recommended |
|-------|----------------|-------------|
| 0-5K | Hostinger shared (₹200/mo) | Fine as-is |
| 5K-20K | Hostinger shared | Upgrade to Hostinger VPS (₹500/mo) — get SSH, Redis, queue worker |
| 20K-50K | VPS | Dedicated server or DigitalOcean Droplet ($24/mo) |
| 50K+ | Dedicated | Multiple servers: app server + DB server + Redis + CDN |

### When to move off shared hosting
- Queue jobs needed (persistent background workers)
- Redis needed (caching, sessions, queues)
- SSH access limitations blocking deployment
- CPU/memory limits causing 503 errors

---

## Priority Order

Do these in order as your user count grows:

| Priority | Optimization | Trigger |
|----------|-------------|---------|
| 1 | Caching (quick win) | Anytime — do it now |
| 2 | Email queue | 500+ daily emails |
| 3 | Photo CDN | Disk filling up |
| 4 | Database indexing | Queries >500ms |
| 5 | Match score caching | 10K+ profiles |
| 6 | Search optimization | Keyword search >1s |
| 7 | Session driver | 10K+ concurrent users |
| 8 | Hosting upgrade | Shared hosting limits hit |

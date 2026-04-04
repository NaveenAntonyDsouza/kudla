# 13. Advertisement Management

Monetize ad spaces on the platform. Admin can manage ads without touching code.

## 13a. Ad Spaces (Frontend Locations)

Pre-defined ad slots where ads appear on the website:

| Ad Space | Location | Size | Type |
|----------|----------|------|------|
| Homepage Banner | Below hero section | 728x90 (leaderboard) | Image / HTML |
| Homepage Sidebar | Right column on homepage | 300x250 (medium rectangle) | Image / HTML |
| Search Results | Between every 5th result | 728x90 | Image / HTML |
| Profile View Sidebar | Right sidebar on profile view | 300x600 (half page) | Image / HTML |
| Dashboard Sidebar | Right sidebar on dashboard | 300x250 | Image / HTML |
| Footer Banner | Above footer (all pages) | 728x90 | Image / HTML |
| Mobile Banner | Between content on mobile | 320x50 (mobile leaderboard) | Image / HTML |

## 13b. Ad Management

| Field | Type | Description |
|-------|------|-------------|
| Ad Title | Text | Internal name (e.g., "Wedding Photography - March 2026") |
| Ad Space | Select | Which slot (from 15a) |
| Ad Type | Select | Image / HTML / Google AdSense |
| Image | File Upload | Banner image (for image type) |
| Click URL | URL | Where ad links to |
| HTML Code | Textarea | Custom HTML/JS (for HTML/AdSense type) |
| Advertiser Name | Text | Who placed the ad |
| Start Date | Date | When to start showing |
| End Date | Date | When to stop showing |
| Is Active | Toggle | Enable/disable |
| Priority | Number | Higher = shown first if multiple ads for same slot |

## 13c. Ad Analytics

| Metric | Description |
|--------|-------------|
| Impressions | How many times ad was shown |
| Clicks | How many times ad was clicked |
| CTR | Click-through rate (%) |
| Revenue | If tracked (manual entry or per-click rate) |
| By Date | Daily impression/click chart |

## 13d. Google AdSense Integration

- Admin can paste AdSense code for any ad space
- Toggle: "Use Google AdSense" per slot (overrides manual ads)
- Allows platform owners to monetize via Google AdSense without code changes

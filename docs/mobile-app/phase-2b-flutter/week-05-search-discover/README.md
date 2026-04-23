# Phase 2b — Week 5: Search & Discover

**Goal:** Full partner search + keyword search + discover hub + matches list. Search is the heart of matrimony — pay attention to filter UX.

**Design reference:** [`../../design/13-flutter-core-screens.md §13.2–13.5, 13.12`](../../design/13-flutter-core-screens.md)

**Screenshots needed:**
- Search screen (3 tabs: partner / keyword / by id)
- Filter bottom sheet (all filter categories)
- Active filter chip bar (horizontal)
- Sort dropdown/options
- Empty results state
- Discover hub (13 tiles)
- Discover category (subcategories)
- Discover results
- Matches (My + Mutual) with segment control
- Saved searches bottom sheet

---

## Steps

| # | Step |
|---|------|
| 1 | step-01-search-screen-shell.md — 3 tabs + filter-sheet button |
| 2 | step-02-filter-bottom-sheet.md — Age/height sliders, multi-select chips, cascading selects |
| 3 | step-03-search-results-list.md — Infinite scroll with pagination |
| 4 | step-04-active-filters-bar.md — Horizontal scroll of active filter chips (tap to remove) |
| 5 | step-05-sort-dropdown.md |
| 6 | step-06-keyword-search-tab.md |
| 7 | step-07-matri-id-lookup-tab.md |
| 8 | step-08-saved-searches-list.md — Load + save + delete |
| 9 | step-09-discover-hub-screen.md — 13 tile grid |
| 10 | step-10-discover-category-screen.md |
| 11 | step-11-discover-results-screen.md |
| 12 | step-12-matches-my-screen.md |
| 13 | step-13-matches-mutual-screen.md — Segment control shared |
| 14 | step-14-search-repository-providers.md |
| 15 | step-15-week-05-integration-test.md |
| 16 | week-05-acceptance.md |

---

## Acceptance

- [ ] Partner search with 5+ filters returns narrowed results
- [ ] Infinite scroll loads next page smoothly
- [ ] Active filter bar lets user remove a filter with 1 tap (results re-fetch)
- [ ] Sort dropdown changes order immediately
- [ ] Keyword search for "software bangalore" returns relevant profiles
- [ ] Matri-ID `AM100042` lookup opens profile directly
- [ ] Saved search: save → name it → run later → same filters applied
- [ ] Discover hub shows 13 tiles with profile counts
- [ ] Discover category shows subcategories (e.g., NRI → countries)
- [ ] Direct-filter categories (Kannadiga) skip subcategory step
- [ ] Matches tab preloads highest-scoring first
- [ ] Mutual Matches shows subset of My Matches

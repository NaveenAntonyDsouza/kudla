# Phase 2b — Week 6: My Profile + Editing

**Goal:** User can view their own profile, edit all 9 sections inline, preview how it looks to others, share a card image.

**Design reference:** [`../../design/13-flutter-core-screens.md §13.7`](../../design/13-flutter-core-screens.md), [`../../design/14-flutter-membership-settings.md`](../../design/14-flutter-membership-settings.md)

**Screenshots needed:**
- My Profile screen (accordion collapsed + expanded)
- Each of 9 section editor sheets (primary / religious / education / family / location / contact / hobbies / social / partner)
- Profile preview (what others see)
- Share card modal
- Profile completion progress bar

---

## Steps

| # | Step |
|---|------|
| 1 | step-01-my-profile-accordion.md — 9-section accordion with summary rows |
| 2 | step-02-completion-progress-bar.md |
| 3 | step-03-primary-section-editor.md |
| 4 | step-04-religious-section-editor.md — With religion-branch conditional |
| 5 | step-05-education-section-editor.md |
| 6 | step-06-family-section-editor.md |
| 7 | step-07-location-section-editor.md |
| 8 | step-08-contact-section-editor.md |
| 9 | step-09-hobbies-section-editor.md — Multi-select chips for each category |
| 10 | step-10-social-section-editor.md — URL validation |
| 11 | step-11-partner-section-editor.md — Range sliders + multi-select |
| 12 | step-12-preview-sheet.md — Shows own profile as ProfileView would render it |
| 13 | step-13-share-card-image.md — RepaintBoundary + toImage + share_plus |
| 14 | step-14-profile-repository-providers.md |
| 15 | step-15-week-06-acceptance.md |

---

## Acceptance

- [ ] All 9 sections editable, save persists
- [ ] Completion % updates live after each save
- [ ] Conditional religion fields correctly show/hide based on religion selection
- [ ] Share card renders on device, opens system share sheet
- [ ] Preview matches what other users see (test by opening another device)

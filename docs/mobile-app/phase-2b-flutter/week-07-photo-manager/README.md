# Phase 2b — Week 7: Photo Manager

**Goal:** User can upload, crop, arrange, delete photos across 3 types (profile/album/family) + privacy toggles + photo request lifecycle.

**Design reference:** [`../../design/13-flutter-core-screens.md §13.8`](../../design/13-flutter-core-screens.md)

**Screenshots needed:**
- Photo manager (3 tabs: profile / album / family)
- Upload button states (empty cell, pending, rejected)
- Image cropper screen
- Privacy settings sheet (3 toggles)
- Photo request list (received / sent)
- Photo request approval dialog

---

## Steps

| # | Step |
|---|------|
| 1 | step-01-photo-manager-tabs.md |
| 2 | step-02-photo-grid-widget.md |
| 3 | step-03-image-picker-integration.md — Gallery / camera |
| 4 | step-04-image-cropper-integration.md — Aspect ratio per photo type |
| 5 | step-05-upload-with-progress.md |
| 6 | step-06-set-primary-action.md |
| 7 | step-07-delete-restore-actions.md |
| 8 | step-08-privacy-settings-sheet.md |
| 9 | step-09-photo-request-list.md |
| 10 | step-10-send-photo-request.md — From Profile View if gated |
| 11 | step-11-approve-ignore-request.md |
| 12 | step-12-week-07-acceptance.md |

---

## Acceptance

- [ ] Upload a 10 MB JPEG → compressed + cropped + uploaded successfully
- [ ] 10th album photo upload blocked with clear message
- [ ] Set primary swaps atomically
- [ ] Delete archives (soft); with confirmation dialog shows "Restore within 30 days"
- [ ] Privacy toggles save and affect how photos appear to others
- [ ] Photo request sent from Profile View → appears in target's received list
- [ ] Approve request → requester can now see full photos (test with second device)

# Phase 2b — Week 8: Interests Inbox

**Goal:** 5-tab inbox with inline accept/decline/star/trash actions. No chat yet — that's Week 9.

**Design reference:** [`../../design/13-flutter-core-screens.md §13.9`](../../design/13-flutter-core-screens.md)

**Screenshots needed:**
- Interest inbox (each tab: Received / Sent / Accepted / Declined / Trash)
- Interest card shapes (pending received with Accept/Decline buttons; accepted with chat preview)
- Swipe-to-action interaction
- Empty state per tab

---

## Steps

| # | Step |
|---|------|
| 1 | step-01-interest-tabs-shell.md |
| 2 | step-02-interest-card-widget.md |
| 3 | step-03-inbox-received-tab.md — With Accept/Decline inline buttons |
| 4 | step-04-inbox-sent-tab.md — With Cancel button in window |
| 5 | step-05-inbox-accepted-tab.md — With chat preview |
| 6 | step-06-inbox-other-tabs.md — Declined, Trash |
| 7 | step-07-swipe-actions.md — Star / trash via swipe |
| 8 | step-08-send-interest-sheet.md — Bottom sheet from Profile View with template picker |
| 9 | step-09-daily-limit-banner.md — Shows usage + upgrade CTA |
| 10 | step-10-empty-states.md |
| 11 | step-11-week-08-acceptance.md |

---

## Acceptance

- [ ] All 5 tabs load data + empty states
- [ ] Accept/Decline buttons work without opening thread
- [ ] Cancel only shown within 24h window
- [ ] Daily limit banner shows "X of Y sent today"
- [ ] 6th send as free user shows friendly "upgrade for more" dialog
- [ ] Swipe-to-star + swipe-to-trash animate smoothly
- [ ] Templates list loads from `/interests/templates` (admin-editable)

# Phase 2b — Week 9: Chat Thread

**Goal:** Real chat screen with polling. User opens an accepted interest → sees full thread → can reply → sees new messages within 10s.

**Design reference:** [`../../design/13-flutter-core-screens.md §13.10`](../../design/13-flutter-core-screens.md)

**Screenshots needed:**
- Chat thread (WhatsApp-style bubble layout)
- Header with other party's avatar + name + "View profile" icon
- Input bar at bottom
- "Premium required" empty input state (if either party free)
- Status chip top of thread (Accepted / Pending / Expired)
- Typing / sending / failed message states

---

## Steps

| # | Step |
|---|------|
| 1 | step-01-chat-thread-shell.md |
| 2 | step-02-message-bubble-widget.md — Own vs other styling |
| 3 | step-03-message-list-view.md — Scroll to bottom on mount, older on scroll up |
| 4 | step-04-message-input-bar.md |
| 5 | step-05-polling-timer.md — 10s interval while focused; pause on background |
| 6 | step-06-since-endpoint-integration.md |
| 7 | step-07-premium-gate-ui.md — "Upgrade to chat" CTA if either party free |
| 8 | step-08-accept-decline-inline.md — For received pending |
| 9 | step-09-cancel-inline.md — For sent pending within 24h |
| 10 | step-10-optimistic-send.md — Show message immediately + sync on server response |
| 11 | step-11-failed-send-retry.md |
| 12 | step-12-week-09-acceptance.md |

---

## Acceptance

- [ ] Open accepted thread → full message history loads
- [ ] Send reply → appears in bubble list immediately
- [ ] Other device sends reply → arrives in < 12s via polling
- [ ] Backgrounding the app stops polling (battery-friendly)
- [ ] Foregrounding resumes polling + triggers immediate sync
- [ ] Free user sees input disabled + upgrade CTA instead
- [ ] Accept/Decline inline for pending received thread works
- [ ] Failed send shows retry button; retry succeeds on reconnect

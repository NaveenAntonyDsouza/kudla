# 8. Moderation, Support & Reports

## 8a. Reported Photos

Photos flagged by system or users:
- Inappropriate content
- Not a real photo
- Group photo as profile

| Column | Details |
|--------|---------|
| Matri ID | Link to profile |
| Photo | Thumbnail (click to view full) |
| Type | Profile/Album/Family |
| Reason | Why flagged |
| Actions | Approve / Remove photo / Warn user |

## 8b. Photos Without Faces (future)

Auto-detect photos without human faces using image analysis.

---

## 8c. Blocked Users List

| Column | Details |
|--------|---------|
| Blocker | Matri ID |
| Blocked | Matri ID |
| Date | When blocked |
| Actions | Unblock (admin override) |

## 8d. Reported Users

| Column | Details |
|--------|---------|
| Reported By | Matri ID |
| Reported User | Matri ID |
| Reason | Why reported |
| Date | When reported |
| Status | Pending / Reviewed / Action Taken |
| Actions | Warn / Suspend / Ban / Dismiss |

## 8e. Banned Users

Permanently banned users with reason and ban date.

---

## 8f. Contact Us / Support Inbox

Manage inquiries submitted via the Contact Us page on the frontend.

| Column | Filter | Details |
|--------|--------|---------|
| Name | Search | Submitter name |
| Email | Search | Submitter email |
| Subject | Search | Inquiry subject |
| Message | Preview | First 80 chars |
| User | Link | Matri ID (if logged-in user submitted) |
| Date | Date Range | When submitted |
| Status | Filter | New / In Progress / Replied / Closed |
| Assigned To | Filter | Staff member handling it |
| Actions | - | View, Reply, Assign, Close |

**Reply flow:**
- Admin clicks "Reply" → compose email reply (pre-filled with user's email)
- Reply sent via platform's SMTP + saved in conversation thread
- User sees reply in their email
- Status auto-changes to "Replied"

**Features:**
- Assign inquiry to staff member (Support role)
- Internal notes (visible to admin only, not sent to user)
- Canned responses / quick reply templates (e.g., "How to upload photos", "Payment issue")
- Auto-reply on submission: "We received your inquiry, we'll respond within 24 hours"
- Overdue indicator: highlight inquiries not replied within 48 hours
- Export inquiries to CSV

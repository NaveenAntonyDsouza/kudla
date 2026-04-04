# 6. Content Management

## 6a. Communities / Castes

CRUD for the community list shown in registration and home page.

| Field | Type |
|-------|------|
| Community Name | Text |
| Religion | Select |
| Sort Order | Number |
| Is Active | Toggle |

## 6b. Reference Data Management

Admin can edit all dropdown options WITHOUT touching code:

**Location (cascading):**
- Country list (with phone codes, flag)
- State list (grouped by country)
- District list (grouped by state)

**Religion (cascading):**
- Religion list
- Denomination list (grouped by religion — e.g., Christian → Latin Catholic, Syrian Catholic)
- Diocese list (grouped by denomination)
- Caste list (grouped by religion — e.g., Hindu → Brahmin, Nair)
- Sub-caste list (grouped by caste)
- Muslim Sect list (grouped under Muslim)
- Muslim Community/Jamath list (grouped under Muslim)
- Jain Sect list (grouped under Jain)
- Nakshatra (Star) list
- Rasi (Zodiac) list
- Gotra/Gothram list

**Education & Profession (grouped):**
- Education qualifications (grouped by level — e.g., Engineering → B.Tech, M.Tech)
- Occupation categories (grouped by sector — e.g., IT → Software Engineer, Data Analyst)
- Employment categories
- Income ranges

**Lifestyle & Preferences:**
- Mother tongue / Language list
- Hobbies list
- Music genres list
- Books genres list
- Movie genres list
- Sports / Fitness list
- Cuisine list
- Diet options
- Cultural background options

**System:**
- Height list
- Weight list
- Interest message templates (send + accept + decline)
- "How did you hear about us" options

## 6c. Static Pages

WYSIWYG editor for:
- Privacy Policy
- Terms of Service
- About Us
- Contact Us
- FAQ / Help
- Custom pages

## 6d. Email Templates

Edit email content from admin:
- Interest Received email
- Interest Accepted email
- Interest Declined email
- Password Reset email
- Welcome email (after registration)
- Email OTP template
- New Match notification email
- Profile View notification email

## 6e. SMS Templates (future)

- Phone OTP template
- Interest received SMS
- Interest accepted SMS

## 6f. Happy Stories (Success Stories)

Showcase couples who found their match through the platform:

| Field | Type | Description |
|-------|------|-------------|
| Groom Name | Text | e.g., "John" |
| Bride Name | Text | e.g., "Mary" |
| Groom Matri ID | Select | Link to profile (optional) |
| Bride Matri ID | Select | Link to profile (optional) |
| Couple Photo | File Upload | Wedding/engagement photo |
| Marriage Date | Date | When they got married |
| Story | Rich Text | Their story (WYSIWYG editor) |
| Location | Text | e.g., "Mangalore, Karnataka" |
| Sort Order | Number | Display order |
| Is Active | Toggle | Show/hide on frontend |

**Frontend display:**
- Homepage: "Happy Stories" section with 3-4 featured stories
- Dedicated `/happy-stories` page with all stories (paginated)
- Each story card: couple photo, names, date, excerpt → click for full story

## 6g. Testimonials

User reviews and feedback displayed on the website:

| Field | Type | Description |
|-------|------|-------------|
| User Name | Text | Display name |
| User Matri ID | Select | Link to profile (optional, for verified badge) |
| User Photo | File Upload | Or auto-pull from profile photo |
| Rating | Number | 1-5 stars |
| Testimonial Text | Textarea | What they said |
| Designation | Text | Optional (e.g., "Software Engineer, Bangalore") |
| Is Verified | Toggle | Verified user badge |
| Sort Order | Number | Display order |
| Is Active | Toggle | Show/hide |

**Frontend display:**
- Homepage: testimonial carousel (3-5 rotating)
- Dedicated `/testimonials` page (optional)
- Star rating display

## 6h. Notification Templates

Edit in-app notification messages:
- Interest received title/message
- Interest accepted title/message
- Interest declined title/message
- New message notification
- Profile view notification
- System/admin broadcast notification

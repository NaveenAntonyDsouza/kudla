# 16. Wedding Directory *(Low Priority — Phase 2)*

A vendor marketplace for wedding-related services. Separate module that adds value but is not core to matrimony.

## 16a. Vendor Categories

Admin manages categories of wedding vendors:

| Field | Type | Description |
|-------|------|-------------|
| Category Name | Text | e.g., "Wedding Photography", "Catering", "Venues" |
| Icon | Select/Upload | Category icon |
| Sort Order | Number | Display order |
| Is Active | Toggle | Show/hide |

**Suggested categories:**
Wedding Photography, Videography, Catering, Wedding Venues/Halls, Wedding Planners, Florists, Bridal Makeup, Mehendi Artists, DJs/Music, Wedding Cards/Invitations, Jewellers, Bridal Wear, Groom Wear, Cake & Bakery, Travel & Honeymoon

## 16b. Vendor Registration & Profile

Vendors register themselves (separate registration flow):

| Field | Type | Description |
|-------|------|-------------|
| Business Name | Text | e.g., "Royal Wedding Photography" |
| Category | Select | From 16a |
| Owner Name | Text | Contact person |
| Phone | Text | Business phone |
| Email | Email | Business email |
| Website | URL | Optional |
| Address | Textarea | Business address |
| City | Text | Service area |
| State | Select | State |
| Description | Rich Text | About the business |
| Logo | File Upload | Business logo |
| Portfolio Images | Multi-Upload | Up to 10 photos |
| Starting Price | Text | e.g., "₹25,000 onwards" |
| Is Verified | Toggle | Admin-verified badge |
| Is Active | Toggle | Show/hide listing |

## 16c. Vendor Dashboard

Each vendor gets their own dashboard after login:
- Profile views count
- Inquiry count
- Inquiry list with user details
- Edit profile/photos
- Activity log

## 16d. Browse & Search Vendors (Frontend)

- `/wedding-directory` — Browse all vendors by category
- `/wedding-directory?category=photography&city=mangalore` — Filter by category + city
- Each vendor card: logo, name, category, city, starting price, rating
- Vendor detail page: full profile, portfolio gallery, inquiry form
- "Similar Vendors" section on each vendor page

## 16e. Vendor Inquiry

Users can send inquiries to vendors:

| Field | Type | Description |
|-------|------|-------------|
| User Name | Auto | From logged-in user |
| Phone | Auto | From profile |
| Event Date | Date | Wedding/event date |
| Message | Textarea | Requirements |
| Budget | Text | Optional |

Vendor receives email + in-dashboard notification for each inquiry.

## 16f. Admin: Vendor Management

- Approve/reject vendor registrations
- View all vendor analytics
- Feature vendors (homepage showcase)
- Manage vendor categories

# 5. Site Settings (White-Label Configuration)

This is the **most important section for CodeCanyon customers**.

## 5a. General Settings

| Setting | Type | Description |
|---------|------|-------------|
| Site Name | Text | e.g., "Your Matrimony Site" |
| Tagline | Text | e.g., "Find Your Perfect Match" |
| Site Area | Text | e.g., "Karnataka's Christian Community" |
| Contact Email | Email | e.g., "info@your-domain.com" |
| Contact Phone | Text | e.g., "+91 484 408 0333" |
| WhatsApp Number | Text | For chat support |
| Address | Textarea | Office address |
| Copyright Text | Text | Footer copyright |

## 5b. Branding / Theme

| Setting | Type | Description |
|---------|------|-------------|
| Logo | File Upload | Header logo |
| Favicon | File Upload | Browser tab icon |
| Primary Color | Color Picker | e.g., #8B1D91 |
| Primary Hover | Color Picker | e.g., #6B1571 |
| Primary Light | Color Picker | e.g., #F3E8F7 |
| Secondary Color | Color Picker | e.g., #00BCD4 |
| Secondary Hover | Color Picker | e.g., #00ACC1 |
| Secondary Light | Color Picker | e.g., #E0F7FA |

**Live preview** — admin sees color changes in real-time before saving.

## 5c. Homepage Content

| Setting | Type | Description |
|---------|------|-------------|
| Hero Title | Text | "Find Your Perfect Match in ..." |
| Hero Subtitle | Text | Tagline text |
| Stats: Members | Number | Shown on homepage |
| Stats: Marriages | Number | Shown on homepage |
| Stats: Years | Number | Shown on homepage |
| "Why Choose Us" items | Repeater | Title + description + icon |
| CTA Title | Text | "Register Free Today" |
| CTA Description | Text | Below CTA title |

## 5d. Email Settings (SMTP)

| Setting | Type | Description |
|---------|------|-------------|
| Mail Driver | Select | smtp/sendmail/log |
| SMTP Host | Text | e.g., smtp.hostinger.com |
| SMTP Port | Number | e.g., 465 |
| SMTP Username | Text | e.g., info@... |
| SMTP Password | Password | Encrypted |
| Encryption | Select | ssl/tls/none |
| From Address | Email | Sender email |
| From Name | Text | Sender name |
| **Test Email** | Button | Send test email to admin |

## 5e. SMS / OTP Gateway

| Setting | Type | Description |
|---------|------|-------------|
| SMS Provider | Select | Fast2SMS / Twilio / MSG91 / TextLocal / Custom |
| API Key | Text | Encrypted |
| API Secret | Password | Encrypted (if required) |
| Sender ID | Text | e.g., "ANUGRA" (6 chars for India) |
| OTP Length | Select | 4 / 6 digits |
| OTP Expiry (minutes) | Number | e.g., 10 |
| OTP Template | Text | "Your {{APP_NAME}} OTP is {{OTP}}. Valid for {{MINUTES}} minutes." |
| Enable Phone OTP | Toggle | ON/OFF |
| Enable WhatsApp OTP | Toggle | ON/OFF (via WhatsApp Business API) |
| WhatsApp Provider | Select | Twilio / Gupshup / Meta Business API |
| WhatsApp API Key | Text | Encrypted |
| **Send Test OTP** | Button | Send test OTP to admin's phone |

## 5f. Payment Gateway

| Setting | Type | Description |
|---------|------|-------------|
| Gateway | Select | Razorpay / Stripe / PayPal |
| API Key | Text | Encrypted |
| API Secret | Password | Encrypted |
| Mode | Toggle | Test / Live |
| Currency | Select | INR / USD / EUR |
| **Test Payment** | Button | Process ₹1 test payment |

## 5g. Registration Settings

| Setting | Type | Description |
|---------|------|-------------|
| Phone OTP Required | Toggle | Require phone verification |
| Email OTP Required | Toggle | Require email verification |
| Auto-Approve Profiles | Toggle | Skip manual approval |
| Matri ID Prefix | Text | e.g., "AM" (generates AM100001) |
| Default Interest Limit (Free) | Number | e.g., 5/day |
| Min Age | Number | e.g., 18 |
| Max Photos (Album) | Number | e.g., 9 |
| Max Photos (Family) | Number | e.g., 3 |

## 5h. Social Links

Website-level social media links displayed in footer and contact page:

| Setting | Type | Description |
|---------|------|-------------|
| Facebook Page URL | URL | e.g., facebook.com/yourpage |
| Instagram URL | URL | e.g., instagram.com/yourhandle |
| Twitter / X URL | URL | Optional |
| YouTube Channel URL | URL | Optional |
| LinkedIn URL | URL | Optional |
| WhatsApp Chat Link | URL | Direct WhatsApp chat (wa.me/91XXXXXXXXXX) |

Displayed in: footer icons, contact page, about page.

## 5i. SEO Settings (Global + Per-Page)

**Global SEO:**

| Setting | Type | Description |
|---------|------|-------------|
| Default Meta Title | Text | Fallback title tag |
| Meta Title Suffix | Text | e.g., " | Your Matrimony Site" (appended to all pages) |
| Default Meta Description | Textarea | Fallback meta description |
| Google Analytics ID | Text | e.g., GA-XXXXXXX |
| Google Tag Manager ID | Text | e.g., GTM-XXXXXX |
| Facebook Pixel ID | Text | For ads tracking |
| OG Image | File Upload | Default social sharing image |
| Robots.txt Content | Textarea | Editable robots.txt |
| Sitemap Auto-Generate | Toggle | Auto-generate XML sitemap |

**Per-Page SEO:**

Admin can set custom SEO for each page:

| Page | Meta Title | Meta Description | OG Image | Canonical URL |
|------|-----------|-----------------|----------|---------------|
| Home | Editable | Editable | Editable | Auto |
| Search | Editable | Editable | Editable | Auto |
| Login | Editable | Editable | - | Auto |
| Register | Editable | Editable | - | Auto |
| Happy Stories | Editable | Editable | Editable | Auto |
| Privacy Policy | Editable | Editable | - | Auto |
| Terms | Editable | Editable | - | Auto |
| About Us | Editable | Editable | Editable | Auto |
| Contact | Editable | Editable | - | Auto |
| Membership Plans | Editable | Editable | Editable | Auto |
| Custom Pages | Editable | Editable | Editable | Auto |

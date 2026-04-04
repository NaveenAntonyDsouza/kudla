# 4. Membership & Payments

## 4a. Subscription Plans Management

Full CRUD for membership plans — admin can create, edit, delete plans.

| Field | Type | Description |
|-------|------|-------------|
| Plan Name | Text | e.g., "Diamond Plus" |
| Slug | Auto | e.g., "diamond-plus" |
| Duration (months) | Number | e.g., 15 |
| Original Price | Number | e.g., 15000 (strikethrough) |
| Sale Price | Number | e.g., 12000 (displayed) |
| Daily Interest Limit | Number | e.g., 50 |
| View Contacts Limit | Number | e.g., 500 |
| Daily Contact Views | Number | e.g., 20 |
| Personalized Messages | Toggle | ON/OFF |
| Featured Profile | Toggle | ON/OFF |
| Priority Support | Toggle | ON/OFF |
| Is Popular | Toggle | Shows "POPULAR" badge |
| Sort Order | Number | Display order |
| Is Active | Toggle | Show/hide plan |

## 4b. Payment History

| Column | Filter | Details |
|--------|--------|---------|
| Transaction ID | Search | Razorpay ID |
| User | Search | Matri ID + Name |
| Plan | Filter | Which plan |
| Amount | Range | Payment amount |
| Status | Filter | Paid/Pending/Failed/Refunded |
| Payment Date | Date Range | When paid |
| Expires | Date Range | Subscription expiry |
| Actions | - | View receipt, Refund, Extend |

## 4c. Manual Subscription

Admin can manually activate a subscription for a user:
- Select user
- Select plan
- Set start/end dates
- Add admin note (e.g., "Complimentary for beta tester")

## 4d. Discount Coupon Generation

Admin can create discount coupons for membership plans:

| Field | Type | Description |
|-------|------|-------------|
| Coupon Code | Text | e.g., "WELCOME50", "DIWALI2026" (auto-generate or manual) |
| Discount Type | Select | Percentage (%) / Fixed Amount (₹) |
| Discount Value | Number | e.g., 50 (for 50%) or 500 (for ₹500 off) |
| Applicable Plans | Multi-select | All plans / specific plans only |
| Min Purchase Amount | Number | e.g., ₹999 (optional) |
| Max Discount Cap | Number | e.g., ₹1000 max (for percentage coupons) |
| Usage Limit (total) | Number | e.g., 100 total uses |
| Usage Limit (per user) | Number | e.g., 1 per user |
| Valid From | Date | Start date |
| Valid Until | Date | Expiry date |
| Is Active | Toggle | Enable/disable |

**Coupon tracking:**
- Total times used
- Revenue impact (total discount given)
- Users who used the coupon
- Export coupon usage report

**Frontend integration:**
- "Have a coupon?" input on checkout/payment page
- Apply coupon → show discounted price with strikethrough original
- Coupon validation: expired, max usage reached, invalid code, plan not eligible

## 4e. Revenue Reports

- Daily/Weekly/Monthly/Yearly revenue
- Revenue by plan
- Revenue by payment method
- Export to CSV

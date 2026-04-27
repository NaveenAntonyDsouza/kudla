# Week 2 Acceptance Checkpoint

Before starting Week 3, verify every flow below end-to-end.

---

## Flow 1 — Fresh registration → verification → dashboard readiness

```bash
# 1. Register
TOKEN=$(curl -s -X POST http://localhost:8000/api/v1/auth/register/step-1 \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"full_name":"Wk2 Test","email":"wk2@example.com","phone":"9876599001","password":"password","password_confirmation":"password","gender":"Male","date_of_birth":"1995-04-12"}' \
  | jq -r '.data.token')

# 2-4. Steps 2-4 (abbreviated)
curl -X POST http://localhost:8000/api/v1/auth/register/step-2 -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" -H "Accept: application/json" -d '{"height":170,"complexion":"Wheatish","body_type":"Average","physical_status":"Normal","marital_status":"Never Married","family_status":"Middle Class","religion":"Hindu","caste":"Brahmin"}'
curl -X POST http://localhost:8000/api/v1/auth/register/step-3 -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" -H "Accept: application/json" -d '{"education_level":"Bachelors","occupation":"Software Professional"}'
curl -X POST http://localhost:8000/api/v1/auth/register/step-4 -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" -H "Accept: application/json" -d '{"native_country":"India","native_state":"Karnataka"}'
curl -X POST http://localhost:8000/api/v1/auth/register/step-5 -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" -H "Accept: application/json" -d '{"created_by":"Self"}'

# 5. Email OTP (purpose=register)
curl -X POST http://localhost:8000/api/v1/auth/otp/email/send -H "Content-Type: application/json" -H "Accept: application/json" -d '{"email":"wk2@example.com","purpose":"register"}'
# Check laravel.log for OTP, or use 123456 in local env

curl -X POST http://localhost:8000/api/v1/auth/otp/email/verify -H "Content-Type: application/json" -H "Accept: application/json" -d '{"email":"wk2@example.com","otp":"123456","purpose":"register"}'

# 6. Me
curl -H "Authorization: Bearer $TOKEN" http://localhost:8000/api/v1/auth/me | jq '.data.profile.onboarding_completed'
# Expect: true
```

- [ ] All 7 calls return `success: true`
- [ ] Final `me` shows `onboarding_completed: true`

---

## Flow 2 — Login variants

```bash
# Password login
curl -X POST http://localhost:8000/api/v1/auth/login/password -H "Content-Type: application/json" -H "Accept: application/json" -d '{"email":"wk2@example.com","password":"password","device_name":"Pixel"}' | jq '.data.token'

# Phone OTP login
curl -X POST http://localhost:8000/api/v1/auth/otp/phone/send -H "Content-Type: application/json" -H "Accept: application/json" -d '{"phone":"9876599001","purpose":"login"}'
curl -X POST http://localhost:8000/api/v1/auth/otp/phone/verify -H "Content-Type: application/json" -H "Accept: application/json" -d '{"phone":"9876599001","otp":"123456","purpose":"login","device_name":"Pixel"}' | jq '.data.token'

# Email OTP login (requires admin toggle email_otp_login_enabled=1)
php artisan tinker
>>> \App\Models\SiteSetting::set('email_otp_login_enabled', '1');
>>> exit
curl -X POST http://localhost:8000/api/v1/auth/otp/email/send -H "Content-Type: application/json" -H "Accept: application/json" -d '{"email":"wk2@example.com","purpose":"login"}'
curl -X POST http://localhost:8000/api/v1/auth/otp/email/verify -H "Content-Type: application/json" -H "Accept: application/json" -d '{"email":"wk2@example.com","otp":"123456","purpose":"login","device_name":"Pixel"}' | jq '.data.token'
```

- [ ] All 3 return tokens

---

## Flow 3 — Forgot + reset

```bash
curl -X POST http://localhost:8000/api/v1/auth/password/forgot -H "Content-Type: application/json" -H "Accept: application/json" -d '{"email":"wk2@example.com"}'

# Extract token from laravel.log (look for Password Reset line)
TOKEN_FROM_EMAIL="..."

curl -X POST http://localhost:8000/api/v1/auth/password/reset -H "Content-Type: application/json" -H "Accept: application/json" -d "{\"token\":\"$TOKEN_FROM_EMAIL\",\"email\":\"wk2@example.com\",\"password\":\"newpass123\",\"password_confirmation\":\"newpass123\"}"

# Can now log in with new password
curl -X POST http://localhost:8000/api/v1/auth/login/password -H "Content-Type: application/json" -H "Accept: application/json" -d '{"email":"wk2@example.com","password":"newpass123"}'
```

- [ ] Forgot returns success
- [ ] Reset returns `{reset: true}`
- [ ] Login with new password works

---

## Flow 4 — Device registration + logout

```bash
TOKEN="<from-login>"

curl -X POST http://localhost:8000/api/v1/devices -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" -H "Accept: application/json" -d '{"fcm_token":"accept_test_token","platform":"android","device_model":"Pixel 8"}' | jq

# /me works
curl -H "Authorization: Bearer $TOKEN" http://localhost:8000/api/v1/auth/me | jq '.data.user.email'

# Logout
curl -X POST -H "Authorization: Bearer $TOKEN" http://localhost:8000/api/v1/auth/logout

# /me fails
curl -s -o /dev/null -w "%{http_code}\n" -H "Authorization: Bearer $TOKEN" http://localhost:8000/api/v1/auth/me
# Expect: 401
```

- [ ] Device created
- [ ] Logout returns success
- [ ] Token invalidated after logout

---

## Pest full suite

```bash
./vendor/bin/pest
```

- [ ] All tests green
- [ ] No skipped tests that should be passing

## Scribe regenerate

```bash
php artisan scribe:generate
```

- [ ] Docs now show Authentication group with ~12 endpoints
- [ ] `/docs` page renders without errors

---

## Go/No-Go for Week 3

All ✅ → proceed to [phase-2a-api/week-03-profiles-photos-search/README.md](../week-03-profiles-photos-search/README.md)

Any 🛑 → fix before moving on.

**Week 2 complete ✅**

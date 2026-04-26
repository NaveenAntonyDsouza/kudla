<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="IE=edge,chrome=1" http-equiv="X-UA-Compatible">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>MatrimonyTheme API v1</title>

    <link href="https://fonts.googleapis.com/css?family=Open+Sans&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset("/vendor/scribe/css/theme-default.style.css") }}" media="screen">
    <link rel="stylesheet" href="{{ asset("/vendor/scribe/css/theme-default.print.css") }}" media="print">

    <script src="https://cdn.jsdelivr.net/npm/lodash@4.17.10/lodash.min.js"></script>

    <link rel="stylesheet"
          href="https://unpkg.com/@highlightjs/cdn-assets@11.6.0/styles/obsidian.min.css">
    <script src="https://unpkg.com/@highlightjs/cdn-assets@11.6.0/highlight.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jets/0.14.1/jets.min.js"></script>

    <style id="language-style">
        /* starts out as display none and is replaced with js later  */
                    body .content .bash-example code { display: none; }
                    body .content .javascript-example code { display: none; }
            </style>

    <script>
        var tryItOutBaseUrl = "http://localhost:8000";
        var useCsrf = Boolean();
        var csrfUrl = "/sanctum/csrf-cookie";
    </script>
    <script src="{{ asset("/vendor/scribe/js/tryitout-5.9.0.js") }}"></script>

    <script src="{{ asset("/vendor/scribe/js/theme-default-5.9.0.js") }}"></script>

</head>

<body data-languages="[&quot;bash&quot;,&quot;javascript&quot;]">

<a href="#" id="nav-button">
    <span>
        MENU
        <img src="{{ asset("/vendor/scribe/images/navbar.png") }}" alt="navbar-image"/>
    </span>
</a>
<div class="tocify-wrapper">
    
            <div class="lang-selector">
                                            <button type="button" class="lang-button" data-language-name="bash">bash</button>
                                            <button type="button" class="lang-button" data-language-name="javascript">javascript</button>
                    </div>
    
    <div class="search">
        <input type="text" class="search" id="input-search" placeholder="Search">
    </div>

    <div id="toc">
                    <ul id="tocify-header-introduction" class="tocify-header">
                <li class="tocify-item level-1" data-unique="introduction">
                    <a href="#introduction">Introduction</a>
                </li>
                            </ul>
                    <ul id="tocify-header-authenticating-requests" class="tocify-header">
                <li class="tocify-item level-1" data-unique="authenticating-requests">
                    <a href="#authenticating-requests">Authenticating requests</a>
                </li>
                            </ul>
                    <ul id="tocify-header-authentication" class="tocify-header">
                <li class="tocify-item level-1" data-unique="authentication">
                    <a href="#authentication">Authentication</a>
                </li>
                                    <ul id="tocify-subheader-authentication" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="authentication-POSTapi-v1-auth-register-step-1">
                                <a href="#authentication-POSTapi-v1-auth-register-step-1">Register a new account (step 1 of 5).</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="authentication-POSTapi-v1-auth-otp-phone-send">
                                <a href="#authentication-POSTapi-v1-auth-otp-phone-send">Dispatch a phone OTP.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="authentication-POSTapi-v1-auth-otp-phone-verify">
                                <a href="#authentication-POSTapi-v1-auth-otp-phone-verify">Verify a phone OTP. Behavior branches on purpose:
  register  -> set user.phone_verified_at, return next onboarding step
  login     -> issue Sanctum token + user/profile/membership snapshot
  reset     -> placeholder ok response; actual reset handled by
               /auth/password/reset (step-13) using Laravel broker</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="authentication-POSTapi-v1-auth-otp-email-send">
                                <a href="#authentication-POSTapi-v1-auth-otp-email-send">Dispatch an email OTP. Mirror of sendPhoneOtp — same 3 purposes,
but feature-flag for login purpose defaults to DISABLED (admin must
explicitly enable email OTP login via site_settings).</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="authentication-POSTapi-v1-auth-otp-email-verify">
                                <a href="#authentication-POSTapi-v1-auth-otp-email-verify">Verify an email OTP. Dispatches to the same handleX handlers used
by phone OTP (single source of truth for the 3 purpose branches).</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="authentication-POSTapi-v1-auth-login-password">
                                <a href="#authentication-POSTapi-v1-auth-login-password">Log in with email + password. Primary login flow for existing users.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="authentication-POSTapi-v1-auth-password-forgot">
                                <a href="#authentication-POSTapi-v1-auth-password-forgot">Send a password reset email via Laravel's Password broker.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="authentication-POSTapi-v1-auth-password-reset">
                                <a href="#authentication-POSTapi-v1-auth-password-reset">Complete the password reset using the token from the reset email.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="authentication-POSTapi-v1-auth-register-step-2">
                                <a href="#authentication-POSTapi-v1-auth-register-step-2">Register step 2: primary + religious + family info.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="authentication-POSTapi-v1-auth-register-step-3">
                                <a href="#authentication-POSTapi-v1-auth-register-step-3">Register step 3: education + professional.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="authentication-POSTapi-v1-auth-register-step-4">
                                <a href="#authentication-POSTapi-v1-auth-register-step-4">Register step 4: location + contact.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="authentication-POSTapi-v1-auth-register-step-5">
                                <a href="#authentication-POSTapi-v1-auth-register-step-5">Register step 5: profile-creator info + finalize. Returns the next
screen: 'verify.email', 'verify.phone', or 'complete'.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="authentication-POSTapi-v1-auth-logout">
                                <a href="#authentication-POSTapi-v1-auth-logout">Revoke the token the current request authenticated with. Only this
device's token is revoked — other devices stay signed in.</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-block" class="tocify-header">
                <li class="tocify-item level-1" data-unique="block">
                    <a href="#block">Block</a>
                </li>
                                    <ul id="tocify-subheader-block" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="block-GETapi-v1-blocked">
                                <a href="#block-GETapi-v1-blocked">GET api/v1/blocked</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="block-POSTapi-v1-profiles--matriId--block">
                                <a href="#block-POSTapi-v1-profiles--matriId--block">Block a target profile. Idempotent — POSTing twice is a no-op
after the first call. Side-effects (cancel pending interests,
remove the viewer's shortlist of target) run in a transaction so
a partial state isn't visible to other requests.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="block-POSTapi-v1-profiles--matriId--unblock">
                                <a href="#block-POSTapi-v1-profiles--matriId--unblock">Unblock a target profile. Idempotent — no-op when no block exists.</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-configuration" class="tocify-header">
                <li class="tocify-item level-1" data-unique="configuration">
                    <a href="#configuration">Configuration</a>
                </li>
                                    <ul id="tocify-subheader-configuration" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="configuration-GETapi-v1-site-settings">
                                <a href="#configuration-GETapi-v1-site-settings">Get site configuration</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="configuration-GETapi-v1-reference">
                                <a href="#configuration-GETapi-v1-reference">List all available reference slugs</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="configuration-GETapi-v1-reference--list-">
                                <a href="#configuration-GETapi-v1-reference--list-">Get a reference list</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-contact" class="tocify-header">
                <li class="tocify-item level-1" data-unique="contact">
                    <a href="#contact">Contact</a>
                </li>
                                    <ul id="tocify-subheader-contact" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="contact-POSTapi-v1-contact">
                                <a href="#contact-POSTapi-v1-contact">Submit a contact-form message.</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-devices" class="tocify-header">
                <li class="tocify-item level-1" data-unique="devices">
                    <a href="#devices">Devices</a>
                </li>
                                    <ul id="tocify-subheader-devices" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="devices-POSTapi-v1-devices">
                                <a href="#devices-POSTapi-v1-devices">Register or refresh an FCM device token for the authenticated user.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="devices-DELETEapi-v1-devices--device_id-">
                                <a href="#devices-DELETEapi-v1-devices--device_id-">Revoke a device (user taps "sign out this device" in settings).</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-discover" class="tocify-header">
                <li class="tocify-item level-1" data-unique="discover">
                    <a href="#discover">Discover</a>
                </li>
                                    <ul id="tocify-subheader-discover" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="discover-GETapi-v1-discover">
                                <a href="#discover-GETapi-v1-discover">GET api/v1/discover</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="discover-GETapi-v1-discover--category-">
                                <a href="#discover-GETapi-v1-discover--category-">Polymorphic response:
  - For direct_filter categories → paginated cards + meta
  - For subcategory-based categories → subcategory list</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="discover-GETapi-v1-discover--category---slug-">
                                <a href="#discover-GETapi-v1-discover--category---slug-">Paginated results for a specific subcategory slug.</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-id-proof" class="tocify-header">
                <li class="tocify-item level-1" data-unique="id-proof">
                    <a href="#id-proof">ID Proof</a>
                </li>
                                    <ul id="tocify-subheader-id-proof" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="id-proof-GETapi-v1-id-proof">
                                <a href="#id-proof-GETapi-v1-id-proof">GET api/v1/id-proof</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="id-proof-POSTapi-v1-id-proof">
                                <a href="#id-proof-POSTapi-v1-id-proof">Upload (or replace) the viewer's ID proof. Multipart body.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="id-proof-DELETEapi-v1-id-proof--idProof_id-">
                                <a href="#id-proof-DELETEapi-v1-id-proof--idProof_id-">Withdraw a pending or rejected submission. Approved IDs cannot be
deleted by the user — they need admin intervention so the verified
badge isn't gameable.</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-ignored" class="tocify-header">
                <li class="tocify-item level-1" data-unique="ignored">
                    <a href="#ignored">Ignored</a>
                </li>
                                    <ul id="tocify-subheader-ignored" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="ignored-GETapi-v1-ignored">
                                <a href="#ignored-GETapi-v1-ignored">GET api/v1/ignored</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="ignored-POSTapi-v1-profiles--matriId--ignore-toggle">
                                <a href="#ignored-POSTapi-v1-profiles--matriId--ignore-toggle">Toggle ignore for a target profile. Each call flips the state;
response carries the authoritative new state.</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-interests" class="tocify-header">
                <li class="tocify-item level-1" data-unique="interests">
                    <a href="#interests">Interests</a>
                </li>
                                    <ul id="tocify-subheader-interests" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="interests-GETapi-v1-interests">
                                <a href="#interests-GETapi-v1-interests">GET api/v1/interests</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="interests-GETapi-v1-interests--interest_id-">
                                <a href="#interests-GETapi-v1-interests--interest_id-">GET api/v1/interests/{interest_id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="interests-POSTapi-v1-profiles--matriId--interest">
                                <a href="#interests-POSTapi-v1-profiles--matriId--interest">POST api/v1/profiles/{matriId}/interest</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="interests-POSTapi-v1-interests--interest_id--accept">
                                <a href="#interests-POSTapi-v1-interests--interest_id--accept">POST api/v1/interests/{interest_id}/accept</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="interests-POSTapi-v1-interests--interest_id--decline">
                                <a href="#interests-POSTapi-v1-interests--interest_id--decline">POST api/v1/interests/{interest_id}/decline</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="interests-POSTapi-v1-interests--interest_id--cancel">
                                <a href="#interests-POSTapi-v1-interests--interest_id--cancel">Sender cancels a pending interest within the cancel window.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="interests-POSTapi-v1-interests--interest_id--star">
                                <a href="#interests-POSTapi-v1-interests--interest_id--star">Toggle the viewer-side star flag (is_starred_by_sender for the
sender, is_starred_by_receiver for the receiver). Used for
favourite-marking interests in the inbox.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="interests-POSTapi-v1-interests--interest_id--trash">
                                <a href="#interests-POSTapi-v1-interests--interest_id--trash">Toggle the viewer-side trash flag. Trashed interests are hidden
from the default "all" inbox tab but still visible in "trash".</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="interests-POSTapi-v1-interests--interest_id--messages">
                                <a href="#interests-POSTapi-v1-interests--interest_id--messages">Send a chat reply in an accepted interest thread. Premium-gated
via InterestService::sendMessage — but if the OTHER party holds
a plan with allows_free_member_chat=true, free senders may also
reply (Bharat-Platinum convention; see Commit A).</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="interests-GETapi-v1-interests--interest_id--messages">
                                <a href="#interests-GETapi-v1-interests--interest_id--messages">List replies in an interest thread (chat polling). Flutter calls
this on a timer (~10s) while the chat screen is open.</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-matches" class="tocify-header">
                <li class="tocify-item level-1" data-unique="matches">
                    <a href="#matches">Matches</a>
                </li>
                                    <ul id="tocify-subheader-matches" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="matches-GETapi-v1-matches-my">
                                <a href="#matches-GETapi-v1-matches-my">GET api/v1/matches/my</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="matches-GETapi-v1-matches-mutual">
                                <a href="#matches-GETapi-v1-matches-mutual">GET api/v1/matches/mutual</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="matches-GETapi-v1-matches-score--matriId-">
                                <a href="#matches-GETapi-v1-matches-score--matriId-">GET api/v1/matches/score/{matriId}</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-membership" class="tocify-header">
                <li class="tocify-item level-1" data-unique="membership">
                    <a href="#membership">Membership</a>
                </li>
                                    <ul id="tocify-subheader-membership" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="membership-GETapi-v1-membership-plans">
                                <a href="#membership-GETapi-v1-membership-plans">GET api/v1/membership/plans</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="membership-GETapi-v1-membership-me">
                                <a href="#membership-GETapi-v1-membership-me">GET api/v1/membership/me</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="membership-POSTapi-v1-membership-coupon-validate">
                                <a href="#membership-POSTapi-v1-membership-coupon-validate">POST api/v1/membership/coupon/validate</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-notifications" class="tocify-header">
                <li class="tocify-item level-1" data-unique="notifications">
                    <a href="#notifications">Notifications</a>
                </li>
                                    <ul id="tocify-subheader-notifications" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="notifications-GETapi-v1-notifications">
                                <a href="#notifications-GETapi-v1-notifications">Paginated list of the viewer's notifications, latest first.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="notifications-GETapi-v1-notifications-unread-count">
                                <a href="#notifications-GETapi-v1-notifications-unread-count">Quick unread-count for the badge. Cheap query — backed by the
`(user_id, is_read)` index on the notifications table.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="notifications-POSTapi-v1-notifications-read-all">
                                <a href="#notifications-POSTapi-v1-notifications-read-all">Mark every unread notification for the viewer as read. Returns
the count that flipped — 0 when the inbox is already empty,
useful for clients to skip a needless badge refresh.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="notifications-POSTapi-v1-notifications--notification_id--read">
                                <a href="#notifications-POSTapi-v1-notifications--notification_id--read">Mark a single notification read. Idempotent — no-ops on an
already-read row, still returns 200. Returns 403 (not 404) when
the notification belongs to a different user, to avoid leaking
which ids exist.</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-onboarding" class="tocify-header">
                <li class="tocify-item level-1" data-unique="onboarding">
                    <a href="#onboarding">Onboarding</a>
                </li>
                                    <ul id="tocify-subheader-onboarding" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="onboarding-POSTapi-v1-onboarding-step-1">
                                <a href="#onboarding-POSTapi-v1-onboarding-step-1">POST api/v1/onboarding/step-1</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="onboarding-POSTapi-v1-onboarding-step-2">
                                <a href="#onboarding-POSTapi-v1-onboarding-step-2">POST api/v1/onboarding/step-2</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="onboarding-POSTapi-v1-onboarding-partner-preferences">
                                <a href="#onboarding-POSTapi-v1-onboarding-partner-preferences">POST api/v1/onboarding/partner-preferences</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="onboarding-POSTapi-v1-onboarding-lifestyle">
                                <a href="#onboarding-POSTapi-v1-onboarding-lifestyle">Final onboarding step — also flips onboarding_completed=true.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="onboarding-POSTapi-v1-onboarding-finish">
                                <a href="#onboarding-POSTapi-v1-onboarding-finish">Skip-to-dashboard sentinel — Flutter calls this from "Do this
later" buttons. Flips onboarding_completed=true so the dashboard
banner stops showing; doesn't touch any field data.</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-payment" class="tocify-header">
                <li class="tocify-item level-1" data-unique="payment">
                    <a href="#payment">Payment</a>
                </li>
                                    <ul id="tocify-subheader-payment" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="payment-POSTapi-v1-webhooks--gateway-">
                                <a href="#payment-POSTapi-v1-webhooks--gateway-">Inbound webhook endpoint for any registered payment gateway.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="payment-POSTapi-v1-payment--gateway--order">
                                <a href="#payment-POSTapi-v1-payment--gateway--order">Create a gateway order, persist a pending Subscription, return
the gateway-specific payload Flutter needs to invoke the gateway
client SDK.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="payment-POSTapi-v1-payment--gateway--verify">
                                <a href="#payment-POSTapi-v1-payment--gateway--verify">Verify a gateway callback after Flutter completes the in-app
payment flow. On valid signature, marks the subscription paid,
creates / extends UserMembership, records coupon usage, and
deactivates prior memberships.</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-photo-requests" class="tocify-header">
                <li class="tocify-item level-1" data-unique="photo-requests">
                    <a href="#photo-requests">Photo Requests</a>
                </li>
                                    <ul id="tocify-subheader-photo-requests" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="photo-requests-GETapi-v1-photo-requests">
                                <a href="#photo-requests-GETapi-v1-photo-requests">GET api/v1/photo-requests</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="photo-requests-POSTapi-v1-profiles--matriId--photo-request">
                                <a href="#photo-requests-POSTapi-v1-profiles--matriId--photo-request">POST api/v1/profiles/{matriId}/photo-request</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="photo-requests-POSTapi-v1-photo-requests--photoRequest_id--approve">
                                <a href="#photo-requests-POSTapi-v1-photo-requests--photoRequest_id--approve">POST api/v1/photo-requests/{photoRequest_id}/approve</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="photo-requests-POSTapi-v1-photo-requests--photoRequest_id--ignore">
                                <a href="#photo-requests-POSTapi-v1-photo-requests--photoRequest_id--ignore">POST api/v1/photo-requests/{photoRequest_id}/ignore</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-photos" class="tocify-header">
                <li class="tocify-item level-1" data-unique="photos">
                    <a href="#photos">Photos</a>
                </li>
                                    <ul id="tocify-subheader-photos" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="photos-GETapi-v1-photos">
                                <a href="#photos-GETapi-v1-photos">GET api/v1/photos</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="photos-POSTapi-v1-photos">
                                <a href="#photos-POSTapi-v1-photos">POST api/v1/photos</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="photos-POSTapi-v1-photos-privacy">
                                <a href="#photos-POSTapi-v1-photos-privacy">Update the authenticated user's photo-privacy settings. PATCH-like
semantics — only the fields present in the payload are changed.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="photos-POSTapi-v1-photos--photo_id--primary">
                                <a href="#photos-POSTapi-v1-photos--photo_id--primary">POST api/v1/photos/{photo_id}/primary</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="photos-POSTapi-v1-photos--photo_id--restore">
                                <a href="#photos-POSTapi-v1-photos--photo_id--restore">POST api/v1/photos/{photo_id}/restore</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="photos-DELETEapi-v1-photos--photo_id--permanent">
                                <a href="#photos-DELETEapi-v1-photos--photo_id--permanent">DELETE api/v1/photos/{photo_id}/permanent</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="photos-DELETEapi-v1-photos--photo_id-">
                                <a href="#photos-DELETEapi-v1-photos--photo_id-">DELETE api/v1/photos/{photo_id}</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-profile" class="tocify-header">
                <li class="tocify-item level-1" data-unique="profile">
                    <a href="#profile">Profile</a>
                </li>
                                    <ul id="tocify-subheader-profile" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="profile-GETapi-v1-dashboard">
                                <a href="#profile-GETapi-v1-dashboard">Show the dashboard payload.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="profile-GETapi-v1-profile-me">
                                <a href="#profile-GETapi-v1-profile-me">Return the authenticated user's own profile with all 9 sections,
contact populated, photos grouped by type.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="profile-GETapi-v1-profiles--matriId-">
                                <a href="#profile-GETapi-v1-profiles--matriId-">View another user's profile. Applies all 7 ProfileAccessService
gates, tracks a deduped ProfileView, returns viewer-context fields
(match score, interest status, shortlist state, etc.).</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="profile-PUTapi-v1-profile-me--section-">
                                <a href="#profile-PUTapi-v1-profile-me--section-">Persist the authenticated user's edits to a single profile section.</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-report" class="tocify-header">
                <li class="tocify-item level-1" data-unique="report">
                    <a href="#report">Report</a>
                </li>
                                    <ul id="tocify-subheader-report" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="report-POSTapi-v1-profiles--matriId--report">
                                <a href="#report-POSTapi-v1-profiles--matriId--report">Submit a profile report.</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-saved-searches" class="tocify-header">
                <li class="tocify-item level-1" data-unique="saved-searches">
                    <a href="#saved-searches">Saved Searches</a>
                </li>
                                    <ul id="tocify-subheader-saved-searches" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="saved-searches-GETapi-v1-search-saved">
                                <a href="#saved-searches-GETapi-v1-search-saved">List the authenticated user's saved searches, most recent first.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="saved-searches-POSTapi-v1-search-saved">
                                <a href="#saved-searches-POSTapi-v1-search-saved">Save the current filter set. Enforces a per-profile quota of
MAX_SAVED_SEARCHES (10) — older rows must be deleted before
additional ones can be created.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="saved-searches-DELETEapi-v1-search-saved--savedSearch_id-">
                                <a href="#saved-searches-DELETEapi-v1-search-saved--savedSearch_id-">Delete a saved search. Only the owner can delete — 403 for
everyone else. Route-model binding + 404 handling comes from
Laravel's default ModelNotFoundException → ApiExceptionHandler
mapping (already in place).</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-search" class="tocify-header">
                <li class="tocify-item level-1" data-unique="search">
                    <a href="#search">Search</a>
                </li>
                                    <ul id="tocify-subheader-search" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="search-GETapi-v1-search-partner">
                                <a href="#search-GETapi-v1-search-partner">Partner search.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="search-GETapi-v1-search-keyword">
                                <a href="#search-GETapi-v1-search-keyword">Free-text search across 7 profile columns using LIKE wildcards.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="search-GETapi-v1-search-id--matriId-">
                                <a href="#search-GETapi-v1-search-id--matriId-">Direct lookup by matri_id. Returns a ProfileCardResource payload
for quick profile access (Flutter's "search by ID" tab).</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-settings" class="tocify-header">
                <li class="tocify-item level-1" data-unique="settings">
                    <a href="#settings">Settings</a>
                </li>
                                    <ul id="tocify-subheader-settings" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="settings-GETapi-v1-settings">
                                <a href="#settings-GETapi-v1-settings">Full settings dump — visibility, alerts, auth flags, account
status. Flutter renders the settings screen entirely from this
single payload.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="settings-PUTapi-v1-settings-visibility">
                                <a href="#settings-PUTapi-v1-settings-visibility">Update profile-visibility toggles. PATCH-style — send only the
keys you want to change.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="settings-PUTapi-v1-settings-alerts">
                                <a href="#settings-PUTapi-v1-settings-alerts">Update notification preferences. PATCH-style — merges with
existing prefs so unchanged keys retain their values.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="settings-PUTapi-v1-settings-password">
                                <a href="#settings-PUTapi-v1-settings-password">Change password + revoke every OTHER active session/token.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="settings-POSTapi-v1-settings-hide">
                                <a href="#settings-POSTapi-v1-settings-hide">Hide profile from search + recommendations.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="settings-POSTapi-v1-settings-unhide">
                                <a href="#settings-POSTapi-v1-settings-unhide">Unhide profile (reverse of hide).</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="settings-POSTapi-v1-settings-delete">
                                <a href="#settings-POSTapi-v1-settings-delete">Soft-delete the account. Sets is_active=false + is_hidden=true,
stores the deletion reason (with optional free-form feedback
folded in for "other"), then SoftDeletes the profile (auto-sets
deleted_at via the Profile model trait), and revokes every
Sanctum token so the current session is dropped too.</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-shortlist" class="tocify-header">
                <li class="tocify-item level-1" data-unique="shortlist">
                    <a href="#shortlist">Shortlist</a>
                </li>
                                    <ul id="tocify-subheader-shortlist" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="shortlist-GETapi-v1-shortlist">
                                <a href="#shortlist-GETapi-v1-shortlist">Paginated list of profiles the viewer has shortlisted, latest first.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="shortlist-POSTapi-v1-profiles--matriId--shortlist">
                                <a href="#shortlist-POSTapi-v1-profiles--matriId--shortlist">Toggle shortlist for a target profile. Idempotent against state —
each call flips, response carries the authoritative new state.</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-static-pages" class="tocify-header">
                <li class="tocify-item level-1" data-unique="static-pages">
                    <a href="#static-pages">Static Pages</a>
                </li>
                                    <ul id="tocify-subheader-static-pages" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="static-pages-GETapi-v1-static-pages--slug-">
                                <a href="#static-pages-GETapi-v1-static-pages--slug-">GET api/v1/static-pages/{slug}</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-success-stories" class="tocify-header">
                <li class="tocify-item level-1" data-unique="success-stories">
                    <a href="#success-stories">Success Stories</a>
                </li>
                                    <ul id="tocify-subheader-success-stories" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="success-stories-GETapi-v1-success-stories">
                                <a href="#success-stories-GETapi-v1-success-stories">Public feed of approved success stories, latest weddings first.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="success-stories-POSTapi-v1-success-stories">
                                <a href="#success-stories-POSTapi-v1-success-stories">Submit a success story. Lands as `is_visible=false` — admin
approval gates publication.</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-views" class="tocify-header">
                <li class="tocify-item level-1" data-unique="views">
                    <a href="#views">Views</a>
                </li>
                                    <ul id="tocify-subheader-views" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="views-GETapi-v1-views">
                                <a href="#views-GETapi-v1-views">GET api/v1/views</a>
                            </li>
                                                                        </ul>
                            </ul>
            </div>

    <ul class="toc-footer" id="toc-footer">
                    <li style="padding-bottom: 5px;"><a href="{{ route("scribe.postman") }}">View Postman collection</a></li>
                            <li style="padding-bottom: 5px;"><a href="{{ route("scribe.openapi") }}">View OpenAPI spec</a></li>
                <li><a href="http://github.com/knuckleswtf/scribe">Documentation powered by Scribe ✍</a></li>
    </ul>

    <ul class="toc-footer" id="last-updated">
        <li>Last updated: April 26, 2026</li>
    </ul>
</div>

<div class="page-wrapper">
    <div class="dark-box"></div>
    <div class="content">
        <h1 id="introduction">Introduction</h1>
<p>REST API for the MatrimonyTheme Flutter mobile app. Base URL: /api/v1</p>
<aside>
    <strong>Base URL</strong>: <code>http://localhost:8000</code>
</aside>
<pre><code>This documentation aims to provide all the information you need to work with our API.

&lt;aside&gt;As you scroll, you'll see code examples for working with the API in different programming languages in the dark area to the right (or as part of the content on mobile).
You can switch the language used with the tabs at the top right (or from the nav menu at the top left on mobile).&lt;/aside&gt;</code></pre>

        <h1 id="authenticating-requests">Authenticating requests</h1>
<p>This API is not authenticated.</p>

        <h1 id="authentication">Authentication</h1>

    

                                <h2 id="authentication-POSTapi-v1-auth-register-step-1">Register a new account (step 1 of 5).</h2>

<p>
</p>

<p>Creates User + Profile from the validated payload, returns a Sanctum
personal access token the client uses to authenticate the next 4 steps.</p>

<span id="example-requests-POSTapi-v1-auth-register-step-1">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/auth/register/step-1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"full_name\": \"b\",
    \"gender\": \"male\",
    \"date_of_birth\": \"1998-05-21\",
    \"phone\": \"8225697751\",
    \"email\": \"ashly64@example.com\",
    \"password\": \"pBNvYg\",
    \"ref\": \"hwaykcmyuwpwlvqw\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/auth/register/step-1"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "full_name": "b",
    "gender": "male",
    "date_of_birth": "1998-05-21",
    "phone": "8225697751",
    "email": "ashly64@example.com",
    "password": "pBNvYg",
    "ref": "hwaykcmyuwpwlvqw"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-auth-register-step-1">
            <blockquote>
            <p>Example response (201, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;user&quot;: {
            &quot;id&quot;: 42,
            &quot;name&quot;: &quot;Naveen&quot;,
            &quot;email&quot;: &quot;naveen@example.com&quot;,
            &quot;phone&quot;: &quot;9876543210&quot;
        },
        &quot;profile&quot;: {
            &quot;matri_id&quot;: &quot;AM100042&quot;,
            &quot;onboarding_step_completed&quot;: 1,
            &quot;onboarding_completed&quot;: false,
            &quot;is_approved&quot;: true
        },
        &quot;token&quot;: &quot;5|WGNdkrOzGwpNR...&quot;,
        &quot;next_step&quot;: &quot;register.step-2&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, duplicate email):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;VALIDATION_FAILED&quot;,
        &quot;message&quot;: &quot;Please check the fields below.&quot;,
        &quot;fields&quot;: {
            &quot;email&quot;: [
                &quot;This email is already registered. Try logging in instead.&quot;
            ]
        }
    }
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-auth-register-step-1" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-auth-register-step-1"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-auth-register-step-1"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-auth-register-step-1" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-auth-register-step-1">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-auth-register-step-1" data-method="POST"
      data-path="api/v1/auth/register/step-1"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-auth-register-step-1', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-auth-register-step-1"
                    onclick="tryItOut('POSTapi-v1-auth-register-step-1');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-auth-register-step-1"
                    onclick="cancelTryOut('POSTapi-v1-auth-register-step-1');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-auth-register-step-1"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/auth/register/step-1</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-auth-register-step-1"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-auth-register-step-1"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>full_name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="full_name"                data-endpoint="POSTapi-v1-auth-register-step-1"
               value="b"
               data-component="body">
    <br>
<p>Must be at least 2 characters. Must not be greater than 100 characters. Example: <code>b</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>gender</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="gender"                data-endpoint="POSTapi-v1-auth-register-step-1"
               value="male"
               data-component="body">
    <br>
<p>Example: <code>male</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>male</code></li> <li><code>female</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>date_of_birth</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="date_of_birth"                data-endpoint="POSTapi-v1-auth-register-step-1"
               value="1998-05-21"
               data-component="body">
    <br>
<p>Must be a valid date. Must be a date before <code>-18 years</code>. Example: <code>1998-05-21</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>phone</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="phone"                data-endpoint="POSTapi-v1-auth-register-step-1"
               value="8225697751"
               data-component="body">
    <br>
<p>Must be 10 digits. Example: <code>8225697751</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="email"                data-endpoint="POSTapi-v1-auth-register-step-1"
               value="ashly64@example.com"
               data-component="body">
    <br>
<p>Must be a valid email address. Example: <code>ashly64@example.com</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>password</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="password"                data-endpoint="POSTapi-v1-auth-register-step-1"
               value="pBNvYg"
               data-component="body">
    <br>
<p>Must be at least 6 characters. Must not be greater than 14 characters. Example: <code>pBNvYg</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>ref</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="ref"                data-endpoint="POSTapi-v1-auth-register-step-1"
               value="hwaykcmyuwpwlvqw"
               data-component="body">
    <br>
<p>Must not be greater than 20 characters. Example: <code>hwaykcmyuwpwlvqw</code></p>
        </div>
        </form>

                    <h2 id="authentication-POSTapi-v1-auth-otp-phone-send">Dispatch a phone OTP.</h2>

<p>
</p>

<p>For purpose=login|reset, we silently short-circuit if the phone is
not in our DB — we respond success either way so attackers can't
enumerate registered numbers.</p>

<span id="example-requests-POSTapi-v1-auth-otp-phone-send">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/auth/otp/phone/send" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"phone\": \"8225697751\",
    \"purpose\": \"login\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/auth/otp/phone/send"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "phone": "8225697751",
    "purpose": "login"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-auth-otp-phone-send">
</span>
<span id="execution-results-POSTapi-v1-auth-otp-phone-send" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-auth-otp-phone-send"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-auth-otp-phone-send"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-auth-otp-phone-send" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-auth-otp-phone-send">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-auth-otp-phone-send" data-method="POST"
      data-path="api/v1/auth/otp/phone/send"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-auth-otp-phone-send', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-auth-otp-phone-send"
                    onclick="tryItOut('POSTapi-v1-auth-otp-phone-send');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-auth-otp-phone-send"
                    onclick="cancelTryOut('POSTapi-v1-auth-otp-phone-send');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-auth-otp-phone-send"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/auth/otp/phone/send</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-auth-otp-phone-send"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-auth-otp-phone-send"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>phone</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="phone"                data-endpoint="POSTapi-v1-auth-otp-phone-send"
               value="8225697751"
               data-component="body">
    <br>
<p>Must be 10 digits. Example: <code>8225697751</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>purpose</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="purpose"                data-endpoint="POSTapi-v1-auth-otp-phone-send"
               value="login"
               data-component="body">
    <br>
<p>Example: <code>login</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>register</code></li> <li><code>login</code></li> <li><code>reset</code></li></ul>
        </div>
        </form>

                    <h2 id="authentication-POSTapi-v1-auth-otp-phone-verify">Verify a phone OTP. Behavior branches on purpose:
  register  -&gt; set user.phone_verified_at, return next onboarding step
  login     -&gt; issue Sanctum token + user/profile/membership snapshot
  reset     -&gt; placeholder ok response; actual reset handled by
               /auth/password/reset (step-13) using Laravel broker</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-auth-otp-phone-verify">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/auth/otp/phone/verify" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"phone\": \"8225697751\",
    \"otp\": \"569775\",
    \"purpose\": \"register\",
    \"device_name\": \"n\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/auth/otp/phone/verify"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "phone": "8225697751",
    "otp": "569775",
    "purpose": "register",
    "device_name": "n"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-auth-otp-phone-verify">
</span>
<span id="execution-results-POSTapi-v1-auth-otp-phone-verify" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-auth-otp-phone-verify"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-auth-otp-phone-verify"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-auth-otp-phone-verify" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-auth-otp-phone-verify">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-auth-otp-phone-verify" data-method="POST"
      data-path="api/v1/auth/otp/phone/verify"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-auth-otp-phone-verify', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-auth-otp-phone-verify"
                    onclick="tryItOut('POSTapi-v1-auth-otp-phone-verify');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-auth-otp-phone-verify"
                    onclick="cancelTryOut('POSTapi-v1-auth-otp-phone-verify');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-auth-otp-phone-verify"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/auth/otp/phone/verify</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-auth-otp-phone-verify"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-auth-otp-phone-verify"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>phone</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="phone"                data-endpoint="POSTapi-v1-auth-otp-phone-verify"
               value="8225697751"
               data-component="body">
    <br>
<p>Must be 10 digits. Example: <code>8225697751</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>otp</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="otp"                data-endpoint="POSTapi-v1-auth-otp-phone-verify"
               value="569775"
               data-component="body">
    <br>
<p>Must be 6 digits. Example: <code>569775</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>purpose</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="purpose"                data-endpoint="POSTapi-v1-auth-otp-phone-verify"
               value="register"
               data-component="body">
    <br>
<p>Example: <code>register</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>register</code></li> <li><code>login</code></li> <li><code>reset</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>device_name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="device_name"                data-endpoint="POSTapi-v1-auth-otp-phone-verify"
               value="n"
               data-component="body">
    <br>
<p>Must not be greater than 60 characters. Example: <code>n</code></p>
        </div>
        </form>

                    <h2 id="authentication-POSTapi-v1-auth-otp-email-send">Dispatch an email OTP. Mirror of sendPhoneOtp — same 3 purposes,
but feature-flag for login purpose defaults to DISABLED (admin must
explicitly enable email OTP login via site_settings).</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-auth-otp-email-send">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/auth/otp/email/send" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"email\": \"gbailey@example.net\",
    \"purpose\": \"reset\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/auth/otp/email/send"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "email": "gbailey@example.net",
    "purpose": "reset"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-auth-otp-email-send">
</span>
<span id="execution-results-POSTapi-v1-auth-otp-email-send" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-auth-otp-email-send"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-auth-otp-email-send"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-auth-otp-email-send" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-auth-otp-email-send">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-auth-otp-email-send" data-method="POST"
      data-path="api/v1/auth/otp/email/send"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-auth-otp-email-send', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-auth-otp-email-send"
                    onclick="tryItOut('POSTapi-v1-auth-otp-email-send');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-auth-otp-email-send"
                    onclick="cancelTryOut('POSTapi-v1-auth-otp-email-send');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-auth-otp-email-send"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/auth/otp/email/send</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-auth-otp-email-send"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-auth-otp-email-send"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="email"                data-endpoint="POSTapi-v1-auth-otp-email-send"
               value="gbailey@example.net"
               data-component="body">
    <br>
<p>Must be a valid email address. Example: <code>gbailey@example.net</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>purpose</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="purpose"                data-endpoint="POSTapi-v1-auth-otp-email-send"
               value="reset"
               data-component="body">
    <br>
<p>Example: <code>reset</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>register</code></li> <li><code>login</code></li> <li><code>reset</code></li></ul>
        </div>
        </form>

                    <h2 id="authentication-POSTapi-v1-auth-otp-email-verify">Verify an email OTP. Dispatches to the same handleX handlers used
by phone OTP (single source of truth for the 3 purpose branches).</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-auth-otp-email-verify">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/auth/otp/email/verify" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"email\": \"gbailey@example.net\",
    \"otp\": \"569775\",
    \"purpose\": \"register\",
    \"device_name\": \"n\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/auth/otp/email/verify"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "email": "gbailey@example.net",
    "otp": "569775",
    "purpose": "register",
    "device_name": "n"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-auth-otp-email-verify">
</span>
<span id="execution-results-POSTapi-v1-auth-otp-email-verify" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-auth-otp-email-verify"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-auth-otp-email-verify"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-auth-otp-email-verify" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-auth-otp-email-verify">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-auth-otp-email-verify" data-method="POST"
      data-path="api/v1/auth/otp/email/verify"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-auth-otp-email-verify', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-auth-otp-email-verify"
                    onclick="tryItOut('POSTapi-v1-auth-otp-email-verify');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-auth-otp-email-verify"
                    onclick="cancelTryOut('POSTapi-v1-auth-otp-email-verify');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-auth-otp-email-verify"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/auth/otp/email/verify</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-auth-otp-email-verify"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-auth-otp-email-verify"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="email"                data-endpoint="POSTapi-v1-auth-otp-email-verify"
               value="gbailey@example.net"
               data-component="body">
    <br>
<p>Must be a valid email address. Example: <code>gbailey@example.net</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>otp</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="otp"                data-endpoint="POSTapi-v1-auth-otp-email-verify"
               value="569775"
               data-component="body">
    <br>
<p>Must be 6 digits. Example: <code>569775</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>purpose</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="purpose"                data-endpoint="POSTapi-v1-auth-otp-email-verify"
               value="register"
               data-component="body">
    <br>
<p>Example: <code>register</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>register</code></li> <li><code>login</code></li> <li><code>reset</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>device_name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="device_name"                data-endpoint="POSTapi-v1-auth-otp-email-verify"
               value="n"
               data-component="body">
    <br>
<p>Must not be greater than 60 characters. Example: <code>n</code></p>
        </div>
        </form>

                    <h2 id="authentication-POSTapi-v1-auth-login-password">Log in with email + password. Primary login flow for existing users.</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-auth-login-password">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/auth/login/password" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"email\": \"gbailey@example.net\",
    \"password\": \"|]|{+-\",
    \"device_name\": \"v\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/auth/login/password"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "email": "gbailey@example.net",
    "password": "|]|{+-",
    "device_name": "v"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-auth-login-password">
</span>
<span id="execution-results-POSTapi-v1-auth-login-password" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-auth-login-password"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-auth-login-password"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-auth-login-password" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-auth-login-password">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-auth-login-password" data-method="POST"
      data-path="api/v1/auth/login/password"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-auth-login-password', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-auth-login-password"
                    onclick="tryItOut('POSTapi-v1-auth-login-password');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-auth-login-password"
                    onclick="cancelTryOut('POSTapi-v1-auth-login-password');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-auth-login-password"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/auth/login/password</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-auth-login-password"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-auth-login-password"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="email"                data-endpoint="POSTapi-v1-auth-login-password"
               value="gbailey@example.net"
               data-component="body">
    <br>
<p>Must be a valid email address. Example: <code>gbailey@example.net</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>password</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="password"                data-endpoint="POSTapi-v1-auth-login-password"
               value="|]|{+-"
               data-component="body">
    <br>
<p>Example: <code>|]|{+-</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>device_name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="device_name"                data-endpoint="POSTapi-v1-auth-login-password"
               value="v"
               data-component="body">
    <br>
<p>Must not be greater than 60 characters. Example: <code>v</code></p>
        </div>
        </form>

                    <h2 id="authentication-POSTapi-v1-auth-password-forgot">Send a password reset email via Laravel&#039;s Password broker.</h2>

<p>
</p>

<p>Always returns envelope success, regardless of whether the email
exists — anti-enumeration. If an account exists, a reset link is
dispatched to the user's email. The link points to APP_URL/reset-password/{token}
which the Flutter App Links intent filter (step-17 of Flutter plan)
intercepts to open the reset screen in-app.</p>

<span id="example-requests-POSTapi-v1-auth-password-forgot">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/auth/password/forgot" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"email\": \"gbailey@example.net\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/auth/password/forgot"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "email": "gbailey@example.net"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-auth-password-forgot">
</span>
<span id="execution-results-POSTapi-v1-auth-password-forgot" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-auth-password-forgot"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-auth-password-forgot"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-auth-password-forgot" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-auth-password-forgot">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-auth-password-forgot" data-method="POST"
      data-path="api/v1/auth/password/forgot"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-auth-password-forgot', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-auth-password-forgot"
                    onclick="tryItOut('POSTapi-v1-auth-password-forgot');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-auth-password-forgot"
                    onclick="cancelTryOut('POSTapi-v1-auth-password-forgot');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-auth-password-forgot"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/auth/password/forgot</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-auth-password-forgot"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-auth-password-forgot"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="email"                data-endpoint="POSTapi-v1-auth-password-forgot"
               value="gbailey@example.net"
               data-component="body">
    <br>
<p>Must be a valid email address. Example: <code>gbailey@example.net</code></p>
        </div>
        </form>

                    <h2 id="authentication-POSTapi-v1-auth-password-reset">Complete the password reset using the token from the reset email.</h2>

<p>
</p>

<p>Side effect: on success, all Sanctum tokens for the user are revoked
(force re-login on every device for security).</p>

<span id="example-requests-POSTapi-v1-auth-password-reset">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/auth/password/reset" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"token\": \"architecto\",
    \"email\": \"zbailey@example.net\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/auth/password/reset"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "token": "architecto",
    "email": "zbailey@example.net"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-auth-password-reset">
</span>
<span id="execution-results-POSTapi-v1-auth-password-reset" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-auth-password-reset"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-auth-password-reset"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-auth-password-reset" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-auth-password-reset">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-auth-password-reset" data-method="POST"
      data-path="api/v1/auth/password/reset"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-auth-password-reset', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-auth-password-reset"
                    onclick="tryItOut('POSTapi-v1-auth-password-reset');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-auth-password-reset"
                    onclick="cancelTryOut('POSTapi-v1-auth-password-reset');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-auth-password-reset"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/auth/password/reset</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-auth-password-reset"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-auth-password-reset"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>token</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="token"                data-endpoint="POSTapi-v1-auth-password-reset"
               value="architecto"
               data-component="body">
    <br>
<p>Example: <code>architecto</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="email"                data-endpoint="POSTapi-v1-auth-password-reset"
               value="zbailey@example.net"
               data-component="body">
    <br>
<p>Must be a valid email address. Example: <code>zbailey@example.net</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>password</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="password"                data-endpoint="POSTapi-v1-auth-password-reset"
               value=""
               data-component="body">
    <br>

        </div>
        </form>

                    <h2 id="authentication-POSTapi-v1-auth-register-step-2">Register step 2: primary + religious + family info.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-v1-auth-register-step-2">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/auth/register/step-2" \
    --header "Content-Type: multipart/form-data" \
    --header "Accept: application/json" \
    --form "height=architecto"\
    --form "complexion=architecto"\
    --form "body_type=architecto"\
    --form "physical_status=architecto"\
    --form "da_category=architecto"\
    --form "da_category_other=n"\
    --form "da_description=g"\
    --form "marital_status=architecto"\
    --form "children_with_me=39"\
    --form "children_not_with_me=84"\
    --form "family_status=architecto"\
    --form "religion=architecto"\
    --form "denomination=architecto"\
    --form "diocese=architecto"\
    --form "diocese_name=architecto"\
    --form "parish_name_place=architecto"\
    --form "caste=architecto"\
    --form "sub_caste=architecto"\
    --form "time_of_birth=architecto"\
    --form "place_of_birth=architecto"\
    --form "rashi=architecto"\
    --form "nakshatra=architecto"\
    --form "gotra=architecto"\
    --form "manglik=architecto"\
    --form "muslim_sect=architecto"\
    --form "muslim_community=architecto"\
    --form "religious_observance=architecto"\
    --form "jain_sect=architecto"\
    --form "other_religion_name=architecto"\
    --form "jathakam=@C:\Users\Lenovo\AppData\Local\Temp\phpFC4B.tmp" </code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/auth/register/step-2"
);

const headers = {
    "Content-Type": "multipart/form-data",
    "Accept": "application/json",
};

const body = new FormData();
body.append('height', 'architecto');
body.append('complexion', 'architecto');
body.append('body_type', 'architecto');
body.append('physical_status', 'architecto');
body.append('da_category', 'architecto');
body.append('da_category_other', 'n');
body.append('da_description', 'g');
body.append('marital_status', 'architecto');
body.append('children_with_me', '39');
body.append('children_not_with_me', '84');
body.append('family_status', 'architecto');
body.append('religion', 'architecto');
body.append('denomination', 'architecto');
body.append('diocese', 'architecto');
body.append('diocese_name', 'architecto');
body.append('parish_name_place', 'architecto');
body.append('caste', 'architecto');
body.append('sub_caste', 'architecto');
body.append('time_of_birth', 'architecto');
body.append('place_of_birth', 'architecto');
body.append('rashi', 'architecto');
body.append('nakshatra', 'architecto');
body.append('gotra', 'architecto');
body.append('manglik', 'architecto');
body.append('muslim_sect', 'architecto');
body.append('muslim_community', 'architecto');
body.append('religious_observance', 'architecto');
body.append('jain_sect', 'architecto');
body.append('other_religion_name', 'architecto');
body.append('jathakam', document.querySelector('input[name="jathakam"]').files[0]);

fetch(url, {
    method: "POST",
    headers,
    body,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-auth-register-step-2">
</span>
<span id="execution-results-POSTapi-v1-auth-register-step-2" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-auth-register-step-2"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-auth-register-step-2"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-auth-register-step-2" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-auth-register-step-2">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-auth-register-step-2" data-method="POST"
      data-path="api/v1/auth/register/step-2"
      data-authed="1"
      data-hasfiles="1"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-auth-register-step-2', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-auth-register-step-2"
                    onclick="tryItOut('POSTapi-v1-auth-register-step-2');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-auth-register-step-2"
                    onclick="cancelTryOut('POSTapi-v1-auth-register-step-2');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-auth-register-step-2"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/auth/register/step-2</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-auth-register-step-2"
               value="multipart/form-data"
               data-component="header">
    <br>
<p>Example: <code>multipart/form-data</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-auth-register-step-2"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>height</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="height"                data-endpoint="POSTapi-v1-auth-register-step-2"
               value="architecto"
               data-component="body">
    <br>
<p>Example: <code>architecto</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>complexion</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="complexion"                data-endpoint="POSTapi-v1-auth-register-step-2"
               value="architecto"
               data-component="body">
    <br>
<p>Example: <code>architecto</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>body_type</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="body_type"                data-endpoint="POSTapi-v1-auth-register-step-2"
               value="architecto"
               data-component="body">
    <br>
<p>Example: <code>architecto</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>physical_status</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="physical_status"                data-endpoint="POSTapi-v1-auth-register-step-2"
               value="architecto"
               data-component="body">
    <br>
<p>Example: <code>architecto</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>da_category</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="da_category"                data-endpoint="POSTapi-v1-auth-register-step-2"
               value="architecto"
               data-component="body">
    <br>
<p>This field is required when <code>physical_status</code> is <code>Differently Abled</code>. Example: <code>architecto</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>da_category_other</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="da_category_other"                data-endpoint="POSTapi-v1-auth-register-step-2"
               value="n"
               data-component="body">
    <br>
<p>This field is required when <code>da_category</code> is <code>Other</code>. Must not be greater than 50 characters. Example: <code>n</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>da_description</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="da_description"                data-endpoint="POSTapi-v1-auth-register-step-2"
               value="g"
               data-component="body">
    <br>
<p>This field is required when <code>physical_status</code> is <code>Differently Abled</code>. Must not be greater than 500 characters. Example: <code>g</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>marital_status</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="marital_status"                data-endpoint="POSTapi-v1-auth-register-step-2"
               value="architecto"
               data-component="body">
    <br>
<p>Example: <code>architecto</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>children_with_me</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="children_with_me"                data-endpoint="POSTapi-v1-auth-register-step-2"
               value="39"
               data-component="body">
    <br>
<p>Must be at least 0. Example: <code>39</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>children_not_with_me</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="children_not_with_me"                data-endpoint="POSTapi-v1-auth-register-step-2"
               value="84"
               data-component="body">
    <br>
<p>Must be at least 0. Example: <code>84</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>family_status</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="family_status"                data-endpoint="POSTapi-v1-auth-register-step-2"
               value="architecto"
               data-component="body">
    <br>
<p>Example: <code>architecto</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>religion</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="religion"                data-endpoint="POSTapi-v1-auth-register-step-2"
               value="architecto"
               data-component="body">
    <br>
<p>Example: <code>architecto</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>denomination</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="denomination"                data-endpoint="POSTapi-v1-auth-register-step-2"
               value="architecto"
               data-component="body">
    <br>
<p>This field is required when <code>religion</code> is <code>Christian</code>. Example: <code>architecto</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>diocese</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="diocese"                data-endpoint="POSTapi-v1-auth-register-step-2"
               value="architecto"
               data-component="body">
    <br>
<p>Example: <code>architecto</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>diocese_name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="diocese_name"                data-endpoint="POSTapi-v1-auth-register-step-2"
               value="architecto"
               data-component="body">
    <br>
<p>Example: <code>architecto</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>parish_name_place</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="parish_name_place"                data-endpoint="POSTapi-v1-auth-register-step-2"
               value="architecto"
               data-component="body">
    <br>
<p>Example: <code>architecto</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>caste</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="caste"                data-endpoint="POSTapi-v1-auth-register-step-2"
               value="architecto"
               data-component="body">
    <br>
<p>This field is required when <code>religion</code> is <code>Hindu</code>. This field is required when <code>religion</code> is <code>Jain</code>. Example: <code>architecto</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>sub_caste</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="sub_caste"                data-endpoint="POSTapi-v1-auth-register-step-2"
               value="architecto"
               data-component="body">
    <br>
<p>Example: <code>architecto</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>time_of_birth</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="time_of_birth"                data-endpoint="POSTapi-v1-auth-register-step-2"
               value="architecto"
               data-component="body">
    <br>
<p>Example: <code>architecto</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>place_of_birth</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="place_of_birth"                data-endpoint="POSTapi-v1-auth-register-step-2"
               value="architecto"
               data-component="body">
    <br>
<p>Example: <code>architecto</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>rashi</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="rashi"                data-endpoint="POSTapi-v1-auth-register-step-2"
               value="architecto"
               data-component="body">
    <br>
<p>Example: <code>architecto</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>nakshatra</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="nakshatra"                data-endpoint="POSTapi-v1-auth-register-step-2"
               value="architecto"
               data-component="body">
    <br>
<p>Example: <code>architecto</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>gotra</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="gotra"                data-endpoint="POSTapi-v1-auth-register-step-2"
               value="architecto"
               data-component="body">
    <br>
<p>Example: <code>architecto</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>manglik</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="manglik"                data-endpoint="POSTapi-v1-auth-register-step-2"
               value="architecto"
               data-component="body">
    <br>
<p>Example: <code>architecto</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>jathakam</code></b>&nbsp;&nbsp;
<small>file</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="file" style="display: none"
                              name="jathakam"                data-endpoint="POSTapi-v1-auth-register-step-2"
               value=""
               data-component="body">
    <br>
<p>Must be a file. Must not be greater than 2048 kilobytes. Example: <code>C:\Users\Lenovo\AppData\Local\Temp\phpFC4B.tmp</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>muslim_sect</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="muslim_sect"                data-endpoint="POSTapi-v1-auth-register-step-2"
               value="architecto"
               data-component="body">
    <br>
<p>This field is required when <code>religion</code> is <code>Muslim</code>. Example: <code>architecto</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>muslim_community</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="muslim_community"                data-endpoint="POSTapi-v1-auth-register-step-2"
               value="architecto"
               data-component="body">
    <br>
<p>Example: <code>architecto</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>religious_observance</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="religious_observance"                data-endpoint="POSTapi-v1-auth-register-step-2"
               value="architecto"
               data-component="body">
    <br>
<p>Example: <code>architecto</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>jain_sect</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="jain_sect"                data-endpoint="POSTapi-v1-auth-register-step-2"
               value="architecto"
               data-component="body">
    <br>
<p>Example: <code>architecto</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>other_religion_name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="other_religion_name"                data-endpoint="POSTapi-v1-auth-register-step-2"
               value="architecto"
               data-component="body">
    <br>
<p>This field is required when <code>religion</code> is <code>Other</code>. Example: <code>architecto</code></p>
        </div>
        </form>

                    <h2 id="authentication-POSTapi-v1-auth-register-step-3">Register step 3: education + professional.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-v1-auth-register-step-3">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/auth/register/step-3" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"highest_education\": \"b\",
    \"education_level\": \"n\",
    \"education_detail\": \"g\",
    \"college_name\": \"z\",
    \"occupation\": \"m\",
    \"occupation_detail\": \"i\",
    \"employment_category\": \"y\",
    \"employer_name\": \"v\",
    \"annual_income\": \"d\",
    \"working_country\": \"l\",
    \"working_state\": \"j\",
    \"working_district\": \"n\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/auth/register/step-3"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "highest_education": "b",
    "education_level": "n",
    "education_detail": "g",
    "college_name": "z",
    "occupation": "m",
    "occupation_detail": "i",
    "employment_category": "y",
    "employer_name": "v",
    "annual_income": "d",
    "working_country": "l",
    "working_state": "j",
    "working_district": "n"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-auth-register-step-3">
</span>
<span id="execution-results-POSTapi-v1-auth-register-step-3" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-auth-register-step-3"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-auth-register-step-3"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-auth-register-step-3" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-auth-register-step-3">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-auth-register-step-3" data-method="POST"
      data-path="api/v1/auth/register/step-3"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-auth-register-step-3', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-auth-register-step-3"
                    onclick="tryItOut('POSTapi-v1-auth-register-step-3');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-auth-register-step-3"
                    onclick="cancelTryOut('POSTapi-v1-auth-register-step-3');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-auth-register-step-3"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/auth/register/step-3</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-auth-register-step-3"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-auth-register-step-3"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>highest_education</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="highest_education"                data-endpoint="POSTapi-v1-auth-register-step-3"
               value="b"
               data-component="body">
    <br>
<p>Must not be greater than 100 characters. Example: <code>b</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>education_level</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="education_level"                data-endpoint="POSTapi-v1-auth-register-step-3"
               value="n"
               data-component="body">
    <br>
<p>Must not be greater than 50 characters. Example: <code>n</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>education_detail</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="education_detail"                data-endpoint="POSTapi-v1-auth-register-step-3"
               value="g"
               data-component="body">
    <br>
<p>Must not be greater than 200 characters. Example: <code>g</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>college_name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="college_name"                data-endpoint="POSTapi-v1-auth-register-step-3"
               value="z"
               data-component="body">
    <br>
<p>Must not be greater than 200 characters. Example: <code>z</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>occupation</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="occupation"                data-endpoint="POSTapi-v1-auth-register-step-3"
               value="m"
               data-component="body">
    <br>
<p>Must not be greater than 100 characters. Example: <code>m</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>occupation_detail</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="occupation_detail"                data-endpoint="POSTapi-v1-auth-register-step-3"
               value="i"
               data-component="body">
    <br>
<p>Must not be greater than 200 characters. Example: <code>i</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>employment_category</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="employment_category"                data-endpoint="POSTapi-v1-auth-register-step-3"
               value="y"
               data-component="body">
    <br>
<p>Must not be greater than 100 characters. Example: <code>y</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>employer_name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="employer_name"                data-endpoint="POSTapi-v1-auth-register-step-3"
               value="v"
               data-component="body">
    <br>
<p>Must not be greater than 200 characters. Example: <code>v</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>annual_income</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="annual_income"                data-endpoint="POSTapi-v1-auth-register-step-3"
               value="d"
               data-component="body">
    <br>
<p>Must not be greater than 50 characters. Example: <code>d</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>working_country</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="working_country"                data-endpoint="POSTapi-v1-auth-register-step-3"
               value="l"
               data-component="body">
    <br>
<p>Must not be greater than 100 characters. Example: <code>l</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>working_state</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="working_state"                data-endpoint="POSTapi-v1-auth-register-step-3"
               value="j"
               data-component="body">
    <br>
<p>Must not be greater than 100 characters. Example: <code>j</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>working_district</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="working_district"                data-endpoint="POSTapi-v1-auth-register-step-3"
               value="n"
               data-component="body">
    <br>
<p>Must not be greater than 100 characters. Example: <code>n</code></p>
        </div>
        </form>

                    <h2 id="authentication-POSTapi-v1-auth-register-step-4">Register step 4: location + contact.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-v1-auth-register-step-4">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/auth/register/step-4" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"native_country\": \"b\",
    \"native_state\": \"n\",
    \"native_district\": \"g\",
    \"whatsapp_number\": \"z\",
    \"mobile_number\": \"m\",
    \"custodian_name\": \"i\",
    \"custodian_relation\": \"y\",
    \"communication_address\": \"v\",
    \"pin_zip_code\": \"dljnik\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/auth/register/step-4"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "native_country": "b",
    "native_state": "n",
    "native_district": "g",
    "whatsapp_number": "z",
    "mobile_number": "m",
    "custodian_name": "i",
    "custodian_relation": "y",
    "communication_address": "v",
    "pin_zip_code": "dljnik"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-auth-register-step-4">
</span>
<span id="execution-results-POSTapi-v1-auth-register-step-4" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-auth-register-step-4"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-auth-register-step-4"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-auth-register-step-4" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-auth-register-step-4">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-auth-register-step-4" data-method="POST"
      data-path="api/v1/auth/register/step-4"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-auth-register-step-4', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-auth-register-step-4"
                    onclick="tryItOut('POSTapi-v1-auth-register-step-4');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-auth-register-step-4"
                    onclick="cancelTryOut('POSTapi-v1-auth-register-step-4');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-auth-register-step-4"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/auth/register/step-4</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-auth-register-step-4"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-auth-register-step-4"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>native_country</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="native_country"                data-endpoint="POSTapi-v1-auth-register-step-4"
               value="b"
               data-component="body">
    <br>
<p>Must not be greater than 100 characters. Example: <code>b</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>native_state</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="native_state"                data-endpoint="POSTapi-v1-auth-register-step-4"
               value="n"
               data-component="body">
    <br>
<p>This field is required when <code>native_country</code> is <code>India</code>. Must not be greater than 100 characters. Example: <code>n</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>native_district</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="native_district"                data-endpoint="POSTapi-v1-auth-register-step-4"
               value="g"
               data-component="body">
    <br>
<p>This field is required when <code>native_country</code> is <code>India</code>. Must not be greater than 100 characters. Example: <code>g</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>whatsapp_number</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="whatsapp_number"                data-endpoint="POSTapi-v1-auth-register-step-4"
               value="z"
               data-component="body">
    <br>
<p>Must not be greater than 15 characters. Example: <code>z</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>mobile_number</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="mobile_number"                data-endpoint="POSTapi-v1-auth-register-step-4"
               value="m"
               data-component="body">
    <br>
<p>Must not be greater than 15 characters. Example: <code>m</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>custodian_name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="custodian_name"                data-endpoint="POSTapi-v1-auth-register-step-4"
               value="i"
               data-component="body">
    <br>
<p>Must not be greater than 100 characters. Example: <code>i</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>custodian_relation</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="custodian_relation"                data-endpoint="POSTapi-v1-auth-register-step-4"
               value="y"
               data-component="body">
    <br>
<p>Must not be greater than 100 characters. Example: <code>y</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>communication_address</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="communication_address"                data-endpoint="POSTapi-v1-auth-register-step-4"
               value="v"
               data-component="body">
    <br>
<p>Must not be greater than 200 characters. Example: <code>v</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>pin_zip_code</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="pin_zip_code"                data-endpoint="POSTapi-v1-auth-register-step-4"
               value="dljnik"
               data-component="body">
    <br>
<p>Must not be greater than 10 characters. Example: <code>dljnik</code></p>
        </div>
        </form>

                    <h2 id="authentication-POSTapi-v1-auth-register-step-5">Register step 5: profile-creator info + finalize. Returns the next
screen: &#039;verify.email&#039;, &#039;verify.phone&#039;, or &#039;complete&#039;.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-v1-auth-register-step-5">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/auth/register/step-5" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"created_by\": \"b\",
    \"creator_name\": \"n\",
    \"creator_contact_number\": \"g\",
    \"how_did_you_hear_about_us\": \"z\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/auth/register/step-5"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "created_by": "b",
    "creator_name": "n",
    "creator_contact_number": "g",
    "how_did_you_hear_about_us": "z"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-auth-register-step-5">
</span>
<span id="execution-results-POSTapi-v1-auth-register-step-5" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-auth-register-step-5"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-auth-register-step-5"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-auth-register-step-5" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-auth-register-step-5">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-auth-register-step-5" data-method="POST"
      data-path="api/v1/auth/register/step-5"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-auth-register-step-5', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-auth-register-step-5"
                    onclick="tryItOut('POSTapi-v1-auth-register-step-5');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-auth-register-step-5"
                    onclick="cancelTryOut('POSTapi-v1-auth-register-step-5');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-auth-register-step-5"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/auth/register/step-5</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-auth-register-step-5"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-auth-register-step-5"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>created_by</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="created_by"                data-endpoint="POSTapi-v1-auth-register-step-5"
               value="b"
               data-component="body">
    <br>
<p>Must not be greater than 50 characters. Example: <code>b</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>creator_name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="creator_name"                data-endpoint="POSTapi-v1-auth-register-step-5"
               value="n"
               data-component="body">
    <br>
<p>Must not be greater than 100 characters. Example: <code>n</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>creator_contact_number</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="creator_contact_number"                data-endpoint="POSTapi-v1-auth-register-step-5"
               value="g"
               data-component="body">
    <br>
<p>Must not be greater than 15 characters. Example: <code>g</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>how_did_you_hear_about_us</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="how_did_you_hear_about_us"                data-endpoint="POSTapi-v1-auth-register-step-5"
               value="z"
               data-component="body">
    <br>
<p>Must not be greater than 100 characters. Example: <code>z</code></p>
        </div>
        </form>

                    <h2 id="authentication-POSTapi-v1-auth-logout">Revoke the token the current request authenticated with. Only this
device&#039;s token is revoked — other devices stay signed in.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-v1-auth-logout">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/auth/logout" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/auth/logout"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-auth-logout">
</span>
<span id="execution-results-POSTapi-v1-auth-logout" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-auth-logout"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-auth-logout"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-auth-logout" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-auth-logout">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-auth-logout" data-method="POST"
      data-path="api/v1/auth/logout"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-auth-logout', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-auth-logout"
                    onclick="tryItOut('POSTapi-v1-auth-logout');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-auth-logout"
                    onclick="cancelTryOut('POSTapi-v1-auth-logout');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-auth-logout"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/auth/logout</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-auth-logout"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-auth-logout"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                <h1 id="block">Block</h1>

    

                                <h2 id="block-GETapi-v1-blocked">GET api/v1/blocked</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-GETapi-v1-blocked">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/blocked?page=16&amp;per_page=16" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/blocked"
);

const params = {
    "page": "16",
    "per_page": "16",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-blocked">
            <blockquote>
            <p>Example response (200, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
  &quot;success&quot;: true,
  &quot;data&quot;: [{&quot;matri_id&quot;: &quot;AM000201&quot;, ...}],
  &quot;meta&quot;: {&quot;page&quot;: 1, &quot;per_page&quot;: 20, &quot;total&quot;: 1, &quot;last_page&quot;: 1}
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, no-profile):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;PROFILE_REQUIRED&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-blocked" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-blocked"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-blocked"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-blocked" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-blocked">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-blocked" data-method="GET"
      data-path="api/v1/blocked"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-blocked', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-blocked"
                    onclick="tryItOut('GETapi-v1-blocked');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-blocked"
                    onclick="cancelTryOut('GETapi-v1-blocked');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-blocked"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/blocked</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-blocked"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-blocked"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Query Parameters</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="page"                data-endpoint="GETapi-v1-blocked"
               value="16"
               data-component="query">
    <br>
<p>Optional. Default 1. Example: <code>16</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>per_page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="per_page"                data-endpoint="GETapi-v1-blocked"
               value="16"
               data-component="query">
    <br>
<p>Optional. 1-50. Default 20. Example: <code>16</code></p>
            </div>
                </form>

                    <h2 id="block-POSTapi-v1-profiles--matriId--block">Block a target profile. Idempotent — POSTing twice is a no-op
after the first call. Side-effects (cancel pending interests,
remove the viewer&#039;s shortlist of target) run in a transaction so
a partial state isn&#039;t visible to other requests.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-v1-profiles--matriId--block">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/profiles/architecto/block" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/profiles/architecto/block"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-profiles--matriId--block">
            <blockquote>
            <p>Example response (200, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;blocked&quot;: true
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (404, not-found):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;NOT_FOUND&quot;,
        &quot;message&quot;: &quot;Profile not available.&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, invalid-target):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;INVALID_TARGET&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, no-profile):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;PROFILE_REQUIRED&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-profiles--matriId--block" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-profiles--matriId--block"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-profiles--matriId--block"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-profiles--matriId--block" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-profiles--matriId--block">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-profiles--matriId--block" data-method="POST"
      data-path="api/v1/profiles/{matriId}/block"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-profiles--matriId--block', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-profiles--matriId--block"
                    onclick="tryItOut('POSTapi-v1-profiles--matriId--block');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-profiles--matriId--block"
                    onclick="cancelTryOut('POSTapi-v1-profiles--matriId--block');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-profiles--matriId--block"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/profiles/{matriId}/block</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-profiles--matriId--block"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-profiles--matriId--block"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>matriId</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="matriId"                data-endpoint="POSTapi-v1-profiles--matriId--block"
               value="architecto"
               data-component="url">
    <br>
<p>Target's matri_id. Example: <code>architecto</code></p>
            </div>
                    </form>

                    <h2 id="block-POSTapi-v1-profiles--matriId--unblock">Unblock a target profile. Idempotent — no-op when no block exists.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-v1-profiles--matriId--unblock">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/profiles/architecto/unblock" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/profiles/architecto/unblock"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-profiles--matriId--unblock">
            <blockquote>
            <p>Example response (200, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;blocked&quot;: false
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (404, not-found):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;NOT_FOUND&quot;,
        &quot;message&quot;: &quot;Profile not available.&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, no-profile):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;PROFILE_REQUIRED&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-profiles--matriId--unblock" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-profiles--matriId--unblock"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-profiles--matriId--unblock"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-profiles--matriId--unblock" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-profiles--matriId--unblock">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-profiles--matriId--unblock" data-method="POST"
      data-path="api/v1/profiles/{matriId}/unblock"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-profiles--matriId--unblock', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-profiles--matriId--unblock"
                    onclick="tryItOut('POSTapi-v1-profiles--matriId--unblock');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-profiles--matriId--unblock"
                    onclick="cancelTryOut('POSTapi-v1-profiles--matriId--unblock');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-profiles--matriId--unblock"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/profiles/{matriId}/unblock</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-profiles--matriId--unblock"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-profiles--matriId--unblock"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>matriId</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="matriId"                data-endpoint="POSTapi-v1-profiles--matriId--unblock"
               value="architecto"
               data-component="url">
    <br>
<p>Target's matri_id. Example: <code>architecto</code></p>
            </div>
                    </form>

                <h1 id="configuration">Configuration</h1>

    

                                <h2 id="configuration-GETapi-v1-site-settings">Get site configuration</h2>

<p>
</p>

<p>Returns site branding, theme colors, feature toggles, registration rules,
Razorpay public key, support contact info, mobile-app version gates,
social links, and policy URLs. The Flutter app calls this on every
launch to hydrate its theme + feature-flag state.</p>
<p>Cached server-side for 5 minutes.</p>

<span id="example-requests-GETapi-v1-site-settings">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/site/settings" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/site/settings"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-site-settings">
            <blockquote>
            <p>Example response (200, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;site&quot;: {
            &quot;name&quot;: &quot;Kudla Matrimony&quot;,
            &quot;tagline&quot;: &quot;Find Your Perfect Match&quot;,
            &quot;logo_url&quot;: &quot;https://kudlamatrimony.com/storage/branding/logo.png&quot;,
            &quot;support_email&quot;: &quot;support@kudlamatrimony.com&quot;
        },
        &quot;theme&quot;: {
            &quot;primary_color&quot;: &quot;#dc2626&quot;,
            &quot;heading_font&quot;: &quot;Playfair Display&quot;
        },
        &quot;features&quot;: {
            &quot;mobile_otp_login_enabled&quot;: true,
            &quot;realtime_chat_enabled&quot;: false
        },
        &quot;registration&quot;: {
            &quot;min_age&quot;: 18,
            &quot;password_min_length&quot;: 6,
            &quot;id_prefix&quot;: &quot;AM&quot;
        },
        &quot;membership&quot;: {
            &quot;razorpay_key&quot;: &quot;rzp_live_abc&quot;,
            &quot;currency&quot;: &quot;INR&quot;
        },
        &quot;app&quot;: {
            &quot;minimum_supported_version&quot;: &quot;1.0.0&quot;
        },
        &quot;social_links&quot;: {
            &quot;facebook&quot;: &quot;...&quot;
        },
        &quot;policies&quot;: {
            &quot;privacy_policy_url&quot;: &quot;https://...&quot;
        }
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-site-settings" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-site-settings"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-site-settings"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-site-settings" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-site-settings">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-site-settings" data-method="GET"
      data-path="api/v1/site/settings"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-site-settings', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-site-settings"
                    onclick="tryItOut('GETapi-v1-site-settings');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-site-settings"
                    onclick="cancelTryOut('GETapi-v1-site-settings');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-site-settings"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/site/settings</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-site-settings"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-site-settings"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="configuration-GETapi-v1-reference">List all available reference slugs</h2>

<p>
</p>

<p>Meta endpoint that returns every reference list slug this API supports.
Lets the Flutter client discover available dropdowns at runtime.</p>

<span id="example-requests-GETapi-v1-reference">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/reference" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/reference"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-reference">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;lists&quot;: [
            &quot;castes&quot;,
            &quot;countries&quot;,
            &quot;occupations&quot;,
            &quot;languages&quot;
        ]
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-reference" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-reference"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-reference"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-reference" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-reference">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-reference" data-method="GET"
      data-path="api/v1/reference"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-reference', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-reference"
                    onclick="tryItOut('GETapi-v1-reference');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-reference"
                    onclick="cancelTryOut('GETapi-v1-reference');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-reference"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/reference</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-reference"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-reference"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="configuration-GETapi-v1-reference--list-">Get a reference list</h2>

<p>
</p>

<p>Returns the items for a specific reference dropdown (religions, castes,
occupations, countries, etc.). Some lists are flat arrays; others are
grouped objects (e.g. <code>denominations</code> groups by Catholic/Non-Catholic).</p>
<p>Pass <code>?flat=1</code> to flatten a grouped list into a single array.
Pass <code>?options=1</code> to get <code>{value: value}</code> pairs suitable for a <code>&lt;select&gt;</code>.</p>
<p>Cached server-side for 1 hour.</p>

<span id="example-requests-GETapi-v1-reference--list-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/reference/castes?flat=1&amp;options=1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/reference/castes"
);

const params = {
    "flat": "1",
    "options": "1",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-reference--list-">
            <blockquote>
            <p>Example response (200, flat list):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: [
        &quot;Brahmin&quot;,
        &quot;Nair&quot;,
        &quot;Ezhava&quot;
    ]
}</code>
 </pre>
            <blockquote>
            <p>Example response (404, unknown list):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;NOT_FOUND&quot;,
        &quot;message&quot;: &quot;Reference list &#039;foo&#039; does not exist.&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-reference--list-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-reference--list-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-reference--list-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-reference--list-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-reference--list-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-reference--list-" data-method="GET"
      data-path="api/v1/reference/{list}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-reference--list-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-reference--list-"
                    onclick="tryItOut('GETapi-v1-reference--list-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-reference--list-"
                    onclick="cancelTryOut('GETapi-v1-reference--list-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-reference--list-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/reference/{list}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-reference--list-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-reference--list-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>list</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="list"                data-endpoint="GETapi-v1-reference--list-"
               value="castes"
               data-component="url">
    <br>
<p>The list slug. See <code>GET /api/v1/reference</code> for the full list. Example: <code>castes</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>Query Parameters</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>flat</code></b>&nbsp;&nbsp;
<small>boolean</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <label data-endpoint="GETapi-v1-reference--list-" style="display: none">
            <input type="radio" name="flat"
                   value="1"
                   data-endpoint="GETapi-v1-reference--list-"
                   data-component="query"             >
            <code>true</code>
        </label>
        <label data-endpoint="GETapi-v1-reference--list-" style="display: none">
            <input type="radio" name="flat"
                   value="0"
                   data-endpoint="GETapi-v1-reference--list-"
                   data-component="query"             >
            <code>false</code>
        </label>
    <br>
<p>Flatten a grouped list. Example: <code>true</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>options</code></b>&nbsp;&nbsp;
<small>boolean</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <label data-endpoint="GETapi-v1-reference--list-" style="display: none">
            <input type="radio" name="options"
                   value="1"
                   data-endpoint="GETapi-v1-reference--list-"
                   data-component="query"             >
            <code>true</code>
        </label>
        <label data-endpoint="GETapi-v1-reference--list-" style="display: none">
            <input type="radio" name="options"
                   value="0"
                   data-endpoint="GETapi-v1-reference--list-"
                   data-component="query"             >
            <code>false</code>
        </label>
    <br>
<p>Return {value: value} pairs. Example: <code>true</code></p>
            </div>
                </form>

                <h1 id="contact">Contact</h1>

    

                                <h2 id="contact-POSTapi-v1-contact">Submit a contact-form message.</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-contact">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/contact" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"name\": \"architecto\",
    \"email\": \"gbailey@example.net\",
    \"phone\": \"architecto\",
    \"subject\": \"architecto\",
    \"message\": \"architecto\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/contact"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "name": "architecto",
    "email": "gbailey@example.net",
    "phone": "architecto",
    "subject": "architecto",
    "message": "architecto"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-contact">
            <blockquote>
            <p>Example response (201, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;submission_id&quot;: 42,
        &quot;message&quot;: &quot;Thanks! We&#039;ll reply within 24 hours.&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, validation):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{&quot;success&quot;: false, &quot;error&quot;: {&quot;code&quot;: &quot;VALIDATION_FAILED&quot;, ...}}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-contact" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-contact"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-contact"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-contact" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-contact">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-contact" data-method="POST"
      data-path="api/v1/contact"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-contact', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-contact"
                    onclick="tryItOut('POSTapi-v1-contact');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-contact"
                    onclick="cancelTryOut('POSTapi-v1-contact');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-contact"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/contact</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-contact"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-contact"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="name"                data-endpoint="POSTapi-v1-contact"
               value="architecto"
               data-component="body">
    <br>
<p>Max 120. Example: <code>architecto</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="email"                data-endpoint="POSTapi-v1-contact"
               value="gbailey@example.net"
               data-component="body">
    <br>
<p>Valid email. Example: <code>gbailey@example.net</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>phone</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="phone"                data-endpoint="POSTapi-v1-contact"
               value="architecto"
               data-component="body">
    <br>
<p>Optional. Max 20 (international-tolerant). Example: <code>architecto</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>subject</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="subject"                data-endpoint="POSTapi-v1-contact"
               value="architecto"
               data-component="body">
    <br>
<p>Max 200. Example: <code>architecto</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>message</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="message"                data-endpoint="POSTapi-v1-contact"
               value="architecto"
               data-component="body">
    <br>
<p>Max 2000. Example: <code>architecto</code></p>
        </div>
        </form>

                <h1 id="devices">Devices</h1>

    

                                <h2 id="devices-POSTapi-v1-devices">Register or refresh an FCM device token for the authenticated user.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Idempotent on fcm_token — same token re-registered just updates
last_seen_at + metadata, returns the same device_id.</p>

<span id="example-requests-POSTapi-v1-devices">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/devices" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"fcm_token\": \"b\",
    \"platform\": \"android\",
    \"device_model\": \"n\",
    \"app_version\": \"gzmiyvdljnikhway\",
    \"os_version\": \"kcmyuwpwlvqwrsit\",
    \"locale\": \"de_DE\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/devices"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "fcm_token": "b",
    "platform": "android",
    "device_model": "n",
    "app_version": "gzmiyvdljnikhway",
    "os_version": "kcmyuwpwlvqwrsit",
    "locale": "de_DE"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-devices">
</span>
<span id="execution-results-POSTapi-v1-devices" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-devices"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-devices"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-devices" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-devices">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-devices" data-method="POST"
      data-path="api/v1/devices"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-devices', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-devices"
                    onclick="tryItOut('POSTapi-v1-devices');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-devices"
                    onclick="cancelTryOut('POSTapi-v1-devices');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-devices"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/devices</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-devices"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-devices"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>fcm_token</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="fcm_token"                data-endpoint="POSTapi-v1-devices"
               value="b"
               data-component="body">
    <br>
<p>Must not be greater than 255 characters. Example: <code>b</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>platform</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="platform"                data-endpoint="POSTapi-v1-devices"
               value="android"
               data-component="body">
    <br>
<p>Example: <code>android</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>android</code></li> <li><code>ios</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>device_model</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="device_model"                data-endpoint="POSTapi-v1-devices"
               value="n"
               data-component="body">
    <br>
<p>Must not be greater than 100 characters. Example: <code>n</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>app_version</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="app_version"                data-endpoint="POSTapi-v1-devices"
               value="gzmiyvdljnikhway"
               data-component="body">
    <br>
<p>Must not be greater than 20 characters. Example: <code>gzmiyvdljnikhway</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>os_version</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="os_version"                data-endpoint="POSTapi-v1-devices"
               value="kcmyuwpwlvqwrsit"
               data-component="body">
    <br>
<p>Must not be greater than 20 characters. Example: <code>kcmyuwpwlvqwrsit</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>locale</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="locale"                data-endpoint="POSTapi-v1-devices"
               value="de_DE"
               data-component="body">
    <br>
<p>Must not be greater than 10 characters. Example: <code>de_DE</code></p>
        </div>
        </form>

                    <h2 id="devices-DELETEapi-v1-devices--device_id-">Revoke a device (user taps &quot;sign out this device&quot; in settings).</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Side effect: also revokes the Sanctum token linked to this device
so the corresponding /auth/logout isn't needed separately.</p>

<span id="example-requests-DELETEapi-v1-devices--device_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "http://localhost:8000/api/v1/devices/16" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/devices/16"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-DELETEapi-v1-devices--device_id-">
</span>
<span id="execution-results-DELETEapi-v1-devices--device_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEapi-v1-devices--device_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEapi-v1-devices--device_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEapi-v1-devices--device_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEapi-v1-devices--device_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEapi-v1-devices--device_id-" data-method="DELETE"
      data-path="api/v1/devices/{device_id}"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEapi-v1-devices--device_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEapi-v1-devices--device_id-"
                    onclick="tryItOut('DELETEapi-v1-devices--device_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEapi-v1-devices--device_id-"
                    onclick="cancelTryOut('DELETEapi-v1-devices--device_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEapi-v1-devices--device_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>api/v1/devices/{device_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEapi-v1-devices--device_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="DELETEapi-v1-devices--device_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>device_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="device_id"                data-endpoint="DELETEapi-v1-devices--device_id-"
               value="16"
               data-component="url">
    <br>
<p>The ID of the device. Example: <code>16</code></p>
            </div>
                    </form>

                <h1 id="discover">Discover</h1>

    

                                <h2 id="discover-GETapi-v1-discover">GET api/v1/discover</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-discover">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/discover" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/discover"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-discover">
            <blockquote>
            <p>Example response (200, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: [
        {
            &quot;category&quot;: &quot;nri-matrimony&quot;,
            &quot;label&quot;: &quot;NRI Matrimony&quot;,
            &quot;show_search&quot;: true,
            &quot;has_subcategories&quot;: true,
            &quot;has_direct_filter&quot;: false
        },
        {
            &quot;category&quot;: &quot;kannadiga-matrimony&quot;,
            &quot;label&quot;: &quot;Kannadiga Matrimony&quot;,
            &quot;show_search&quot;: false,
            &quot;has_subcategories&quot;: false,
            &quot;has_direct_filter&quot;: true
        }
    ]
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-discover" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-discover"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-discover"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-discover" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-discover">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-discover" data-method="GET"
      data-path="api/v1/discover"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-discover', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-discover"
                    onclick="tryItOut('GETapi-v1-discover');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-discover"
                    onclick="cancelTryOut('GETapi-v1-discover');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-discover"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/discover</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-discover"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-discover"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="discover-GETapi-v1-discover--category-">Polymorphic response:
  - For direct_filter categories → paginated cards + meta
  - For subcategory-based categories → subcategory list</h2>

<p>
</p>

<p>Flutter uses the hub's has_subcategories / has_direct_filter
discriminators to predict which shape to expect.</p>

<span id="example-requests-GETapi-v1-discover--category-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/discover/architecto" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/discover/architecto"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-discover--category-">
            <blockquote>
            <p>Example response (200, subcategories):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;category&quot;: &quot;nri-matrimony&quot;,
        &quot;label&quot;: &quot;NRI Matrimony&quot;,
        &quot;subcategories&quot;: [
            {
                &quot;label&quot;: &quot;USA Profiles&quot;,
                &quot;slug&quot;: &quot;usa&quot;,
                &quot;filter&quot;: {
                    &quot;residing_country&quot;: &quot;USA&quot;
                }
            }
        ]
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (200, direct-results):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: [
        {
            &quot;matri_id&quot;: &quot;AM100042&quot;
        }
    ],
    &quot;meta&quot;: {
        &quot;page&quot;: 1,
        &quot;per_page&quot;: 20,
        &quot;total&quot;: 137,
        &quot;last_page&quot;: 7,
        &quot;category&quot;: &quot;kannadiga-matrimony&quot;,
        &quot;label&quot;: &quot;Kannadiga Matrimony&quot;,
        &quot;direct_filter&quot;: {
            &quot;mother_tongue&quot;: &quot;Kannada&quot;
        }
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (404, unknown-category):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;NOT_FOUND&quot;,
        &quot;message&quot;: &quot;Category not found.&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-discover--category-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-discover--category-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-discover--category-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-discover--category-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-discover--category-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-discover--category-" data-method="GET"
      data-path="api/v1/discover/{category}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-discover--category-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-discover--category-"
                    onclick="tryItOut('GETapi-v1-discover--category-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-discover--category-"
                    onclick="cancelTryOut('GETapi-v1-discover--category-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-discover--category-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/discover/{category}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-discover--category-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-discover--category-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>category</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="category"                data-endpoint="GETapi-v1-discover--category-"
               value="architecto"
               data-component="url">
    <br>
<p>e.g. nri-matrimony, kannadiga-matrimony. Example: <code>architecto</code></p>
            </div>
                    </form>

                    <h2 id="discover-GETapi-v1-discover--category---slug-">Paginated results for a specific subcategory slug.</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-discover--category---slug-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/discover/architecto/architecto" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/discover/architecto/architecto"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-discover--category---slug-">
            <blockquote>
            <p>Example response (200, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: [
        {
            &quot;matri_id&quot;: &quot;AM100042&quot;
        }
    ],
    &quot;meta&quot;: {
        &quot;page&quot;: 1,
        &quot;per_page&quot;: 20,
        &quot;total&quot;: 47,
        &quot;last_page&quot;: 3,
        &quot;category&quot;: &quot;nri-matrimony&quot;,
        &quot;slug&quot;: &quot;usa&quot;,
        &quot;filter&quot;: {
            &quot;residing_country&quot;: &quot;USA&quot;
        }
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (404, unknown-category):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;NOT_FOUND&quot;,
        &quot;message&quot;: &quot;Category not found.&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (404, unknown-slug):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;NOT_FOUND&quot;,
        &quot;message&quot;: &quot;Subcategory not found.&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-discover--category---slug-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-discover--category---slug-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-discover--category---slug-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-discover--category---slug-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-discover--category---slug-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-discover--category---slug-" data-method="GET"
      data-path="api/v1/discover/{category}/{slug}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-discover--category---slug-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-discover--category---slug-"
                    onclick="tryItOut('GETapi-v1-discover--category---slug-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-discover--category---slug-"
                    onclick="cancelTryOut('GETapi-v1-discover--category---slug-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-discover--category---slug-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/discover/{category}/{slug}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-discover--category---slug-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-discover--category---slug-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>category</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="category"                data-endpoint="GETapi-v1-discover--category---slug-"
               value="architecto"
               data-component="url">
    <br>
<p>Category key. Example: <code>architecto</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>slug</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="slug"                data-endpoint="GETapi-v1-discover--category---slug-"
               value="architecto"
               data-component="url">
    <br>
<p>Subcategory slug (e.g. "usa", "hindu-brahmin"). Example: <code>architecto</code></p>
            </div>
                    </form>

                <h1 id="id-proof">ID Proof</h1>

    

                                <h2 id="id-proof-GETapi-v1-id-proof">GET api/v1/id-proof</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-GETapi-v1-id-proof">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/id-proof" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/id-proof"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-id-proof">
            <blockquote>
            <p>Example response (200, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
  &quot;success&quot;: true,
  &quot;data&quot;: {
    &quot;id_proof&quot;: {&quot;id&quot;: 12, &quot;document_type&quot;: &quot;aadhaar&quot;, &quot;document_url&quot;: &quot;https://.../id-proofs/...&quot;, &quot;verification_status&quot;: &quot;pending&quot;, &quot;rejection_reason&quot;: null, &quot;submitted_at&quot;: &quot;2026-04-26T...&quot;, &quot;verified_at&quot;: null},
    &quot;accepted_types&quot;: [{&quot;value&quot;: &quot;aadhaar&quot;, &quot;label&quot;: &quot;Aadhaar Card&quot;}, ...]
  }
}</code>
 </pre>
            <blockquote>
            <p>Example response (200, no-submission):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
  &quot;success&quot;: true,
  &quot;data&quot;: {&quot;id_proof&quot;: null, &quot;accepted_types&quot;: [...]}
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, no-profile):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;PROFILE_REQUIRED&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-id-proof" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-id-proof"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-id-proof"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-id-proof" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-id-proof">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-id-proof" data-method="GET"
      data-path="api/v1/id-proof"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-id-proof', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-id-proof"
                    onclick="tryItOut('GETapi-v1-id-proof');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-id-proof"
                    onclick="cancelTryOut('GETapi-v1-id-proof');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-id-proof"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/id-proof</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-id-proof"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-id-proof"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="id-proof-POSTapi-v1-id-proof">Upload (or replace) the viewer&#039;s ID proof. Multipart body.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Replaces any existing submission — both the row AND the underlying
file are removed first. Always lands in <code>pending</code> state so the
admin reviews from scratch.</p>

<span id="example-requests-POSTapi-v1-id-proof">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/id-proof" \
    --header "Content-Type: multipart/form-data" \
    --header "Accept: application/json" \
    --form "document_type=architecto"\
    --form "document=@C:\Users\Lenovo\AppData\Local\Temp\php9C22.tmp" </code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/id-proof"
);

const headers = {
    "Content-Type": "multipart/form-data",
    "Accept": "application/json",
};

const body = new FormData();
body.append('document_type', 'architecto');
body.append('document', document.querySelector('input[name="document"]').files[0]);

fetch(url, {
    method: "POST",
    headers,
    body,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-id-proof">
            <blockquote>
            <p>Example response (201, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;id_proof&quot;: {
            &quot;id&quot;: 12,
            &quot;document_type&quot;: &quot;aadhaar&quot;,
            &quot;document_url&quot;: &quot;...&quot;,
            &quot;verification_status&quot;: &quot;pending&quot;,
            &quot;submitted_at&quot;: &quot;...&quot;
        }
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, validation):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{&quot;success&quot;: false, &quot;error&quot;: {&quot;code&quot;: &quot;VALIDATION_FAILED&quot;, ...}}</code>
 </pre>
            <blockquote>
            <p>Example response (422, no-profile):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;PROFILE_REQUIRED&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-id-proof" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-id-proof"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-id-proof"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-id-proof" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-id-proof">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-id-proof" data-method="POST"
      data-path="api/v1/id-proof"
      data-authed="1"
      data-hasfiles="1"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-id-proof', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-id-proof"
                    onclick="tryItOut('POSTapi-v1-id-proof');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-id-proof"
                    onclick="cancelTryOut('POSTapi-v1-id-proof');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-id-proof"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/id-proof</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-id-proof"
               value="multipart/form-data"
               data-component="header">
    <br>
<p>Example: <code>multipart/form-data</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-id-proof"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>document_type</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="document_type"                data-endpoint="POSTapi-v1-id-proof"
               value="architecto"
               data-component="body">
    <br>
<p>One of: aadhaar, passport, voter_id, driving_license. Example: <code>architecto</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>document</code></b>&nbsp;&nbsp;
<small>file</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="file" style="display: none"
                              name="document"                data-endpoint="POSTapi-v1-id-proof"
               value=""
               data-component="body">
    <br>
<p>JPG/PNG/PDF/WEBP, max 5 MB. Example: <code>C:\Users\Lenovo\AppData\Local\Temp\php9C22.tmp</code></p>
        </div>
        </form>

                    <h2 id="id-proof-DELETEapi-v1-id-proof--idProof_id-">Withdraw a pending or rejected submission. Approved IDs cannot be
deleted by the user — they need admin intervention so the verified
badge isn&#039;t gameable.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-DELETEapi-v1-id-proof--idProof_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "http://localhost:8000/api/v1/id-proof/16" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/id-proof/16"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-DELETEapi-v1-id-proof--idProof_id-">
            <blockquote>
            <p>Example response (200, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;deleted&quot;: true
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (403, not-owner):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;UNAUTHORIZED&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, already-verified):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;ALREADY_VERIFIED&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-DELETEapi-v1-id-proof--idProof_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEapi-v1-id-proof--idProof_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEapi-v1-id-proof--idProof_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEapi-v1-id-proof--idProof_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEapi-v1-id-proof--idProof_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEapi-v1-id-proof--idProof_id-" data-method="DELETE"
      data-path="api/v1/id-proof/{idProof_id}"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEapi-v1-id-proof--idProof_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEapi-v1-id-proof--idProof_id-"
                    onclick="tryItOut('DELETEapi-v1-id-proof--idProof_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEapi-v1-id-proof--idProof_id-"
                    onclick="cancelTryOut('DELETEapi-v1-id-proof--idProof_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEapi-v1-id-proof--idProof_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>api/v1/id-proof/{idProof_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEapi-v1-id-proof--idProof_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="DELETEapi-v1-id-proof--idProof_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>idProof_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="idProof_id"                data-endpoint="DELETEapi-v1-id-proof--idProof_id-"
               value="16"
               data-component="url">
    <br>
<p>The ID of the idProof. Example: <code>16</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>idProof</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="idProof"                data-endpoint="DELETEapi-v1-id-proof--idProof_id-"
               value="16"
               data-component="url">
    <br>
<p>ID-proof row id. Example: <code>16</code></p>
            </div>
                    </form>

                <h1 id="ignored">Ignored</h1>

    

                                <h2 id="ignored-GETapi-v1-ignored">GET api/v1/ignored</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-GETapi-v1-ignored">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/ignored?page=16&amp;per_page=16" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/ignored"
);

const params = {
    "page": "16",
    "per_page": "16",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-ignored">
            <blockquote>
            <p>Example response (200, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
  &quot;success&quot;: true,
  &quot;data&quot;: [{&quot;matri_id&quot;: &quot;AM000201&quot;, ...}],
  &quot;meta&quot;: {&quot;page&quot;: 1, &quot;per_page&quot;: 20, &quot;total&quot;: 1, &quot;last_page&quot;: 1}
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, no-profile):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;PROFILE_REQUIRED&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-ignored" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-ignored"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-ignored"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-ignored" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-ignored">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-ignored" data-method="GET"
      data-path="api/v1/ignored"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-ignored', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-ignored"
                    onclick="tryItOut('GETapi-v1-ignored');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-ignored"
                    onclick="cancelTryOut('GETapi-v1-ignored');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-ignored"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/ignored</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-ignored"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-ignored"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Query Parameters</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="page"                data-endpoint="GETapi-v1-ignored"
               value="16"
               data-component="query">
    <br>
<p>Optional. Default 1. Example: <code>16</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>per_page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="per_page"                data-endpoint="GETapi-v1-ignored"
               value="16"
               data-component="query">
    <br>
<p>Optional. 1-50. Default 20. Example: <code>16</code></p>
            </div>
                </form>

                    <h2 id="ignored-POSTapi-v1-profiles--matriId--ignore-toggle">Toggle ignore for a target profile. Each call flips the state;
response carries the authoritative new state.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-v1-profiles--matriId--ignore-toggle">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/profiles/architecto/ignore-toggle" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/profiles/architecto/ignore-toggle"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-profiles--matriId--ignore-toggle">
            <blockquote>
            <p>Example response (200, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;is_ignored&quot;: true
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (404, not-found):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;NOT_FOUND&quot;,
        &quot;message&quot;: &quot;Profile not available.&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, invalid-target):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;INVALID_TARGET&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, no-profile):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;PROFILE_REQUIRED&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-profiles--matriId--ignore-toggle" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-profiles--matriId--ignore-toggle"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-profiles--matriId--ignore-toggle"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-profiles--matriId--ignore-toggle" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-profiles--matriId--ignore-toggle">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-profiles--matriId--ignore-toggle" data-method="POST"
      data-path="api/v1/profiles/{matriId}/ignore-toggle"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-profiles--matriId--ignore-toggle', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-profiles--matriId--ignore-toggle"
                    onclick="tryItOut('POSTapi-v1-profiles--matriId--ignore-toggle');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-profiles--matriId--ignore-toggle"
                    onclick="cancelTryOut('POSTapi-v1-profiles--matriId--ignore-toggle');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-profiles--matriId--ignore-toggle"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/profiles/{matriId}/ignore-toggle</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-profiles--matriId--ignore-toggle"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-profiles--matriId--ignore-toggle"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>matriId</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="matriId"                data-endpoint="POSTapi-v1-profiles--matriId--ignore-toggle"
               value="architecto"
               data-component="url">
    <br>
<p>Target's matri_id. Example: <code>architecto</code></p>
            </div>
                    </form>

                <h1 id="interests">Interests</h1>

    

                                <h2 id="interests-GETapi-v1-interests">GET api/v1/interests</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-GETapi-v1-interests">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/interests?tab=architecto&amp;per_page=16" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/interests"
);

const params = {
    "tab": "architecto",
    "per_page": "16",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-interests">
            <blockquote>
            <p>Example response (200, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: [
        {
            &quot;id&quot;: 42,
            &quot;direction&quot;: &quot;received&quot;,
            &quot;status&quot;: &quot;pending&quot;
        }
    ],
    &quot;meta&quot;: {
        &quot;page&quot;: 1,
        &quot;per_page&quot;: 20,
        &quot;total&quot;: 12,
        &quot;last_page&quot;: 1,
        &quot;tab&quot;: &quot;received&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, no-profile):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;PROFILE_REQUIRED&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-interests" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-interests"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-interests"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-interests" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-interests">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-interests" data-method="GET"
      data-path="api/v1/interests"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-interests', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-interests"
                    onclick="tryItOut('GETapi-v1-interests');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-interests"
                    onclick="cancelTryOut('GETapi-v1-interests');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-interests"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/interests</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-interests"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-interests"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Query Parameters</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>tab</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="tab"                data-endpoint="GETapi-v1-interests"
               value="architecto"
               data-component="query">
    <br>
<p>One of: all (default), received, sent, accepted, declined, starred, trash. Example: <code>architecto</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>per_page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="per_page"                data-endpoint="GETapi-v1-interests"
               value="16"
               data-component="query">
    <br>
<p>Default 20, max 50. Example: <code>16</code></p>
            </div>
                </form>

                    <h2 id="interests-GETapi-v1-interests--interest_id-">GET api/v1/interests/{interest_id}</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-GETapi-v1-interests--interest_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/interests/16" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/interests/16"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-interests--interest_id-">
            <blockquote>
            <p>Example response (200, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;id&quot;: 42,
        &quot;status&quot;: &quot;accepted&quot;,
        &quot;replies&quot;: []
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (403, not-party):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;UNAUTHORIZED&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (404, not-found):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;NOT_FOUND&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-interests--interest_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-interests--interest_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-interests--interest_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-interests--interest_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-interests--interest_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-interests--interest_id-" data-method="GET"
      data-path="api/v1/interests/{interest_id}"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-interests--interest_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-interests--interest_id-"
                    onclick="tryItOut('GETapi-v1-interests--interest_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-interests--interest_id-"
                    onclick="cancelTryOut('GETapi-v1-interests--interest_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-interests--interest_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/interests/{interest_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-interests--interest_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-interests--interest_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>interest_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="interest_id"                data-endpoint="GETapi-v1-interests--interest_id-"
               value="16"
               data-component="url">
    <br>
<p>The ID of the interest. Example: <code>16</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>interest</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="interest"                data-endpoint="GETapi-v1-interests--interest_id-"
               value="16"
               data-component="url">
    <br>
<p>Interest id. Example: <code>16</code></p>
            </div>
                    </form>

                    <h2 id="interests-POSTapi-v1-profiles--matriId--interest">POST api/v1/profiles/{matriId}/interest</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-v1-profiles--matriId--interest">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/profiles/architecto/interest" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"template_id\": \"architecto\",
    \"custom_message\": \"architecto\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/profiles/architecto/interest"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "template_id": "architecto",
    "custom_message": "architecto"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-profiles--matriId--interest">
            <blockquote>
            <p>Example response (201, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;id&quot;: 42,
        &quot;status&quot;: &quot;pending&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (404, target-not-found):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;NOT_FOUND&quot;,
        &quot;message&quot;: &quot;Profile not available.&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, invalid-interest):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;INVALID_INTEREST&quot;,
        &quot;message&quot;: &quot;Daily interest limit reached...&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, no-profile):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;PROFILE_REQUIRED&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-profiles--matriId--interest" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-profiles--matriId--interest"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-profiles--matriId--interest"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-profiles--matriId--interest" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-profiles--matriId--interest">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-profiles--matriId--interest" data-method="POST"
      data-path="api/v1/profiles/{matriId}/interest"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-profiles--matriId--interest', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-profiles--matriId--interest"
                    onclick="tryItOut('POSTapi-v1-profiles--matriId--interest');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-profiles--matriId--interest"
                    onclick="cancelTryOut('POSTapi-v1-profiles--matriId--interest');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-profiles--matriId--interest"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/profiles/{matriId}/interest</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-profiles--matriId--interest"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-profiles--matriId--interest"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>matriId</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="matriId"                data-endpoint="POSTapi-v1-profiles--matriId--interest"
               value="architecto"
               data-component="url">
    <br>
<p>Target's matri_id. Example: <code>architecto</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>template_id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="template_id"                data-endpoint="POSTapi-v1-profiles--matriId--interest"
               value="architecto"
               data-component="body">
    <br>
<p>Opaque template identifier (optional). Example: <code>architecto</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>custom_message</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="custom_message"                data-endpoint="POSTapi-v1-profiles--matriId--interest"
               value="architecto"
               data-component="body">
    <br>
<p>Personalized text (optional, premium-gated unless target's plan has allows_free_member_chat=true). Example: <code>architecto</code></p>
        </div>
        </form>

                    <h2 id="interests-POSTapi-v1-interests--interest_id--accept">POST api/v1/interests/{interest_id}/accept</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-v1-interests--interest_id--accept">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/interests/16/accept" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"template_id\": \"architecto\",
    \"custom_message\": \"architecto\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/interests/16/accept"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "template_id": "architecto",
    "custom_message": "architecto"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-interests--interest_id--accept">
            <blockquote>
            <p>Example response (200, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;id&quot;: 42,
        &quot;status&quot;: &quot;accepted&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (403, not-receiver):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;UNAUTHORIZED&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, not-pending):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;INVALID_INTEREST&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-interests--interest_id--accept" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-interests--interest_id--accept"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-interests--interest_id--accept"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-interests--interest_id--accept" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-interests--interest_id--accept">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-interests--interest_id--accept" data-method="POST"
      data-path="api/v1/interests/{interest_id}/accept"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-interests--interest_id--accept', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-interests--interest_id--accept"
                    onclick="tryItOut('POSTapi-v1-interests--interest_id--accept');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-interests--interest_id--accept"
                    onclick="cancelTryOut('POSTapi-v1-interests--interest_id--accept');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-interests--interest_id--accept"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/interests/{interest_id}/accept</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-interests--interest_id--accept"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-interests--interest_id--accept"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>interest_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="interest_id"                data-endpoint="POSTapi-v1-interests--interest_id--accept"
               value="16"
               data-component="url">
    <br>
<p>The ID of the interest. Example: <code>16</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>template_id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="template_id"                data-endpoint="POSTapi-v1-interests--interest_id--accept"
               value="architecto"
               data-component="body">
    <br>
<p>Optional reply template id. Example: <code>architecto</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>custom_message</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="custom_message"                data-endpoint="POSTapi-v1-interests--interest_id--accept"
               value="architecto"
               data-component="body">
    <br>
<p>Optional acceptance message text. Example: <code>architecto</code></p>
        </div>
        </form>

                    <h2 id="interests-POSTapi-v1-interests--interest_id--decline">POST api/v1/interests/{interest_id}/decline</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-v1-interests--interest_id--decline">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/interests/16/decline" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"template_id\": \"architecto\",
    \"custom_message\": \"architecto\",
    \"silent\": false
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/interests/16/decline"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "template_id": "architecto",
    "custom_message": "architecto",
    "silent": false
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-interests--interest_id--decline">
            <blockquote>
            <p>Example response (200, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;id&quot;: 42,
        &quot;status&quot;: &quot;declined&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (403, not-receiver):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;UNAUTHORIZED&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, not-pending):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;INVALID_INTEREST&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-interests--interest_id--decline" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-interests--interest_id--decline"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-interests--interest_id--decline"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-interests--interest_id--decline" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-interests--interest_id--decline">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-interests--interest_id--decline" data-method="POST"
      data-path="api/v1/interests/{interest_id}/decline"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-interests--interest_id--decline', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-interests--interest_id--decline"
                    onclick="tryItOut('POSTapi-v1-interests--interest_id--decline');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-interests--interest_id--decline"
                    onclick="cancelTryOut('POSTapi-v1-interests--interest_id--decline');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-interests--interest_id--decline"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/interests/{interest_id}/decline</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-interests--interest_id--decline"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-interests--interest_id--decline"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>interest_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="interest_id"                data-endpoint="POSTapi-v1-interests--interest_id--decline"
               value="16"
               data-component="url">
    <br>
<p>The ID of the interest. Example: <code>16</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>template_id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="template_id"                data-endpoint="POSTapi-v1-interests--interest_id--decline"
               value="architecto"
               data-component="body">
    <br>
<p>Optional reply template id. Example: <code>architecto</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>custom_message</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="custom_message"                data-endpoint="POSTapi-v1-interests--interest_id--decline"
               value="architecto"
               data-component="body">
    <br>
<p>Optional decline message text. Example: <code>architecto</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>silent</code></b>&nbsp;&nbsp;
<small>boolean</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <label data-endpoint="POSTapi-v1-interests--interest_id--decline" style="display: none">
            <input type="radio" name="silent"
                   value="true"
                   data-endpoint="POSTapi-v1-interests--interest_id--decline"
                   data-component="body"             >
            <code>true</code>
        </label>
        <label data-endpoint="POSTapi-v1-interests--interest_id--decline" style="display: none">
            <input type="radio" name="silent"
                   value="false"
                   data-endpoint="POSTapi-v1-interests--interest_id--decline"
                   data-component="body"             >
            <code>false</code>
        </label>
    <br>
<p>If true, no notification fires to the sender. Default false. Example: <code>false</code></p>
        </div>
        </form>

                    <h2 id="interests-POSTapi-v1-interests--interest_id--cancel">Sender cancels a pending interest within the cancel window.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-v1-interests--interest_id--cancel">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/interests/16/cancel" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/interests/16/cancel"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-interests--interest_id--cancel">
            <blockquote>
            <p>Example response (200, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;id&quot;: 42,
        &quot;status&quot;: &quot;cancelled&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (403, not-sender):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;UNAUTHORIZED&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, not-pending):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;INVALID_INTEREST&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, window-expired):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;CANCEL_WINDOW_EXPIRED&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-interests--interest_id--cancel" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-interests--interest_id--cancel"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-interests--interest_id--cancel"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-interests--interest_id--cancel" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-interests--interest_id--cancel">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-interests--interest_id--cancel" data-method="POST"
      data-path="api/v1/interests/{interest_id}/cancel"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-interests--interest_id--cancel', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-interests--interest_id--cancel"
                    onclick="tryItOut('POSTapi-v1-interests--interest_id--cancel');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-interests--interest_id--cancel"
                    onclick="cancelTryOut('POSTapi-v1-interests--interest_id--cancel');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-interests--interest_id--cancel"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/interests/{interest_id}/cancel</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-interests--interest_id--cancel"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-interests--interest_id--cancel"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>interest_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="interest_id"                data-endpoint="POSTapi-v1-interests--interest_id--cancel"
               value="16"
               data-component="url">
    <br>
<p>The ID of the interest. Example: <code>16</code></p>
            </div>
                    </form>

                    <h2 id="interests-POSTapi-v1-interests--interest_id--star">Toggle the viewer-side star flag (is_starred_by_sender for the
sender, is_starred_by_receiver for the receiver). Used for
favourite-marking interests in the inbox.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-v1-interests--interest_id--star">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/interests/16/star" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/interests/16/star"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-interests--interest_id--star">
            <blockquote>
            <p>Example response (200, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;id&quot;: 42,
        &quot;is_starred&quot;: true
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (403, not-party):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;UNAUTHORIZED&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-interests--interest_id--star" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-interests--interest_id--star"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-interests--interest_id--star"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-interests--interest_id--star" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-interests--interest_id--star">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-interests--interest_id--star" data-method="POST"
      data-path="api/v1/interests/{interest_id}/star"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-interests--interest_id--star', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-interests--interest_id--star"
                    onclick="tryItOut('POSTapi-v1-interests--interest_id--star');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-interests--interest_id--star"
                    onclick="cancelTryOut('POSTapi-v1-interests--interest_id--star');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-interests--interest_id--star"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/interests/{interest_id}/star</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-interests--interest_id--star"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-interests--interest_id--star"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>interest_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="interest_id"                data-endpoint="POSTapi-v1-interests--interest_id--star"
               value="16"
               data-component="url">
    <br>
<p>The ID of the interest. Example: <code>16</code></p>
            </div>
                    </form>

                    <h2 id="interests-POSTapi-v1-interests--interest_id--trash">Toggle the viewer-side trash flag. Trashed interests are hidden
from the default &quot;all&quot; inbox tab but still visible in &quot;trash&quot;.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-v1-interests--interest_id--trash">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/interests/16/trash" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/interests/16/trash"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-interests--interest_id--trash">
            <blockquote>
            <p>Example response (200, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;id&quot;: 42,
        &quot;is_trashed&quot;: true
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (403, not-party):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;UNAUTHORIZED&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-interests--interest_id--trash" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-interests--interest_id--trash"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-interests--interest_id--trash"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-interests--interest_id--trash" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-interests--interest_id--trash">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-interests--interest_id--trash" data-method="POST"
      data-path="api/v1/interests/{interest_id}/trash"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-interests--interest_id--trash', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-interests--interest_id--trash"
                    onclick="tryItOut('POSTapi-v1-interests--interest_id--trash');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-interests--interest_id--trash"
                    onclick="cancelTryOut('POSTapi-v1-interests--interest_id--trash');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-interests--interest_id--trash"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/interests/{interest_id}/trash</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-interests--interest_id--trash"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-interests--interest_id--trash"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>interest_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="interest_id"                data-endpoint="POSTapi-v1-interests--interest_id--trash"
               value="16"
               data-component="url">
    <br>
<p>The ID of the interest. Example: <code>16</code></p>
            </div>
                    </form>

                    <h2 id="interests-POSTapi-v1-interests--interest_id--messages">Send a chat reply in an accepted interest thread. Premium-gated
via InterestService::sendMessage — but if the OTHER party holds
a plan with allows_free_member_chat=true, free senders may also
reply (Bharat-Platinum convention; see Commit A).</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-v1-interests--interest_id--messages">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/interests/16/messages" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"message\": \"architecto\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/interests/16/messages"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "message": "architecto"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-interests--interest_id--messages">
            <blockquote>
            <p>Example response (201, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;reply_id&quot;: 7,
        &quot;interest_id&quot;: 42
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (403, not-party):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;UNAUTHORIZED&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, not-accepted):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;INVALID_INTEREST&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, not-premium):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;INVALID_INTEREST&quot;,
        &quot;message&quot;: &quot;Upgrade to a paid plan to send messages.&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (429, throttled):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;THROTTLED&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-interests--interest_id--messages" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-interests--interest_id--messages"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-interests--interest_id--messages"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-interests--interest_id--messages" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-interests--interest_id--messages">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-interests--interest_id--messages" data-method="POST"
      data-path="api/v1/interests/{interest_id}/messages"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-interests--interest_id--messages', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-interests--interest_id--messages"
                    onclick="tryItOut('POSTapi-v1-interests--interest_id--messages');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-interests--interest_id--messages"
                    onclick="cancelTryOut('POSTapi-v1-interests--interest_id--messages');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-interests--interest_id--messages"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/interests/{interest_id}/messages</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-interests--interest_id--messages"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-interests--interest_id--messages"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>interest_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="interest_id"                data-endpoint="POSTapi-v1-interests--interest_id--messages"
               value="16"
               data-component="url">
    <br>
<p>The ID of the interest. Example: <code>16</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>message</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="message"                data-endpoint="POSTapi-v1-interests--interest_id--messages"
               value="architecto"
               data-component="body">
    <br>
<p>Message text (1-2000 chars). Example: <code>architecto</code></p>
        </div>
        </form>

                    <h2 id="interests-GETapi-v1-interests--interest_id--messages">List replies in an interest thread (chat polling). Flutter calls
this on a timer (~10s) while the chat screen is open.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Cursor-style pagination via <code>?after=N</code> — returns replies with
<code>id &gt; after</code>, ordered by id ascending. Initial poll uses
<code>?after=0</code> (or omits) to fetch the whole history; subsequent
polls send the previous response's <code>latest_message_id</code>.</p>
<p>Returns the current thread status alongside so Flutter can react
if the interest was blocked / cancelled while the user was idle.</p>
<p>Pairs with POST /interests/{interest}/messages from step-1 —
write counterpart is <code>reply()</code>, read counterpart is this method.</p>

<span id="example-requests-GETapi-v1-interests--interest_id--messages">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/interests/16/messages?after=16&amp;limit=16" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/interests/16/messages"
);

const params = {
    "after": "16",
    "limit": "16",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-interests--interest_id--messages">
            <blockquote>
            <p>Example response (200, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;replies&quot;: [
            {
                &quot;id&quot;: 7,
                &quot;from&quot;: &quot;me&quot;,
                &quot;type&quot;: &quot;message&quot;,
                &quot;template_id&quot;: null,
                &quot;text&quot;: &quot;Hi!&quot;,
                &quot;created_at&quot;: &quot;2026-04-25T...&quot;
            }
        ],
        &quot;latest_message_id&quot;: 7,
        &quot;thread_status&quot;: &quot;accepted&quot;,
        &quot;polled_at&quot;: &quot;2026-04-25T...&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (403, not-party):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;UNAUTHORIZED&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, no-profile):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;PROFILE_REQUIRED&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (429, throttled):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;THROTTLED&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-interests--interest_id--messages" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-interests--interest_id--messages"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-interests--interest_id--messages"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-interests--interest_id--messages" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-interests--interest_id--messages">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-interests--interest_id--messages" data-method="GET"
      data-path="api/v1/interests/{interest_id}/messages"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-interests--interest_id--messages', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-interests--interest_id--messages"
                    onclick="tryItOut('GETapi-v1-interests--interest_id--messages');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-interests--interest_id--messages"
                    onclick="cancelTryOut('GETapi-v1-interests--interest_id--messages');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-interests--interest_id--messages"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/interests/{interest_id}/messages</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-interests--interest_id--messages"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-interests--interest_id--messages"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>interest_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="interest_id"                data-endpoint="GETapi-v1-interests--interest_id--messages"
               value="16"
               data-component="url">
    <br>
<p>The ID of the interest. Example: <code>16</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>interest</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="interest"                data-endpoint="GETapi-v1-interests--interest_id--messages"
               value="16"
               data-component="url">
    <br>
<p>Interest id. Example: <code>16</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>Query Parameters</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>after</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="after"                data-endpoint="GETapi-v1-interests--interest_id--messages"
               value="16"
               data-component="query">
    <br>
<p>Return replies with id &gt; this. Default 0 (full history). Example: <code>16</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>limit</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="limit"                data-endpoint="GETapi-v1-interests--interest_id--messages"
               value="16"
               data-component="query">
    <br>
<p>Default 50, max 100. Example: <code>16</code></p>
            </div>
                </form>

                <h1 id="matches">Matches</h1>

    

                                <h2 id="matches-GETapi-v1-matches-my">GET api/v1/matches/my</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-GETapi-v1-matches-my">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/matches/my?page=16&amp;per_page=16" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/matches/my"
);

const params = {
    "page": "16",
    "per_page": "16",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-matches-my">
            <blockquote>
            <p>Example response (200, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: [
        {
            &quot;matri_id&quot;: &quot;AM100042&quot;,
            &quot;match_score&quot;: 87,
            &quot;match_badge&quot;: &quot;great&quot;
        }
    ],
    &quot;meta&quot;: {
        &quot;page&quot;: 1,
        &quot;per_page&quot;: 20,
        &quot;total&quot;: 47,
        &quot;last_page&quot;: 3
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (200, no-preferences):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: [],
    &quot;meta&quot;: {
        &quot;page&quot;: 1,
        &quot;per_page&quot;: 20,
        &quot;total&quot;: 0,
        &quot;last_page&quot;: 1
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (401, unauthenticated):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;UNAUTHENTICATED&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, no-profile):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;PROFILE_REQUIRED&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-matches-my" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-matches-my"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-matches-my"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-matches-my" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-matches-my">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-matches-my" data-method="GET"
      data-path="api/v1/matches/my"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-matches-my', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-matches-my"
                    onclick="tryItOut('GETapi-v1-matches-my');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-matches-my"
                    onclick="cancelTryOut('GETapi-v1-matches-my');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-matches-my"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/matches/my</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-matches-my"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-matches-my"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Query Parameters</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="page"                data-endpoint="GETapi-v1-matches-my"
               value="16"
               data-component="query">
    <br>
<p>Page number (default 1). Example: <code>16</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>per_page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="per_page"                data-endpoint="GETapi-v1-matches-my"
               value="16"
               data-component="query">
    <br>
<p>Results per page (default 20, max 50). Example: <code>16</code></p>
            </div>
                </form>

                    <h2 id="matches-GETapi-v1-matches-mutual">GET api/v1/matches/mutual</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-GETapi-v1-matches-mutual">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/matches/mutual?page=16&amp;per_page=16" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/matches/mutual"
);

const params = {
    "page": "16",
    "per_page": "16",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-matches-mutual">
            <blockquote>
            <p>Example response (200, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: [
        {
            &quot;matri_id&quot;: &quot;AM100042&quot;,
            &quot;match_score&quot;: 91,
            &quot;match_badge&quot;: &quot;great&quot;
        }
    ],
    &quot;meta&quot;: {
        &quot;page&quot;: 1,
        &quot;per_page&quot;: 20,
        &quot;total&quot;: 12,
        &quot;last_page&quot;: 1
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, no-profile):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;PROFILE_REQUIRED&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-matches-mutual" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-matches-mutual"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-matches-mutual"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-matches-mutual" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-matches-mutual">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-matches-mutual" data-method="GET"
      data-path="api/v1/matches/mutual"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-matches-mutual', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-matches-mutual"
                    onclick="tryItOut('GETapi-v1-matches-mutual');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-matches-mutual"
                    onclick="cancelTryOut('GETapi-v1-matches-mutual');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-matches-mutual"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/matches/mutual</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-matches-mutual"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-matches-mutual"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Query Parameters</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="page"                data-endpoint="GETapi-v1-matches-mutual"
               value="16"
               data-component="query">
    <br>
<p>Page number (default 1). Example: <code>16</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>per_page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="per_page"                data-endpoint="GETapi-v1-matches-mutual"
               value="16"
               data-component="query">
    <br>
<p>Results per page (default 20, max 50). Example: <code>16</code></p>
            </div>
                </form>

                    <h2 id="matches-GETapi-v1-matches-score--matriId-">GET api/v1/matches/score/{matriId}</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-GETapi-v1-matches-score--matriId-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/matches/score/architecto" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/matches/score/architecto"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-matches-score--matriId-">
            <blockquote>
            <p>Example response (200, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;score&quot;: 87,
        &quot;breakdown&quot;: [
            {
                &quot;criterion&quot;: &quot;religion&quot;,
                &quot;label&quot;: &quot;Religion&quot;,
                &quot;weight&quot;: 15,
                &quot;matched&quot;: true
            }
        ],
        &quot;badge&quot;: &quot;great&quot;,
        &quot;cached&quot;: false
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (403, same-gender):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;GENDER_MISMATCH&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (404, not-found-or-restricted):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;NOT_FOUND&quot;,
        &quot;message&quot;: &quot;Profile not available.&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, no-profile):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;PROFILE_REQUIRED&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, no-preferences):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;PREFERENCES_REQUIRED&quot;,
        &quot;message&quot;: &quot;Set partner preferences before scoring matches.&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (429, throttled):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;THROTTLED&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-matches-score--matriId-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-matches-score--matriId-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-matches-score--matriId-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-matches-score--matriId-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-matches-score--matriId-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-matches-score--matriId-" data-method="GET"
      data-path="api/v1/matches/score/{matriId}"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-matches-score--matriId-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-matches-score--matriId-"
                    onclick="tryItOut('GETapi-v1-matches-score--matriId-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-matches-score--matriId-"
                    onclick="cancelTryOut('GETapi-v1-matches-score--matriId-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-matches-score--matriId-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/matches/score/{matriId}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-matches-score--matriId-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-matches-score--matriId-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>matriId</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="matriId"                data-endpoint="GETapi-v1-matches-score--matriId-"
               value="architecto"
               data-component="url">
    <br>
<p>The target's matri_id. Example: <code>architecto</code></p>
            </div>
                    </form>

                <h1 id="membership">Membership</h1>

    

                                <h2 id="membership-GETapi-v1-membership-plans">GET api/v1/membership/plans</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-membership-plans">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/membership/plans" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/membership/plans"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-membership-plans">
            <blockquote>
            <p>Example response (200, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: [
        {
            &quot;id&quot;: 5,
            &quot;slug&quot;: &quot;diamond-plus&quot;,
            &quot;name&quot;: &quot;Diamond Plus&quot;,
            &quot;duration_months&quot;: 6,
            &quot;price_inr&quot;: 2999,
            &quot;strike_price_inr&quot;: 4999,
            &quot;discount_pct&quot;: 40,
            &quot;daily_interest_limit&quot;: 50,
            &quot;view_contacts_limit&quot;: 0,
            &quot;daily_contact_views&quot;: 0,
            &quot;can_view_contact&quot;: true,
            &quot;personalized_messages&quot;: false,
            &quot;allows_free_member_chat&quot;: true,
            &quot;exposes_contact_to_free&quot;: true,
            &quot;featured_profile&quot;: false,
            &quot;priority_support&quot;: false,
            &quot;is_popular&quot;: false,
            &quot;is_highlighted&quot;: false,
            &quot;features&quot;: [
                &quot;50 interests/day&quot;
            ]
        }
    ]
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-membership-plans" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-membership-plans"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-membership-plans"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-membership-plans" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-membership-plans">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-membership-plans" data-method="GET"
      data-path="api/v1/membership/plans"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-membership-plans', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-membership-plans"
                    onclick="tryItOut('GETapi-v1-membership-plans');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-membership-plans"
                    onclick="cancelTryOut('GETapi-v1-membership-plans');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-membership-plans"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/membership/plans</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-membership-plans"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-membership-plans"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="membership-GETapi-v1-membership-me">GET api/v1/membership/me</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-GETapi-v1-membership-me">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/membership/me" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/membership/me"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-membership-me">
            <blockquote>
            <p>Example response (200, paid-member):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;membership&quot;: {
            &quot;plan_id&quot;: 5,
            &quot;plan_name&quot;: &quot;Diamond Plus&quot;,
            &quot;is_premium&quot;: true,
            &quot;starts_at&quot;: &quot;2026-04-01T...&quot;,
            &quot;ends_at&quot;: &quot;2026-10-01T...&quot;,
            &quot;days_remaining&quot;: 159,
            &quot;is_active&quot;: true
        },
        &quot;usage_today&quot;: {
            &quot;interests_sent&quot;: 3,
            &quot;interests_limit&quot;: 50,
            &quot;contacts_viewed&quot;: null,
            &quot;contacts_limit&quot;: 0
        }
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (200, free-tier):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;membership&quot;: {
            &quot;plan_id&quot;: null,
            &quot;plan_name&quot;: &quot;Free&quot;,
            &quot;is_premium&quot;: false,
            &quot;starts_at&quot;: null,
            &quot;ends_at&quot;: null,
            &quot;days_remaining&quot;: null,
            &quot;is_active&quot;: false
        },
        &quot;usage_today&quot;: {
            &quot;interests_sent&quot;: 0,
            &quot;interests_limit&quot;: 5,
            &quot;contacts_viewed&quot;: null,
            &quot;contacts_limit&quot;: 0
        }
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, no-profile):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;PROFILE_REQUIRED&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-membership-me" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-membership-me"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-membership-me"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-membership-me" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-membership-me">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-membership-me" data-method="GET"
      data-path="api/v1/membership/me"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-membership-me', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-membership-me"
                    onclick="tryItOut('GETapi-v1-membership-me');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-membership-me"
                    onclick="cancelTryOut('GETapi-v1-membership-me');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-membership-me"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/membership/me</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-membership-me"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-membership-me"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="membership-POSTapi-v1-membership-coupon-validate">POST api/v1/membership/coupon/validate</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-v1-membership-coupon-validate">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/membership/coupon/validate" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"plan_id\": 16,
    \"coupon_code\": \"architecto\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/membership/coupon/validate"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "plan_id": 16,
    "coupon_code": "architecto"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-membership-coupon-validate">
            <blockquote>
            <p>Example response (200, valid):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;valid&quot;: true,
        &quot;coupon_code&quot;: &quot;WELCOME20&quot;,
        &quot;discount_type&quot;: &quot;percentage&quot;,
        &quot;discount_value&quot;: 20,
        &quot;original_amount_inr&quot;: 2999,
        &quot;discount_amount_inr&quot;: 599,
        &quot;final_amount_inr&quot;: 2400
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (400, invalid-coupon):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;COUPON_INVALID&quot;,
        &quot;message&quot;: &quot;This coupon has expired.&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (404, unknown-plan):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;NOT_FOUND&quot;,
        &quot;message&quot;: &quot;Plan not found.&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, validation-failed):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{&quot;success&quot;: false, &quot;error&quot;: {&quot;code&quot;: &quot;VALIDATION_FAILED&quot;, &quot;message&quot;: &quot;...&quot;, &quot;fields&quot;: {...}}}</code>
 </pre>
            <blockquote>
            <p>Example response (429, throttled):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;THROTTLED&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-membership-coupon-validate" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-membership-coupon-validate"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-membership-coupon-validate"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-membership-coupon-validate" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-membership-coupon-validate">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-membership-coupon-validate" data-method="POST"
      data-path="api/v1/membership/coupon/validate"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-membership-coupon-validate', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-membership-coupon-validate"
                    onclick="tryItOut('POSTapi-v1-membership-coupon-validate');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-membership-coupon-validate"
                    onclick="cancelTryOut('POSTapi-v1-membership-coupon-validate');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-membership-coupon-validate"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/membership/coupon/validate</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-membership-coupon-validate"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-membership-coupon-validate"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>plan_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="plan_id"                data-endpoint="POSTapi-v1-membership-coupon-validate"
               value="16"
               data-component="body">
    <br>
<p>Plan to apply coupon against. Example: <code>16</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>coupon_code</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="coupon_code"                data-endpoint="POSTapi-v1-membership-coupon-validate"
               value="architecto"
               data-component="body">
    <br>
<p>Coupon code (case-insensitive matched). Example: <code>architecto</code></p>
        </div>
        </form>

                <h1 id="notifications">Notifications</h1>

    

                                <h2 id="notifications-GETapi-v1-notifications">Paginated list of the viewer&#039;s notifications, latest first.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-GETapi-v1-notifications">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/notifications?filter=architecto&amp;page=16&amp;per_page=16" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/notifications"
);

const params = {
    "filter": "architecto",
    "page": "16",
    "per_page": "16",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-notifications">
            <blockquote>
            <p>Example response (200, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: [
        {
            &quot;id&quot;: 1,
            &quot;type&quot;: &quot;interest_received&quot;,
            &quot;title&quot;: &quot;...&quot;,
            &quot;message&quot;: &quot;...&quot;,
            &quot;data&quot;: {
                &quot;interest_id&quot;: 42
            },
            &quot;is_read&quot;: false,
            &quot;created_at&quot;: &quot;2026-04-26T...&quot;,
            &quot;icon_type&quot;: &quot;interest&quot;,
            &quot;from_profile_id&quot;: 7
        }
    ],
    &quot;meta&quot;: {
        &quot;page&quot;: 1,
        &quot;per_page&quot;: 20,
        &quot;total&quot;: 1,
        &quot;last_page&quot;: 1,
        &quot;unread_count&quot;: 1
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-notifications" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-notifications"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-notifications"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-notifications" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-notifications">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-notifications" data-method="GET"
      data-path="api/v1/notifications"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-notifications', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-notifications"
                    onclick="tryItOut('GETapi-v1-notifications');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-notifications"
                    onclick="cancelTryOut('GETapi-v1-notifications');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-notifications"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/notifications</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-notifications"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-notifications"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Query Parameters</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>filter</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="filter"                data-endpoint="GETapi-v1-notifications"
               value="architecto"
               data-component="query">
    <br>
<p>Optional. "unread" to limit to unread items. Example: <code>architecto</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="page"                data-endpoint="GETapi-v1-notifications"
               value="16"
               data-component="query">
    <br>
<p>Optional. Pagination page. Default 1. Example: <code>16</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>per_page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="per_page"                data-endpoint="GETapi-v1-notifications"
               value="16"
               data-component="query">
    <br>
<p>Optional. Items per page (1-50). Default 20. Example: <code>16</code></p>
            </div>
                </form>

                    <h2 id="notifications-GETapi-v1-notifications-unread-count">Quick unread-count for the badge. Cheap query — backed by the
`(user_id, is_read)` index on the notifications table.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-GETapi-v1-notifications-unread-count">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/notifications/unread-count" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/notifications/unread-count"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-notifications-unread-count">
            <blockquote>
            <p>Example response (200, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;unread_count&quot;: 12
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-notifications-unread-count" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-notifications-unread-count"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-notifications-unread-count"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-notifications-unread-count" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-notifications-unread-count">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-notifications-unread-count" data-method="GET"
      data-path="api/v1/notifications/unread-count"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-notifications-unread-count', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-notifications-unread-count"
                    onclick="tryItOut('GETapi-v1-notifications-unread-count');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-notifications-unread-count"
                    onclick="cancelTryOut('GETapi-v1-notifications-unread-count');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-notifications-unread-count"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/notifications/unread-count</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-notifications-unread-count"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-notifications-unread-count"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="notifications-POSTapi-v1-notifications-read-all">Mark every unread notification for the viewer as read. Returns
the count that flipped — 0 when the inbox is already empty,
useful for clients to skip a needless badge refresh.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-v1-notifications-read-all">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/notifications/read-all" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/notifications/read-all"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-notifications-read-all">
            <blockquote>
            <p>Example response (200, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;marked_read&quot;: 12
    }
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-notifications-read-all" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-notifications-read-all"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-notifications-read-all"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-notifications-read-all" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-notifications-read-all">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-notifications-read-all" data-method="POST"
      data-path="api/v1/notifications/read-all"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-notifications-read-all', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-notifications-read-all"
                    onclick="tryItOut('POSTapi-v1-notifications-read-all');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-notifications-read-all"
                    onclick="cancelTryOut('POSTapi-v1-notifications-read-all');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-notifications-read-all"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/notifications/read-all</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-notifications-read-all"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-notifications-read-all"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="notifications-POSTapi-v1-notifications--notification_id--read">Mark a single notification read. Idempotent — no-ops on an
already-read row, still returns 200. Returns 403 (not 404) when
the notification belongs to a different user, to avoid leaking
which ids exist.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-v1-notifications--notification_id--read">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/notifications/16/read" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/notifications/16/read"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-notifications--notification_id--read">
            <blockquote>
            <p>Example response (200, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;notification&quot;: {
            &quot;id&quot;: 1,
            &quot;is_read&quot;: true
        }
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (403, not-owner):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;UNAUTHORIZED&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (404, not-found):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;NOT_FOUND&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-notifications--notification_id--read" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-notifications--notification_id--read"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-notifications--notification_id--read"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-notifications--notification_id--read" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-notifications--notification_id--read">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-notifications--notification_id--read" data-method="POST"
      data-path="api/v1/notifications/{notification_id}/read"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-notifications--notification_id--read', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-notifications--notification_id--read"
                    onclick="tryItOut('POSTapi-v1-notifications--notification_id--read');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-notifications--notification_id--read"
                    onclick="cancelTryOut('POSTapi-v1-notifications--notification_id--read');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-notifications--notification_id--read"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/notifications/{notification_id}/read</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-notifications--notification_id--read"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-notifications--notification_id--read"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>notification_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="notification_id"                data-endpoint="POSTapi-v1-notifications--notification_id--read"
               value="16"
               data-component="url">
    <br>
<p>The ID of the notification. Example: <code>16</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>notification</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="notification"                data-endpoint="POSTapi-v1-notifications--notification_id--read"
               value="16"
               data-component="url">
    <br>
<p>Notification id. Example: <code>16</code></p>
            </div>
                    </form>

                <h1 id="onboarding">Onboarding</h1>

    

                                <h2 id="onboarding-POSTapi-v1-onboarding-step-1">POST api/v1/onboarding/step-1</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-v1-onboarding-step-1">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/onboarding/step-1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"personal\": {
        \"weight_kg\": \"bngzmiyvdljnikhw\",
        \"blood_group\": \"aykcmy\",
        \"mother_tongue\": \"u\",
        \"languages_known\": [
            \"w\"
        ],
        \"about_me\": \"p\"
    },
    \"professional\": {
        \"education_detail\": \"w\",
        \"occupation_detail\": \"l\",
        \"employer_name\": \"v\"
    },
    \"family\": {
        \"father_name\": \"q\",
        \"father_house_name\": \"w\",
        \"father_native_place\": \"r\",
        \"father_occupation\": \"s\",
        \"mother_name\": \"i\",
        \"mother_house_name\": \"t\",
        \"mother_native_place\": \"c\",
        \"mother_occupation\": \"p\",
        \"candidate_asset_details\": \"s\",
        \"about_candidate_family\": \"c\",
        \"brothers_married\": 29,
        \"brothers_unmarried\": 50,
        \"brothers_priest\": 16,
        \"sisters_married\": 38,
        \"sisters_unmarried\": 57,
        \"sisters_nun\": 52
    }
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/onboarding/step-1"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "personal": {
        "weight_kg": "bngzmiyvdljnikhw",
        "blood_group": "aykcmy",
        "mother_tongue": "u",
        "languages_known": [
            "w"
        ],
        "about_me": "p"
    },
    "professional": {
        "education_detail": "w",
        "occupation_detail": "l",
        "employer_name": "v"
    },
    "family": {
        "father_name": "q",
        "father_house_name": "w",
        "father_native_place": "r",
        "father_occupation": "s",
        "mother_name": "i",
        "mother_house_name": "t",
        "mother_native_place": "c",
        "mother_occupation": "p",
        "candidate_asset_details": "s",
        "about_candidate_family": "c",
        "brothers_married": 29,
        "brothers_unmarried": 50,
        "brothers_priest": 16,
        "sisters_married": 38,
        "sisters_unmarried": 57,
        "sisters_nun": 52
    }
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-onboarding-step-1">
</span>
<span id="execution-results-POSTapi-v1-onboarding-step-1" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-onboarding-step-1"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-onboarding-step-1"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-onboarding-step-1" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-onboarding-step-1">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-onboarding-step-1" data-method="POST"
      data-path="api/v1/onboarding/step-1"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-onboarding-step-1', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-onboarding-step-1"
                    onclick="tryItOut('POSTapi-v1-onboarding-step-1');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-onboarding-step-1"
                    onclick="cancelTryOut('POSTapi-v1-onboarding-step-1');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-onboarding-step-1"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/onboarding/step-1</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-onboarding-step-1"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-onboarding-step-1"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>personal</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>weight_kg</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="personal.weight_kg"                data-endpoint="POSTapi-v1-onboarding-step-1"
               value="bngzmiyvdljnikhw"
               data-component="body">
    <br>
<p>Personal — match web's existing rules (string weight per schema column). Must not be greater than 20 characters. Example: <code>bngzmiyvdljnikhw</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>blood_group</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="personal.blood_group"                data-endpoint="POSTapi-v1-onboarding-step-1"
               value="aykcmy"
               data-component="body">
    <br>
<p>Must not be greater than 10 characters. Example: <code>aykcmy</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>mother_tongue</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="personal.mother_tongue"                data-endpoint="POSTapi-v1-onboarding-step-1"
               value="u"
               data-component="body">
    <br>
<p>Must not be greater than 50 characters. Example: <code>u</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>languages_known</code></b>&nbsp;&nbsp;
<small>string[]</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="personal.languages_known[0]"                data-endpoint="POSTapi-v1-onboarding-step-1"
               data-component="body">
        <input type="text" style="display: none"
               name="personal.languages_known[1]"                data-endpoint="POSTapi-v1-onboarding-step-1"
               data-component="body">
    <br>
<p>Must not be greater than 50 characters.</p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>about_me</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="personal.about_me"                data-endpoint="POSTapi-v1-onboarding-step-1"
               value="p"
               data-component="body">
    <br>
<p>Must not be greater than 5000 characters. Example: <code>p</code></p>
                    </div>
                                    </details>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>professional</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>education_detail</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="professional.education_detail"                data-endpoint="POSTapi-v1-onboarding-step-1"
               value="w"
               data-component="body">
    <br>
<p>Professional → education_details. Must not be greater than 200 characters. Example: <code>w</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>occupation_detail</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="professional.occupation_detail"                data-endpoint="POSTapi-v1-onboarding-step-1"
               value="l"
               data-component="body">
    <br>
<p>Must not be greater than 200 characters. Example: <code>l</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>employer_name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="professional.employer_name"                data-endpoint="POSTapi-v1-onboarding-step-1"
               value="v"
               data-component="body">
    <br>
<p>Must not be greater than 100 characters. Example: <code>v</code></p>
                    </div>
                                    </details>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>family</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>father_name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="family.father_name"                data-endpoint="POSTapi-v1-onboarding-step-1"
               value="q"
               data-component="body">
    <br>
<p>Family → family_details. Must not be greater than 100 characters. Example: <code>q</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>father_house_name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="family.father_house_name"                data-endpoint="POSTapi-v1-onboarding-step-1"
               value="w"
               data-component="body">
    <br>
<p>Must not be greater than 100 characters. Example: <code>w</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>father_native_place</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="family.father_native_place"                data-endpoint="POSTapi-v1-onboarding-step-1"
               value="r"
               data-component="body">
    <br>
<p>Must not be greater than 100 characters. Example: <code>r</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>father_occupation</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="family.father_occupation"                data-endpoint="POSTapi-v1-onboarding-step-1"
               value="s"
               data-component="body">
    <br>
<p>Must not be greater than 100 characters. Example: <code>s</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>mother_name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="family.mother_name"                data-endpoint="POSTapi-v1-onboarding-step-1"
               value="i"
               data-component="body">
    <br>
<p>Must not be greater than 100 characters. Example: <code>i</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>mother_house_name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="family.mother_house_name"                data-endpoint="POSTapi-v1-onboarding-step-1"
               value="t"
               data-component="body">
    <br>
<p>Must not be greater than 100 characters. Example: <code>t</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>mother_native_place</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="family.mother_native_place"                data-endpoint="POSTapi-v1-onboarding-step-1"
               value="c"
               data-component="body">
    <br>
<p>Must not be greater than 100 characters. Example: <code>c</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>mother_occupation</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="family.mother_occupation"                data-endpoint="POSTapi-v1-onboarding-step-1"
               value="p"
               data-component="body">
    <br>
<p>Must not be greater than 100 characters. Example: <code>p</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>candidate_asset_details</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="family.candidate_asset_details"                data-endpoint="POSTapi-v1-onboarding-step-1"
               value="s"
               data-component="body">
    <br>
<p>Must not be greater than 500 characters. Example: <code>s</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>about_candidate_family</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="family.about_candidate_family"                data-endpoint="POSTapi-v1-onboarding-step-1"
               value="c"
               data-component="body">
    <br>
<p>Must not be greater than 5000 characters. Example: <code>c</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>brothers_married</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="family.brothers_married"                data-endpoint="POSTapi-v1-onboarding-step-1"
               value="29"
               data-component="body">
    <br>
<p>Must be at least 0. Example: <code>29</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>brothers_unmarried</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="family.brothers_unmarried"                data-endpoint="POSTapi-v1-onboarding-step-1"
               value="50"
               data-component="body">
    <br>
<p>Must be at least 0. Example: <code>50</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>brothers_priest</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="family.brothers_priest"                data-endpoint="POSTapi-v1-onboarding-step-1"
               value="16"
               data-component="body">
    <br>
<p>Must be at least 0. Example: <code>16</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>sisters_married</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="family.sisters_married"                data-endpoint="POSTapi-v1-onboarding-step-1"
               value="38"
               data-component="body">
    <br>
<p>Must be at least 0. Example: <code>38</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>sisters_unmarried</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="family.sisters_unmarried"                data-endpoint="POSTapi-v1-onboarding-step-1"
               value="57"
               data-component="body">
    <br>
<p>Must be at least 0. Example: <code>57</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>sisters_nun</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="family.sisters_nun"                data-endpoint="POSTapi-v1-onboarding-step-1"
               value="52"
               data-component="body">
    <br>
<p>Must be at least 0. Example: <code>52</code></p>
                    </div>
                                    </details>
        </div>
        </form>

                    <h2 id="onboarding-POSTapi-v1-onboarding-step-2">POST api/v1/onboarding/step-2</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-v1-onboarding-step-2">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/onboarding/step-2" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"location\": {
        \"residing_country\": \"b\",
        \"residency_status\": \"n\",
        \"outstation_leave_date_from\": \"2026-04-26T17:40:14\",
        \"outstation_leave_date_to\": \"2052-05-19\"
    },
    \"contact\": {
        \"residential_phone_number\": \"ngzmiyvdljnikhwa\",
        \"secondary_phone\": \"y\",
        \"preferred_call_time\": \"k\",
        \"alternate_email\": \"lyric80@example.com\",
        \"reference_name\": \"p\",
        \"reference_relationship\": \"w\",
        \"reference_mobile\": \"l\",
        \"present_address_same_as_comm\": false,
        \"present_address\": \"v\",
        \"present_pin_zip_code\": \"qwrsit\",
        \"permanent_address_same_as_comm\": true,
        \"permanent_address_same_as_present\": false,
        \"permanent_address\": \"c\",
        \"permanent_pin_zip_code\": \"pscqld\"
    }
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/onboarding/step-2"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "location": {
        "residing_country": "b",
        "residency_status": "n",
        "outstation_leave_date_from": "2026-04-26T17:40:14",
        "outstation_leave_date_to": "2052-05-19"
    },
    "contact": {
        "residential_phone_number": "ngzmiyvdljnikhwa",
        "secondary_phone": "y",
        "preferred_call_time": "k",
        "alternate_email": "lyric80@example.com",
        "reference_name": "p",
        "reference_relationship": "w",
        "reference_mobile": "l",
        "present_address_same_as_comm": false,
        "present_address": "v",
        "present_pin_zip_code": "qwrsit",
        "permanent_address_same_as_comm": true,
        "permanent_address_same_as_present": false,
        "permanent_address": "c",
        "permanent_pin_zip_code": "pscqld"
    }
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-onboarding-step-2">
</span>
<span id="execution-results-POSTapi-v1-onboarding-step-2" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-onboarding-step-2"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-onboarding-step-2"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-onboarding-step-2" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-onboarding-step-2">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-onboarding-step-2" data-method="POST"
      data-path="api/v1/onboarding/step-2"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-onboarding-step-2', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-onboarding-step-2"
                    onclick="tryItOut('POSTapi-v1-onboarding-step-2');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-onboarding-step-2"
                    onclick="cancelTryOut('POSTapi-v1-onboarding-step-2');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-onboarding-step-2"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/onboarding/step-2</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-onboarding-step-2"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-onboarding-step-2"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>location</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>residing_country</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="location.residing_country"                data-endpoint="POSTapi-v1-onboarding-step-2"
               value="b"
               data-component="body">
    <br>
<p>Location → location_infos. Must not be greater than 100 characters. Example: <code>b</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>residency_status</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="location.residency_status"                data-endpoint="POSTapi-v1-onboarding-step-2"
               value="n"
               data-component="body">
    <br>
<p>Must not be greater than 50 characters. Example: <code>n</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>outstation_leave_date_from</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="location.outstation_leave_date_from"                data-endpoint="POSTapi-v1-onboarding-step-2"
               value="2026-04-26T17:40:14"
               data-component="body">
    <br>
<p>Must be a valid date. Example: <code>2026-04-26T17:40:14</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>outstation_leave_date_to</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="location.outstation_leave_date_to"                data-endpoint="POSTapi-v1-onboarding-step-2"
               value="2052-05-19"
               data-component="body">
    <br>
<p>Must be a valid date. Must be a date after or equal to <code>location.outstation_leave_date_from</code>. Example: <code>2052-05-19</code></p>
                    </div>
                                    </details>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>contact</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>residential_phone_number</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="contact.residential_phone_number"                data-endpoint="POSTapi-v1-onboarding-step-2"
               value="ngzmiyvdljnikhwa"
               data-component="body">
    <br>
<p>Contact → contact_infos. Must not be greater than 20 characters. Example: <code>ngzmiyvdljnikhwa</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>secondary_phone</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="contact.secondary_phone"                data-endpoint="POSTapi-v1-onboarding-step-2"
               value="y"
               data-component="body">
    <br>
<p>Must not be greater than 15 characters. Example: <code>y</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>preferred_call_time</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="contact.preferred_call_time"                data-endpoint="POSTapi-v1-onboarding-step-2"
               value="k"
               data-component="body">
    <br>
<p>Must not be greater than 30 characters. Example: <code>k</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>alternate_email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="contact.alternate_email"                data-endpoint="POSTapi-v1-onboarding-step-2"
               value="lyric80@example.com"
               data-component="body">
    <br>
<p>Must be a valid email address. Must not be greater than 150 characters. Example: <code>lyric80@example.com</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>reference_name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="contact.reference_name"                data-endpoint="POSTapi-v1-onboarding-step-2"
               value="p"
               data-component="body">
    <br>
<p>Must not be greater than 100 characters. Example: <code>p</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>reference_relationship</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="contact.reference_relationship"                data-endpoint="POSTapi-v1-onboarding-step-2"
               value="w"
               data-component="body">
    <br>
<p>Must not be greater than 50 characters. Example: <code>w</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>reference_mobile</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="contact.reference_mobile"                data-endpoint="POSTapi-v1-onboarding-step-2"
               value="l"
               data-component="body">
    <br>
<p>Must not be greater than 15 characters. Example: <code>l</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>present_address_same_as_comm</code></b>&nbsp;&nbsp;
<small>boolean</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <label data-endpoint="POSTapi-v1-onboarding-step-2" style="display: none">
            <input type="radio" name="contact.present_address_same_as_comm"
                   value="true"
                   data-endpoint="POSTapi-v1-onboarding-step-2"
                   data-component="body"             >
            <code>true</code>
        </label>
        <label data-endpoint="POSTapi-v1-onboarding-step-2" style="display: none">
            <input type="radio" name="contact.present_address_same_as_comm"
                   value="false"
                   data-endpoint="POSTapi-v1-onboarding-step-2"
                   data-component="body"             >
            <code>false</code>
        </label>
    <br>
<p>Example: <code>false</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>present_address</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="contact.present_address"                data-endpoint="POSTapi-v1-onboarding-step-2"
               value="v"
               data-component="body">
    <br>
<p>Must not be greater than 200 characters. Example: <code>v</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>present_pin_zip_code</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="contact.present_pin_zip_code"                data-endpoint="POSTapi-v1-onboarding-step-2"
               value="qwrsit"
               data-component="body">
    <br>
<p>Must not be greater than 10 characters. Example: <code>qwrsit</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>permanent_address_same_as_comm</code></b>&nbsp;&nbsp;
<small>boolean</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <label data-endpoint="POSTapi-v1-onboarding-step-2" style="display: none">
            <input type="radio" name="contact.permanent_address_same_as_comm"
                   value="true"
                   data-endpoint="POSTapi-v1-onboarding-step-2"
                   data-component="body"             >
            <code>true</code>
        </label>
        <label data-endpoint="POSTapi-v1-onboarding-step-2" style="display: none">
            <input type="radio" name="contact.permanent_address_same_as_comm"
                   value="false"
                   data-endpoint="POSTapi-v1-onboarding-step-2"
                   data-component="body"             >
            <code>false</code>
        </label>
    <br>
<p>Example: <code>true</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>permanent_address_same_as_present</code></b>&nbsp;&nbsp;
<small>boolean</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <label data-endpoint="POSTapi-v1-onboarding-step-2" style="display: none">
            <input type="radio" name="contact.permanent_address_same_as_present"
                   value="true"
                   data-endpoint="POSTapi-v1-onboarding-step-2"
                   data-component="body"             >
            <code>true</code>
        </label>
        <label data-endpoint="POSTapi-v1-onboarding-step-2" style="display: none">
            <input type="radio" name="contact.permanent_address_same_as_present"
                   value="false"
                   data-endpoint="POSTapi-v1-onboarding-step-2"
                   data-component="body"             >
            <code>false</code>
        </label>
    <br>
<p>Example: <code>false</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>permanent_address</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="contact.permanent_address"                data-endpoint="POSTapi-v1-onboarding-step-2"
               value="c"
               data-component="body">
    <br>
<p>Must not be greater than 200 characters. Example: <code>c</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>permanent_pin_zip_code</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="contact.permanent_pin_zip_code"                data-endpoint="POSTapi-v1-onboarding-step-2"
               value="pscqld"
               data-component="body">
    <br>
<p>Must not be greater than 10 characters. Example: <code>pscqld</code></p>
                    </div>
                                    </details>
        </div>
        </form>

                    <h2 id="onboarding-POSTapi-v1-onboarding-partner-preferences">POST api/v1/onboarding/partner-preferences</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-v1-onboarding-partner-preferences">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/onboarding/partner-preferences" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"age_from\": 1,
    \"age_to\": 22,
    \"height_from_cm\": 7,
    \"height_to_cm\": 16,
    \"about_partner\": \"m\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/onboarding/partner-preferences"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "age_from": 1,
    "age_to": 22,
    "height_from_cm": 7,
    "height_to_cm": 16,
    "about_partner": "m"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-onboarding-partner-preferences">
</span>
<span id="execution-results-POSTapi-v1-onboarding-partner-preferences" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-onboarding-partner-preferences"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-onboarding-partner-preferences"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-onboarding-partner-preferences" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-onboarding-partner-preferences">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-onboarding-partner-preferences" data-method="POST"
      data-path="api/v1/onboarding/partner-preferences"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-onboarding-partner-preferences', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-onboarding-partner-preferences"
                    onclick="tryItOut('POSTapi-v1-onboarding-partner-preferences');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-onboarding-partner-preferences"
                    onclick="cancelTryOut('POSTapi-v1-onboarding-partner-preferences');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-onboarding-partner-preferences"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/onboarding/partner-preferences</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-onboarding-partner-preferences"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-onboarding-partner-preferences"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>age_from</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="age_from"                data-endpoint="POSTapi-v1-onboarding-partner-preferences"
               value="1"
               data-component="body">
    <br>
<p>Must be at least 18. Must not be greater than 70. Example: <code>1</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>age_to</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="age_to"                data-endpoint="POSTapi-v1-onboarding-partner-preferences"
               value="22"
               data-component="body">
    <br>
<p>Must be at least 18. Must not be greater than 70. Example: <code>22</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>height_from_cm</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="height_from_cm"                data-endpoint="POSTapi-v1-onboarding-partner-preferences"
               value="7"
               data-component="body">
    <br>
<p>Must be at least 100. Must not be greater than 250. Example: <code>7</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>height_to_cm</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="height_to_cm"                data-endpoint="POSTapi-v1-onboarding-partner-preferences"
               value="16"
               data-component="body">
    <br>
<p>Must be at least 100. Must not be greater than 250. Example: <code>16</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>complexion</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="complexion"                data-endpoint="POSTapi-v1-onboarding-partner-preferences"
               value=""
               data-component="body">
    <br>

        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>body_type</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="body_type"                data-endpoint="POSTapi-v1-onboarding-partner-preferences"
               value=""
               data-component="body">
    <br>

        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>marital_status</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="marital_status"                data-endpoint="POSTapi-v1-onboarding-partner-preferences"
               value=""
               data-component="body">
    <br>

        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>children_status</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="children_status"                data-endpoint="POSTapi-v1-onboarding-partner-preferences"
               value=""
               data-component="body">
    <br>

        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>physical_status</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="physical_status"                data-endpoint="POSTapi-v1-onboarding-partner-preferences"
               value=""
               data-component="body">
    <br>

        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>da_category</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="da_category"                data-endpoint="POSTapi-v1-onboarding-partner-preferences"
               value=""
               data-component="body">
    <br>

        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>family_status</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="family_status"                data-endpoint="POSTapi-v1-onboarding-partner-preferences"
               value=""
               data-component="body">
    <br>

        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>religions</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="religions"                data-endpoint="POSTapi-v1-onboarding-partner-preferences"
               value=""
               data-component="body">
    <br>

        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>denomination</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="denomination"                data-endpoint="POSTapi-v1-onboarding-partner-preferences"
               value=""
               data-component="body">
    <br>

        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>diocese</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="diocese"                data-endpoint="POSTapi-v1-onboarding-partner-preferences"
               value=""
               data-component="body">
    <br>

        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>caste</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="caste"                data-endpoint="POSTapi-v1-onboarding-partner-preferences"
               value=""
               data-component="body">
    <br>

        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>sub_caste</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="sub_caste"                data-endpoint="POSTapi-v1-onboarding-partner-preferences"
               value=""
               data-component="body">
    <br>

        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>muslim_sect</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="muslim_sect"                data-endpoint="POSTapi-v1-onboarding-partner-preferences"
               value=""
               data-component="body">
    <br>

        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>muslim_community</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="muslim_community"                data-endpoint="POSTapi-v1-onboarding-partner-preferences"
               value=""
               data-component="body">
    <br>

        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>jain_sect</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="jain_sect"                data-endpoint="POSTapi-v1-onboarding-partner-preferences"
               value=""
               data-component="body">
    <br>

        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>manglik</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="manglik"                data-endpoint="POSTapi-v1-onboarding-partner-preferences"
               value=""
               data-component="body">
    <br>

        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>mother_tongues</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="mother_tongues"                data-endpoint="POSTapi-v1-onboarding-partner-preferences"
               value=""
               data-component="body">
    <br>

        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>languages_known</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="languages_known"                data-endpoint="POSTapi-v1-onboarding-partner-preferences"
               value=""
               data-component="body">
    <br>

        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>education_levels</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="education_levels"                data-endpoint="POSTapi-v1-onboarding-partner-preferences"
               value=""
               data-component="body">
    <br>

        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>educational_qualifications</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="educational_qualifications"                data-endpoint="POSTapi-v1-onboarding-partner-preferences"
               value=""
               data-component="body">
    <br>

        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>occupations</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="occupations"                data-endpoint="POSTapi-v1-onboarding-partner-preferences"
               value=""
               data-component="body">
    <br>

        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>employment_status</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="employment_status"                data-endpoint="POSTapi-v1-onboarding-partner-preferences"
               value=""
               data-component="body">
    <br>

        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>income_range</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="income_range"                data-endpoint="POSTapi-v1-onboarding-partner-preferences"
               value=""
               data-component="body">
    <br>

        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>working_countries</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="working_countries"                data-endpoint="POSTapi-v1-onboarding-partner-preferences"
               value=""
               data-component="body">
    <br>

        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>native_countries</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="native_countries"                data-endpoint="POSTapi-v1-onboarding-partner-preferences"
               value=""
               data-component="body">
    <br>

        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>about_partner</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="about_partner"                data-endpoint="POSTapi-v1-onboarding-partner-preferences"
               value="m"
               data-component="body">
    <br>
<p>Must not be greater than 5000 characters. Example: <code>m</code></p>
        </div>
        </form>

                    <h2 id="onboarding-POSTapi-v1-onboarding-lifestyle">Final onboarding step — also flips onboarding_completed=true.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-v1-onboarding-lifestyle">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/onboarding/lifestyle" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"lifestyle\": {
        \"diet\": \"b\",
        \"drinking\": \"ngzmiyvdljnikhwa\",
        \"smoking\": \"ykcmyuwpwlvqwrsi\",
        \"cultural_background\": \"t\"
    },
    \"social\": {
        \"facebook_url\": \"http:\\/\\/www.okeefe.net\\/et-consequatur-aut-dolores-enim\",
        \"instagram_url\": \"http:\\/\\/vonrueden.com\\/\",
        \"linkedin_url\": \"http:\\/\\/www.leffler.info\\/quis-adipisci-molestias-fugit-deleniti-distinctio-eum\",
        \"youtube_url\": \"https:\\/\\/nitzsche.net\\/aliquam-veniam-corporis-dolorem-mollitia-deleniti-nemo.html\",
        \"website_url\": \"https:\\/\\/www.balistreri.org\\/dignissimos-neque-blanditiis-odio\"
    }
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/onboarding/lifestyle"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "lifestyle": {
        "diet": "b",
        "drinking": "ngzmiyvdljnikhwa",
        "smoking": "ykcmyuwpwlvqwrsi",
        "cultural_background": "t"
    },
    "social": {
        "facebook_url": "http:\/\/www.okeefe.net\/et-consequatur-aut-dolores-enim",
        "instagram_url": "http:\/\/vonrueden.com\/",
        "linkedin_url": "http:\/\/www.leffler.info\/quis-adipisci-molestias-fugit-deleniti-distinctio-eum",
        "youtube_url": "https:\/\/nitzsche.net\/aliquam-veniam-corporis-dolorem-mollitia-deleniti-nemo.html",
        "website_url": "https:\/\/www.balistreri.org\/dignissimos-neque-blanditiis-odio"
    }
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-onboarding-lifestyle">
</span>
<span id="execution-results-POSTapi-v1-onboarding-lifestyle" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-onboarding-lifestyle"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-onboarding-lifestyle"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-onboarding-lifestyle" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-onboarding-lifestyle">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-onboarding-lifestyle" data-method="POST"
      data-path="api/v1/onboarding/lifestyle"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-onboarding-lifestyle', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-onboarding-lifestyle"
                    onclick="tryItOut('POSTapi-v1-onboarding-lifestyle');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-onboarding-lifestyle"
                    onclick="cancelTryOut('POSTapi-v1-onboarding-lifestyle');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-onboarding-lifestyle"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/onboarding/lifestyle</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-onboarding-lifestyle"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-onboarding-lifestyle"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>lifestyle</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>diet</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="lifestyle.diet"                data-endpoint="POSTapi-v1-onboarding-lifestyle"
               value="b"
               data-component="body">
    <br>
<p>Lifestyle → lifestyle_infos (excluding languages_known, preserved from step-1). Must not be greater than 30 characters. Example: <code>b</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>drinking</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="lifestyle.drinking"                data-endpoint="POSTapi-v1-onboarding-lifestyle"
               value="ngzmiyvdljnikhwa"
               data-component="body">
    <br>
<p>Must not be greater than 20 characters. Example: <code>ngzmiyvdljnikhwa</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>smoking</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="lifestyle.smoking"                data-endpoint="POSTapi-v1-onboarding-lifestyle"
               value="ykcmyuwpwlvqwrsi"
               data-component="body">
    <br>
<p>Must not be greater than 20 characters. Example: <code>ykcmyuwpwlvqwrsi</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>cultural_background</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="lifestyle.cultural_background"                data-endpoint="POSTapi-v1-onboarding-lifestyle"
               value="t"
               data-component="body">
    <br>
<p>Must not be greater than 30 characters. Example: <code>t</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>hobbies</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="lifestyle.hobbies"                data-endpoint="POSTapi-v1-onboarding-lifestyle"
               value=""
               data-component="body">
    <br>

                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>favorite_music</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="lifestyle.favorite_music"                data-endpoint="POSTapi-v1-onboarding-lifestyle"
               value=""
               data-component="body">
    <br>

                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>preferred_books</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="lifestyle.preferred_books"                data-endpoint="POSTapi-v1-onboarding-lifestyle"
               value=""
               data-component="body">
    <br>

                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>preferred_movies</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="lifestyle.preferred_movies"                data-endpoint="POSTapi-v1-onboarding-lifestyle"
               value=""
               data-component="body">
    <br>

                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>sports_fitness_games</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="lifestyle.sports_fitness_games"                data-endpoint="POSTapi-v1-onboarding-lifestyle"
               value=""
               data-component="body">
    <br>

                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>favorite_cuisine</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="lifestyle.favorite_cuisine"                data-endpoint="POSTapi-v1-onboarding-lifestyle"
               value=""
               data-component="body">
    <br>

                    </div>
                                    </details>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>social</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>facebook_url</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="social.facebook_url"                data-endpoint="POSTapi-v1-onboarding-lifestyle"
               value="http://www.okeefe.net/et-consequatur-aut-dolores-enim"
               data-component="body">
    <br>
<p>Social → social_media_links. Must be a valid URL. Must not be greater than 200 characters. Example: <code>http://www.okeefe.net/et-consequatur-aut-dolores-enim</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>instagram_url</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="social.instagram_url"                data-endpoint="POSTapi-v1-onboarding-lifestyle"
               value="http://vonrueden.com/"
               data-component="body">
    <br>
<p>Must be a valid URL. Must not be greater than 200 characters. Example: <code>http://vonrueden.com/</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>linkedin_url</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="social.linkedin_url"                data-endpoint="POSTapi-v1-onboarding-lifestyle"
               value="http://www.leffler.info/quis-adipisci-molestias-fugit-deleniti-distinctio-eum"
               data-component="body">
    <br>
<p>Must be a valid URL. Must not be greater than 200 characters. Example: <code>http://www.leffler.info/quis-adipisci-molestias-fugit-deleniti-distinctio-eum</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>youtube_url</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="social.youtube_url"                data-endpoint="POSTapi-v1-onboarding-lifestyle"
               value="https://nitzsche.net/aliquam-veniam-corporis-dolorem-mollitia-deleniti-nemo.html"
               data-component="body">
    <br>
<p>Must be a valid URL. Must not be greater than 200 characters. Example: <code>https://nitzsche.net/aliquam-veniam-corporis-dolorem-mollitia-deleniti-nemo.html</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>website_url</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="social.website_url"                data-endpoint="POSTapi-v1-onboarding-lifestyle"
               value="https://www.balistreri.org/dignissimos-neque-blanditiis-odio"
               data-component="body">
    <br>
<p>Must be a valid URL. Must not be greater than 200 characters. Example: <code>https://www.balistreri.org/dignissimos-neque-blanditiis-odio</code></p>
                    </div>
                                    </details>
        </div>
        </form>

                    <h2 id="onboarding-POSTapi-v1-onboarding-finish">Skip-to-dashboard sentinel — Flutter calls this from &quot;Do this
later&quot; buttons. Flips onboarding_completed=true so the dashboard
banner stops showing; doesn&#039;t touch any field data.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-v1-onboarding-finish">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/onboarding/finish" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/onboarding/finish"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-onboarding-finish">
</span>
<span id="execution-results-POSTapi-v1-onboarding-finish" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-onboarding-finish"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-onboarding-finish"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-onboarding-finish" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-onboarding-finish">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-onboarding-finish" data-method="POST"
      data-path="api/v1/onboarding/finish"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-onboarding-finish', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-onboarding-finish"
                    onclick="tryItOut('POSTapi-v1-onboarding-finish');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-onboarding-finish"
                    onclick="cancelTryOut('POSTapi-v1-onboarding-finish');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-onboarding-finish"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/onboarding/finish</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-onboarding-finish"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-onboarding-finish"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                <h1 id="payment">Payment</h1>

    

                                <h2 id="payment-POSTapi-v1-webhooks--gateway-">Inbound webhook endpoint for any registered payment gateway.</h2>

<p>
</p>

<p>Each gateway owns its own signature scheme + event dispatch
via PaymentGatewayInterface::handleWebhook. The controller
just resolves the slug and delegates.</p>
<p>NO authentication on this route — gateway servers can't carry
Sanctum tokens. Authenticity is established by the per-gateway
signature check inside handleWebhook.</p>

<span id="example-requests-POSTapi-v1-webhooks--gateway-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/webhooks/architecto" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/webhooks/architecto"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-webhooks--gateway-">
            <blockquote>
            <p>Example response (200, processed):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;status&quot;: &quot;processed&quot;
}</code>
 </pre>
            <blockquote>
            <p>Example response (200, duplicate):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;status&quot;: &quot;duplicate&quot;
}</code>
 </pre>
            <blockquote>
            <p>Example response (200, ignored-event-type):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;status&quot;: &quot;ignored&quot;
}</code>
 </pre>
            <blockquote>
            <p>Example response (401, invalid-signature):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;error&quot;: &quot;Invalid signature.&quot;
}</code>
 </pre>
            <blockquote>
            <p>Example response (404, unknown-gateway):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;error&quot;: &quot;Unknown payment gateway.&quot;
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, malformed-payload):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;error&quot;: &quot;Malformed JSON.&quot;
}</code>
 </pre>
            <blockquote>
            <p>Example response (503, webhook-not-configured):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;error&quot;: &quot;Webhook secret not configured.&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-webhooks--gateway-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-webhooks--gateway-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-webhooks--gateway-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-webhooks--gateway-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-webhooks--gateway-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-webhooks--gateway-" data-method="POST"
      data-path="api/v1/webhooks/{gateway}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-webhooks--gateway-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-webhooks--gateway-"
                    onclick="tryItOut('POSTapi-v1-webhooks--gateway-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-webhooks--gateway-"
                    onclick="cancelTryOut('POSTapi-v1-webhooks--gateway-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-webhooks--gateway-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/webhooks/{gateway}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-webhooks--gateway-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-webhooks--gateway-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>gateway</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="gateway"                data-endpoint="POSTapi-v1-webhooks--gateway-"
               value="architecto"
               data-component="url">
    <br>
<p>Gateway slug. Example: <code>architecto</code></p>
            </div>
                    </form>

                    <h2 id="payment-POSTapi-v1-payment--gateway--order">Create a gateway order, persist a pending Subscription, return
the gateway-specific payload Flutter needs to invoke the gateway
client SDK.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-v1-payment--gateway--order">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/payment/architecto/order" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"plan_id\": 16,
    \"coupon_code\": \"architecto\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/payment/architecto/order"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "plan_id": 16,
    "coupon_code": "architecto"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-payment--gateway--order">
            <blockquote>
            <p>Example response (201, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;subscription_id&quot;: 123,
        &quot;gateway&quot;: &quot;razorpay&quot;,
        &quot;amount_inr&quot;: 2400,
        &quot;currency&quot;: &quot;INR&quot;,
        &quot;gateway_data&quot;: {
            &quot;order_id&quot;: &quot;order_M1zXabc...&quot;,
            &quot;key_id&quot;: &quot;rzp_test_xxxxx&quot;,
            &quot;amount&quot;: 240000,
            &quot;currency&quot;: &quot;INR&quot;,
            &quot;status&quot;: &quot;created&quot;
        }
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (404, unknown-gateway):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;NOT_FOUND&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (404, unknown-plan):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;NOT_FOUND&quot;,
        &quot;message&quot;: &quot;Plan not found.&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, gateway-not-configured):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;GATEWAY_NOT_CONFIGURED&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, coupon-invalid):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;COUPON_INVALID&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, validation-failed):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;VALIDATION_FAILED&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (502, gateway-error):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;GATEWAY_ERROR&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-payment--gateway--order" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-payment--gateway--order"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-payment--gateway--order"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-payment--gateway--order" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-payment--gateway--order">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-payment--gateway--order" data-method="POST"
      data-path="api/v1/payment/{gateway}/order"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-payment--gateway--order', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-payment--gateway--order"
                    onclick="tryItOut('POSTapi-v1-payment--gateway--order');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-payment--gateway--order"
                    onclick="cancelTryOut('POSTapi-v1-payment--gateway--order');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-payment--gateway--order"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/payment/{gateway}/order</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-payment--gateway--order"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-payment--gateway--order"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>gateway</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="gateway"                data-endpoint="POSTapi-v1-payment--gateway--order"
               value="architecto"
               data-component="url">
    <br>
<p>Gateway slug (razorpay, stripe, paypal, paytm, phonepe). Example: <code>architecto</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>plan_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="plan_id"                data-endpoint="POSTapi-v1-payment--gateway--order"
               value="16"
               data-component="body">
    <br>
<p>Plan to subscribe to. Example: <code>16</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>coupon_code</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="coupon_code"                data-endpoint="POSTapi-v1-payment--gateway--order"
               value="architecto"
               data-component="body">
    <br>
<p>Optional coupon code. Example: <code>architecto</code></p>
        </div>
        </form>

                    <h2 id="payment-POSTapi-v1-payment--gateway--verify">Verify a gateway callback after Flutter completes the in-app
payment flow. On valid signature, marks the subscription paid,
creates / extends UserMembership, records coupon usage, and
deactivates prior memberships.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-v1-payment--gateway--verify">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/payment/architecto/verify" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"subscription_id\": 16,
    \"razorpay_order_id\": \"architecto\",
    \"razorpay_payment_id\": \"architecto\",
    \"razorpay_signature\": \"architecto\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/payment/architecto/verify"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "subscription_id": 16,
    "razorpay_order_id": "architecto",
    "razorpay_payment_id": "architecto",
    "razorpay_signature": "architecto"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-payment--gateway--verify">
            <blockquote>
            <p>Example response (200, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;subscription_id&quot;: 123,
        &quot;payment_status&quot;: &quot;paid&quot;,
        &quot;is_active&quot;: true,
        &quot;starts_at&quot;: &quot;2026-04-25T...&quot;,
        &quot;expires_at&quot;: &quot;2026-10-25T...&quot;,
        &quot;membership&quot;: {
            &quot;plan_id&quot;: 5,
            &quot;plan_name&quot;: &quot;Diamond Plus&quot;,
            &quot;is_premium&quot;: true
        }
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (200, already-verified):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;already_verified&quot;: true,
        &quot;subscription_id&quot;: 123
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (403, not-owner):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;UNAUTHORIZED&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (404, unknown-gateway):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;NOT_FOUND&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (404, subscription-not-found):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;NOT_FOUND&quot;,
        &quot;message&quot;: &quot;Subscription not found.&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, signature-invalid):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;SIGNATURE_INVALID&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, validation-failed):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;VALIDATION_FAILED&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-payment--gateway--verify" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-payment--gateway--verify"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-payment--gateway--verify"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-payment--gateway--verify" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-payment--gateway--verify">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-payment--gateway--verify" data-method="POST"
      data-path="api/v1/payment/{gateway}/verify"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-payment--gateway--verify', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-payment--gateway--verify"
                    onclick="tryItOut('POSTapi-v1-payment--gateway--verify');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-payment--gateway--verify"
                    onclick="cancelTryOut('POSTapi-v1-payment--gateway--verify');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-payment--gateway--verify"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/payment/{gateway}/verify</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-payment--gateway--verify"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-payment--gateway--verify"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>gateway</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="gateway"                data-endpoint="POSTapi-v1-payment--gateway--verify"
               value="architecto"
               data-component="url">
    <br>
<p>Gateway slug. Example: <code>architecto</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>subscription_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="subscription_id"                data-endpoint="POSTapi-v1-payment--gateway--verify"
               value="16"
               data-component="body">
    <br>
<p>Pending subscription id from /order. Example: <code>16</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>razorpay_order_id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="razorpay_order_id"                data-endpoint="POSTapi-v1-payment--gateway--verify"
               value="architecto"
               data-component="body">
    <br>
<p>Required for Razorpay. Example: <code>architecto</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>razorpay_payment_id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="razorpay_payment_id"                data-endpoint="POSTapi-v1-payment--gateway--verify"
               value="architecto"
               data-component="body">
    <br>
<p>Required for Razorpay. Example: <code>architecto</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>razorpay_signature</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="razorpay_signature"                data-endpoint="POSTapi-v1-payment--gateway--verify"
               value="architecto"
               data-component="body">
    <br>
<p>Required for Razorpay. Example: <code>architecto</code></p>
        </div>
        </form>

                <h1 id="photo-requests">Photo Requests</h1>

    

                                <h2 id="photo-requests-GETapi-v1-photo-requests">GET api/v1/photo-requests</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-GETapi-v1-photo-requests">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/photo-requests" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/photo-requests"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-photo-requests">
            <blockquote>
            <p>Example response (200, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
  &quot;success&quot;: true,
  &quot;data&quot;: {
    &quot;received&quot;: [{&quot;id&quot;: 1, &quot;requester&quot;: {...}, &quot;status&quot;: &quot;pending&quot;, &quot;created_at&quot;: &quot;...&quot;}],
    &quot;sent&quot;: [{&quot;id&quot;: 2, &quot;target&quot;: {...}, &quot;status&quot;: &quot;approved&quot;, &quot;created_at&quot;: &quot;...&quot;}]
  }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, no-profile):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;PROFILE_REQUIRED&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-photo-requests" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-photo-requests"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-photo-requests"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-photo-requests" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-photo-requests">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-photo-requests" data-method="GET"
      data-path="api/v1/photo-requests"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-photo-requests', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-photo-requests"
                    onclick="tryItOut('GETapi-v1-photo-requests');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-photo-requests"
                    onclick="cancelTryOut('GETapi-v1-photo-requests');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-photo-requests"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/photo-requests</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-photo-requests"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-photo-requests"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="photo-requests-POSTapi-v1-profiles--matriId--photo-request">POST api/v1/profiles/{matriId}/photo-request</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-v1-profiles--matriId--photo-request">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/profiles/architecto/photo-request" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/profiles/architecto/photo-request"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-profiles--matriId--photo-request">
            <blockquote>
            <p>Example response (201, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;request_id&quot;: 42,
        &quot;status&quot;: &quot;pending&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (403, same-gender):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;GENDER_MISMATCH&quot;,
        &quot;message&quot;: &quot;Cannot request photos from a same-gender profile.&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (404, not-found-or-restricted):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;NOT_FOUND&quot;,
        &quot;message&quot;: &quot;Profile not available.&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (409, already-exists):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;ALREADY_EXISTS&quot;,
        &quot;message&quot;: &quot;You already have an open photo request with this profile.&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, self-request):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;SELF_REQUEST&quot;,
        &quot;message&quot;: &quot;You cannot request photos from your own profile.&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, no-profile):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;PROFILE_REQUIRED&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (429, throttled):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;THROTTLED&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-profiles--matriId--photo-request" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-profiles--matriId--photo-request"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-profiles--matriId--photo-request"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-profiles--matriId--photo-request" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-profiles--matriId--photo-request">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-profiles--matriId--photo-request" data-method="POST"
      data-path="api/v1/profiles/{matriId}/photo-request"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-profiles--matriId--photo-request', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-profiles--matriId--photo-request"
                    onclick="tryItOut('POSTapi-v1-profiles--matriId--photo-request');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-profiles--matriId--photo-request"
                    onclick="cancelTryOut('POSTapi-v1-profiles--matriId--photo-request');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-profiles--matriId--photo-request"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/profiles/{matriId}/photo-request</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-profiles--matriId--photo-request"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-profiles--matriId--photo-request"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>matriId</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="matriId"                data-endpoint="POSTapi-v1-profiles--matriId--photo-request"
               value="architecto"
               data-component="url">
    <br>
<p>Target profile's matri_id (AM######). Example: <code>architecto</code></p>
            </div>
                    </form>

                    <h2 id="photo-requests-POSTapi-v1-photo-requests--photoRequest_id--approve">POST api/v1/photo-requests/{photoRequest_id}/approve</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-v1-photo-requests--photoRequest_id--approve">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/photo-requests/16/approve" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/photo-requests/16/approve"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-photo-requests--photoRequest_id--approve">
            <blockquote>
            <p>Example response (200, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;approved&quot;: true,
        &quot;request_id&quot;: 42
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (403, not-target):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;UNAUTHORIZED&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (404, not-found):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;NOT_FOUND&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, not-pending):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;VALIDATION_FAILED&quot;,
        &quot;message&quot;: &quot;...&quot;,
        &quot;fields&quot;: {
            &quot;status&quot;: [
                &quot;...&quot;
            ]
        }
    }
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-photo-requests--photoRequest_id--approve" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-photo-requests--photoRequest_id--approve"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-photo-requests--photoRequest_id--approve"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-photo-requests--photoRequest_id--approve" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-photo-requests--photoRequest_id--approve">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-photo-requests--photoRequest_id--approve" data-method="POST"
      data-path="api/v1/photo-requests/{photoRequest_id}/approve"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-photo-requests--photoRequest_id--approve', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-photo-requests--photoRequest_id--approve"
                    onclick="tryItOut('POSTapi-v1-photo-requests--photoRequest_id--approve');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-photo-requests--photoRequest_id--approve"
                    onclick="cancelTryOut('POSTapi-v1-photo-requests--photoRequest_id--approve');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-photo-requests--photoRequest_id--approve"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/photo-requests/{photoRequest_id}/approve</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-photo-requests--photoRequest_id--approve"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-photo-requests--photoRequest_id--approve"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>photoRequest_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="photoRequest_id"                data-endpoint="POSTapi-v1-photo-requests--photoRequest_id--approve"
               value="16"
               data-component="url">
    <br>
<p>The ID of the photoRequest. Example: <code>16</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>photoRequest</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="photoRequest"                data-endpoint="POSTapi-v1-photo-requests--photoRequest_id--approve"
               value="16"
               data-component="url">
    <br>
<p>The PhotoRequest id. Example: <code>16</code></p>
            </div>
                    </form>

                    <h2 id="photo-requests-POSTapi-v1-photo-requests--photoRequest_id--ignore">POST api/v1/photo-requests/{photoRequest_id}/ignore</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-v1-photo-requests--photoRequest_id--ignore">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/photo-requests/16/ignore" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/photo-requests/16/ignore"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-photo-requests--photoRequest_id--ignore">
            <blockquote>
            <p>Example response (200, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;ignored&quot;: true,
        &quot;request_id&quot;: 42
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (403, not-target):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;UNAUTHORIZED&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (404, not-found):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;NOT_FOUND&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-photo-requests--photoRequest_id--ignore" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-photo-requests--photoRequest_id--ignore"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-photo-requests--photoRequest_id--ignore"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-photo-requests--photoRequest_id--ignore" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-photo-requests--photoRequest_id--ignore">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-photo-requests--photoRequest_id--ignore" data-method="POST"
      data-path="api/v1/photo-requests/{photoRequest_id}/ignore"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-photo-requests--photoRequest_id--ignore', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-photo-requests--photoRequest_id--ignore"
                    onclick="tryItOut('POSTapi-v1-photo-requests--photoRequest_id--ignore');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-photo-requests--photoRequest_id--ignore"
                    onclick="cancelTryOut('POSTapi-v1-photo-requests--photoRequest_id--ignore');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-photo-requests--photoRequest_id--ignore"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/photo-requests/{photoRequest_id}/ignore</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-photo-requests--photoRequest_id--ignore"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-photo-requests--photoRequest_id--ignore"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>photoRequest_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="photoRequest_id"                data-endpoint="POSTapi-v1-photo-requests--photoRequest_id--ignore"
               value="16"
               data-component="url">
    <br>
<p>The ID of the photoRequest. Example: <code>16</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>photoRequest</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="photoRequest"                data-endpoint="POSTapi-v1-photo-requests--photoRequest_id--ignore"
               value="16"
               data-component="url">
    <br>
<p>The PhotoRequest id. Example: <code>16</code></p>
            </div>
                    </form>

                <h1 id="photos">Photos</h1>

    

                                <h2 id="photos-GETapi-v1-photos">GET api/v1/photos</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-GETapi-v1-photos">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/photos" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/photos"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-photos">
            <blockquote>
            <p>Example response (200, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;limits&quot;: {
            &quot;max_profile&quot;: 1,
            &quot;max_album&quot;: 9,
            &quot;max_family&quot;: 3,
            &quot;max_size_mb&quot;: 5
        },
        &quot;counts&quot;: {
            &quot;profile_used&quot;: 1,
            &quot;album_used&quot;: 3,
            &quot;family_used&quot;: 0
        },
        &quot;active&quot;: {
            &quot;profile&quot;: [],
            &quot;album&quot;: [],
            &quot;family&quot;: []
        },
        &quot;pending&quot;: [],
        &quot;rejected&quot;: [],
        &quot;archived&quot;: [],
        &quot;privacy&quot;: {
            &quot;privacy_level&quot;: &quot;visible_to_all&quot;,
            &quot;profile_photo_privacy&quot;: null,
            &quot;album_photos_privacy&quot;: null,
            &quot;family_photos_privacy&quot;: null
        }
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (401, unauthenticated):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;UNAUTHENTICATED&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, no-profile):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;PROFILE_REQUIRED&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-photos" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-photos"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-photos"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-photos" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-photos">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-photos" data-method="GET"
      data-path="api/v1/photos"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-photos', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-photos"
                    onclick="tryItOut('GETapi-v1-photos');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-photos"
                    onclick="cancelTryOut('GETapi-v1-photos');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-photos"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/photos</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-photos"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-photos"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="photos-POSTapi-v1-photos">POST api/v1/photos</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-v1-photos">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/photos" \
    --header "Content-Type: multipart/form-data" \
    --header "Accept: application/json" \
    --form "photo_type=album"\
    --form "photo=@C:\Users\Lenovo\AppData\Local\Temp\php35E5.tmp" </code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/photos"
);

const headers = {
    "Content-Type": "multipart/form-data",
    "Accept": "application/json",
};

const body = new FormData();
body.append('photo_type', 'album');
body.append('photo', document.querySelector('input[name="photo"]').files[0]);

fetch(url, {
    method: "POST",
    headers,
    body,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-photos">
            <blockquote>
            <p>Example response (201, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;photo&quot;: {
            &quot;id&quot;: 1,
            &quot;url&quot;: &quot;...&quot;,
            &quot;is_primary&quot;: true
        },
        &quot;needs_approval&quot;: false
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, validation-failed):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;VALIDATION_FAILED&quot;,
        &quot;message&quot;: &quot;...&quot;,
        &quot;fields&quot;: {
            &quot;photo&quot;: [
                &quot;...&quot;
            ]
        }
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, slot-full):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;VALIDATION_FAILED&quot;,
        &quot;message&quot;: &quot;Maximum 9 album photos allowed. Delete or archive one first.&quot;,
        &quot;fields&quot;: {
            &quot;photo_type&quot;: [
                &quot;...&quot;
            ]
        }
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, no-profile):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;PROFILE_REQUIRED&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-photos" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-photos"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-photos"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-photos" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-photos">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-photos" data-method="POST"
      data-path="api/v1/photos"
      data-authed="1"
      data-hasfiles="1"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-photos', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-photos"
                    onclick="tryItOut('POSTapi-v1-photos');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-photos"
                    onclick="cancelTryOut('POSTapi-v1-photos');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-photos"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/photos</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-photos"
               value="multipart/form-data"
               data-component="header">
    <br>
<p>Example: <code>multipart/form-data</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-photos"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>photo</code></b>&nbsp;&nbsp;
<small>file</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="file" style="display: none"
                              name="photo"                data-endpoint="POSTapi-v1-photos"
               value=""
               data-component="body">
    <br>
<p>Must be a file. Must be an image. Must not be greater than 30720 kilobytes. Example: <code>C:\Users\Lenovo\AppData\Local\Temp\php35E5.tmp</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>photo_type</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="photo_type"                data-endpoint="POSTapi-v1-photos"
               value="album"
               data-component="body">
    <br>
<p>Example: <code>album</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>profile</code></li> <li><code>album</code></li> <li><code>family</code></li></ul>
        </div>
        </form>

                    <h2 id="photos-POSTapi-v1-photos-privacy">Update the authenticated user&#039;s photo-privacy settings. PATCH-like
semantics — only the fields present in the payload are changed.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>The photo_privacy_settings row is created on-demand via
updateOrCreate if the user has never saved a setting before.</p>

<span id="example-requests-POSTapi-v1-photos-privacy">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/photos/privacy" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"privacy_level\": \"visible_to_all\",
    \"profile_photo_privacy\": \"visible_to_all\",
    \"album_photos_privacy\": \"hidden\",
    \"family_photos_privacy\": \"hidden\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/photos/privacy"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "privacy_level": "visible_to_all",
    "profile_photo_privacy": "visible_to_all",
    "album_photos_privacy": "hidden",
    "family_photos_privacy": "hidden"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-photos-privacy">
            <blockquote>
            <p>Example response (200, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;privacy&quot;: {
            &quot;privacy_level&quot;: &quot;visible_to_all&quot;,
            &quot;profile_photo_privacy&quot;: &quot;visible_to_all&quot;,
            &quot;album_photos_privacy&quot;: &quot;interest_accepted&quot;,
            &quot;family_photos_privacy&quot;: &quot;interest_accepted&quot;
        }
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, empty-payload):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;VALIDATION_FAILED&quot;,
        &quot;message&quot;: &quot;Please check the fields below.&quot;,
        &quot;fields&quot;: {
            &quot;privacy_level&quot;: [
                &quot;Provide at least one privacy field to update.&quot;
            ]
        }
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, invalid-level):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;VALIDATION_FAILED&quot;,
        &quot;message&quot;: &quot;...&quot;,
        &quot;fields&quot;: {
            &quot;privacy_level&quot;: [
                &quot;...&quot;
            ]
        }
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, no-profile):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;PROFILE_REQUIRED&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-photos-privacy" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-photos-privacy"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-photos-privacy"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-photos-privacy" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-photos-privacy">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-photos-privacy" data-method="POST"
      data-path="api/v1/photos/privacy"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-photos-privacy', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-photos-privacy"
                    onclick="tryItOut('POSTapi-v1-photos-privacy');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-photos-privacy"
                    onclick="cancelTryOut('POSTapi-v1-photos-privacy');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-photos-privacy"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/photos/privacy</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-photos-privacy"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-photos-privacy"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>privacy_level</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="privacy_level"                data-endpoint="POSTapi-v1-photos-privacy"
               value="visible_to_all"
               data-component="body">
    <br>
<p>Example: <code>visible_to_all</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>visible_to_all</code></li> <li><code>interest_accepted</code></li> <li><code>hidden</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>profile_photo_privacy</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="profile_photo_privacy"                data-endpoint="POSTapi-v1-photos-privacy"
               value="visible_to_all"
               data-component="body">
    <br>
<p>Example: <code>visible_to_all</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>visible_to_all</code></li> <li><code>interest_accepted</code></li> <li><code>hidden</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>album_photos_privacy</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="album_photos_privacy"                data-endpoint="POSTapi-v1-photos-privacy"
               value="hidden"
               data-component="body">
    <br>
<p>Example: <code>hidden</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>visible_to_all</code></li> <li><code>interest_accepted</code></li> <li><code>hidden</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>family_photos_privacy</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="family_photos_privacy"                data-endpoint="POSTapi-v1-photos-privacy"
               value="hidden"
               data-component="body">
    <br>
<p>Example: <code>hidden</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>visible_to_all</code></li> <li><code>interest_accepted</code></li> <li><code>hidden</code></li></ul>
        </div>
        </form>

                    <h2 id="photos-POSTapi-v1-photos--photo_id--primary">POST api/v1/photos/{photo_id}/primary</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-v1-photos--photo_id--primary">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/photos/16/primary" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/photos/16/primary"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-photos--photo_id--primary">
            <blockquote>
            <p>Example response (200, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;photo_id&quot;: 1,
        &quot;is_primary&quot;: true
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (403, not-owner):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;UNAUTHORIZED&quot;,
        &quot;message&quot;: &quot;You do not have permission to perform this action.&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (404, not-found):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;NOT_FOUND&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, wrong-type):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;VALIDATION_FAILED&quot;,
        &quot;message&quot;: &quot;Only profile-type photos can be primary.&quot;,
        &quot;fields&quot;: {
            &quot;photo_type&quot;: [
                &quot;...&quot;
            ]
        }
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, archived-or-pending):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;VALIDATION_FAILED&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-photos--photo_id--primary" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-photos--photo_id--primary"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-photos--photo_id--primary"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-photos--photo_id--primary" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-photos--photo_id--primary">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-photos--photo_id--primary" data-method="POST"
      data-path="api/v1/photos/{photo_id}/primary"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-photos--photo_id--primary', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-photos--photo_id--primary"
                    onclick="tryItOut('POSTapi-v1-photos--photo_id--primary');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-photos--photo_id--primary"
                    onclick="cancelTryOut('POSTapi-v1-photos--photo_id--primary');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-photos--photo_id--primary"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/photos/{photo_id}/primary</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-photos--photo_id--primary"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-photos--photo_id--primary"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>photo_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="photo_id"                data-endpoint="POSTapi-v1-photos--photo_id--primary"
               value="16"
               data-component="url">
    <br>
<p>The ID of the photo. Example: <code>16</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="id"                data-endpoint="POSTapi-v1-photos--photo_id--primary"
               value="16"
               data-component="url">
    <br>
<p>The ProfilePhoto id. Example: <code>16</code></p>
            </div>
                    </form>

                    <h2 id="photos-POSTapi-v1-photos--photo_id--restore">POST api/v1/photos/{photo_id}/restore</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-v1-photos--photo_id--restore">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/photos/16/restore" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/photos/16/restore"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-photos--photo_id--restore">
            <blockquote>
            <p>Example response (200, restored):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;photo&quot;: {}
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (403, not-owner):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;UNAUTHORIZED&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (404, not-found):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;NOT_FOUND&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, slot-full):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;VALIDATION_FAILED&quot;,
        &quot;message&quot;: &quot;Cannot restore: slot limit reached.&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-photos--photo_id--restore" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-photos--photo_id--restore"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-photos--photo_id--restore"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-photos--photo_id--restore" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-photos--photo_id--restore">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-photos--photo_id--restore" data-method="POST"
      data-path="api/v1/photos/{photo_id}/restore"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-photos--photo_id--restore', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-photos--photo_id--restore"
                    onclick="tryItOut('POSTapi-v1-photos--photo_id--restore');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-photos--photo_id--restore"
                    onclick="cancelTryOut('POSTapi-v1-photos--photo_id--restore');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-photos--photo_id--restore"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/photos/{photo_id}/restore</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-photos--photo_id--restore"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-photos--photo_id--restore"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>photo_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="photo_id"                data-endpoint="POSTapi-v1-photos--photo_id--restore"
               value="16"
               data-component="url">
    <br>
<p>The ID of the photo. Example: <code>16</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="id"                data-endpoint="POSTapi-v1-photos--photo_id--restore"
               value="16"
               data-component="url">
    <br>
<p>The ProfilePhoto id. Example: <code>16</code></p>
            </div>
                    </form>

                    <h2 id="photos-DELETEapi-v1-photos--photo_id--permanent">DELETE api/v1/photos/{photo_id}/permanent</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-DELETEapi-v1-photos--photo_id--permanent">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "http://localhost:8000/api/v1/photos/16/permanent" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/photos/16/permanent"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-DELETEapi-v1-photos--photo_id--permanent">
            <blockquote>
            <p>Example response (200, deleted):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;deleted&quot;: true,
        &quot;photo_id&quot;: 1
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (403, not-owner):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;UNAUTHORIZED&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (404, not-found):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;NOT_FOUND&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-DELETEapi-v1-photos--photo_id--permanent" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEapi-v1-photos--photo_id--permanent"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEapi-v1-photos--photo_id--permanent"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEapi-v1-photos--photo_id--permanent" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEapi-v1-photos--photo_id--permanent">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEapi-v1-photos--photo_id--permanent" data-method="DELETE"
      data-path="api/v1/photos/{photo_id}/permanent"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEapi-v1-photos--photo_id--permanent', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEapi-v1-photos--photo_id--permanent"
                    onclick="tryItOut('DELETEapi-v1-photos--photo_id--permanent');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEapi-v1-photos--photo_id--permanent"
                    onclick="cancelTryOut('DELETEapi-v1-photos--photo_id--permanent');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEapi-v1-photos--photo_id--permanent"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>api/v1/photos/{photo_id}/permanent</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEapi-v1-photos--photo_id--permanent"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="DELETEapi-v1-photos--photo_id--permanent"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>photo_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="photo_id"                data-endpoint="DELETEapi-v1-photos--photo_id--permanent"
               value="16"
               data-component="url">
    <br>
<p>The ID of the photo. Example: <code>16</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="id"                data-endpoint="DELETEapi-v1-photos--photo_id--permanent"
               value="16"
               data-component="url">
    <br>
<p>The ProfilePhoto id. Example: <code>16</code></p>
            </div>
                    </form>

                    <h2 id="photos-DELETEapi-v1-photos--photo_id-">DELETE api/v1/photos/{photo_id}</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-DELETEapi-v1-photos--photo_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "http://localhost:8000/api/v1/photos/16" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/photos/16"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-DELETEapi-v1-photos--photo_id-">
            <blockquote>
            <p>Example response (200, archived):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;archived&quot;: true,
        &quot;photo_id&quot;: 1,
        &quot;undo_until&quot;: &quot;2026-05-25T...&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (403, not-owner):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;UNAUTHORIZED&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (404, not-found):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;NOT_FOUND&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-DELETEapi-v1-photos--photo_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEapi-v1-photos--photo_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEapi-v1-photos--photo_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEapi-v1-photos--photo_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEapi-v1-photos--photo_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEapi-v1-photos--photo_id-" data-method="DELETE"
      data-path="api/v1/photos/{photo_id}"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEapi-v1-photos--photo_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEapi-v1-photos--photo_id-"
                    onclick="tryItOut('DELETEapi-v1-photos--photo_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEapi-v1-photos--photo_id-"
                    onclick="cancelTryOut('DELETEapi-v1-photos--photo_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEapi-v1-photos--photo_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>api/v1/photos/{photo_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEapi-v1-photos--photo_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="DELETEapi-v1-photos--photo_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>photo_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="photo_id"                data-endpoint="DELETEapi-v1-photos--photo_id-"
               value="16"
               data-component="url">
    <br>
<p>The ID of the photo. Example: <code>16</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="id"                data-endpoint="DELETEapi-v1-photos--photo_id-"
               value="16"
               data-component="url">
    <br>
<p>The ProfilePhoto id. Example: <code>16</code></p>
            </div>
                    </form>

                <h1 id="profile">Profile</h1>

    

                                <h2 id="profile-GETapi-v1-dashboard">Show the dashboard payload.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-GETapi-v1-dashboard">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/dashboard" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/dashboard"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-dashboard">
            <blockquote>
            <p>Example response (200, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;cta&quot;: {
            &quot;show_profile_completion&quot;: true,
            &quot;profile_completion_pct&quot;: 42,
            &quot;show_photo_upload&quot;: true,
            &quot;show_verify_email&quot;: false,
            &quot;show_verify_phone&quot;: false,
            &quot;show_upgrade&quot;: true
        },
        &quot;stats&quot;: {
            &quot;interests_received&quot;: 3,
            &quot;interests_sent&quot;: 1,
            &quot;profile_views_total&quot;: 57,
            &quot;shortlisted_count&quot;: 12,
            &quot;unread_notifications&quot;: 2
        },
        &quot;recommended_matches&quot;: [],
        &quot;mutual_matches&quot;: [],
        &quot;recent_views&quot;: [],
        &quot;newly_joined&quot;: [],
        &quot;discover_teasers&quot;: [
            {
                &quot;category&quot;: &quot;nri-matrimony&quot;,
                &quot;label&quot;: &quot;NRI Matrimony&quot;,
                &quot;count&quot;: null
            }
        ]
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (401, unauthenticated):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;UNAUTHENTICATED&quot;,
        &quot;message&quot;: &quot;Unauthenticated.&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, no-profile):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;PROFILE_REQUIRED&quot;,
        &quot;message&quot;: &quot;Complete registration before loading the dashboard.&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-dashboard" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-dashboard"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-dashboard"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-dashboard" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-dashboard">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-dashboard" data-method="GET"
      data-path="api/v1/dashboard"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-dashboard', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-dashboard"
                    onclick="tryItOut('GETapi-v1-dashboard');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-dashboard"
                    onclick="cancelTryOut('GETapi-v1-dashboard');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-dashboard"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/dashboard</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-dashboard"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-dashboard"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="profile-GETapi-v1-profile-me">Return the authenticated user&#039;s own profile with all 9 sections,
contact populated, photos grouped by type.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-GETapi-v1-profile-me">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/profile/me" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/profile/me"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-profile-me">
            <blockquote>
            <p>Example response (200, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;profile&quot;: {
            &quot;matri_id&quot;: &quot;AM100042&quot;,
            &quot;full_name&quot;: &quot;Priya Rani&quot;,
            &quot;gender&quot;: &quot;female&quot;,
            &quot;age&quot;: 29,
            &quot;is_premium&quot;: true,
            &quot;sections&quot;: {
                &quot;primary&quot;: {},
                &quot;religious&quot;: {},
                &quot;education&quot;: {},
                &quot;family&quot;: {},
                &quot;location&quot;: {},
                &quot;contact&quot;: {},
                &quot;hobbies&quot;: {},
                &quot;social&quot;: {},
                &quot;partner&quot;: {}
            },
            &quot;photos&quot;: {
                &quot;profile&quot;: [],
                &quot;album&quot;: [],
                &quot;family&quot;: [],
                &quot;photo_privacy&quot;: null
            }
        }
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (401, unauthenticated):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;UNAUTHENTICATED&quot;,
        &quot;message&quot;: &quot;Unauthenticated.&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, no-profile):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;PROFILE_REQUIRED&quot;,
        &quot;message&quot;: &quot;Complete registration before viewing your profile.&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-profile-me" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-profile-me"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-profile-me"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-profile-me" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-profile-me">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-profile-me" data-method="GET"
      data-path="api/v1/profile/me"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-profile-me', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-profile-me"
                    onclick="tryItOut('GETapi-v1-profile-me');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-profile-me"
                    onclick="cancelTryOut('GETapi-v1-profile-me');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-profile-me"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/profile/me</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-profile-me"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-profile-me"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="profile-GETapi-v1-profiles--matriId-">View another user&#039;s profile. Applies all 7 ProfileAccessService
gates, tracks a deduped ProfileView, returns viewer-context fields
(match score, interest status, shortlist state, etc.).</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-GETapi-v1-profiles--matriId-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/profiles/architecto" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/profiles/architecto"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-profiles--matriId-">
            <blockquote>
            <p>Example response (200, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;profile&quot;: {
            &quot;matri_id&quot;: &quot;AM100042&quot;,
            &quot;full_name&quot;: &quot;Priya Rani&quot;,
            &quot;sections&quot;: {
                &quot;contact&quot;: null
            }
        },
        &quot;match_score&quot;: {
            &quot;score&quot;: 85,
            &quot;badge&quot;: &quot;great&quot;,
            &quot;breakdown&quot;: []
        },
        &quot;interest_status&quot;: &quot;sent&quot;,
        &quot;is_shortlisted&quot;: false,
        &quot;is_blocked&quot;: false,
        &quot;photo_request_status&quot;: null,
        &quot;can_view_contact&quot;: false
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (403, same-gender):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;GENDER_MISMATCH&quot;,
        &quot;message&quot;: &quot;Cannot view same-gender profile.&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (403, premium-only):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;PREMIUM_REQUIRED&quot;,
        &quot;message&quot;: &quot;This profile is visible to premium members only.&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (404, not-found-or-restricted):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;NOT_FOUND&quot;,
        &quot;message&quot;: &quot;Profile not available.&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, no-viewer-profile):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;PROFILE_REQUIRED&quot;,
        &quot;message&quot;: &quot;Complete registration before browsing profiles.&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-profiles--matriId-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-profiles--matriId-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-profiles--matriId-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-profiles--matriId-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-profiles--matriId-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-profiles--matriId-" data-method="GET"
      data-path="api/v1/profiles/{matriId}"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-profiles--matriId-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-profiles--matriId-"
                    onclick="tryItOut('GETapi-v1-profiles--matriId-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-profiles--matriId-"
                    onclick="cancelTryOut('GETapi-v1-profiles--matriId-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-profiles--matriId-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/profiles/{matriId}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-profiles--matriId-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-profiles--matriId-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>matriId</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="matriId"                data-endpoint="GETapi-v1-profiles--matriId-"
               value="architecto"
               data-component="url">
    <br>
<p>The profile's matri_id (e.g. AM100042). Example: <code>architecto</code></p>
            </div>
                    </form>

                    <h2 id="profile-PUTapi-v1-profile-me--section-">Persist the authenticated user&#039;s edits to a single profile section.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Each section has its own FormRequest for validation (9 classes in
App\Http\Requests\Api\V1\Profile*). After the save, the completion
percentage is recomputed and returned so Flutter's progress ring
refreshes without a follow-up GET.</p>

<span id="example-requests-PUTapi-v1-profile-me--section-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PUT \
    "http://localhost:8000/api/v1/profile/me/architecto" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/profile/me/architecto"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "PUT",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PUTapi-v1-profile-me--section-">
            <blockquote>
            <p>Example response (200, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;section&quot;: &quot;primary&quot;,
        &quot;updated_fields&quot;: [
            &quot;about_me&quot;,
            &quot;languages_known&quot;
        ],
        &quot;profile_completion_pct&quot;: 68
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (404, unknown-section):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;NOT_FOUND&quot;,
        &quot;message&quot;: &quot;Profile not available.&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, validation-failed):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;VALIDATION_FAILED&quot;,
        &quot;message&quot;: &quot;Please check the fields below.&quot;,
        &quot;fields&quot;: {
            &quot;mother_tongue&quot;: [
                &quot;The mother tongue field is required.&quot;
            ]
        }
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, no-profile):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;PROFILE_REQUIRED&quot;,
        &quot;message&quot;: &quot;Complete registration before updating your profile.&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (429, throttled):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;THROTTLED&quot;,
        &quot;message&quot;: &quot;Too many requests. Try again in 60 seconds.&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-PUTapi-v1-profile-me--section-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PUTapi-v1-profile-me--section-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PUTapi-v1-profile-me--section-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PUTapi-v1-profile-me--section-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PUTapi-v1-profile-me--section-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PUTapi-v1-profile-me--section-" data-method="PUT"
      data-path="api/v1/profile/me/{section}"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PUTapi-v1-profile-me--section-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PUTapi-v1-profile-me--section-"
                    onclick="tryItOut('PUTapi-v1-profile-me--section-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PUTapi-v1-profile-me--section-"
                    onclick="cancelTryOut('PUTapi-v1-profile-me--section-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PUTapi-v1-profile-me--section-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-darkblue">PUT</small>
            <b><code>api/v1/profile/me/{section}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PUTapi-v1-profile-me--section-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PUTapi-v1-profile-me--section-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>section</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="section"                data-endpoint="PUTapi-v1-profile-me--section-"
               value="architecto"
               data-component="url">
    <br>
<p>One of: primary, religious,
education, family, location, contact, hobbies, social, partner. Example: <code>architecto</code></p>
            </div>
                    </form>

                <h1 id="report">Report</h1>

    

                                <h2 id="report-POSTapi-v1-profiles--matriId--report">Submit a profile report.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-v1-profiles--matriId--report">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/profiles/architecto/report" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"reason\": \"architecto\",
    \"description\": \"Eius et animi quos velit et.\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/profiles/architecto/report"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "reason": "architecto",
    "description": "Eius et animi quos velit et."
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-profiles--matriId--report">
            <blockquote>
            <p>Example response (201, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;report_id&quot;: 42,
        &quot;status&quot;: &quot;pending&quot;,
        &quot;message&quot;: &quot;Our team will review within 48 hours.&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (404, not-found):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;NOT_FOUND&quot;,
        &quot;message&quot;: &quot;Profile not available.&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (409, duplicate):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;ALREADY_EXISTS&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, invalid-target):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;INVALID_TARGET&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, no-profile):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;PROFILE_REQUIRED&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, validation):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;VALIDATION_FAILED&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-profiles--matriId--report" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-profiles--matriId--report"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-profiles--matriId--report"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-profiles--matriId--report" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-profiles--matriId--report">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-profiles--matriId--report" data-method="POST"
      data-path="api/v1/profiles/{matriId}/report"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-profiles--matriId--report', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-profiles--matriId--report"
                    onclick="tryItOut('POSTapi-v1-profiles--matriId--report');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-profiles--matriId--report"
                    onclick="cancelTryOut('POSTapi-v1-profiles--matriId--report');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-profiles--matriId--report"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/profiles/{matriId}/report</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-profiles--matriId--report"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-profiles--matriId--report"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>matriId</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="matriId"                data-endpoint="POSTapi-v1-profiles--matriId--report"
               value="architecto"
               data-component="url">
    <br>
<p>Target's matri_id. Example: <code>architecto</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>reason</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="reason"                data-endpoint="POSTapi-v1-profiles--matriId--report"
               value="architecto"
               data-component="body">
    <br>
<p>One of: fake_profile, inappropriate_photo, harassment, fraud, already_married, wrong_info, other. Example: <code>architecto</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>description</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="description"                data-endpoint="POSTapi-v1-profiles--matriId--report"
               value="Eius et animi quos velit et."
               data-component="body">
    <br>
<p>Optional. Max 1000 chars. Free-form context for the admin reviewer. Example: <code>Eius et animi quos velit et.</code></p>
        </div>
        </form>

                <h1 id="saved-searches">Saved Searches</h1>

    

                                <h2 id="saved-searches-GETapi-v1-search-saved">List the authenticated user&#039;s saved searches, most recent first.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Returns them with Flutter-friendly field names (<code>name</code> / <code>filters</code>)
mapped from the DB's <code>search_name</code> / <code>criteria</code> columns.</p>

<span id="example-requests-GETapi-v1-search-saved">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/search/saved" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/search/saved"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-search-saved">
            <blockquote>
            <p>Example response (200, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: [
        {
            &quot;id&quot;: 1,
            &quot;name&quot;: &quot;Bangalore Hindu&quot;,
            &quot;filters&quot;: {
                &quot;religion&quot;: [
                    &quot;Hindu&quot;
                ],
                &quot;native_country&quot;: &quot;India&quot;
            },
            &quot;created_at&quot;: &quot;2026-04-25T...&quot;
        }
    ]
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, no-profile):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;PROFILE_REQUIRED&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-search-saved" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-search-saved"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-search-saved"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-search-saved" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-search-saved">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-search-saved" data-method="GET"
      data-path="api/v1/search/saved"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-search-saved', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-search-saved"
                    onclick="tryItOut('GETapi-v1-search-saved');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-search-saved"
                    onclick="cancelTryOut('GETapi-v1-search-saved');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-search-saved"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/search/saved</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-search-saved"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-search-saved"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="saved-searches-POSTapi-v1-search-saved">Save the current filter set. Enforces a per-profile quota of
MAX_SAVED_SEARCHES (10) — older rows must be deleted before
additional ones can be created.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>API accepts <code>name</code> + <code>filters</code>; stored internally as <code>search_name</code></p>
<ul>
<li><code>criteria</code> (matches the web SavedSearch schema). Flutter never
sees the internal column names.</li>
</ul>

<span id="example-requests-POSTapi-v1-search-saved">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/search/saved" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"name\": \"b\",
    \"filters\": []
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/search/saved"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "name": "b",
    "filters": []
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-search-saved">
            <blockquote>
            <p>Example response (201, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;id&quot;: 1,
        &quot;name&quot;: &quot;Bangalore Hindu&quot;,
        &quot;filters&quot;: {
            &quot;religion&quot;: [
                &quot;Hindu&quot;
            ]
        },
        &quot;created_at&quot;: &quot;...&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, validation-failed):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;VALIDATION_FAILED&quot;,
        &quot;message&quot;: &quot;...&quot;,
        &quot;fields&quot;: {
            &quot;name&quot;: [
                &quot;...&quot;
            ]
        }
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, quota-exceeded):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;VALIDATION_FAILED&quot;,
        &quot;message&quot;: &quot;You&#039;ve reached the 10 saved searches limit. Delete one first.&quot;,
        &quot;fields&quot;: {
            &quot;name&quot;: [
                &quot;...&quot;
            ]
        }
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, no-profile):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;PROFILE_REQUIRED&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-search-saved" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-search-saved"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-search-saved"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-search-saved" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-search-saved">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-search-saved" data-method="POST"
      data-path="api/v1/search/saved"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-search-saved', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-search-saved"
                    onclick="tryItOut('POSTapi-v1-search-saved');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-search-saved"
                    onclick="cancelTryOut('POSTapi-v1-search-saved');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-search-saved"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/search/saved</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-search-saved"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-search-saved"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="name"                data-endpoint="POSTapi-v1-search-saved"
               value="b"
               data-component="body">
    <br>
<p>Must not be greater than 80 characters. Example: <code>b</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>filters</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="filters"                data-endpoint="POSTapi-v1-search-saved"
               value=""
               data-component="body">
    <br>

        </div>
        </form>

                    <h2 id="saved-searches-DELETEapi-v1-search-saved--savedSearch_id-">Delete a saved search. Only the owner can delete — 403 for
everyone else. Route-model binding + 404 handling comes from
Laravel&#039;s default ModelNotFoundException → ApiExceptionHandler
mapping (already in place).</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-DELETEapi-v1-search-saved--savedSearch_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "http://localhost:8000/api/v1/search/saved/16" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/search/saved/16"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-DELETEapi-v1-search-saved--savedSearch_id-">
            <blockquote>
            <p>Example response (200, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;deleted&quot;: true,
        &quot;id&quot;: 1
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (403, not-owner):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;UNAUTHORIZED&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (404, not-found):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;NOT_FOUND&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, no-profile):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;PROFILE_REQUIRED&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-DELETEapi-v1-search-saved--savedSearch_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEapi-v1-search-saved--savedSearch_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEapi-v1-search-saved--savedSearch_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEapi-v1-search-saved--savedSearch_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEapi-v1-search-saved--savedSearch_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEapi-v1-search-saved--savedSearch_id-" data-method="DELETE"
      data-path="api/v1/search/saved/{savedSearch_id}"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEapi-v1-search-saved--savedSearch_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEapi-v1-search-saved--savedSearch_id-"
                    onclick="tryItOut('DELETEapi-v1-search-saved--savedSearch_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEapi-v1-search-saved--savedSearch_id-"
                    onclick="cancelTryOut('DELETEapi-v1-search-saved--savedSearch_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEapi-v1-search-saved--savedSearch_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>api/v1/search/saved/{savedSearch_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEapi-v1-search-saved--savedSearch_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="DELETEapi-v1-search-saved--savedSearch_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>savedSearch_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="savedSearch_id"                data-endpoint="DELETEapi-v1-search-saved--savedSearch_id-"
               value="16"
               data-component="url">
    <br>
<p>The ID of the savedSearch. Example: <code>16</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>savedSearch</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="savedSearch"                data-endpoint="DELETEapi-v1-search-saved--savedSearch_id-"
               value="16"
               data-component="url">
    <br>
<p>The SavedSearch id. Example: <code>16</code></p>
            </div>
                    </form>

                <h1 id="search">Search</h1>

    

                                <h2 id="search-GETapi-v1-search-partner">Partner search.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-GETapi-v1-search-partner">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/search/partner?page=16&amp;per_page=16&amp;sort=architecto&amp;age_from=16&amp;age_to=16&amp;height_from=16&amp;height_to=16&amp;religion[]=architecto&amp;caste[]=architecto&amp;denomination[]=architecto&amp;mother_tongue[]=architecto&amp;marital_status[]=architecto&amp;body_type[]=architecto&amp;physical_status[]=architecto&amp;education[]=architecto&amp;occupation[]=architecto&amp;annual_income[]=architecto&amp;working_country=architecto&amp;native_country=architecto&amp;family_status[]=architecto&amp;diet[]=architecto&amp;smoking[]=architecto&amp;drinking[]=architecto&amp;with_photo=" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/search/partner"
);

const params = {
    "page": "16",
    "per_page": "16",
    "sort": "architecto",
    "age_from": "16",
    "age_to": "16",
    "height_from": "16",
    "height_to": "16",
    "religion[0]": "architecto",
    "caste[0]": "architecto",
    "denomination[0]": "architecto",
    "mother_tongue[0]": "architecto",
    "marital_status[0]": "architecto",
    "body_type[0]": "architecto",
    "physical_status[0]": "architecto",
    "education[0]": "architecto",
    "occupation[0]": "architecto",
    "annual_income[0]": "architecto",
    "working_country": "architecto",
    "native_country": "architecto",
    "family_status[0]": "architecto",
    "diet[0]": "architecto",
    "smoking[0]": "architecto",
    "drinking[0]": "architecto",
    "with_photo": "0",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-search-partner">
            <blockquote>
            <p>Example response (200, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: [
        {
            &quot;matri_id&quot;: &quot;AM100042&quot;,
            &quot;full_name&quot;: &quot;...&quot;,
            &quot;age&quot;: 28
        }
    ],
    &quot;meta&quot;: {
        &quot;page&quot;: 1,
        &quot;per_page&quot;: 20,
        &quot;total&quot;: 137,
        &quot;last_page&quot;: 7,
        &quot;applied_filters&quot;: {
            &quot;religion&quot;: [
                &quot;Hindu&quot;
            ],
            &quot;age_from&quot;: 25
        }
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (401, unauthenticated):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;UNAUTHENTICATED&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, no-profile):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;PROFILE_REQUIRED&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (429, throttled):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;THROTTLED&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-search-partner" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-search-partner"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-search-partner"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-search-partner" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-search-partner">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-search-partner" data-method="GET"
      data-path="api/v1/search/partner"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-search-partner', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-search-partner"
                    onclick="tryItOut('GETapi-v1-search-partner');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-search-partner"
                    onclick="cancelTryOut('GETapi-v1-search-partner');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-search-partner"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/search/partner</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-search-partner"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-search-partner"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Query Parameters</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="page"                data-endpoint="GETapi-v1-search-partner"
               value="16"
               data-component="query">
    <br>
<p>Page number (default 1). Example: <code>16</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>per_page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="per_page"                data-endpoint="GETapi-v1-search-partner"
               value="16"
               data-component="query">
    <br>
<p>Results per page (default 20, max 50). Example: <code>16</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>sort</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="sort"                data-endpoint="GETapi-v1-search-partner"
               value="architecto"
               data-component="query">
    <br>
<p>One of: relevance (default), newest, recently_active, age_low, age_high. Example: <code>architecto</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>age_from</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="age_from"                data-endpoint="GETapi-v1-search-partner"
               value="16"
               data-component="query">
    <br>
<p>Minimum age. Example: <code>16</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>age_to</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="age_to"                data-endpoint="GETapi-v1-search-partner"
               value="16"
               data-component="query">
    <br>
<p>Maximum age. Example: <code>16</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>height_from</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="height_from"                data-endpoint="GETapi-v1-search-partner"
               value="16"
               data-component="query">
    <br>
<p>Minimum height in cm. Example: <code>16</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>height_to</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="height_to"                data-endpoint="GETapi-v1-search-partner"
               value="16"
               data-component="query">
    <br>
<p>Maximum height in cm. Example: <code>16</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>religion</code></b>&nbsp;&nbsp;
<small>string[]</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="religion[0]"                data-endpoint="GETapi-v1-search-partner"
               data-component="query">
        <input type="text" style="display: none"
               name="religion[1]"                data-endpoint="GETapi-v1-search-partner"
               data-component="query">
    <br>
<p>Religion filter (multi-select, comma-separated).</p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>caste</code></b>&nbsp;&nbsp;
<small>string[]</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="caste[0]"                data-endpoint="GETapi-v1-search-partner"
               data-component="query">
        <input type="text" style="display: none"
               name="caste[1]"                data-endpoint="GETapi-v1-search-partner"
               data-component="query">
    <br>
<p>Caste filter.</p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>denomination</code></b>&nbsp;&nbsp;
<small>string[]</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="denomination[0]"                data-endpoint="GETapi-v1-search-partner"
               data-component="query">
        <input type="text" style="display: none"
               name="denomination[1]"                data-endpoint="GETapi-v1-search-partner"
               data-component="query">
    <br>
<p>Denomination filter (usually chained with religion).</p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>mother_tongue</code></b>&nbsp;&nbsp;
<small>string[]</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="mother_tongue[0]"                data-endpoint="GETapi-v1-search-partner"
               data-component="query">
        <input type="text" style="display: none"
               name="mother_tongue[1]"                data-endpoint="GETapi-v1-search-partner"
               data-component="query">
    <br>
<p>Mother tongue filter.</p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>marital_status</code></b>&nbsp;&nbsp;
<small>string[]</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="marital_status[0]"                data-endpoint="GETapi-v1-search-partner"
               data-component="query">
        <input type="text" style="display: none"
               name="marital_status[1]"                data-endpoint="GETapi-v1-search-partner"
               data-component="query">
    <br>
<p>Marital status filter.</p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>body_type</code></b>&nbsp;&nbsp;
<small>string[]</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="body_type[0]"                data-endpoint="GETapi-v1-search-partner"
               data-component="query">
        <input type="text" style="display: none"
               name="body_type[1]"                data-endpoint="GETapi-v1-search-partner"
               data-component="query">
    <br>
<p>Body type filter.</p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>physical_status</code></b>&nbsp;&nbsp;
<small>string[]</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="physical_status[0]"                data-endpoint="GETapi-v1-search-partner"
               data-component="query">
        <input type="text" style="display: none"
               name="physical_status[1]"                data-endpoint="GETapi-v1-search-partner"
               data-component="query">
    <br>
<p>Physical status filter.</p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>education</code></b>&nbsp;&nbsp;
<small>string[]</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="education[0]"                data-endpoint="GETapi-v1-search-partner"
               data-component="query">
        <input type="text" style="display: none"
               name="education[1]"                data-endpoint="GETapi-v1-search-partner"
               data-component="query">
    <br>
<p>Education level filter.</p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>occupation</code></b>&nbsp;&nbsp;
<small>string[]</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="occupation[0]"                data-endpoint="GETapi-v1-search-partner"
               data-component="query">
        <input type="text" style="display: none"
               name="occupation[1]"                data-endpoint="GETapi-v1-search-partner"
               data-component="query">
    <br>
<p>Occupation filter.</p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>annual_income</code></b>&nbsp;&nbsp;
<small>string[]</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="annual_income[0]"                data-endpoint="GETapi-v1-search-partner"
               data-component="query">
        <input type="text" style="display: none"
               name="annual_income[1]"                data-endpoint="GETapi-v1-search-partner"
               data-component="query">
    <br>
<p>Income bracket filter.</p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>working_country</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="working_country"                data-endpoint="GETapi-v1-search-partner"
               value="architecto"
               data-component="query">
    <br>
<p>Single country filter (not array). Example: <code>architecto</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>native_country</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="native_country"                data-endpoint="GETapi-v1-search-partner"
               value="architecto"
               data-component="query">
    <br>
<p>Single country filter (not array). Example: <code>architecto</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>family_status</code></b>&nbsp;&nbsp;
<small>string[]</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="family_status[0]"                data-endpoint="GETapi-v1-search-partner"
               data-component="query">
        <input type="text" style="display: none"
               name="family_status[1]"                data-endpoint="GETapi-v1-search-partner"
               data-component="query">
    <br>
<p>Family status filter.</p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>diet</code></b>&nbsp;&nbsp;
<small>string[]</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="diet[0]"                data-endpoint="GETapi-v1-search-partner"
               data-component="query">
        <input type="text" style="display: none"
               name="diet[1]"                data-endpoint="GETapi-v1-search-partner"
               data-component="query">
    <br>
<p>Diet filter.</p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>smoking</code></b>&nbsp;&nbsp;
<small>string[]</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="smoking[0]"                data-endpoint="GETapi-v1-search-partner"
               data-component="query">
        <input type="text" style="display: none"
               name="smoking[1]"                data-endpoint="GETapi-v1-search-partner"
               data-component="query">
    <br>
<p>Smoking filter.</p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>drinking</code></b>&nbsp;&nbsp;
<small>string[]</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="drinking[0]"                data-endpoint="GETapi-v1-search-partner"
               data-component="query">
        <input type="text" style="display: none"
               name="drinking[1]"                data-endpoint="GETapi-v1-search-partner"
               data-component="query">
    <br>
<p>Drinking filter.</p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>with_photo</code></b>&nbsp;&nbsp;
<small>boolean</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <label data-endpoint="GETapi-v1-search-partner" style="display: none">
            <input type="radio" name="with_photo"
                   value="1"
                   data-endpoint="GETapi-v1-search-partner"
                   data-component="query"             >
            <code>true</code>
        </label>
        <label data-endpoint="GETapi-v1-search-partner" style="display: none">
            <input type="radio" name="with_photo"
                   value="0"
                   data-endpoint="GETapi-v1-search-partner"
                   data-component="query"             >
            <code>false</code>
        </label>
    <br>
<p>If true, only return profiles with a primary photo. Example: <code>false</code></p>
            </div>
                </form>

                    <h2 id="search-GETapi-v1-search-keyword">Free-text search across 7 profile columns using LIKE wildcards.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Mirrors web's SearchController::index() keyword branch, searching:
profiles.full_name, profiles.about_me, profiles.matri_id,
educationDetail.occupation_detail, educationDetail.employer_name,
religiousInfo.religion, religiousInfo.denomination.</p>
<p>Uses baseQuery() for the same 7-gate pre-filter as partner search.
Blocked / hidden / suspended profiles never appear regardless of
what the caller types.</p>

<span id="example-requests-GETapi-v1-search-keyword">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/search/keyword?q=architecto&amp;per_page=16" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"q\": \"b\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/search/keyword"
);

const params = {
    "q": "architecto",
    "per_page": "16",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "q": "b"
};

fetch(url, {
    method: "GET",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-search-keyword">
            <blockquote>
            <p>Example response (200, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: [
        {
            &quot;matri_id&quot;: &quot;AM100042&quot;,
            &quot;full_name&quot;: &quot;...&quot;
        }
    ],
    &quot;meta&quot;: {
        &quot;page&quot;: 1,
        &quot;per_page&quot;: 20,
        &quot;total&quot;: 3,
        &quot;last_page&quot;: 1,
        &quot;query_term&quot;: &quot;Bangalore&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, no-profile):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;PROFILE_REQUIRED&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, validation-failed):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;VALIDATION_FAILED&quot;,
        &quot;message&quot;: &quot;...&quot;,
        &quot;fields&quot;: {
            &quot;q&quot;: [
                &quot;...&quot;
            ]
        }
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (429, throttled):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;THROTTLED&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-search-keyword" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-search-keyword"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-search-keyword"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-search-keyword" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-search-keyword">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-search-keyword" data-method="GET"
      data-path="api/v1/search/keyword"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-search-keyword', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-search-keyword"
                    onclick="tryItOut('GETapi-v1-search-keyword');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-search-keyword"
                    onclick="cancelTryOut('GETapi-v1-search-keyword');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-search-keyword"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/search/keyword</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-search-keyword"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-search-keyword"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Query Parameters</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>q</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="q"                data-endpoint="GETapi-v1-search-keyword"
               value="architecto"
               data-component="query">
    <br>
<p>Search term (2-100 chars). Example: <code>architecto</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>per_page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="per_page"                data-endpoint="GETapi-v1-search-keyword"
               value="16"
               data-component="query">
    <br>
<p>Results per page (default 20, max 50). Example: <code>16</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>q</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="q"                data-endpoint="GETapi-v1-search-keyword"
               value="b"
               data-component="body">
    <br>
<p>Must be at least 2 characters. Must not be greater than 100 characters. Example: <code>b</code></p>
        </div>
        </form>

                    <h2 id="search-GETapi-v1-search-id--matriId-">Direct lookup by matri_id. Returns a ProfileCardResource payload
for quick profile access (Flutter&#039;s &quot;search by ID&quot; tab).</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Matri IDs are normalised to uppercase before the lookup since
users may type them in any case. On any access-gate failure
(blocked, hidden, suspended, same-gender, etc.) returns 404
NOT_FOUND with the same body as "profile doesn't exist" —
anti-enumeration, matches step-5's GET /profiles/{matriId}.</p>

<span id="example-requests-GETapi-v1-search-id--matriId-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/search/id/architecto" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/search/id/architecto"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-search-id--matriId-">
            <blockquote>
            <p>Example response (200, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;matri_id&quot;: &quot;AM100042&quot;,
        &quot;full_name&quot;: &quot;...&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (404, not-found-or-restricted):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;NOT_FOUND&quot;,
        &quot;message&quot;: &quot;No profile with that ID.&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, no-profile):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;PROFILE_REQUIRED&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-search-id--matriId-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-search-id--matriId-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-search-id--matriId-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-search-id--matriId-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-search-id--matriId-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-search-id--matriId-" data-method="GET"
      data-path="api/v1/search/id/{matriId}"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-search-id--matriId-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-search-id--matriId-"
                    onclick="tryItOut('GETapi-v1-search-id--matriId-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-search-id--matriId-"
                    onclick="cancelTryOut('GETapi-v1-search-id--matriId-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-search-id--matriId-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/search/id/{matriId}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-search-id--matriId-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-search-id--matriId-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>matriId</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="matriId"                data-endpoint="GETapi-v1-search-id--matriId-"
               value="architecto"
               data-component="url">
    <br>
<p>The profile's matri_id (e.g. AM100042). Example: <code>architecto</code></p>
            </div>
                    </form>

                <h1 id="settings">Settings</h1>

    

                                <h2 id="settings-GETapi-v1-settings">Full settings dump — visibility, alerts, auth flags, account
status. Flutter renders the settings screen entirely from this
single payload.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-GETapi-v1-settings">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/settings" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/settings"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-settings">
            <blockquote>
            <p>Example response (200, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
  &quot;success&quot;: true,
  &quot;data&quot;: {
    &quot;visibility&quot;: {&quot;show_profile_to&quot;: &quot;all&quot;, &quot;only_same_religion&quot;: false, &quot;only_same_denomination&quot;: false, &quot;only_same_mother_tongue&quot;: false, &quot;is_hidden&quot;: false},
    &quot;alerts&quot;: {&quot;email_interest&quot;: true, &quot;push_interest&quot;: true, &quot;quiet_hours_start&quot;: null, &quot;quiet_hours_end&quot;: null, ...},
    &quot;auth&quot;: {&quot;has_password&quot;: true},
    &quot;account&quot;: {&quot;email&quot;: &quot;...&quot;, &quot;phone&quot;: &quot;...&quot;, &quot;email_verified&quot;: true, &quot;phone_verified&quot;: true}
  }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, no-profile):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;PROFILE_REQUIRED&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-settings" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-settings"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-settings"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-settings" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-settings">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-settings" data-method="GET"
      data-path="api/v1/settings"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-settings', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-settings"
                    onclick="tryItOut('GETapi-v1-settings');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-settings"
                    onclick="cancelTryOut('GETapi-v1-settings');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-settings"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/settings</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-settings"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-settings"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="settings-PUTapi-v1-settings-visibility">Update profile-visibility toggles. PATCH-style — send only the
keys you want to change.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-PUTapi-v1-settings-visibility">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PUT \
    "http://localhost:8000/api/v1/settings/visibility" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"show_profile_to\": \"architecto\",
    \"only_same_religion\": false,
    \"only_same_denomination\": false,
    \"only_same_mother_tongue\": false
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/settings/visibility"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "show_profile_to": "architecto",
    "only_same_religion": false,
    "only_same_denomination": false,
    "only_same_mother_tongue": false
};

fetch(url, {
    method: "PUT",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PUTapi-v1-settings-visibility">
            <blockquote>
            <p>Example response (200, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;updated&quot;: true
    }
}</code>
 </pre>
    </span>
<span id="execution-results-PUTapi-v1-settings-visibility" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PUTapi-v1-settings-visibility"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PUTapi-v1-settings-visibility"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PUTapi-v1-settings-visibility" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PUTapi-v1-settings-visibility">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PUTapi-v1-settings-visibility" data-method="PUT"
      data-path="api/v1/settings/visibility"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PUTapi-v1-settings-visibility', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PUTapi-v1-settings-visibility"
                    onclick="tryItOut('PUTapi-v1-settings-visibility');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PUTapi-v1-settings-visibility"
                    onclick="cancelTryOut('PUTapi-v1-settings-visibility');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PUTapi-v1-settings-visibility"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-darkblue">PUT</small>
            <b><code>api/v1/settings/visibility</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PUTapi-v1-settings-visibility"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PUTapi-v1-settings-visibility"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>show_profile_to</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="show_profile_to"                data-endpoint="PUTapi-v1-settings-visibility"
               value="architecto"
               data-component="body">
    <br>
<p>Optional. all|premium|matches. Example: <code>architecto</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>only_same_religion</code></b>&nbsp;&nbsp;
<small>boolean</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <label data-endpoint="PUTapi-v1-settings-visibility" style="display: none">
            <input type="radio" name="only_same_religion"
                   value="true"
                   data-endpoint="PUTapi-v1-settings-visibility"
                   data-component="body"             >
            <code>true</code>
        </label>
        <label data-endpoint="PUTapi-v1-settings-visibility" style="display: none">
            <input type="radio" name="only_same_religion"
                   value="false"
                   data-endpoint="PUTapi-v1-settings-visibility"
                   data-component="body"             >
            <code>false</code>
        </label>
    <br>
<p>Optional. Example: <code>false</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>only_same_denomination</code></b>&nbsp;&nbsp;
<small>boolean</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <label data-endpoint="PUTapi-v1-settings-visibility" style="display: none">
            <input type="radio" name="only_same_denomination"
                   value="true"
                   data-endpoint="PUTapi-v1-settings-visibility"
                   data-component="body"             >
            <code>true</code>
        </label>
        <label data-endpoint="PUTapi-v1-settings-visibility" style="display: none">
            <input type="radio" name="only_same_denomination"
                   value="false"
                   data-endpoint="PUTapi-v1-settings-visibility"
                   data-component="body"             >
            <code>false</code>
        </label>
    <br>
<p>Optional. Example: <code>false</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>only_same_mother_tongue</code></b>&nbsp;&nbsp;
<small>boolean</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <label data-endpoint="PUTapi-v1-settings-visibility" style="display: none">
            <input type="radio" name="only_same_mother_tongue"
                   value="true"
                   data-endpoint="PUTapi-v1-settings-visibility"
                   data-component="body"             >
            <code>true</code>
        </label>
        <label data-endpoint="PUTapi-v1-settings-visibility" style="display: none">
            <input type="radio" name="only_same_mother_tongue"
                   value="false"
                   data-endpoint="PUTapi-v1-settings-visibility"
                   data-component="body"             >
            <code>false</code>
        </label>
    <br>
<p>Optional. Example: <code>false</code></p>
        </div>
        </form>

                    <h2 id="settings-PUTapi-v1-settings-alerts">Update notification preferences. PATCH-style — merges with
existing prefs so unchanged keys retain their values.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Quiet-hours window is optional; both start and end must be
present together (or both null/absent). Used by
NotificationService::sendPush to skip non-priority pushes
during the user's chosen quiet hours.</p>

<span id="example-requests-PUTapi-v1-settings-alerts">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PUT \
    "http://localhost:8000/api/v1/settings/alerts" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"email_interest\": false,
    \"email_accepted\": false,
    \"email_declined\": false,
    \"email_views\": false,
    \"email_promotions\": false,
    \"push_interest\": false,
    \"push_accepted\": false,
    \"push_declined\": false,
    \"push_views\": false,
    \"push_promotions\": false,
    \"quiet_hours_start\": \"architecto\",
    \"quiet_hours_end\": \"architecto\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/settings/alerts"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "email_interest": false,
    "email_accepted": false,
    "email_declined": false,
    "email_views": false,
    "email_promotions": false,
    "push_interest": false,
    "push_accepted": false,
    "push_declined": false,
    "push_views": false,
    "push_promotions": false,
    "quiet_hours_start": "architecto",
    "quiet_hours_end": "architecto"
};

fetch(url, {
    method: "PUT",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PUTapi-v1-settings-alerts">
            <blockquote>
            <p>Example response (200, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;updated&quot;: true
    }
}</code>
 </pre>
    </span>
<span id="execution-results-PUTapi-v1-settings-alerts" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PUTapi-v1-settings-alerts"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PUTapi-v1-settings-alerts"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PUTapi-v1-settings-alerts" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PUTapi-v1-settings-alerts">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PUTapi-v1-settings-alerts" data-method="PUT"
      data-path="api/v1/settings/alerts"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PUTapi-v1-settings-alerts', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PUTapi-v1-settings-alerts"
                    onclick="tryItOut('PUTapi-v1-settings-alerts');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PUTapi-v1-settings-alerts"
                    onclick="cancelTryOut('PUTapi-v1-settings-alerts');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PUTapi-v1-settings-alerts"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-darkblue">PUT</small>
            <b><code>api/v1/settings/alerts</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PUTapi-v1-settings-alerts"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PUTapi-v1-settings-alerts"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>email_interest</code></b>&nbsp;&nbsp;
<small>boolean</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <label data-endpoint="PUTapi-v1-settings-alerts" style="display: none">
            <input type="radio" name="email_interest"
                   value="true"
                   data-endpoint="PUTapi-v1-settings-alerts"
                   data-component="body"             >
            <code>true</code>
        </label>
        <label data-endpoint="PUTapi-v1-settings-alerts" style="display: none">
            <input type="radio" name="email_interest"
                   value="false"
                   data-endpoint="PUTapi-v1-settings-alerts"
                   data-component="body"             >
            <code>false</code>
        </label>
    <br>
<p>Optional. Example: <code>false</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>email_accepted</code></b>&nbsp;&nbsp;
<small>boolean</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <label data-endpoint="PUTapi-v1-settings-alerts" style="display: none">
            <input type="radio" name="email_accepted"
                   value="true"
                   data-endpoint="PUTapi-v1-settings-alerts"
                   data-component="body"             >
            <code>true</code>
        </label>
        <label data-endpoint="PUTapi-v1-settings-alerts" style="display: none">
            <input type="radio" name="email_accepted"
                   value="false"
                   data-endpoint="PUTapi-v1-settings-alerts"
                   data-component="body"             >
            <code>false</code>
        </label>
    <br>
<p>Optional. Example: <code>false</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>email_declined</code></b>&nbsp;&nbsp;
<small>boolean</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <label data-endpoint="PUTapi-v1-settings-alerts" style="display: none">
            <input type="radio" name="email_declined"
                   value="true"
                   data-endpoint="PUTapi-v1-settings-alerts"
                   data-component="body"             >
            <code>true</code>
        </label>
        <label data-endpoint="PUTapi-v1-settings-alerts" style="display: none">
            <input type="radio" name="email_declined"
                   value="false"
                   data-endpoint="PUTapi-v1-settings-alerts"
                   data-component="body"             >
            <code>false</code>
        </label>
    <br>
<p>Optional. Example: <code>false</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>email_views</code></b>&nbsp;&nbsp;
<small>boolean</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <label data-endpoint="PUTapi-v1-settings-alerts" style="display: none">
            <input type="radio" name="email_views"
                   value="true"
                   data-endpoint="PUTapi-v1-settings-alerts"
                   data-component="body"             >
            <code>true</code>
        </label>
        <label data-endpoint="PUTapi-v1-settings-alerts" style="display: none">
            <input type="radio" name="email_views"
                   value="false"
                   data-endpoint="PUTapi-v1-settings-alerts"
                   data-component="body"             >
            <code>false</code>
        </label>
    <br>
<p>Optional. Example: <code>false</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>email_promotions</code></b>&nbsp;&nbsp;
<small>boolean</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <label data-endpoint="PUTapi-v1-settings-alerts" style="display: none">
            <input type="radio" name="email_promotions"
                   value="true"
                   data-endpoint="PUTapi-v1-settings-alerts"
                   data-component="body"             >
            <code>true</code>
        </label>
        <label data-endpoint="PUTapi-v1-settings-alerts" style="display: none">
            <input type="radio" name="email_promotions"
                   value="false"
                   data-endpoint="PUTapi-v1-settings-alerts"
                   data-component="body"             >
            <code>false</code>
        </label>
    <br>
<p>Optional. Example: <code>false</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>push_interest</code></b>&nbsp;&nbsp;
<small>boolean</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <label data-endpoint="PUTapi-v1-settings-alerts" style="display: none">
            <input type="radio" name="push_interest"
                   value="true"
                   data-endpoint="PUTapi-v1-settings-alerts"
                   data-component="body"             >
            <code>true</code>
        </label>
        <label data-endpoint="PUTapi-v1-settings-alerts" style="display: none">
            <input type="radio" name="push_interest"
                   value="false"
                   data-endpoint="PUTapi-v1-settings-alerts"
                   data-component="body"             >
            <code>false</code>
        </label>
    <br>
<p>Optional. Example: <code>false</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>push_accepted</code></b>&nbsp;&nbsp;
<small>boolean</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <label data-endpoint="PUTapi-v1-settings-alerts" style="display: none">
            <input type="radio" name="push_accepted"
                   value="true"
                   data-endpoint="PUTapi-v1-settings-alerts"
                   data-component="body"             >
            <code>true</code>
        </label>
        <label data-endpoint="PUTapi-v1-settings-alerts" style="display: none">
            <input type="radio" name="push_accepted"
                   value="false"
                   data-endpoint="PUTapi-v1-settings-alerts"
                   data-component="body"             >
            <code>false</code>
        </label>
    <br>
<p>Optional. Example: <code>false</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>push_declined</code></b>&nbsp;&nbsp;
<small>boolean</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <label data-endpoint="PUTapi-v1-settings-alerts" style="display: none">
            <input type="radio" name="push_declined"
                   value="true"
                   data-endpoint="PUTapi-v1-settings-alerts"
                   data-component="body"             >
            <code>true</code>
        </label>
        <label data-endpoint="PUTapi-v1-settings-alerts" style="display: none">
            <input type="radio" name="push_declined"
                   value="false"
                   data-endpoint="PUTapi-v1-settings-alerts"
                   data-component="body"             >
            <code>false</code>
        </label>
    <br>
<p>Optional. Example: <code>false</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>push_views</code></b>&nbsp;&nbsp;
<small>boolean</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <label data-endpoint="PUTapi-v1-settings-alerts" style="display: none">
            <input type="radio" name="push_views"
                   value="true"
                   data-endpoint="PUTapi-v1-settings-alerts"
                   data-component="body"             >
            <code>true</code>
        </label>
        <label data-endpoint="PUTapi-v1-settings-alerts" style="display: none">
            <input type="radio" name="push_views"
                   value="false"
                   data-endpoint="PUTapi-v1-settings-alerts"
                   data-component="body"             >
            <code>false</code>
        </label>
    <br>
<p>Optional. Example: <code>false</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>push_promotions</code></b>&nbsp;&nbsp;
<small>boolean</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <label data-endpoint="PUTapi-v1-settings-alerts" style="display: none">
            <input type="radio" name="push_promotions"
                   value="true"
                   data-endpoint="PUTapi-v1-settings-alerts"
                   data-component="body"             >
            <code>true</code>
        </label>
        <label data-endpoint="PUTapi-v1-settings-alerts" style="display: none">
            <input type="radio" name="push_promotions"
                   value="false"
                   data-endpoint="PUTapi-v1-settings-alerts"
                   data-component="body"             >
            <code>false</code>
        </label>
    <br>
<p>Optional. Example: <code>false</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>quiet_hours_start</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="quiet_hours_start"                data-endpoint="PUTapi-v1-settings-alerts"
               value="architecto"
               data-component="body">
    <br>
<p>Optional. HH:MM 24h. Pair with quiet_hours_end. Example: <code>architecto</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>quiet_hours_end</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="quiet_hours_end"                data-endpoint="PUTapi-v1-settings-alerts"
               value="architecto"
               data-component="body">
    <br>
<p>Optional. HH:MM 24h. Example: <code>architecto</code></p>
        </div>
        </form>

                    <h2 id="settings-PUTapi-v1-settings-password">Change password + revoke every OTHER active session/token.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Current session stays alive so Flutter doesn't get logged out
mid-update.</p>

<span id="example-requests-PUTapi-v1-settings-password">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PUT \
    "http://localhost:8000/api/v1/settings/password" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"current_password\": \"architecto\",
    \"new_password\": \"architecto\",
    \"new_password_confirmation\": \"architecto\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/settings/password"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "current_password": "architecto",
    "new_password": "architecto",
    "new_password_confirmation": "architecto"
};

fetch(url, {
    method: "PUT",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PUTapi-v1-settings-password">
            <blockquote>
            <p>Example response (200, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;password_changed&quot;: true,
        &quot;tokens_revoked_count&quot;: 2
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, wrong-current):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;VALIDATION_FAILED&quot;,
        &quot;fields&quot;: {
            &quot;current_password&quot;: [
                &quot;Incorrect password.&quot;
            ]
        }
    }
}</code>
 </pre>
    </span>
<span id="execution-results-PUTapi-v1-settings-password" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PUTapi-v1-settings-password"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PUTapi-v1-settings-password"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PUTapi-v1-settings-password" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PUTapi-v1-settings-password">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PUTapi-v1-settings-password" data-method="PUT"
      data-path="api/v1/settings/password"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PUTapi-v1-settings-password', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PUTapi-v1-settings-password"
                    onclick="tryItOut('PUTapi-v1-settings-password');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PUTapi-v1-settings-password"
                    onclick="cancelTryOut('PUTapi-v1-settings-password');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PUTapi-v1-settings-password"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-darkblue">PUT</small>
            <b><code>api/v1/settings/password</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PUTapi-v1-settings-password"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PUTapi-v1-settings-password"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>current_password</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="current_password"                data-endpoint="PUTapi-v1-settings-password"
               value="architecto"
               data-component="body">
    <br>
<p>Current password. Example: <code>architecto</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>new_password</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="new_password"                data-endpoint="PUTapi-v1-settings-password"
               value="architecto"
               data-component="body">
    <br>
<p>Min 6, max 14, must match new_password_confirmation. Example: <code>architecto</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>new_password_confirmation</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="new_password_confirmation"                data-endpoint="PUTapi-v1-settings-password"
               value="architecto"
               data-component="body">
    <br>
<p>Confirmation. Example: <code>architecto</code></p>
        </div>
        </form>

                    <h2 id="settings-POSTapi-v1-settings-hide">Hide profile from search + recommendations.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-v1-settings-hide">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/settings/hide" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/settings/hide"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-settings-hide">
            <blockquote>
            <p>Example response (200, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;is_hidden&quot;: true
    }
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-settings-hide" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-settings-hide"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-settings-hide"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-settings-hide" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-settings-hide">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-settings-hide" data-method="POST"
      data-path="api/v1/settings/hide"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-settings-hide', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-settings-hide"
                    onclick="tryItOut('POSTapi-v1-settings-hide');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-settings-hide"
                    onclick="cancelTryOut('POSTapi-v1-settings-hide');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-settings-hide"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/settings/hide</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-settings-hide"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-settings-hide"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="settings-POSTapi-v1-settings-unhide">Unhide profile (reverse of hide).</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-v1-settings-unhide">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/settings/unhide" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/settings/unhide"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-settings-unhide">
            <blockquote>
            <p>Example response (200, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;is_hidden&quot;: false
    }
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-settings-unhide" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-settings-unhide"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-settings-unhide"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-settings-unhide" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-settings-unhide">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-settings-unhide" data-method="POST"
      data-path="api/v1/settings/unhide"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-settings-unhide', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-settings-unhide"
                    onclick="tryItOut('POSTapi-v1-settings-unhide');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-settings-unhide"
                    onclick="cancelTryOut('POSTapi-v1-settings-unhide');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-settings-unhide"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/settings/unhide</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-settings-unhide"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-settings-unhide"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="settings-POSTapi-v1-settings-delete">Soft-delete the account. Sets is_active=false + is_hidden=true,
stores the deletion reason (with optional free-form feedback
folded in for &quot;other&quot;), then SoftDeletes the profile (auto-sets
deleted_at via the Profile model trait), and revokes every
Sanctum token so the current session is dropped too.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Reactivation is admin-only — buyer's support team handles it.</p>
<p>Schema note: there is NO <code>deletion_feedback</code> column. Optional
text is concatenated into <code>deletion_reason</code> like the web flow
already does ("Other: <text>").</p>

<span id="example-requests-POSTapi-v1-settings-delete">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/settings/delete" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"password\": \"|]|{+-\",
    \"reason\": \"architecto\",
    \"feedback\": \"architecto\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/settings/delete"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "password": "|]|{+-",
    "reason": "architecto",
    "feedback": "architecto"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-settings-delete">
            <blockquote>
            <p>Example response (200, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;deleted&quot;: true,
        &quot;logged_out&quot;: true
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, wrong-password):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;VALIDATION_FAILED&quot;,
        &quot;fields&quot;: {
            &quot;password&quot;: [
                &quot;Password does not match.&quot;
            ]
        }
    }
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-settings-delete" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-settings-delete"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-settings-delete"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-settings-delete" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-settings-delete">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-settings-delete" data-method="POST"
      data-path="api/v1/settings/delete"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-settings-delete', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-settings-delete"
                    onclick="tryItOut('POSTapi-v1-settings-delete');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-settings-delete"
                    onclick="cancelTryOut('POSTapi-v1-settings-delete');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-settings-delete"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/settings/delete</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-settings-delete"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-settings-delete"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>password</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="password"                data-endpoint="POSTapi-v1-settings-delete"
               value="|]|{+-"
               data-component="body">
    <br>
<p>Password confirmation — defends against accidental delete. Example: <code>|]|{+-</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>reason</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="reason"                data-endpoint="POSTapi-v1-settings-delete"
               value="architecto"
               data-component="body">
    <br>
<p>One of: found_partner, poor_experience, not_interested, other. Example: <code>architecto</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>feedback</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="feedback"                data-endpoint="POSTapi-v1-settings-delete"
               value="architecto"
               data-component="body">
    <br>
<p>Optional. Free-form text (max 2000) — folded into deletion_reason when reason=other. Example: <code>architecto</code></p>
        </div>
        </form>

                <h1 id="shortlist">Shortlist</h1>

    

                                <h2 id="shortlist-GETapi-v1-shortlist">Paginated list of profiles the viewer has shortlisted, latest first.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-GETapi-v1-shortlist">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/shortlist?page=16&amp;per_page=16" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/shortlist"
);

const params = {
    "page": "16",
    "per_page": "16",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-shortlist">
            <blockquote>
            <p>Example response (200, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
  &quot;success&quot;: true,
  &quot;data&quot;: [{&quot;matri_id&quot;: &quot;AM000123&quot;, &quot;full_name&quot;: &quot;...&quot;, &quot;is_shortlisted&quot;: true, ...}],
  &quot;meta&quot;: {&quot;page&quot;: 1, &quot;per_page&quot;: 20, &quot;total&quot;: 1, &quot;last_page&quot;: 1}
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, no-profile):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;PROFILE_REQUIRED&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-shortlist" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-shortlist"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-shortlist"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-shortlist" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-shortlist">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-shortlist" data-method="GET"
      data-path="api/v1/shortlist"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-shortlist', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-shortlist"
                    onclick="tryItOut('GETapi-v1-shortlist');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-shortlist"
                    onclick="cancelTryOut('GETapi-v1-shortlist');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-shortlist"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/shortlist</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-shortlist"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-shortlist"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Query Parameters</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="page"                data-endpoint="GETapi-v1-shortlist"
               value="16"
               data-component="query">
    <br>
<p>Optional. Page number. Default 1. Example: <code>16</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>per_page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="per_page"                data-endpoint="GETapi-v1-shortlist"
               value="16"
               data-component="query">
    <br>
<p>Optional. 1-50. Default 20. Example: <code>16</code></p>
            </div>
                </form>

                    <h2 id="shortlist-POSTapi-v1-profiles--matriId--shortlist">Toggle shortlist for a target profile. Idempotent against state —
each call flips, response carries the authoritative new state.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-v1-profiles--matriId--shortlist">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/profiles/architecto/shortlist" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/profiles/architecto/shortlist"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-profiles--matriId--shortlist">
            <blockquote>
            <p>Example response (200, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;is_shortlisted&quot;: true,
        &quot;shortlist_count&quot;: 5
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (404, not-found):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;NOT_FOUND&quot;,
        &quot;message&quot;: &quot;Profile not available.&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, invalid-target):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;INVALID_TARGET&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, no-profile):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;PROFILE_REQUIRED&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-profiles--matriId--shortlist" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-profiles--matriId--shortlist"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-profiles--matriId--shortlist"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-profiles--matriId--shortlist" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-profiles--matriId--shortlist">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-profiles--matriId--shortlist" data-method="POST"
      data-path="api/v1/profiles/{matriId}/shortlist"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-profiles--matriId--shortlist', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-profiles--matriId--shortlist"
                    onclick="tryItOut('POSTapi-v1-profiles--matriId--shortlist');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-profiles--matriId--shortlist"
                    onclick="cancelTryOut('POSTapi-v1-profiles--matriId--shortlist');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-profiles--matriId--shortlist"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/profiles/{matriId}/shortlist</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-profiles--matriId--shortlist"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-profiles--matriId--shortlist"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>matriId</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="matriId"                data-endpoint="POSTapi-v1-profiles--matriId--shortlist"
               value="architecto"
               data-component="url">
    <br>
<p>Target's matri_id (uppercase alphanumeric). Example: <code>architecto</code></p>
            </div>
                    </form>

                <h1 id="static-pages">Static Pages</h1>

    

                                <h2 id="static-pages-GETapi-v1-static-pages--slug-">GET api/v1/static-pages/{slug}</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-static-pages--slug-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/static-pages/architecto" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/static-pages/architecto"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-static-pages--slug-">
            <blockquote>
            <p>Example response (200, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;slug&quot;: &quot;about&quot;,
        &quot;title&quot;: &quot;About Us&quot;,
        &quot;content_html&quot;: &quot;&lt;p&gt;...&lt;/p&gt;&quot;,
        &quot;meta_title&quot;: &quot;...&quot;,
        &quot;meta_description&quot;: &quot;...&quot;,
        &quot;updated_at&quot;: &quot;2026-04-26T...&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (404, not-found):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;NOT_FOUND&quot;,
        &quot;message&quot;: &quot;Page not found.&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-static-pages--slug-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-static-pages--slug-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-static-pages--slug-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-static-pages--slug-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-static-pages--slug-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-static-pages--slug-" data-method="GET"
      data-path="api/v1/static-pages/{slug}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-static-pages--slug-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-static-pages--slug-"
                    onclick="tryItOut('GETapi-v1-static-pages--slug-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-static-pages--slug-"
                    onclick="cancelTryOut('GETapi-v1-static-pages--slug-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-static-pages--slug-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/static-pages/{slug}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-static-pages--slug-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-static-pages--slug-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>slug</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="slug"                data-endpoint="GETapi-v1-static-pages--slug-"
               value="architecto"
               data-component="url">
    <br>
<p>Page slug (lowercase, hyphens only). Example: <code>architecto</code></p>
            </div>
                    </form>

                <h1 id="success-stories">Success Stories</h1>

    

                                <h2 id="success-stories-GETapi-v1-success-stories">Public feed of approved success stories, latest weddings first.</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-success-stories">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/success-stories?page=16&amp;per_page=16" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/success-stories"
);

const params = {
    "page": "16",
    "per_page": "16",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-success-stories">
            <blockquote>
            <p>Example response (200, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: [
        {
            &quot;id&quot;: 1,
            &quot;couple_names&quot;: &quot;Anita &amp; Ravi&quot;,
            &quot;story&quot;: &quot;...&quot;,
            &quot;photo_url&quot;: &quot;https://.../photo.jpg&quot;,
            &quot;wedding_date&quot;: &quot;2026-02-14&quot;,
            &quot;location&quot;: &quot;Mumbai&quot;
        }
    ],
    &quot;meta&quot;: {
        &quot;page&quot;: 1,
        &quot;per_page&quot;: 10,
        &quot;total&quot;: 1,
        &quot;last_page&quot;: 1
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-success-stories" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-success-stories"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-success-stories"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-success-stories" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-success-stories">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-success-stories" data-method="GET"
      data-path="api/v1/success-stories"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-success-stories', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-success-stories"
                    onclick="tryItOut('GETapi-v1-success-stories');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-success-stories"
                    onclick="cancelTryOut('GETapi-v1-success-stories');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-success-stories"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/success-stories</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-success-stories"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-success-stories"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Query Parameters</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="page"                data-endpoint="GETapi-v1-success-stories"
               value="16"
               data-component="query">
    <br>
<p>Optional. Default 1. Example: <code>16</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>per_page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="per_page"                data-endpoint="GETapi-v1-success-stories"
               value="16"
               data-component="query">
    <br>
<p>Optional. 1-30. Default 10. Example: <code>16</code></p>
            </div>
                </form>

                    <h2 id="success-stories-POSTapi-v1-success-stories">Submit a success story. Lands as `is_visible=false` — admin
approval gates publication.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-v1-success-stories">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/success-stories" \
    --header "Content-Type: multipart/form-data" \
    --header "Accept: application/json" \
    --form "couple_names=architecto"\
    --form "story=architecto"\
    --form "wedding_date=architecto"\
    --form "location=architecto"\
    --form "photo=@C:\Users\Lenovo\AppData\Local\Temp\phpC0D3.tmp" </code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/success-stories"
);

const headers = {
    "Content-Type": "multipart/form-data",
    "Accept": "application/json",
};

const body = new FormData();
body.append('couple_names', 'architecto');
body.append('story', 'architecto');
body.append('wedding_date', 'architecto');
body.append('location', 'architecto');
body.append('photo', document.querySelector('input[name="photo"]').files[0]);

fetch(url, {
    method: "POST",
    headers,
    body,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-success-stories">
            <blockquote>
            <p>Example response (201, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;story_id&quot;: 42,
        &quot;status&quot;: &quot;pending&quot;,
        &quot;message&quot;: &quot;Thanks! ...&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, validation):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{&quot;success&quot;: false, &quot;error&quot;: {&quot;code&quot;: &quot;VALIDATION_FAILED&quot;, ...}}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-success-stories" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-success-stories"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-success-stories"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-success-stories" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-success-stories">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-success-stories" data-method="POST"
      data-path="api/v1/success-stories"
      data-authed="1"
      data-hasfiles="1"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-success-stories', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-success-stories"
                    onclick="tryItOut('POSTapi-v1-success-stories');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-success-stories"
                    onclick="cancelTryOut('POSTapi-v1-success-stories');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-success-stories"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/success-stories</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-success-stories"
               value="multipart/form-data"
               data-component="header">
    <br>
<p>Example: <code>multipart/form-data</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-success-stories"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>couple_names</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="couple_names"                data-endpoint="POSTapi-v1-success-stories"
               value="architecto"
               data-component="body">
    <br>
<p>Max 200. Example: <code>architecto</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>story</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="story"                data-endpoint="POSTapi-v1-success-stories"
               value="architecto"
               data-component="body">
    <br>
<p>20-2000 chars. Example: <code>architecto</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>wedding_date</code></b>&nbsp;&nbsp;
<small>date</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="wedding_date"                data-endpoint="POSTapi-v1-success-stories"
               value="architecto"
               data-component="body">
    <br>
<p>Optional. ISO date, ≤ today. Example: <code>architecto</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>location</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="location"                data-endpoint="POSTapi-v1-success-stories"
               value="architecto"
               data-component="body">
    <br>
<p>Optional. Max 100. Example: <code>architecto</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>photo</code></b>&nbsp;&nbsp;
<small>file</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="file" style="display: none"
                              name="photo"                data-endpoint="POSTapi-v1-success-stories"
               value=""
               data-component="body">
    <br>
<p>Optional. JPG/PNG/WEBP, max 3 MB. Example: <code>C:\Users\Lenovo\AppData\Local\Temp\phpC0D3.tmp</code></p>
        </div>
        </form>

                <h1 id="views">Views</h1>

    

                                <h2 id="views-GETapi-v1-views">GET api/v1/views</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-GETapi-v1-views">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/views?tab=architecto&amp;page=16&amp;per_page=16" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/views"
);

const params = {
    "tab": "architecto",
    "page": "16",
    "per_page": "16",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-views">
            <blockquote>
            <p>Example response (200, viewed_by-free):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;tab&quot;: &quot;viewed_by&quot;,
        &quot;is_premium&quot;: false,
        &quot;total_count&quot;: 7,
        &quot;viewers&quot;: []
    },
    &quot;meta&quot;: {
        &quot;page&quot;: 1,
        &quot;per_page&quot;: 20,
        &quot;total&quot;: 0,
        &quot;last_page&quot;: 1
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (200, viewed_by-premium):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
  &quot;success&quot;: true,
  &quot;data&quot;: {&quot;tab&quot;: &quot;viewed_by&quot;, &quot;is_premium&quot;: true, &quot;total_count&quot;: 7, &quot;viewers&quot;: [{&quot;matri_id&quot;: &quot;AM000123&quot;, ...}]},
  &quot;meta&quot;: {&quot;page&quot;: 1, &quot;per_page&quot;: 20, &quot;total&quot;: 7, &quot;last_page&quot;: 1}
}</code>
 </pre>
            <blockquote>
            <p>Example response (200, i_viewed):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
  &quot;success&quot;: true,
  &quot;data&quot;: {&quot;tab&quot;: &quot;i_viewed&quot;, &quot;is_premium&quot;: false, &quot;viewed_profiles&quot;: [...]},
  &quot;meta&quot;: {&quot;page&quot;: 1, &quot;per_page&quot;: 20, &quot;total&quot;: 3, &quot;last_page&quot;: 1}
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, no-profile):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;PROFILE_REQUIRED&quot;,
        &quot;message&quot;: &quot;...&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-views" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-views"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-views"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-views" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-views">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-views" data-method="GET"
      data-path="api/v1/views"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-views', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-views"
                    onclick="tryItOut('GETapi-v1-views');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-views"
                    onclick="cancelTryOut('GETapi-v1-views');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-views"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/views</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-views"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-views"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Query Parameters</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>tab</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="tab"                data-endpoint="GETapi-v1-views"
               value="architecto"
               data-component="query">
    <br>
<p>Optional. "viewed_by" (default) or "i_viewed". Example: <code>architecto</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="page"                data-endpoint="GETapi-v1-views"
               value="16"
               data-component="query">
    <br>
<p>Optional. Default 1. Example: <code>16</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>per_page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="per_page"                data-endpoint="GETapi-v1-views"
               value="16"
               data-component="query">
    <br>
<p>Optional. 1-50. Default 20. Example: <code>16</code></p>
            </div>
                </form>

            

        
    </div>
    <div class="dark-box">
                    <div class="lang-selector">
                                                        <button type="button" class="lang-button" data-language-name="bash">bash</button>
                                                        <button type="button" class="lang-button" data-language-name="javascript">javascript</button>
                            </div>
            </div>
</div>
</body>
</html>

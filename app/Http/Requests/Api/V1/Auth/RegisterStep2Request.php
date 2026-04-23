<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Http\Requests\RegisterStep2Request as WebRegisterStep2Request;

/**
 * API validation for POST /api/v1/auth/register/step-2.
 *
 * Rules are identical to the web version — thin subclass.
 * Jathakam file upload is handled by a separate endpoint
 * (POST /api/v1/profile/me/jathakam, multipart) instead of being part
 * of this JSON step. The 'jathakam' rule remains nullable here so the
 * field being absent isn't a hard error.
 */
class RegisterStep2Request extends WebRegisterStep2Request
{
    // Rules + messages inherited from web.
}

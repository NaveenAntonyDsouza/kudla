<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Base class for every FormRequest used by the /api/v1/* controllers.
 *
 * Why a base:
 *  - Gives us a single place to customize API-wide FormRequest behaviour
 *    later (shared validation error formatting, user context resolution,
 *    per-route auth policies, etc.) without touching 20+ subclasses.
 *  - Sets `authorize() => true` by default — API route access is gated
 *    by middleware (auth:sanctum) and per-action policies, NOT by the
 *    per-field authorize() hook that most Laravel FormRequest tutorials
 *    use. Override in subclasses when there's truly request-level authz
 *    to enforce.
 *
 * ValidationException thrown by validate() flows through
 * App\Exceptions\ApiExceptionHandler and is converted to the envelope
 * error shape automatically — no special error handling needed here.
 *
 * Design reference:
 *   docs/mobile-app/phase-2a-api/week-02-auth-registration/step-05-api-form-request-pattern.md
 */
abstract class ApiFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
}

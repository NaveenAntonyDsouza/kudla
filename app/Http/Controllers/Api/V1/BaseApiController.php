<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;

/**
 * Base class for all /api/v1/* controllers.
 *
 * Not strictly required (controllers could extend App\Http\Controllers\Controller
 * directly), but gives us a single place to add API-wide helpers later
 * (e.g., resolveProfile, requireAdmin, device context, etc.)
 *
 * Design reference: docs/mobile-app/design/01-api-foundations.md §1.2
 */
abstract class BaseApiController extends Controller
{
    //
}

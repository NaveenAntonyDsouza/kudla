<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Responses\ApiResponse;
use App\Services\ReferenceDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * GET /api/v1/reference/{list}
 *
 * Returns reference dropdown data — all the pre-defined option lists used
 * by registration + profile editing + partner search (religions, castes,
 * occupations, dioceses, languages, hobbies, etc.).
 *
 * Data source: App\Services\ReferenceDataService
 *   - Reads DB override from site_settings.ref_data_{key} first
 *   - Falls back to config/reference_data.php
 *   - Admin-editable via Filament ReferenceDataEditor
 *
 * Response shapes:
 *   Flat list    -> {success: true, data: ["item1", "item2", ...]}
 *   Grouped list -> {success: true, data: {"Group A": ["a1", "a2"], "Group B": ["b1"]}}
 *
 * The `?flat=1` query param forces a grouped list to be flattened.
 * The `?options=1` query param returns key-value objects suitable for
 * <select> dropdowns.
 *
 * Design reference: docs/mobile-app/design/09-engagement-api.md §9.9
 */
class ReferenceDataController extends BaseApiController
{
    /**
     * Allow-list of valid reference keys. Prevents enumeration of
     * site_settings keys and returns a clean 404 for unknowns.
     *
     * Mapped slug -> actual ReferenceDataService key.
     * The external slug uses hyphens for URL-friendliness; the internal
     * key matches config/reference_data.php.
     */
    private const VALID_LISTS = [
        'heights' => 'height_list',
        'weights' => 'weight_list',
        'income-ranges' => 'annual_income_list',
        'countries' => 'country_list',
        'denominations' => 'denomination_list',
        'dioceses' => 'diocese_list',
        'castes' => 'caste_list',
        'sub-castes' => 'sub_caste_list',
        'muslim-sects' => 'muslim_sect_list',
        'jain-sects' => 'jain_sect_list',
        'rasi' => 'rasi_list',
        'nakshatras' => 'nakshatra_list',
        'gothrams' => 'gothram_list',
        'education-levels' => 'educational_qualifications_list',
        'occupations' => 'occupation_category_list',
        'how-did-you-hear' => 'how_did_you_hear_list',
        'languages' => 'language_list',
        'eating-habits' => 'eating_habits',
        'drinking-habits' => 'drinking_habits',
        'smoking-habits' => 'smoking_habits',
        'cultural-backgrounds' => 'cultural_background_list',
        'hobbies' => 'hobbies_list',
        'music' => 'music_list',
        'books' => 'books_list',
        'movies' => 'movies_list',
        'sports' => 'sports_list',
        'cuisines' => 'cuisine_list',
        'jamaats' => 'jamath_list',
        'phone-codes' => 'phone_codes',
    ];

    /**
     * Get a reference list
     *
     * Returns the items for a specific reference dropdown (religions, castes,
     * occupations, countries, etc.). Some lists are flat arrays; others are
     * grouped objects (e.g. `denominations` groups by Catholic/Non-Catholic).
     *
     * Pass `?flat=1` to flatten a grouped list into a single array.
     * Pass `?options=1` to get `{value: value}` pairs suitable for a `<select>`.
     *
     * Cached server-side for 1 hour.
     *
     * @unauthenticated
     * @group Configuration
     *
     * @urlParam list string required The list slug. See `GET /api/v1/reference` for the full list. Example: castes
     * @queryParam flat bool Flatten a grouped list. Example: 1
     * @queryParam options bool Return {value: value} pairs. Example: 1
     *
     * @response 200 scenario="flat list" {
     *   "success": true,
     *   "data": ["Brahmin", "Nair", "Ezhava"]
     * }
     * @response 404 scenario="unknown list" {
     *   "success": false,
     *   "error": { "code": "NOT_FOUND", "message": "Reference list 'foo' does not exist." }
     * }
     */
    public function show(Request $request, string $list): JsonResponse
    {
        if (! array_key_exists($list, self::VALID_LISTS)) {
            return ApiResponse::error(
                code: 'NOT_FOUND',
                message: "Reference list '{$list}' does not exist.",
                status: 404,
            );
        }

        $refKey = self::VALID_LISTS[$list];
        $flat = $request->boolean('flat');
        $options = $request->boolean('options');

        // Cache per-shape so we don't recompute variants
        $cacheKey = sprintf(
            'api:v1:reference:%s:%s',
            $list,
            $flat ? 'flat' : ($options ? 'options' : 'raw'),
        );

        $data = Cache::remember($cacheKey, now()->addHour(), function () use ($refKey, $flat, $options) {
            if ($flat) {
                return ReferenceDataService::getFlat($refKey);
            }

            if ($options) {
                return ReferenceDataService::getOptions($refKey);
            }

            return ReferenceDataService::get($refKey);
        });

        return ApiResponse::ok($data);
    }

    /**
     * List all available reference slugs
     *
     * Meta endpoint that returns every reference list slug this API supports.
     * Lets the Flutter client discover available dropdowns at runtime.
     *
     * @unauthenticated
     * @group Configuration
     *
     * @response 200 {
     *   "success": true,
     *   "data": { "lists": ["castes", "countries", "occupations", "languages"] }
     * }
     */
    public function index(): JsonResponse
    {
        return ApiResponse::ok([
            'lists' => array_keys(self::VALID_LISTS),
        ]);
    }
}

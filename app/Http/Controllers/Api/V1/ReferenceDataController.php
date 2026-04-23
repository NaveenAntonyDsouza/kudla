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
     * GET /api/v1/reference
     *
     * Meta endpoint that lists all available reference list slugs.
     * Used by Flutter to discover what reference lists exist at runtime
     * (also documented statically in reference docs).
     */
    public function index(): JsonResponse
    {
        return ApiResponse::ok([
            'lists' => array_keys(self::VALID_LISTS),
        ]);
    }
}

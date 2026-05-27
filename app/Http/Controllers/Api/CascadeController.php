<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Community;
use App\Models\ReferenceDataOption;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CascadeController extends Controller
{
    /**
     * Get communities for a given religion.
     * GET /api/cascade/communities?religion=Hindu
     */
    public function communities(Request $request): JsonResponse
    {
        $religion = $request->query('religion');
        if (!$religion) {
            return response()->json([]);
        }

        $communities = Community::where('religion', $religion)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'community_name', 'sub_communities']);

        return response()->json($communities);
    }

    /**
     * Get dioceses for a given Christian denomination, filtered by the
     * denomination's rite (Latin / Syro-Malabar / Syro-Malankara).
     * GET /api/cascade/dioceses?denomination=Roman%20Catholic
     *
     * Returns a flat array of diocese names. An EMPTY array signals the caller
     * to show a free-text diocese box — used for Non-Catholic denominations,
     * which aren't in the denomination_rite map (their dioceses aren't in the
     * Catholic diocese list). Full-list fallback if a rite has no tagged
     * dioceses, so the dropdown is never empty for a Catholic denomination.
     */
    public function dioceses(Request $request): JsonResponse
    {
        $denomination = $request->query('denomination');
        if (!$denomination) {
            return response()->json([]);
        }

        $rite = config('reference_data.denomination_rite')[$denomination] ?? null;
        if (!$rite) {
            return response()->json([]); // Non-Catholic → free-text diocese
        }

        $base = ReferenceDataOption::where('category', 'diocese')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('value');

        $dioceses = (clone $base)->where('cascade_group', $rite)->pluck('value');

        // Fallback: never show an empty dropdown for a Catholic denomination.
        if ($dioceses->isEmpty()) {
            $dioceses = $base->pluck('value');
        }

        return response()->json($dioceses->values());
    }

    /**
     * Get Indian states.
     * GET /api/cascade/states
     */
    public function states(): JsonResponse
    {
        $states = config('locations.indian_states', []);
        return response()->json($states);
    }

    /**
     * Get districts for a given state.
     * GET /api/cascade/districts?state=Karnataka
     */
    public function districts(Request $request): JsonResponse
    {
        $state = $request->query('state');
        if (!$state) {
            return response()->json([]);
        }

        $districtMap = config('locations.state_district_map', []);
        $districts = $districtMap[$state] ?? [];
        return response()->json($districts);
    }

    /**
     * Two modes, by whether a country is given:
     *  - No ?country=  → flat list of countries (legacy behaviour).
     *  - ?country=USA  → that country's states/provinces, wrapped as
     *    {"locations": [...]} — the shape the registration forms consume
     *    (this.states = data.locations || []).
     *
     * An EMPTY locations array signals the caller to fall back to a free-text
     * state input — used for India (handled via the dedicated /states route),
     * city/micro-states (Singapore, Malta), and every unmapped country, so a
     * member can always enter their state. Keys in country_state_map match the
     * country VALUE strings in reference_data.country_list exactly.
     * GET /api/cascade/countries[?country=USA]
     */
    public function countries(Request $request): JsonResponse
    {
        $country = $request->query('country');

        if (!$country) {
            return response()->json(config('locations.countries', []));
        }

        $map = config('locations.country_state_map', []);
        return response()->json(['locations' => $map[$country] ?? []]);
    }
}

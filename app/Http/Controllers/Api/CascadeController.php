<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Community;
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
     * Get countries.
     * GET /api/cascade/countries
     */
    public function countries(): JsonResponse
    {
        $countries = config('locations.countries', []);
        return response()->json($countries);
    }
}

<?php

namespace App\Http\Requests\Api\V1\Profile;

use App\Http\Requests\Api\V1\ApiFormRequest;

/**
 * Validates PUT /api/v1/profile/me/location.
 *
 * Mirrors App\Http\Controllers\ProfileController::updateLocation.
 * Outstation date range uses `after_or_equal` to prevent inverted ranges.
 */
class UpdateLocationSectionRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'native_country' => 'nullable|string|max:100',
            'native_state' => 'nullable|string|max:100',
            'native_district' => 'nullable|string|max:100',
            'residing_country' => 'nullable|string|max:100',
            'residency_status' => 'nullable|string|max:50',
            'pin_zip_code' => 'nullable|string|max:10',
            'outstation_leave_date_from' => 'nullable|date',
            'outstation_leave_date_to' => 'nullable|date|after_or_equal:outstation_leave_date_from',
        ];
    }
}

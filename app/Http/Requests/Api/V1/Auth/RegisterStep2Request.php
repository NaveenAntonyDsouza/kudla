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

    /**
     * Scribe body-parameter metadata. Documents the ~30 fields step-2 asks
     * for so the OpenAPI spec is human-usable. Many fields are
     * conditionally required (e.g. caste required when religion=Hindu) —
     * the conditionality lives in rules() and is summarised here.
     */
    public function bodyParameters(): array
    {
        return [
            // Physical
            'height' => ['description' => 'Free-form height label (e.g. "170 cm - 5 ft 07 inch"). Stored as written.', 'example' => '170 cm - 5 ft 07 inch'],
            'complexion' => ['description' => 'Self-described complexion. Reference list at GET /reference/complexions.', 'example' => 'Wheatish'],
            'body_type' => ['description' => 'Body type. Reference list at GET /reference/body-types.', 'example' => 'Average'],
            'physical_status' => ['description' => 'Physical status. "Differently Abled" requires da_category + da_description.', 'example' => 'Normal'],
            'da_category' => ['description' => 'Differently-abled category. Required when physical_status="Differently Abled". "Other" requires da_category_other.', 'required' => false],
            'da_category_other' => ['description' => 'Free-text DA category when da_category="Other". Max 50.', 'required' => false],
            'da_description' => ['description' => 'Free-text DA description. Required when physical_status="Differently Abled". Max 500.', 'required' => false],
            // Marital
            'marital_status' => ['description' => 'Marital status. Reference list at GET /reference/marital-statuses.', 'example' => 'Never Married'],
            'children_with_me' => ['description' => 'Children currently living with the user. 0+.', 'required' => false, 'example' => 0],
            'children_not_with_me' => ['description' => 'Children not currently living with the user. 0+.', 'required' => false, 'example' => 0],
            'family_status' => ['description' => 'Family status. Reference list at GET /reference/family-statuses.', 'example' => 'Middle Class'],
            // Religion
            'religion' => ['description' => 'Religion (Hindu/Christian/Muslim/Jain/Other). Drives downstream conditional fields.', 'example' => 'Hindu'],
            'denomination' => ['description' => 'Christian denomination. Required when religion="Christian".', 'required' => false],
            'diocese' => ['description' => 'Diocese (Christian).', 'required' => false],
            'diocese_name' => ['description' => 'Diocese name (Christian, free-text).', 'required' => false],
            'parish_name_place' => ['description' => 'Parish name + place (Christian, free-text).', 'required' => false],
            'caste' => ['description' => 'Caste. Required when religion="Hindu" or "Jain".', 'required' => false, 'example' => 'Brahmin'],
            'sub_caste' => ['description' => 'Sub-caste (Hindu/Jain).', 'required' => false],
            'time_of_birth' => ['description' => 'Time of birth (HH:MM, optional, used for jathakam matching).', 'required' => false, 'example' => '06:30'],
            'place_of_birth' => ['description' => 'Place of birth (free-text).', 'required' => false],
            'rashi' => ['description' => 'Rashi / moon sign.', 'required' => false],
            'nakshatra' => ['description' => 'Nakshatra / birth star.', 'required' => false],
            'gotra' => ['description' => 'Gotra (Hindu).', 'required' => false],
            'manglik' => ['description' => 'Manglik status (Yes / No / Partial).', 'required' => false],
            'jathakam' => ['description' => 'Jathakam document (jpg/jpeg/png/pdf, max 2 MB). NOTE: API flow uploads jathakam via POST /api/v1/profile/me/jathakam separately — this field is accepted but optional here.', 'type' => 'file', 'required' => false],
            'muslim_sect' => ['description' => 'Muslim sect (Shia/Sunni/etc). Required when religion="Muslim".', 'required' => false],
            'muslim_community' => ['description' => 'Muslim community / sub-group.', 'required' => false],
            'religious_observance' => ['description' => 'Religious observance level (free-text).', 'required' => false],
            'jain_sect' => ['description' => 'Jain sect (Digambar/Svetambar).', 'required' => false],
            'other_religion_name' => ['description' => 'Free-text religion name. Required when religion="Other".', 'required' => false],
        ];
    }
}

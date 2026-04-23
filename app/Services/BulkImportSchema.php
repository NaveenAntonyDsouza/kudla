<?php

namespace App\Services;

/**
 * BulkImportSchema — defines the CSV column structure for bulk member imports.
 *
 * V1 MVP: 15 columns covering the essentials of a matrimony profile.
 * V2 (future) can extend without breaking V1 imports — just add to ACCEPTED_COLUMNS.
 *
 * Column metadata:
 *   - required: bool (must be present and non-empty)
 *   - type: 'string' | 'email' | 'phone' | 'date' | 'enum' | 'reference_data'
 *   - enum_values: array (for type=enum)
 *   - reference_key: string (for type=reference_data — name of array in config/reference_data.php)
 *   - help: string (shown in template)
 *   - example: string (example value)
 */
class BulkImportSchema
{
    /**
     * The accepted columns for the CSV upload.
     */
    public static function columns(): array
    {
        return [
            'full_name' => [
                'required' => true,
                'type' => 'string',
                'help' => 'Full name as it should appear on the profile.',
                'example' => 'Anita Kumari',
            ],
            'email' => [
                'required' => true,
                'type' => 'email',
                'help' => 'Unique email — used as login. Must not exist in the system.',
                'example' => 'anita@example.com',
            ],
            'phone' => [
                'required' => true,
                'type' => 'phone',
                'help' => '10-digit Indian phone (no country code). Must be unique.',
                'example' => '9876543210',
            ],
            'gender' => [
                'required' => true,
                'type' => 'enum',
                'enum_values' => ['male', 'female'],
                'help' => 'male or female (lowercase).',
                'example' => 'female',
            ],
            'date_of_birth' => [
                'required' => true,
                'type' => 'date',
                'help' => 'YYYY-MM-DD. Member must be at least 18 years old.',
                'example' => '1995-06-15',
            ],
            'religion' => [
                'required' => true,
                'type' => 'string',
                'help' => 'Religion (e.g., Hindu, Muslim, Christian, Sikh, Jain, Buddhist).',
                'example' => 'Hindu',
            ],
            'mother_tongue' => [
                'required' => true,
                'type' => 'reference_data',
                'reference_key' => 'language_list',
                'help' => 'Language from the supported list. Download "Reference Data" CSV for valid values.',
                'example' => 'Kannada',
            ],
            'marital_status' => [
                'required' => false,
                'type' => 'enum',
                'enum_values' => ['never_married', 'divorced', 'widowed', 'awaiting_divorce'],
                'help' => 'Defaults to never_married if blank.',
                'example' => 'never_married',
            ],
            'height' => [
                'required' => false,
                'type' => 'reference_data',
                'reference_key' => 'height_list',
                'help' => 'Exact value from height_list (e.g., "170 cm - 5 ft 07 inch"). See Reference Data CSV.',
                'example' => '165 cm - 5 ft 05 inch',
            ],
            'state' => [
                'required' => false,
                'type' => 'string',
                'help' => 'State (mapped to native_state).',
                'example' => 'Karnataka',
            ],
            'city' => [
                'required' => false,
                'type' => 'string',
                'help' => 'City / district (mapped to native_district).',
                'example' => 'Mangalore',
            ],
            'highest_education' => [
                'required' => false,
                'type' => 'string',
                'help' => 'Education category (e.g., "Engineering", "Medical", "Arts").',
                'example' => 'Engineering',
            ],
            'occupation' => [
                'required' => false,
                'type' => 'string',
                'help' => 'Occupation / job title.',
                'example' => 'Software Engineer',
            ],
            'denomination' => [
                'required' => false,
                'type' => 'string',
                'help' => 'For Christian: denomination (Roman Catholic, Protestant, etc.). For Hindu: caste.',
                'example' => 'Roman Catholic',
            ],
            'branch_code' => [
                'required' => false,
                'type' => 'string',
                'help' => 'Branch code to attribute (e.g., MNG). If blank, uses upload default.',
                'example' => 'MNG',
            ],
        ];
    }

    /**
     * Get just the column names in order (for CSV header row).
     */
    public static function columnNames(): array
    {
        return array_keys(self::columns());
    }

    /**
     * Get one example row (for the CSV template).
     */
    public static function exampleRow(): array
    {
        return collect(self::columns())
            ->map(fn ($col) => $col['example'] ?? '')
            ->values()
            ->toArray();
    }

    /**
     * Get just the required columns.
     */
    public static function requiredColumns(): array
    {
        return collect(self::columns())
            ->filter(fn ($col) => $col['required'] === true)
            ->keys()
            ->toArray();
    }

    /**
     * Get full column metadata for one column (or null if unknown).
     */
    public static function getColumn(string $name): ?array
    {
        return self::columns()[$name] ?? null;
    }

    /**
     * Reference Data — shows admins the valid values for enum / reference_data columns.
     * Used by the "Download Reference Data CSV" action.
     */
    public static function referenceDataRows(): array
    {
        $rows = [];

        foreach (self::columns() as $colName => $meta) {
            if ($meta['type'] === 'enum') {
                foreach ($meta['enum_values'] as $value) {
                    $rows[] = [$colName, $value];
                }
            } elseif ($meta['type'] === 'reference_data') {
                $values = config('reference_data.' . $meta['reference_key'], []);
                foreach ($values as $value) {
                    if (is_string($value)) {
                        $rows[] = [$colName, $value];
                    }
                }
            }
        }

        return $rows;
    }
}

<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\User;
use Carbon\Carbon;

/**
 * BulkImportValidator — validates CSV rows against BulkImportSchema.
 *
 * Returns structured results so the preview UI can show:
 *   - Valid rows (will be imported)
 *   - Invalid rows (with column-level error messages)
 *
 * Validation layers:
 *   1. Required columns present + non-empty
 *   2. Type validation (email format, phone format, date format)
 *   3. Enum validation (gender, marital_status)
 *   4. Reference data validation (mother_tongue, height — must be in config arrays)
 *   5. Uniqueness (email + phone not already in users table OR earlier rows of THIS import)
 *   6. Branch code resolution (if provided, must match an active branch)
 *   7. Age >= 18
 */
class BulkImportValidator
{
    /**
     * Validate a single row.
     *
     * @param  array  $row  Associative array (column_name => value), already normalized to lowercase keys
     * @param  array  $seenInThisImport  Email/phone collisions within the same upload — array with 'emails' and 'phones' keys
     * @return array{valid: bool, errors: array<string, string>, normalized: array}
     */
    public function validateRow(array $row, array $seenInThisImport = []): array
    {
        $errors = [];
        $normalized = [];

        $columns = BulkImportSchema::columns();

        // Layer 1 + 2 + 3 + 4: per-column validation
        foreach ($columns as $colName => $meta) {
            $value = $row[$colName] ?? null;
            $value = is_string($value) ? trim($value) : $value;

            // Required check
            if ($meta['required'] && ($value === null || $value === '')) {
                $errors[$colName] = "Required field is empty";
                continue;
            }

            // Skip remaining checks if value is empty AND not required
            if ($value === null || $value === '') {
                $normalized[$colName] = null;
                continue;
            }

            // Type-specific validation
            $result = $this->validateValue($colName, $value, $meta);
            if (isset($result['error'])) {
                $errors[$colName] = $result['error'];
            } else {
                $normalized[$colName] = $result['value'];
            }
        }

        // Layer 5: uniqueness (email + phone)
        if (!isset($errors['email']) && isset($normalized['email'])) {
            $email = $normalized['email'];

            if (in_array(strtolower($email), $seenInThisImport['emails'] ?? [], true)) {
                $errors['email'] = "Duplicate email within this CSV (appears in an earlier row)";
            } elseif (User::where('email', $email)->exists()) {
                $errors['email'] = "Email already exists in the system";
            }
        }

        if (!isset($errors['phone']) && isset($normalized['phone'])) {
            $phone = $normalized['phone'];

            if (in_array($phone, $seenInThisImport['phones'] ?? [], true)) {
                $errors['phone'] = "Duplicate phone within this CSV (appears in an earlier row)";
            } elseif (User::where('phone', $phone)->exists()) {
                $errors['phone'] = "Phone already exists in the system";
            }
        }

        // Layer 6: branch_code resolution (optional)
        if (!isset($errors['branch_code']) && !empty($normalized['branch_code'])) {
            $code = strtoupper($normalized['branch_code']);
            $branch = Branch::active()->where('code', $code)->first();
            if (!$branch) {
                $errors['branch_code'] = "Branch code '$code' not found or inactive";
            } else {
                $normalized['_resolved_branch_id'] = $branch->id;
            }
        }

        // Layer 7: age >= 18
        if (!isset($errors['date_of_birth']) && isset($normalized['date_of_birth'])) {
            try {
                $dob = Carbon::parse($normalized['date_of_birth']);
                $age = $dob->diffInYears(now());
                if ($age < 18) {
                    $errors['date_of_birth'] = "Member must be at least 18 years old (currently $age)";
                }
            } catch (\Throwable $e) {
                // Already caught by date type validation
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'normalized' => $normalized,
        ];
    }

    /**
     * Validate a single value against its column meta.
     *
     * @return array{value?: mixed, error?: string}
     */
    private function validateValue(string $colName, $value, array $meta): array
    {
        switch ($meta['type']) {
            case 'email':
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    return ['error' => "Invalid email format"];
                }
                return ['value' => strtolower($value)];

            case 'phone':
                $cleaned = preg_replace('/\D/', '', (string) $value);
                if (strlen($cleaned) !== 10) {
                    return ['error' => "Phone must be exactly 10 digits (got " . strlen($cleaned) . ")"];
                }
                return ['value' => $cleaned];

            case 'date':
                try {
                    $date = Carbon::parse($value);
                    return ['value' => $date->toDateString()];
                } catch (\Throwable $e) {
                    return ['error' => "Invalid date format. Use YYYY-MM-DD"];
                }

            case 'enum':
                $valueLower = strtolower(trim((string) $value));
                $enumLower = array_map('strtolower', $meta['enum_values']);
                if (!in_array($valueLower, $enumLower, true)) {
                    $valid = implode(', ', $meta['enum_values']);
                    return ['error' => "Must be one of: $valid"];
                }
                // Normalize to canonical case from enum_values
                $idx = array_search($valueLower, $enumLower, true);
                return ['value' => $meta['enum_values'][$idx]];

            case 'reference_data':
                $validValues = config('reference_data.' . $meta['reference_key'], []);
                $flatValues = [];
                foreach ($validValues as $v) {
                    if (is_string($v)) {
                        $flatValues[] = $v;
                    } elseif (is_array($v)) {
                        foreach ($v as $sub) {
                            if (is_string($sub)) $flatValues[] = $sub;
                        }
                    }
                }
                // Case-insensitive match
                $valueLower = strtolower(trim((string) $value));
                foreach ($flatValues as $v) {
                    if (strtolower($v) === $valueLower) {
                        return ['value' => $v]; // canonical case
                    }
                }
                return ['error' => "Value '$value' not in valid list. Download Reference Data CSV for valid values."];

            case 'string':
            default:
                return ['value' => trim((string) $value)];
        }
    }

    /**
     * Validate an entire batch of rows. Tracks seen emails/phones across rows.
     *
     * @return array{rows: array, valid_count: int, invalid_count: int, errors_summary: array}
     */
    public function validateBatch(array $rows): array
    {
        $seen = ['emails' => [], 'phones' => []];
        $results = [];
        $validCount = 0;
        $invalidCount = 0;

        foreach ($rows as $rowIndex => $row) {
            $result = $this->validateRow($row, $seen);

            // Track seen emails/phones for next-row uniqueness checks (only if THIS row is valid)
            if ($result['valid']) {
                if (!empty($result['normalized']['email'])) {
                    $seen['emails'][] = strtolower($result['normalized']['email']);
                }
                if (!empty($result['normalized']['phone'])) {
                    $seen['phones'][] = $result['normalized']['phone'];
                }
                $validCount++;
            } else {
                $invalidCount++;
            }

            $results[$rowIndex] = $result;
        }

        // Build errors summary (counts per column)
        $errorsSummary = [];
        foreach ($results as $r) {
            foreach ($r['errors'] as $col => $msg) {
                $errorsSummary[$col] = ($errorsSummary[$col] ?? 0) + 1;
            }
        }

        return [
            'rows' => $results,
            'valid_count' => $validCount,
            'invalid_count' => $invalidCount,
            'errors_summary' => $errorsSummary,
        ];
    }
}

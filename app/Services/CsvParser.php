<?php

namespace App\Services;

/**
 * CsvParser — minimal CSV reader for bulk imports.
 *
 * Reads CSV file into:
 *   - Header row (first non-empty row of column names, lowercase + trimmed)
 *   - Data rows (associative arrays keyed by header)
 *
 * Handles:
 *   - UTF-8 BOM (stripped automatically)
 *   - Empty lines (skipped)
 *   - Inconsistent row lengths (extra cells dropped, missing cells = empty string)
 */
class CsvParser
{
    /**
     * Parse a CSV file path into structured rows.
     *
     * @param  string  $path  Absolute path to CSV file
     * @return array{headers: array, rows: array, total_rows: int, errors: array}
     */
    public function parseFile(string $path): array
    {
        if (!file_exists($path)) {
            return ['headers' => [], 'rows' => [], 'total_rows' => 0, 'errors' => ["File not found: $path"]];
        }

        $handle = fopen($path, 'r');
        if (!$handle) {
            return ['headers' => [], 'rows' => [], 'total_rows' => 0, 'errors' => ['Could not open file']];
        }

        try {
            return $this->parseStream($handle);
        } finally {
            fclose($handle);
        }
    }

    /**
     * Parse from an open file handle.
     */
    public function parseStream($handle): array
    {
        $headers = [];
        $rows = [];
        $errors = [];

        // Read first row as headers
        while ($firstRow = fgetcsv($handle)) {
            // Skip empty lines
            $firstRow = array_map(fn($c) => is_string($c) ? trim($c) : $c, $firstRow);
            if (count(array_filter($firstRow, fn($c) => $c !== '' && $c !== null)) === 0) {
                continue;
            }

            // Strip UTF-8 BOM from first column
            if (!empty($firstRow[0])) {
                $firstRow[0] = preg_replace('/^\xEF\xBB\xBF/', '', $firstRow[0]);
            }

            $headers = array_map(fn($h) => strtolower(trim((string) $h)), $firstRow);
            break;
        }

        if (empty($headers)) {
            return ['headers' => [], 'rows' => [], 'total_rows' => 0, 'errors' => ['No header row found in CSV']];
        }

        // Read data rows
        $rowNumber = 1; // 1-indexed (header is row 1)
        while (($rowData = fgetcsv($handle)) !== false) {
            $rowNumber++;

            // Skip empty rows
            $rowData = array_map(fn($c) => is_string($c) ? trim($c) : $c, $rowData);
            if (count(array_filter($rowData, fn($c) => $c !== '' && $c !== null)) === 0) {
                continue;
            }

            // Map to associative array (extra cells dropped, missing cells = '')
            $assoc = [];
            foreach ($headers as $i => $header) {
                $assoc[$header] = $rowData[$i] ?? '';
            }

            $rows[] = [
                '_row_number' => $rowNumber,
                'data' => $assoc,
            ];
        }

        return [
            'headers' => $headers,
            'rows' => $rows,
            'total_rows' => count($rows),
            'errors' => $errors,
        ];
    }

    /**
     * Validate that the CSV's headers match (or are a superset of) BulkImportSchema.
     */
    public function validateHeaders(array $headers): array
    {
        $expected = BulkImportSchema::columnNames();
        $required = BulkImportSchema::requiredColumns();
        $errors = [];

        foreach ($required as $req) {
            if (!in_array($req, $headers, true)) {
                $errors[] = "Missing required column: '$req'";
            }
        }

        $unknown = array_diff($headers, $expected);
        if (!empty($unknown)) {
            // Just a warning — extra columns are silently ignored
            // (Don't add to errors — they're informational)
        }

        return $errors;
    }
}

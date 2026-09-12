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

        // Excel (.xlsx/.xls) → parse via PhpSpreadsheet into the same structure.
        // Detect by extension, with a magic-byte fallback (xlsx = ZIP "PK", xls =
        // OLE) in case the stored name lost/mismatched its extension.
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $isExcel = in_array($ext, ['xlsx', 'xls'], true);
        if (! $isExcel && $ext !== 'csv') {
            $head = (string) file_get_contents($path, false, null, 0, 8);
            if (str_starts_with($head, "PK\x03\x04") || str_starts_with($head, "\xD0\xCF\x11\xE0")) {
                $isExcel = true;
            }
        }
        if ($isExcel) {
            return $this->parseSpreadsheet($path);
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
     * Parse an Excel workbook (.xlsx/.xls) into the SAME structure parseStream
     * returns, so the validator/executor are format-agnostic. Reads the first
     * (active) sheet; first non-empty row = headers.
     */
    private function parseSpreadsheet(string $path): array
    {
        try {
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($path);
            // NOT read-data-only: we need cell number-format info to tell real
            // Excel dates apart from plain numbers.
            $reader->setReadEmptyCells(false);
            $spreadsheet = $reader->load($path);
            $sheet = $spreadsheet->getActiveSheet();
        } catch (\Throwable $e) {
            return ['headers' => [], 'rows' => [], 'total_rows' => 0, 'errors' => ['Could not read the Excel file. Re-save it as .xlsx or CSV and try again.']];
        }

        $headers = [];
        $rows = [];

        foreach ($sheet->getRowIterator() as $row) {
            $cellIterator = $row->getCellIterator();
            // Include empty cells so column positions stay aligned with headers.
            $cellIterator->setIterateOnlyExistingCells(false);

            $values = [];
            foreach ($cellIterator as $cell) {
                $values[] = $this->excelCellValue($cell);
            }
            $values = array_map(fn ($c) => is_string($c) ? trim($c) : $c, $values);

            // Skip fully-empty rows (leading blanks before the header, or gaps).
            if (count(array_filter($values, fn ($c) => $c !== '' && $c !== null)) === 0) {
                continue;
            }

            if (empty($headers)) {
                // First non-empty row = headers (BOM strip is a no-op for xlsx but harmless).
                $values[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) ($values[0] ?? ''));
                $headers = array_map(fn ($h) => strtolower(trim((string) $h)), $values);
                continue;
            }

            $assoc = [];
            foreach ($headers as $i => $header) {
                $assoc[$header] = $values[$i] ?? '';
            }
            $rows[] = [
                '_row_number' => $row->getRowIndex(), // real spreadsheet row (matches Excel)
                'data' => $assoc,
            ];
        }

        if (empty($headers)) {
            return ['headers' => [], 'rows' => [], 'total_rows' => 0, 'errors' => ['No header row found in the spreadsheet']];
        }

        return [
            'headers' => $headers,
            'rows' => $rows,
            'total_rows' => count($rows),
            'errors' => [],
        ];
    }

    /**
     * Read one Excel cell as a clean string, handling the two classic import
     * gotchas: real dates (serial numbers → Y-m-d) and long integers like phone
     * numbers (avoid scientific notation).
     */
    private function excelCellValue(\PhpOffice\PhpSpreadsheet\Cell\Cell $cell): string
    {
        $value = $cell->getValue();
        if ($value === null) {
            return '';
        }

        // Real Excel date/time cell → normalize to YYYY-MM-DD for the validator.
        if (is_numeric($value) && \PhpOffice\PhpSpreadsheet\Shared\Date::isDateTime($cell)) {
            try {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $value)->format('Y-m-d');
            } catch (\Throwable $e) {
                // fall through to generic handling
            }
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        // Whole numbers (phones, numeric IDs) → plain digits, never 9.8E+09.
        if ((is_int($value) || is_float($value)) && floor((float) $value) == (float) $value && abs((float) $value) < 1e15) {
            return number_format((float) $value, 0, '', '');
        }

        return (string) $value;
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

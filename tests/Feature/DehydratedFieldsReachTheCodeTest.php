<?php

/**
 * A form field marked ->dehydrated(false) is REMOVED from the array that
 * mutateFormDataBeforeCreate/Save and handleRecordCreation receive, so a page
 * that then reads $data['that_field'] always falls back to its default and the
 * admin's typed value is silently thrown away.
 *
 * Nov 2026, all three found at once:
 *   - Staff "Send credentials via email": off still emailed the password.
 *   - Staff targets: revenue target and incentive saved as 0.
 *   - Branch payouts: gross revenue and payout amount saved as 0.
 *
 * Reading from $this->form->getRawState() is the correct way to see such a
 * field (EditUser does that for the differently-abled details), so only reads
 * from the dehydrated array — conventionally named $data — are a problem.
 */

use Illuminate\Support\Str;

it('never reads a dehydrated-away field from the form data', function () {
    $resourceDir = __DIR__.'/../../app/Filament/Resources';
    $problems = [];

    foreach (glob($resourceDir.'/*.php') as $resourceFile) {
        $src = file_get_contents($resourceFile);
        $lines = explode("\n", $src);

        // field names whose own statement turns dehydration off
        $dehydratedAway = [];
        foreach ($lines as $i => $line) {
            if (! preg_match('/(?:TextInput|Toggle|Select|Textarea|Checkbox|DatePicker|FileUpload)::make\(\s*[\'"]([\w.]+)[\'"]\s*\)/', $line, $m)) {
                continue;
            }
            $stmt = $line;
            for ($j = $i + 1; $j < min($i + 20, count($lines)); $j++) {
                if (preg_match('/::make\(/', $lines[$j])) {
                    break;
                }
                $stmt .= "\n".$lines[$j];
                if (preg_match('/,\s*$/', rtrim($lines[$j]))) {
                    break;
                }
            }
            if (preg_match('/->dehydrated\(\s*false\s*\)/', $stmt)) {
                $dehydratedAway[] = $m[1];
            }
        }

        if (! $dehydratedAway) {
            continue;
        }

        // the Create*/Edit* pages belonging to this resource
        $pagesDir = $resourceDir.'/'.Str::beforeLast(basename($resourceFile), '.php').'/Pages';
        foreach (glob($pagesDir.'/*.php') ?: [] as $pageFile) {
            $pageSrc = file_get_contents($pageFile);
            foreach ($dehydratedAway as $field) {
                // a READ only: "$data['x'] = ..." fills the form on load and is fine
                if (preg_match('/\$data\[\s*[\'"]'.preg_quote($field, '/').'[\'"]\s*\](?!\s*=[^=>])/', $pageSrc)) {
                    $problems[] = basename($pageFile).' reads $data[\''.$field.'\'], but '
                        .basename($resourceFile).' marks that field dehydrated(false)';
                }
            }
        }
    }

    expect($problems)->toBe([]);
});

<?php

/*
 * Mass-assignment audit — every Eloquent model under app/Models must
 * explicitly declare $fillable OR $guarded.
 *
 * Why: Eloquent's default `$guarded = ['*']` is replaced by the model's
 * own value at compile time. A model that declares neither inherits the
 * Model base's `$guarded = []` (no protection), which means
 * Model::create($input) accepts arbitrary fields including `id`,
 * `is_admin`, `email_verified_at`, etc. — classic mass-assignment vuln.
 *
 * Pivot models (BelongsToMany pivots) and polymorphic relation models
 * are typically safe because they're not exposed to user input, but the
 * default rule remains: declare or document.
 *
 * Run with: php tests/Tools/mass-assignment-audit.php
 *
 * Exit code 0 = clean, 1 = unprotected models found.
 */

$modelDir = __DIR__.'/../../app/Models';
$files = glob($modelDir.'/*.php');

$errors = [];
$warnings = [];
$total = 0;
$declared = 0;

foreach ($files as $file) {
    $name = basename($file, '.php');
    $body = file_get_contents($file);

    // Skip abstract / trait files.
    if (preg_match('/abstract\s+class\s+'.preg_quote($name, '/').'\b/', $body)) {
        continue;
    }
    if (preg_match('/^trait\s+'.preg_quote($name, '/').'\b/m', $body)) {
        continue;
    }

    $total++;

    $hasFillable = (bool) preg_match('/protected\s+\$fillable\s*=/', $body);
    $hasGuarded = (bool) preg_match('/protected\s+\$guarded\s*=/', $body);

    if ($hasFillable && $hasGuarded) {
        $warnings[] = "$name declares both \$fillable and \$guarded — pick one";
        $declared++;
    } elseif ($hasFillable || $hasGuarded) {
        $declared++;
    } else {
        // Check if this model is meant to be a pivot or non-mass-assignable
        // helper. Recognized escape hatches: Pivot subclass; explicit doc
        // comment "no mass assignment".
        $isPivot = (bool) preg_match('/extends\s+(Pivot|MorphPivot)\b/', $body);
        $explicitlyEmptyArr = (bool) preg_match('/\$guarded\s*=\s*\[\]/', $body);
        $hasNoMaInDoc = (bool) preg_match('/@no-mass-assignment\b/', $body);

        if ($isPivot || $explicitlyEmptyArr || $hasNoMaInDoc) {
            $declared++;
            continue;
        }

        $errors[] = "$name has no \$fillable or \$guarded declaration (mass-assignment risk)";
    }
}

echo str_pad('', 60, '-')."\n";
echo "Mass-assignment audit\n";
echo str_pad('', 60, '-')."\n";
echo "Models scanned:  $total\n";
echo "Declared:        $declared\n";

if ($warnings) {
    echo "\nWarnings (".count($warnings)."):\n";
    foreach ($warnings as $w) {
        echo "  WARN  $w\n";
    }
}

if ($errors) {
    echo "\nErrors (".count($errors)."):\n";
    foreach ($errors as $e) {
        echo "  ERROR $e\n";
    }
    echo "\nAudit FAILED.\n";
    exit(1);
}

echo "\nAudit clean — every model has explicit \$fillable or \$guarded.\n";
exit(0);

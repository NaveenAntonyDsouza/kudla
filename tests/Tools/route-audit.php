<?php

/*
 * Route audit — cross-reference between three sources of truth:
 *   1. routes/api.php (actual URI registry)
 *   2. docs/mobile-app/reference/endpoint-catalogue.md (documented contract)
 *   3. tests/Feature/Api/V1/* (Pest coverage)
 *
 * Reports:
 *   - documented but not registered (doc drift, dead link)
 *   - registered but not documented (undocumented endpoint)
 *   - registered but no Pest assertion mentions the URI (untested)
 *
 * Run with: php tests/Tools/route-audit.php
 *
 * Exit code 0 = clean, 1 = drift found.
 */

// 1. Registered routes — pulled via `php artisan route:list --json`,
// avoiding the manual-bootstrap dance that breaks on the Filesystem
// service provider (PHPStan-style standalone scripts can't fully boot).
chdir(__DIR__.'/../../');
$json = shell_exec('php artisan route:list --json --path=api/v1 2>&1');
$allRoutes = json_decode((string) $json, true) ?: [];

$registered = [];
foreach ($allRoutes as $r) {
    if (! str_starts_with((string) $r['uri'], 'api/v1')) {
        continue;
    }
    $methods = is_array($r['method']) ? $r['method'] : explode('|', (string) $r['method']);
    foreach ($methods as $m) {
        if ($m === 'HEAD') {
            continue;
        }
        $key = $m.' /'.$r['uri'];
        $registered[$key] = ['method' => $m, 'uri' => '/'.$r['uri'], 'middleware' => $r['middleware'] ?? []];
    }
}

// 2. Documented endpoints from endpoint-catalogue.md.
$catalogue = file_get_contents(__DIR__.'/../../docs/mobile-app/reference/endpoint-catalogue.md');
// Format: `| METHOD | \`/path\` | …`
preg_match_all('/^\|\s*(GET|POST|PUT|PATCH|DELETE)\s*\|\s*`([^`]+)`/m', $catalogue, $m, PREG_SET_ORDER);
$documented = [];
foreach ($m as $row) {
    // catalogue uses paths like `/auth/me`; routes use `/api/v1/auth/me`. Normalize.
    $uri = '/api/v1'.$row[2];
    $key = $row[1].' '.$uri;
    $documented[$key] = true;
}

// Normalize parametrized URLs from catalogue (`/profiles/{matriId}`)
// against routes (`/profiles/{matriId}` — should already match).
// Convert any `{var}` pattern variations.
$normalize = function (string $uri): string {
    // Replace any `{xxx_id}` with `{xxx}` and lowercase params consistent
    return preg_replace('/\{[^}]+\}/', '{}', $uri);
};

$registeredNorm = [];
foreach ($registered as $key => $r) {
    [$method, $uri] = explode(' ', $key, 2);
    $registeredNorm[$method.' '.$normalize($uri)] = $key;
}

$documentedNorm = [];
foreach (array_keys($documented) as $key) {
    [$method, $uri] = explode(' ', $key, 2);
    $documentedNorm[$method.' '.$normalize($uri)] = $key;
}

// 3. Pest test coverage — search for URIs in tests/Feature/Api/V1.
$testFiles = glob(__DIR__.'/../../tests/Feature/Api/V1/*.php');
$testBlob = '';
foreach ($testFiles as $f) {
    $testBlob .= file_get_contents($f);
}

// Cross-reference
$undocumented = [];   // registered but not in catalogue
$missing = [];        // catalogue but not registered
$untested = [];       // registered but no test mentions URI

foreach ($registeredNorm as $normKey => $origKey) {
    if (! isset($documentedNorm[$normKey])) {
        $undocumented[] = $origKey;
    }

    [$method, $uri] = explode(' ', $origKey, 2);
    // Search test blob for the URI pattern (drop the api/v1 prefix
    // for matching; tests usually match on `/profiles/{matriId}` etc.)
    $regexUri = preg_quote($uri, '#');
    // Replace {param} placeholders with a permissive token so e.g.
    // /profiles/{matriId} matches /profiles/AM000200 in test asserts.
    $regexUri = preg_replace('#\\\{[^}]+\\\}#', '[^\'"\\s/]+', $regexUri);
    if (! preg_match('#'.$regexUri.'#', $testBlob)) {
        $untested[] = $origKey;
    }
}

foreach ($documentedNorm as $normKey => $origKey) {
    if (! isset($registeredNorm[$normKey])) {
        $missing[] = $origKey;
    }
}

// Report
echo str_pad('', 60, '-')."\n";
echo "API route audit\n";
echo str_pad('', 60, '-')."\n";
echo 'Routes registered:           '.count($registered)."\n";
echo 'Endpoints documented:        '.count($documented)."\n";
echo 'Test files searched:         '.count($testFiles)."\n";
echo "\n";

if ($missing) {
    echo "Documented but NOT registered (doc drift / dead links):\n";
    foreach ($missing as $m) {
        echo "  $m\n";
    }
    echo "\n";
}

if ($undocumented) {
    echo "Registered but NOT in catalogue:\n";
    foreach ($undocumented as $u) {
        echo "  $u\n";
    }
    echo "\n";
}

if ($untested) {
    echo "Registered but no test mentions the URI (".count($untested)."):\n";
    foreach ($untested as $u) {
        echo "  $u\n";
    }
    echo "\n";
}

$errors = count($missing);  // doc drift is a real error
$warnings = count($undocumented) + count($untested);

if ($errors > 0) {
    echo "Audit FAILED — $errors errors, $warnings warnings.\n";
    exit(1);
}

if ($warnings > 0) {
    echo "Audit clean (errors=0) — $warnings warnings to triage.\n";
    exit(0);
}

echo "Audit clean — no drift.\n";
exit(0);

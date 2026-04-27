<?php

/*
 * Bruno collection structural lint — fast static check for every .bru file.
 *
 * Validates:
 *   1. has `meta { name, type, seq }` block with valid fields
 *   2. has exactly one HTTP verb block (get/post/put/patch/delete)
 *   3. URL uses {{baseUrl}} interpolation
 *   4. auth=bearer requests reference {{token}}
 *   5. tests block exists and has at least one assertion
 *   6. Every {{var}} referenced is declared in environments/local.bru
 *
 * Run with: php tests/Tools/bruno-lint.php
 *
 * Exit code 0 = clean, 1 = lint errors found.
 */

$collectionRoot = __DIR__.'/../../docs/bruno/kudla-api-v1';
$envFile = $collectionRoot.'/environments/local.bru';

if (! is_dir($collectionRoot)) {
    fwrite(STDERR, "Collection not found at $collectionRoot\n");
    exit(2);
}

// 1. Parse env vars from environments/local.bru — use line-anchored close
// so embedded `{…}` (e.g. inside a quoted value) doesn't fool brace matching.
$declaredVars = ['baseUrl' => true];  // {{baseUrl}} always implicit
if (is_file($envFile)) {
    $envContents = file_get_contents($envFile);
    if (preg_match('/^vars\s*\{\s*$(.*?)^\}\s*$/sm', $envContents, $m)) {
        foreach (explode("\n", $m[1]) as $line) {
            if (preg_match('/^\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*:/', $line, $vm)) {
                $declaredVars[$vm[1]] = true;
            }
        }
    }
}

// 2. Walk every .bru file and validate
$bruFiles = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($collectionRoot));
foreach ($it as $f) {
    if ($f->isFile() && str_ends_with($f->getFilename(), '.bru') && ! str_contains($f->getPathname(), 'environments')) {
        $bruFiles[] = $f->getPathname();
    }
}
sort($bruFiles);

$errors = [];
$warnings = [];
$totalFiles = count($bruFiles);
$totalAssertions = 0;
$varsUsed = [];

foreach ($bruFiles as $path) {
    $rel = str_replace($collectionRoot.DIRECTORY_SEPARATOR, '', $path);
    $rel = str_replace('\\', '/', $rel);
    $body = file_get_contents($path);

    // 1. meta block — use line-anchored close so URL templates like
    // {matriId} inside the `name:` value don't fool the brace match.
    if (! preg_match('/^meta\s*\{\s*$(.*?)^\}\s*$/sm', $body, $metaMatch)) {
        $errors[] = "$rel: missing `meta {…}` block";
        continue;
    }
    $meta = $metaMatch[1];
    foreach (['name', 'type', 'seq'] as $field) {
        if (! preg_match('/\b'.$field.'\s*:/', $meta)) {
            $errors[] = "$rel: meta missing `$field`";
        }
    }

    // 2. exactly one verb block
    $verbCount = 0;
    foreach (['get', 'post', 'put', 'patch', 'delete'] as $verb) {
        if (preg_match('/^'.$verb.'\s*\{/m', $body)) {
            $verbCount++;
        }
    }
    if ($verbCount !== 1) {
        $errors[] = "$rel: expected exactly one HTTP verb block, found $verbCount";
    }

    // 3. URL uses {{baseUrl}}
    if (! preg_match('/url\s*:\s*\{\{baseUrl\}\}/', $body)) {
        $errors[] = "$rel: URL does not use {{baseUrl}}";
    }

    // 4. bearer auth references {{token}}
    if (preg_match('/auth\s*:\s*bearer/', $body) && ! preg_match('/auth:bearer\s*\{[^}]*token:\s*\{\{token\}\}/s', $body)) {
        $errors[] = "$rel: bearer auth declared but does not bind `token: {{token}}`";
    }

    // 5. tests block with at least one assertion
    if (! preg_match('/tests\s*\{(.*)/s', $body, $testsMatch)) {
        $warnings[] = "$rel: no `tests {…}` block (smoke without assertions is allowed but reduces value)";
    } else {
        $assertionCount = preg_match_all('/\btest\s*\(/', $testsMatch[1]);
        if ($assertionCount === 0) {
            $warnings[] = "$rel: tests block present but no `test(…)` calls";
        }
        $totalAssertions += $assertionCount;
    }

    // 6. collect all {{var}} references — variables can be declared in three places:
    //    a) environments/local.bru (env-level, shared across the whole collection)
    //    b) vars:post-response { ... } in this or an earlier file (chain captures)
    //    c) script:pre-request via bru.setVar("name", value) (runtime-set)
    // Build a per-file set of locally-set vars (b + c), then validate every {{ref}}.
    $localDeclared = [];
    if (preg_match('/^vars:post-response\s*\{\s*$(.*?)^\}\s*$/sm', $body, $vpr)) {
        if (preg_match_all('/^\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*:/m', $vpr[1], $names)) {
            foreach ($names[1] as $n) {
                $localDeclared[$n] = true;
            }
        }
    }
    if (preg_match('/^script:pre-request\s*\{\s*$(.*?)^\}\s*$/sm', $body, $spr)) {
        if (preg_match_all('/bru\.setVar\(\s*[\'"]([a-zA-Z_][a-zA-Z0-9_]*)[\'"]/', $spr[1], $names)) {
            foreach ($names[1] as $n) {
                $localDeclared[$n] = true;
            }
        }
    }

    if (preg_match_all('/\{\{([a-zA-Z_][a-zA-Z0-9_]*)\}\}/', $body, $vm)) {
        foreach ($vm[1] as $varName) {
            $varsUsed[$varName] = true;
            if (! isset($declaredVars[$varName]) && ! isset($localDeclared[$varName])) {
                // Bruno also supports special $randomInt etc. — skip those
                if (str_starts_with($varName, '$')) {
                    continue;
                }
                $errors[] = "$rel: references {{".$varName."}} but it's not declared in environments/local.bru, vars:post-response, or set via script:pre-request";
            }
        }
    }
}

// 3. Report
echo str_pad('', 60, '-')."\n";
echo "Bruno collection lint — $totalFiles .bru files\n";
echo str_pad('', 60, '-')."\n";
echo "Declared env vars: ".count($declaredVars)." (".implode(', ', array_keys($declaredVars)).")\n";
echo "Vars referenced:   ".count($varsUsed)."\n";
echo "Total `test(…)` assertions: $totalAssertions\n";
echo "\n";

if ($warnings) {
    echo "Warnings (".count($warnings)."):\n";
    foreach ($warnings as $w) {
        echo "  WARN  $w\n";
    }
    echo "\n";
}

if ($errors) {
    echo "Errors (".count($errors)."):\n";
    foreach ($errors as $e) {
        echo "  ERROR $e\n";
    }
    echo "\nLint FAILED.\n";
    exit(1);
}

echo "Lint clean — $totalFiles files, $totalAssertions assertions, 0 errors.\n";
exit(0);

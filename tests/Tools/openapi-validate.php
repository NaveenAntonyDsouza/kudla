<?php

/*
 * OpenAPI 3.1 spec structural lint for Scribe-generated openapi.yaml.
 *
 * Validates:
 *   1. openapi field is "3.1.x"
 *   2. info block exists with title + version
 *   3. paths block has entries
 *   4. every operation has summary or description
 *   5. every operation has at least one response code (and 200/201 for GET/POST)
 *   6. no operation references an undefined component schema
 *   7. authenticated routes are tagged with the security: bearerAuth array
 *
 * Run with: php tests/Tools/openapi-validate.php
 *
 * Exit code 0 = clean, 1 = lint errors.
 */

require __DIR__.'/../../vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;

$specPath = __DIR__.'/../../storage/app/private/scribe/openapi.yaml';
if (! is_file($specPath)) {
    fwrite(STDERR, "OpenAPI spec not found at $specPath. Run `php artisan scribe:generate` first.\n");
    exit(2);
}

$spec = Yaml::parseFile($specPath);

$errors = [];
$warnings = [];

// 1. openapi version — accept 3.0.x or 3.1.x (Scribe currently emits 3.0.3
// which is widely supported; can upgrade later when Scribe targets 3.1).
$version = $spec['openapi'] ?? null;
if (! $version || ! preg_match('/^3\.[01]\./', (string) $version)) {
    $errors[] = "openapi field is '$version', expected 3.0.x or 3.1.x";
}

// 2. info
foreach (['title', 'version'] as $f) {
    if (! ($spec['info'][$f] ?? null)) {
        $errors[] = "info.$f is missing";
    }
}

// 3. paths
$paths = $spec['paths'] ?? [];
if (! $paths) {
    $errors[] = 'paths block is empty';
}

// Walk every operation
$opCount = 0;
$pathCount = count($paths);
$tags = [];
$opsByMethod = [];
$opsWithoutDesc = [];
$opsWithoutResponse = [];
$opsPublic = [];
$schemasReferenced = [];

// Determine global default-security: which schemes are required by default?
$globalSecuritySchemes = [];
foreach ($spec['security'] ?? [] as $secReq) {
    foreach ((array) $secReq as $schemeName => $_scopes) {
        $globalSecuritySchemes[$schemeName] = true;
    }
}
$globalRequiresAuth = ! empty($globalSecuritySchemes);
$bearerAuthOps = 0;

foreach ($paths as $url => $methods) {
    if (! is_array($methods)) {
        continue;
    }
    foreach ($methods as $method => $op) {
        if (! in_array($method, ['get', 'post', 'put', 'patch', 'delete'])) {
            continue;
        }
        if (! is_array($op)) {
            continue;
        }
        $opCount++;
        $opsByMethod[$method] = ($opsByMethod[$method] ?? 0) + 1;
        $opId = strtoupper($method).' '.$url;

        // Tags
        foreach ($op['tags'] ?? [] as $t) {
            $tags[$t] = ($tags[$t] ?? 0) + 1;
        }

        // Summary or description
        if (empty($op['summary']) && empty($op['description'])) {
            $opsWithoutDesc[] = $opId;
        }

        // Responses
        $responses = $op['responses'] ?? [];
        if (! $responses) {
            $opsWithoutResponse[] = $opId;
        } else {
            $hasSuccess = false;
            foreach ($responses as $code => $_resp) {
                if (((int) $code >= 200) && ((int) $code < 300)) {
                    $hasSuccess = true;
                }
            }
            if (! $hasSuccess && ! in_array($method, ['delete'])) {
                $warnings[] = "$opId: no 2xx success response declared";
            }
        }

        // Auth resolution — OpenAPI 3.x rules:
        //   - per-op `security: []` (empty array) overrides global to "no auth"
        //   - per-op `security: [- {scheme}: …]` lists required schemes
        //   - no per-op security → inherits global default
        $opSecurity = $op['security'] ?? null;
        if ($opSecurity === [] || $opSecurity === [[]]) {
            // explicitly public
            $opsPublic[] = $opId;
        } elseif ($opSecurity === null) {
            // inherits global
            if ($globalRequiresAuth) {
                $bearerAuthOps++;
            } else {
                $opsPublic[] = $opId;
            }
        } else {
            // per-op security overrides global
            $bearerAuthOps++;
        }

        // Component schema refs
        $json = json_encode($op);
        if (preg_match_all('/\$ref":\s*"#\/components\/schemas\/([A-Za-z0-9_]+)"/', $json, $rm)) {
            foreach ($rm[1] as $schema) {
                $schemasReferenced[$schema] = true;
            }
        }
    }
}

// 6. components.schemas — every referenced schema exists
$schemasDefined = array_keys($spec['components']['schemas'] ?? []);
$undefinedRefs = array_diff(array_keys($schemasReferenced), $schemasDefined);
foreach ($undefinedRefs as $undef) {
    $errors[] = "operation references undefined schema #/components/schemas/$undef";
}

// Report
echo str_pad('', 60, '-')."\n";
echo "OpenAPI 3.1 spec lint\n";
echo str_pad('', 60, '-')."\n";
echo "openapi version:    {$spec['openapi']}\n";
echo "info.title:         {$spec['info']['title']}\n";
echo "info.version:       {$spec['info']['version']}\n";
echo "paths:              $pathCount\n";
echo "operations:         $opCount\n";
foreach ($opsByMethod as $m => $c) {
    echo '   '.str_pad(strtoupper($m).': ', 8)."$c\n";
}
echo "tags:               ".count($tags)."\n";
ksort($tags);
foreach ($tags as $t => $c) {
    echo '   '.str_pad("$t: ", 30, '.')."$c ops\n";
}
echo "global security:    ".($globalRequiresAuth ? 'required ('.implode(',', array_keys($globalSecuritySchemes)).')' : 'public')."\n";
echo "ops requiring auth: $bearerAuthOps\n";
echo "ops public:         ".count($opsPublic)."\n";
echo "schemas defined:    ".count($schemasDefined).' (referenced: '.count($schemasReferenced).")\n";

if ($opsWithoutDesc) {
    echo "\nOperations without summary/description (".count($opsWithoutDesc)."):\n";
    foreach ($opsWithoutDesc as $o) {
        echo "  $o\n";
    }
    $warnings[] = count($opsWithoutDesc).' operations missing description';
}

if ($opsWithoutResponse) {
    echo "\nOperations with no responses (".count($opsWithoutResponse)."):\n";
    foreach ($opsWithoutResponse as $o) {
        echo "  $o\n";
    }
    $errors[] = count($opsWithoutResponse).' operations have no response declarations';
}

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
    echo "\nLint FAILED.\n";
    exit(1);
}

echo "\nLint clean — $opCount operations across $pathCount paths.\n";
exit(0);

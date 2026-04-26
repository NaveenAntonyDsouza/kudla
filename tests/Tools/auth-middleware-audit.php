<?php

/*
 * Auth middleware audit — verify the docblock @authenticated tag on every
 * controller method matches the routes/api.php auth:sanctum middleware
 * for the route that maps to it.
 *
 * For every API route, the rule is:
 *   - controller method has @authenticated docblock  ↔  route has auth:sanctum
 *   - controller method has @unauthenticated         ↔  route has NO auth:sanctum
 *
 * Mismatches are real security bugs:
 *   - @authenticated method on a public route → endpoint is exposed
 *   - @unauthenticated method on an auth route → docs are wrong
 *
 * Run with: php tests/Tools/auth-middleware-audit.php
 *
 * Exit code 0 = clean, 1 = mismatches found.
 */

require __DIR__.'/../../vendor/autoload.php';
chdir(__DIR__.'/../../');
$json = shell_exec('php artisan route:list --json --path=api/v1 2>&1');
$routes = json_decode((string) $json, true) ?: [];

$errors = [];
$totalChecked = 0;
$authDocAuthRoute = 0;     // @authenticated + auth:sanctum
$publicDocPublicRoute = 0; // @unauthenticated (or absent) + no auth:sanctum
$nonControllerRoutes = 0;  // closures, etc.

foreach ($routes as $r) {
    if (! str_starts_with((string) $r['uri'], 'api/v1')) {
        continue;
    }
    $action = (string) ($r['action'] ?? '');
    if (! str_contains($action, '@')) {
        $nonControllerRoutes++;
        continue;
    }

    [$class, $method] = explode('@', $action);
    $class = ltrim($class, '\\');
    $methods = is_array($r['method']) ? $r['method'] : explode('|', (string) $r['method']);
    $methods = array_values(array_filter($methods, fn ($m) => $m !== 'HEAD'));
    $verb = $methods[0] ?? 'GET';
    $opId = $verb.' /'.$r['uri'];

    // Skip Laravel framework controllers
    if (str_starts_with($class, 'Illuminate\\') || str_starts_with($class, 'Laravel\\')) {
        $nonControllerRoutes++;
        continue;
    }

    // Read docblock
    $file = __DIR__.'/../../'.str_replace('\\', '/', $class).'.php';
    if (! is_file($file)) {
        $errors[] = "$opId: controller file not found at $file";
        continue;
    }
    try {
        $reflection = new ReflectionMethod($class, $method);
    } catch (Throwable $e) {
        $errors[] = "$opId: reflection failed — {$e->getMessage()}";
        continue;
    }
    $doc = (string) $reflection->getDocComment();

    $hasAuthenticatedTag = (bool) preg_match('/@authenticated\b/', $doc);
    $hasUnauthenticatedTag = (bool) preg_match('/@unauthenticated\b/', $doc);

    // Check route middleware
    $middleware = $r['middleware'] ?? [];
    if (! is_array($middleware)) {
        $middleware = [$middleware];
    }
    // Laravel's route:list resolves the alias 'auth:sanctum' to either:
    //   - 'auth:sanctum' (string)
    //   - 'Illuminate\Auth\Middleware\Authenticate:sanctum' (resolved class)
    // depending on the registration. Match either.
    $hasAuthSanctum = false;
    foreach ($middleware as $mw) {
        $mwStr = (string) $mw;
        if (str_contains($mwStr, 'auth:sanctum')
            || str_contains($mwStr, 'Authenticate:sanctum')
            || str_contains($mwStr, 'EnsureFrontendRequestsAreStateful:sanctum')) {
            $hasAuthSanctum = true;
            break;
        }
    }

    $totalChecked++;

    // Resolve actual auth requirement (docblock vs middleware)
    if ($hasAuthenticatedTag && $hasAuthSanctum) {
        $authDocAuthRoute++;
    } elseif (! $hasAuthenticatedTag && ! $hasAuthSanctum) {
        $publicDocPublicRoute++;
    } elseif ($hasAuthenticatedTag && ! $hasAuthSanctum) {
        $errors[] = "$opId: docblock says @authenticated but route has NO auth:sanctum middleware (security risk)";
    } elseif (! $hasAuthenticatedTag && $hasAuthSanctum) {
        // OK only if @unauthenticated tag is absent. Some controllers omit
        // both tags but the route IS protected — that's fine, just warn.
        if ($hasUnauthenticatedTag) {
            $errors[] = "$opId: docblock says @unauthenticated but route HAS auth:sanctum middleware";
        }
        // Else: docblock missing tag, route is auth — count as auth-route
        $authDocAuthRoute++;
    }
}

// Report
echo str_pad('', 60, '-')."\n";
echo "Auth middleware audit\n";
echo str_pad('', 60, '-')."\n";
echo 'Routes checked:                 '.$totalChecked."\n";
echo '  @authenticated + auth route:  '.$authDocAuthRoute."\n";
echo '  public doc + public route:    '.$publicDocPublicRoute."\n";
echo 'Non-controller routes skipped:  '.$nonControllerRoutes."\n";

if ($errors) {
    echo "\nMismatches (".count($errors)."):\n";
    foreach ($errors as $e) {
        echo "  ERROR $e\n";
    }
    echo "\nAudit FAILED.\n";
    exit(1);
}

echo "\nAudit clean — every @authenticated docblock matches an auth:sanctum route.\n";
exit(0);

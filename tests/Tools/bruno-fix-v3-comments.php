<?php

/*
 * One-shot migrator: convert top-level `# ...` comments in .bru files to
 * the `docs { ... }` block syntax that Bruno v3+ requires.
 *
 * Bruno v2 silently tolerated stray `# ...` lines between blocks; v3's
 * stricter parser rejects them with "Expected end of input, ..." and
 * skips the entire file. The collection's smoke runs were silently
 * broken on v3 until this lint script surfaced the issue.
 *
 * Strategy:
 *   1. Walk each line, tracking brace depth.
 *   2. At depth 0, group consecutive lines starting with `#`.
 *   3. Replace each group with a `docs { ... }` block whose content is
 *      the comment text (with the leading `# ` stripped).
 *   4. Other lines pass through unchanged.
 *
 * Run with: php tests/Tools/bruno-fix-v3-comments.php
 *
 * Idempotent — running twice on already-converted files is a no-op.
 */

$root = __DIR__.'/../../docs/bruno/kudla-api-v1';
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
$converted = 0;
$alreadyClean = 0;

foreach ($it as $f) {
    if (! $f->isFile() || ! str_ends_with($f->getFilename(), '.bru')) {
        continue;
    }
    $path = $f->getPathname();
    $rel = str_replace($root.DIRECTORY_SEPARATOR, '', $path);

    $lines = file($path, FILE_IGNORE_NEW_LINES);
    $out = [];
    $depth = 0;
    $inCommentGroup = false;
    $commentBuffer = [];

    $flushCommentGroup = function () use (&$out, &$commentBuffer, &$inCommentGroup) {
        if (! $commentBuffer) {
            return;
        }
        $out[] = 'docs {';
        foreach ($commentBuffer as $c) {
            // Strip leading `# ` (or `#`), keep the rest verbatim.
            $stripped = preg_replace('/^#\s?/', '', $c);
            // Indent each docs body line 2 spaces for readability.
            $out[] = '  '.$stripped;
        }
        $out[] = '}';
        $commentBuffer = [];
        $inCommentGroup = false;
    };

    foreach ($lines as $line) {
        // Top-level comment at depth 0 — buffer it.
        if ($depth === 0 && preg_match('/^#(\s|$)/', ltrim($line))) {
            $commentBuffer[] = ltrim($line);
            $inCommentGroup = true;
            continue;
        }

        // Empty line during a comment group — keep buffering until next
        // non-empty / non-comment line to avoid trailing whitespace eating
        // the trailing comment of a multi-paragraph group.
        if ($inCommentGroup && trim($line) === '') {
            // Hold off on flushing — allow comment groups to contain
            // blank lines only if surrounded by comments. Cheap heuristic:
            // peek nothing; just flush. Multi-paragraph comments are rare.
            $flushCommentGroup();
            $out[] = $line;
            continue;
        }

        // Non-comment, non-blank line: flush any pending comment group.
        if ($inCommentGroup) {
            $flushCommentGroup();
        }

        $out[] = $line;

        // Update brace depth — count the unbalanced { vs } on this line.
        // Skip strings to avoid counting braces inside body:json bodies.
        // Bruno block syntax uses literal `{` after the block name; the
        // body's JSON also uses `{`. Both are at depth>0 once we enter a
        // block. Naive brace counting works because we're only tracking
        // depth-0 (outside-any-block) comments.
        $stripped = preg_replace('/"[^"]*"/', '""', $line);  // erase double-quoted strings
        $depth += substr_count($stripped, '{') - substr_count($stripped, '}');
        if ($depth < 0) {
            $depth = 0;  // defensive — never go negative
        }
    }

    // EOF — flush any trailing comment group.
    if ($inCommentGroup) {
        $flushCommentGroup();
    }

    $newContent = implode("\n", $out)."\n";
    $oldContent = file_get_contents($path);

    // Normalize line endings for comparison
    $newNorm = str_replace("\r\n", "\n", $newContent);
    $oldNorm = str_replace("\r\n", "\n", $oldContent);

    if ($newNorm === $oldNorm) {
        $alreadyClean++;
        continue;
    }

    file_put_contents($path, $newContent);
    echo "  converted: $rel\n";
    $converted++;
}

echo "\n";
echo "Converted:    $converted file(s)\n";
echo "Already clean: $alreadyClean file(s)\n";

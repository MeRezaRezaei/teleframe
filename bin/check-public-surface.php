<?php

declare(strict_types=1);

/**
 * check-public-surface.php — the mechanical gate for the private/public split.
 *
 * The private repository is the whole working repo. The public repository is a
 * SANITIZED COPY produced by `bin/export-public-repo.sh` from the allowlist in
 * `bin/public-manifest.txt`. Nothing about the split is a convention: this gate
 * is what makes it enforceable, and it fails CLOSED — a path that nobody
 * thought about is a violation, not a neutral.
 *
 * TWO INDEPENDENT RULES, both must hold:
 *
 *   1. ALLOWLIST — every tracked path must match at least one pattern in
 *      bin/public-manifest.txt. This is what stops a new private file from
 *      reaching the public repo by accident: the default is OUT.
 *   2. STRIP SET — no path may sit under a path listed in PRIVATE_ONLY below.
 *      This is deliberately a SECOND, independent rule rather than a
 *      consequence of rule 1. Someone can always widen the manifest; this
 *      rule is the one that says a path declared private is private no matter
 *      what the manifest claims. Decisions D-035 in docs/decisions.md names
 *      every entry here, and requires that a gate fails the build if any of
 *      them is ever tracked in the public tree.
 *
 * EXIT CODES (also printed by --help):
 *   0  clean — every tracked path is inside the manifest and outside the strip set
 *   1  violations — at least one path is outside the manifest, or inside the strip set
 *   2  usage error — bad arguments, missing manifest, or a broken `git ls-files`
 *
 * HOW PATHS ARE ENUMERATED (documented here because the choice matters):
 *   default          `git ls-files -z` via proc_open, run in the repository root
 *                    that contains this script. Tracked files only — an
 *                    untracked file is not part of the published surface,
 *                    because `git archive` cannot emit it either, so the gate
 *                    and the producer enumerate the SAME set by construction.
 *   --tree=DIR       plain recursive directory walk instead, for verifying an
 *                    already-exported public tree. Used by
 *                    bin/export-public-repo.sh as its second check.
 *
 * ZERO REGEX. This file lives in bin/, and the project's zero-regex rule
 * (`preg_*` is banned outside src/Laravel, src/Schema, src/Bot) is treated here
 * as STRICTLY enforced. All matching is `fnmatch()` plus string functions.
 */
$root = dirname(__DIR__);
$manifestPath = $root.'/bin/public-manifest.txt';

// Paths that are private-only by decision D-035. See docs/decisions.md.
// This list is the strip set: the files `export-public-repo.sh` leaves behind,
// and the files the public branch's strip commit deletes. Rule 2 above fails
// even if the manifest is later widened to include one of them.
$privateOnly = [
    '.ai/',
    '.brain/',
    '.graphify/',
    '.ledger/',
    '.opencode-ci/',
    '.opencode/',
    '.superpowers/',
    '.openclaude/',
    'graphify-out/',
    'inspection-snapshot/',
    'HANDOFF.md',
    '.warm-ledger.json',
    '.credential-guard-baseline',
    // The agent's own documentation moved OUT of docs/ and INTO .brain/ (the
    // operator's ruling: docs/ was the old system; .brain is his brain). The
    // names below no longer exist as tracked paths, and they are kept here
    // deliberately: a `git mv` that half-happens, or a file restored from an
    // old checkout, must still fail the gate rather than land in the public
    // tree. A strip-set entry for a path that does not exist costs nothing and
    // catches the regression that would otherwise publish it.
    'docs/subagents/',
    'docs/verbatim/',
    'docs/verbatims/',
    'docs/evidence/',
    'docs/test-reports/',
    'docs/plans/',
    'docs/superpowers/',
    'docs/decisions.md',
    'docs/decisions/',
    'docs/agent-registry.md',
    'docs/agent-identities.md',
    'docs/verbatim.md',
    'docs/verbatim-report.md',
    'docs/verbatim-goals.md',
    'docs/verbatim-coverage.md',
    'docs/repo-status.md',
    'docs/what-has-been-done.md',
];

$usage = <<<'TXT'
check-public-surface.php — enforce the private/public split (allowlist + strip set)

USAGE
  php bin/check-public-surface.php [--tree=DIR] [--quiet] [--help]

OPTIONS
  --tree=DIR   Walk DIR instead of running `git ls-files -z`. Use this to
               verify an already-exported public tree.
  --list       Print the paths this repository publishes, one per line, and
               exit 0. This is the ONLY supported way to ask "what is public":
               bin/export-public-repo.sh calls it, so the exporter and the gate
               can never disagree about the answer. Prints nothing else — not
               even the summary line — so it is safe to pipe.
               The strip set is applied first, then the allowlist.
  --quiet      Print only the summary line and the violation count.
  --help       Print this text.

EXIT CODES
  0  clean      every tracked path is inside the manifest and outside the strip set
  1  violations at least one path is outside the manifest, or inside the strip set
  2  usage error bad arguments, missing manifest, or a broken `git ls-files`

ON SUCCESS prints one line:
  public surface OK: N tracked files, 0 outside the manifest

ON FAILURE prints, per violation group, a count and the offending paths, then a
total. The groups are bucketised by the first two path segments (or "(root
files)"), which is a READING aid only — it does not affect the verdict.
TXT;

$treeDir = null;
$quiet = false;
$listOnly = false;

foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--help' || $arg === '-h') {
        echo $usage;
        exit(0);
    }
    if ($arg === '--quiet') {
        $quiet = true;

        continue;
    }
    if ($arg === '--list') {
        $listOnly = true;

        continue;
    }
    if (str_starts_with($arg, '--tree=')) {
        $treeDir = substr($arg, 7);
        if ($treeDir === '' || ! is_dir($treeDir)) {
            fwrite(STDERR, "check-public-surface: --tree=DIR is not a directory: {$treeDir}\n");
            exit(2);
        }

        continue;
    }
    fwrite(STDERR, "check-public-surface: unknown argument: {$arg}\n\n{$usage}");
    exit(2);
}

if (! is_file($manifestPath)) {
    fwrite(STDERR, "check-public-surface: manifest not found: {$manifestPath}\n");
    exit(2);
}

// ---------------------------------------------------------------------------
// Manifest: read, strip comments and blanks. A trailing slash means "this
// directory and every path under it"; anything else is an fnmatch() pattern.
// ---------------------------------------------------------------------------

$manifestRaw = @file_get_contents($manifestPath);
if ($manifestRaw === false) {
    fwrite(STDERR, "check-public-surface: cannot read manifest: {$manifestPath}\n");
    exit(2);
}

$patterns = [];
foreach (explode("\n", $manifestRaw) as $lineNo => $line) {
    $line = trim($line);
    if ($line === '' || str_starts_with($line, '#')) {
        continue;
    }
    $patterns[] = $line;
}

if ($patterns === []) {
    fwrite(STDERR, "check-public-surface: manifest has no patterns: {$manifestPath}\n");
    exit(2);
}

/**
 * Does $path match $pattern?
 *
 *  - `dir/`  -> $path is `dir` or lives under it. This is a SUBTREE rule, not a
 *               glob, because a bare `src/` must not accidentally mean `src`
 *               alone and it must not require a recursive glob dialect.
 *  - else    -> exact path, then two fnmatch() passes: FNM_PATHNAME so `*`
 *               does NOT cross a `/`, then the permissive pass so a `**`
 *               reaches across directories.
 */
function pathMatches(string $path, string $pattern): bool
{
    if ($pattern === '') {
        return false;
    }

    if (str_ends_with($pattern, '/')) {
        $dir = rtrim($pattern, '/');

        return $path === $dir || str_starts_with($path, $dir.'/');
    }

    if ($path === $pattern) {
        return true;
    }

    if (fnmatch($pattern, $path, FNM_PATHNAME)) {
        return true;
    }

    return fnmatch($pattern, $path);
}

/** Bucket a violating path for the report. Purely cosmetic. */
function bucketOf(string $path): string
{
    $parts = explode('/', $path);
    if (count($parts) === 1) {
        return '(root files)';
    }
    if (count($parts) === 2) {
        return $parts[0].'/';
    }

    return $parts[0].'/'.$parts[1].'/';
}

// ---------------------------------------------------------------------------
// Enumerate paths.
// ---------------------------------------------------------------------------

/** @return list<string> */
function walkTree(string $dir, string $prefix): array
{
    $out = [];
    $entries = @scandir($dir);
    if ($entries === false) {
        return $out;
    }
    sort($entries);
    foreach ($entries as $entry) {
        if ($entry === '.' || $entry === '..' || $entry === '.git') {
            continue;
        }
        $full = $dir.'/'.$entry;
        $rel = $prefix === '' ? $entry : $prefix.'/'.$entry;
        if (is_dir($full)) {
            foreach (walkTree($full, $rel) as $nested) {
                $out[] = $nested;
            }

            continue;
        }
        if (is_file($full)) {
            $out[] = $rel;
        }
    }

    return $out;
}

/** @return list<string> */
function gitTracked(string $root): array
{
    $cmd = ['git', '-C', $root, 'ls-files', '-z'];
    $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
    $proc = @proc_open($cmd, $descriptors, $pipes, $root);
    if (! is_resource($proc)) {
        fwrite(STDERR, "check-public-surface: cannot run git ls-files in {$root}\n");
        exit(2);
    }
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $code = proc_close($proc);

    if ($code !== 0) {
        fwrite(STDERR, "check-public-surface: git ls-files failed ({$code}): ".trim((string) $stderr)."\n");
        exit(2);
    }

    $paths = [];
    foreach (explode("\0", (string) $stdout) as $path) {
        if ($path !== '') {
            $paths[] = $path;
        }
    }

    return $paths;
}

$paths = $treeDir !== null ? walkTree($treeDir, '') : gitTracked($root);
sort($paths);
$total = count($paths);

// ---------------------------------------------------------------------------
// Verdict.
// ---------------------------------------------------------------------------

/** @var array<string, list<string>> $stripSetViolations */
$stripSetViolations = [];
/** @var array<string, list<string>> $allowlistViolations */
$allowlistViolations = [];

foreach ($paths as $path) {
    $inStripSet = false;
    foreach ($privateOnly as $needle) {
        if (pathMatches($path, $needle)) {
            $inStripSet = true;
            break;
        }
    }
    if ($inStripSet) {
        $stripSetViolations[bucketOf($path)][] = $path;

        // Reported once, in the strip-set section. Counting it twice would
        // make the total disagree with the number of offending files.
        continue;
    }

    $inManifest = false;
    foreach ($patterns as $pattern) {
        if (pathMatches($path, $pattern)) {
            $inManifest = true;
            break;
        }
    }
    if (! $inManifest) {
        $allowlistViolations[bucketOf($path)][] = $path;
    }
}

$stripCount = 0;
foreach ($stripSetViolations as $group) {
    $stripCount += count($group);
}
$allowCount = 0;
foreach ($allowlistViolations as $group) {
    $allowCount += count($group);
}

$violationCount = $stripCount + $allowCount;

// ---------------------------------------------------------------------------
// --list: the published set, and NOTHING else on stdout.
//
// This runs BEFORE the verdict so it is available whatever the verdict is: the
// exporter wants the list on a clean tree, and a person debugging a violation
// wants to see exactly what the manifest does and does not cover. The strip set
// is applied first and wins over any manifest match, so a path that is private
// by decision never appears here even if a glob would have matched it.
// ---------------------------------------------------------------------------
if ($listOnly) {
    $published = [];
    foreach ($paths as $path) {
        foreach ($privateOnly as $needle) {
            if (pathMatches($path, $needle)) {
                continue 2;
            }
        }
        foreach ($patterns as $pattern) {
            if (pathMatches($path, $pattern)) {
                $published[] = $path;

                break;
            }
        }
    }
    sort($published);
    foreach ($published as $path) {
        echo $path, "\n";
    }
    exit(0);
}

/**
 * @param  array<string, list<string>>  $groups
 */
function printGroups(array $groups, string $heading, string $explanation, bool $quiet): void
{
    if ($groups === []) {
        return;
    }
    ksort($groups);
    echo "\n{$heading}\n";
    if (! $quiet) {
        echo "{$explanation}\n";
    }
    foreach ($groups as $bucket => $items) {
        sort($items);
        echo "\n  {$bucket}  (".bucketCountLabel(count($items)).")\n";
        foreach ($items as $item) {
            echo "    {$item}\n";
        }
    }
}

function bucketCountLabel(int $n): string
{
    return $n === 1 ? '1 file' : $n.' files';
}

if ($violationCount === 0) {
    echo "public surface OK: {$total} tracked files, 0 outside the manifest\n";
    exit(0);
}

echo "public surface VIOLATION: {$total} tracked files, {$violationCount} outside the manifest\n";

printGroups(
    $stripSetViolations,
    '== STRIP SET: private-only paths that are tracked ==',
    "These are private by decision D-035 (docs/decisions.md). They fail even if\n"
    ."bin/public-manifest.txt were widened to include them. The public branch's\n"
    ."strip commit deletes exactly these; .gitattributes merge=ours keeps them\n"
    .'alive in the private repo when public main is merged back.',
    $quiet
);

printGroups(
    $allowlistViolations,
    '== OUTSIDE THE ALLOWLIST: tracked but not published ==',
    "Not matched by any pattern in bin/public-manifest.txt, and not on the strip\n"
    ."set. Either it is public and the manifest is missing an entry (add it, with\n"
    ."a stated reason at the top of the manifest), or it is private and belongs on\n"
    .'the strip set. Deciding which is the whole point of the allowlist.',
    $quiet
);

echo "\n".$violationCount." of {$total} tracked paths must be dealt with before publishing.\n";
echo "Fix by EDITING bin/public-manifest.txt, never by deleting the file from this\n";
echo "repository — the local repo IS the private repo; the strip happens at publish\n";
echo "time in bin/export-public-repo.sh.\n";

exit(1);

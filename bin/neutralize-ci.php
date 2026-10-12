<?php

declare(strict_types=1);

/**
 * neutralize-ci.php — make the PRIVATE repository's GitHub Actions inert,
 * reversibly, without ever creating a second copy of a workflow.
 *
 * THE PROBLEM THIS SOLVES. The operator is splitting one repository into two:
 *
 *   teleframe-private (PRIVATE)  long-term storage of EVERYTHING, including
 *                               .brain/, .ledger/ and .opencode-ci/. Its
 *                               GitHub Actions must NEVER run — but the
 *                               workflow files must stay TRACKED, because the
 *                               CI definition is part of the record.
 *   teleframe         (PUBLIC)  fed only by the sanitized export
 *                               (bin/export-public-repo.sh +
 *                               bin/public-manifest.txt +
 *                               bin/check-public-surface.php). Its Actions DO
 *                               run.
 *
 * `bin/public-manifest.txt:110` already allows `.github/**`, so the exporter
 * publishes the REAL workflow files to the public repo. That single fact
 * decides the whole design of this tool.
 *
 * WHY NOT "JUST COMMENT THE LINES OUT". The private repo needs its workflows
 * tracked AND the public repo needs them intact, from ONE source. Any solution
 * that maintains a second copy — a `.yml.disabled` twin that is edited in
 * lockstep, or a hand-maintained commented-out variant — creates a second
 * source of truth, and the exporter and the gate then drift apart. This
 * repository already has a name for that disease: see the header of
 * bin/check-public-surface.php. So this tool is a BUILD STEP applied to the
 * private repo's tree, and it is fully reversible: push-time the workflows are
 * renamed out of GitHub's reach, and `enable` puts the original bytes back.
 * No second copy is ever written; the `.neutralized` file IS the original file
 * under a different name, with a fixed-width marker prepended.
 *
 * THE MECHANISM, AND WHY GITHUB CANNOT EXECUTE IT.
 *   GitHub Actions discovers workflows by listing `.github/workflows/` and
 *   reading only those entries whose file name ends in `.yml` or `.yaml`.
 *   `security.yml.neutralized` ends in `.neutralized`, so it matches neither
 *   suffix: Actions never opens it, never parses it as YAML, and therefore
 *   never dispatches a single job from it. Its CONTENTS are irrelevant to
 *   Actions — which is the point, and the reason renaming beats commenting.
 *
 *   Commenting every line out would leave a LIVE file whose inertness depends
 *   on YAML comment semantics: one uncommented `on:` key re-arms the whole
 *   repository, YAML block scalars can swallow a `#`, and undoing it is lossy
 *   (a commented line has lost its original indentation and quoting). A
 *   rename is inert by a property of the FILENAME alone, which is the one
 *   thing a human reviewing a diff can check at a glance, and it is exactly
 *   reversible because nothing was rewritten — only renamed and prefixed with
 *   a byte-counted marker.
 *
 * USAGE
 *   php bin/neutralize-ci.php --mode=disable [--dir=DIR]
 *   php bin/neutralize-ci.php --mode=enable  [--dir=DIR]
 *
 *   --dir=DIR   the repository whose `.github/workflows/` is rewritten.
 *               Defaults to this script's own repository root.
 *   --mode      `disable` renames every workflow to `*.yml.neutralized` and
 *               prepends the marker; `enable` strips the marker and renames
 *               back. Anything else is a usage error.
 *
 * GUARANTEES
 *   LOSSLESS. `enable` removes exactly the length of the marker block from the front
 *   and restores the original name. No other byte is read, written, trimmed,
 *   re-encoded or normalised, so a disable/enable round trip is byte-identical
 *   by construction rather than by hope. Proven, not asserted — see
 *   tests/Standalone/NeutralizeCiGateTest.php.
 *
 *   IDEMPOTENT. `disable` twice is the same tree as `disable` once; `enable`
 *   on a never-disabled tree is a no-op that exits 0.
 *
 *   CONFINED. Only `<DIR>/.github/workflows/*.yml` and `*.yaml` are ever read
 *   or written. The directory is resolved once and every path is built from
 *   that resolved root, so no argument value can steer a write elsewhere.
 *
 *   FAILS LOUD. Anything this tool cannot handle unambiguously — a file that
 *   exists in BOTH states, a `.neutralized` file that carries no marker, a live
 *   `.yml` that carries a marker, an unreadable file, a subdirectory, a
 *   symlink — is an error with a non-zero exit. It is never silently skipped,
 *   because a skip here is a workflow that quietly stayed armed.
 *
 *   NEVER PRINTS CONTENT. Workflow files embed job names, host names and
 *   occasionally values that should not be echoed into a CI log. This tool
 *   reports COUNTS and PATHS only; it never writes a file's bytes to stdout or
 *   stderr, including in its error messages.
 *
 * EXIT CODES
 *   0  done      the tree is in the requested state (including no-ops)
 *   1  refused   an input exists this tool cannot handle unambiguously
 *   2  usage     bad arguments, or the target is not a directory tree with a
 *                `.github/workflows/` directory
 */
const MARKER_LINES = [
    '# ======================================================================',
    '# NEUTRALIZED by bin/neutralize-ci.php — THIS WORKFLOW IS INERT.',
    '#',
    "# This file's name ends in `.neutralized`. GitHub Actions reads only",
    '# workflow files whose names end in `.yml` or `.yaml`, so this file is',
    '# never discovered, never parsed, and never dispatched.',
    '#',
    '# This file IS the original workflow, byte for byte, under a name GitHub',
    '# ignores. There is no second copy anywhere.',
    '#',
    '# REVERSE IT (byte-identical restore):',
    '#     php bin/neutralize-ci.php --mode=enable',
    '#',
    '# Everything up to and including the marker-end line below belongs to the',
    '# tool. Everything after it is the original file, untouched.',
    '# --- neutralize-ci: MARKER END ---',
    '# ===== neutralize-ci: ORIGINAL FILE BYTES BEGIN BELOW THIS LINE =====',
];

// Built at runtime, not with `const`: implode()/strlen() are function calls and
// a const initializer must be a constant expression.
$marker = implode("\n", MARKER_LINES)."\n";
$markerLength = strlen($marker);

const SUFFIX = '.neutralized';
const WORKFLOW_EXTENSIONS = ['.yml', '.yaml'];

$usage = <<<'TXT'
neutralize-ci.php — make a private repository's GitHub Actions inert, reversibly.

USAGE
  php bin/neutralize-ci.php --mode=disable [--dir=DIR]   rename workflows to *.yml.neutralized
  php bin/neutralize-ci.php --mode=enable  [--dir=DIR]   restore them byte-identically

  --dir=DIR   repository to rewrite (default: this script's repository root)

WHY A RENAME. GitHub Actions reads only files under .github/workflows/ whose
names end in .yml or .yaml. A name ending in .neutralized matches neither, so
the file is never opened and never dispatched — inert by filename alone, which
is why nothing is commented out and nothing can be half-undone.

EXIT CODES
  0  done     the tree is in the requested state (no-ops included)
  1  refused  an input this tool cannot handle unambiguously
  2  usage    bad arguments, or the target has no .github/workflows/ directory
TXT;

// ---------------------------------------------------------------------------
// Arguments. Nothing here can steer a write: --dir is resolved to an absolute
// path immediately, and every path below is built from that resolved root.
// ---------------------------------------------------------------------------

$mode = null;
$dir = dirname(__DIR__);

foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--help' || $arg === '-h') {
        echo $usage."\n";
        exit(0);
    }
    if (str_starts_with($arg, '--mode=')) {
        $candidate = substr($arg, 7);
        if ($candidate !== 'disable' && $candidate !== 'enable') {
            fwrite(STDERR, "neutralize-ci: --mode must be `disable` or `enable`, got: {$candidate}\n");
            exit(2);
        }
        $mode = $candidate;

        continue;
    }
    if (str_starts_with($arg, '--dir=')) {
        $candidate = substr($arg, 6);
        if ($candidate === '') {
            fwrite(STDERR, "neutralize-ci: --dir= requires a directory\n");
            exit(2);
        }
        $dir = $candidate;

        continue;
    }
    fwrite(STDERR, "neutralize-ci: unknown argument: {$arg}\n\n{$usage}");
    exit(2);
}

if ($mode === null) {
    fwrite(STDERR, "neutralize-ci: --mode=disable|enable is required\n\n{$usage}");
    exit(2);
}

$root = realpath($dir);
if ($root === false || ! is_dir($root)) {
    fwrite(STDERR, "neutralize-ci: --dir is not a directory: {$dir}\n");
    exit(2);
}

$workflows = $root.'/.github/workflows';
$workflowsReal = realpath($workflows);
if ($workflowsReal === false || ! is_dir($workflowsReal)) {
    // NOT a silent skip. A missing directory is indistinguishable from a typo'd
    // --dir, and "neutralized nothing, exit 0" is exactly the failure this
    // tool exists to prevent.
    fwrite(STDERR, "neutralize-ci: no .github/workflows/ directory under: {$root}\n");
    exit(2);
}
$workflows = $workflowsReal;

// ---------------------------------------------------------------------------
// Enumerate. Only the TOP level of the directory: GitHub does not read
// subdirectories of .github/workflows, so a subdirectory is inert already, but
// it is still an input this tool will not quietly walk past.
// ---------------------------------------------------------------------------

/**
 * @return array<string, string> entry name => absolute path, sorted by name
 */
function scanDirectory(string $directory): array
{
    $entries = @scandir($directory);
    if ($entries === false) {
        fwrite(STDERR, 'neutralize-ci: cannot read directory: '.$directory."\n");
        exit(1);
    }
    sort($entries);

    $files = [];
    $others = [];
    foreach ($entries as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        $full = $directory.'/'.$entry;
        if (is_link($full)) {
            $others[] = $entry.' (symlink)';

            continue;
        }
        if (is_dir($full)) {
            $others[] = $entry.' (directory)';

            continue;
        }
        if (! is_file($full)) {
            $others[] = $entry.' (not a regular file)';

            continue;
        }
        $files[$entry] = $full;
    }

    if ($others !== []) {
        // Reported by name only — never by content.
        fwrite(STDERR, 'neutralize-ci: refusing '.count($others).' entr'.(count($others) === 1 ? 'y' : 'ies')
            .' in .github/workflows/ that are not regular files: '.implode(', ', $others)."\n");
        fwrite(STDERR, "neutralize-ci: each must be removed or moved by hand; this tool will not skip one.\n");
        exit(1);
    }

    return $files;
}

/** @param  array<string, string>  $files  entry name => absolute path */
function workflowFiles(array $files, string $extension): array
{
    $matched = [];
    foreach ($files as $entry => $path) {
        if (str_ends_with($entry, $extension)) {
            $matched[$entry] = $path;
        }
    }

    return $matched;
}

function neutralizedName(string $entry): string
{
    return $entry.SUFFIX;
}

function isNeutralized(string $entry): bool
{
    return str_ends_with($entry, SUFFIX);
}

/** @return array{0: string, 1: string} original entry name, error */
function restoreTargetName(string $entry): array
{
    $base = substr($entry, 0, -strlen(SUFFIX));
    if ($base === '' || ! hasWorkflowExtension($base)) {
        return ['', $entry.' does not end in '.SUFFIX.' after a workflow extension, so this tool '
            .'will not guess which file it restores'];
    }

    return [$base, ''];
}

/**
 * Does this NAME end in `.yml` or `.yaml`?
 *
 * The dot is part of the extension, so the two spellings are compared at their
 * own lengths rather than at a single offset — comparing `.yml` and `.yaml` at
 * one fixed offset silently rejects half of every valid workflow name.
 */
function hasWorkflowExtension(string $name): bool
{
    foreach (WORKFLOW_EXTENSIONS as $extension) {
        if (str_ends_with($name, $extension)) {
            return true;
        }
    }

    return false;
}

function read(string $path): string
{
    $contents = @file_get_contents($path);
    if ($contents === false) {
        fwrite(STDERR, 'neutralize-ci: cannot read: '.$path."\n");
        exit(1);
    }

    return $contents;
}

function writeExact(string $path, string $bytes): void
{
    $written = @file_put_contents($path, $bytes, LOCK_EX);
    if ($written === false || $written !== strlen($bytes)) {
        fwrite(STDERR, 'neutralize-ci: cannot write in full: '.$path."\n");
        exit(1);
    }
}

$files = scanDirectory($workflows);
$changed = 0;
$unchanged = 0;
$acted = [];

if ($mode === 'disable') {
    $workflowsOn = workflowFiles($files, '.yml');
    $workflowsOn += workflowFiles($files, '.yaml');

    $already = [];
    foreach ($files as $entry => $path) {
        if (! isNeutralized($entry)) {
            continue;
        }
        $error = restoreTargetName($entry)[1];
        if ($error !== '') {
            fwrite(STDERR, 'neutralize-ci: refusing to touch '.$entry.": {$error}\n");
            exit(1);
        }
        // A `.neutralized` file WITHOUT the marker is not ours: it may be a real
        // tracked file that merely happens to end in that string. Overwriting it
        // would destroy data, so this is refused rather than repaired.
        if (! str_starts_with(read($path), $marker)) {
            fwrite(STDERR, "neutralize-ci: refusing to touch {$entry}: it carries no neutralize-ci marker, "
                ."so it is not a file this tool disabled\n");
            exit(1);
        }
        $already[] = $entry;
    }

    // A name present in BOTH states is ambiguous: which one is the record?
    $collisions = [];
    foreach ($already as $entry) {
        $original = restoreTargetName($entry)[0];
        if (isset($files[$original])) {
            $collisions[] = $original.' and '.$entry;
        }
    }
    if ($collisions !== []) {
        fwrite(STDERR, 'neutralize-ci: refusing to proceed: name(s) present in BOTH the live and the '
            .'neutralized state: '.implode(', ', $collisions)."\n");
        fwrite(STDERR, "neutralize-ci: resolve by hand, then re-run; this tool will not pick one.\n");
        exit(1);
    }

    foreach ($workflowsOn as $entry => $path) {
        // A live workflow carrying the marker is a half-undone file. Silently
        // stripping it would let the tool "restore" a workflow it never
        // disabled, so it is refused instead.
        if (str_starts_with(read($path), $marker)) {
            fwrite(STDERR, "neutralize-ci: refusing to touch {$entry}: a LIVE workflow carries the "
                ."neutralize-ci marker, which is a half-undone state\n");
            exit(1);
        }

        writeExact($workflows.'/'.neutralizedName($entry), $marker.read($path));
        if (! @unlink($path)) {
            fwrite(STDERR, "neutralize-ci: could not remove the live file {$entry}; the tree is now "
                ."half-disabled and must be fixed by hand\n");
            exit(1);
        }
        $changed++;
        $acted[] = $entry.' -> '.neutralizedName($entry);
    }

    printf(
        "neutralize-ci: disabled %d workflow(s), %d already disabled, %d untouched\n",
        $changed,
        count($already),
        count($files) - $changed - count($already),
    );
    foreach ($acted as $line) {
        echo '  '.$line."\n";
    }
    exit(0);
}

// --mode=enable
$disabled = [];
foreach ($files as $entry => $path) {
    if (isNeutralized($entry)) {
        $error = restoreTargetName($entry)[1];
        if ($error !== '') {
            fwrite(STDERR, 'neutralize-ci: refusing to touch '.$entry.": {$error}\n");
            exit(1);
        }
        if (! str_starts_with(read($path), $marker)) {
            fwrite(STDERR, "neutralize-ci: refusing to touch {$entry}: it carries no neutralize-ci marker, "
                ."so it is not a file this tool disabled\n");
            exit(1);
        }
        $disabled[$entry] = $path;

        continue;
    }

    // Symmetric refusal to the disable-side half-undo check: a live file that
    // carries the marker means someone undid the rename by hand and left the
    // prefix behind.
    if (hasWorkflowExtension($entry) && str_starts_with(read($path), $marker)) {
        fwrite(STDERR, "neutralize-ci: refusing to touch {$entry}: a live workflow carries the "
            ."neutralize-ci marker, which is a half-undone state\n");
        exit(1);
    }
}

$collisions = [];
foreach ($disabled as $entry => $path) {
    $original = restoreTargetName($entry)[0];
    if (isset($files[$original])) {
        $collisions[] = $original.' and '.$entry;
    }
}
if ($collisions !== []) {
    fwrite(STDERR, 'neutralize-ci: refusing to proceed: name(s) present in BOTH the live and the '
        .'neutralized state: '.implode(', ', $collisions)."\n");
    fwrite(STDERR, "neutralize-ci: resolve by hand, then re-run; this tool will not pick one.\n");
    exit(1);
}

foreach ($disabled as $entry => $path) {
    $original = restoreTargetName($entry)[0];
    // The whole of losslessness: drop exactly the marker, write the rest.
    writeExact($workflows.'/'.$original, substr(read($path), $markerLength));
    if (! @unlink($path)) {
        fwrite(STDERR, "neutralize-ci: could not remove the neutralized file {$entry}; the tree is "
            ."half-enabled and must be fixed by hand\n");
        exit(1);
    }
    $changed++;
    $acted[] = $entry.' -> '.$original;
}

printf(
    "neutralize-ci: restored %d workflow(s), %d untouched\n",
    $changed,
    count($files) - $changed,
);
foreach ($acted as $line) {
    echo '  '.$line."\n";
}
exit(0);

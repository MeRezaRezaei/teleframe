#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * verify-public-pr.php — the MECHANICAL gate a pull request to the PUBLIC
 * repository must pass BEFORE any AI is asked for a judgement.
 *
 * THE OPERATOR'S REASON THIS FILE EXISTS, verbatim:
 *   "i cant review ai work and accpet it we need an agent to accept that and
 *    giving it the power to do so or rejcet with reason its a porsigre thing
 *    and both of its end is obiovus does not need me"
 *
 * The public repository MeRezaRezaei/teleframe has branch protection requiring
 * 1 approving review. The GitLab `publish-public` job opens the PR and then
 * waits forever for a human who will never read it. So the review has to be
 * made by a machine. This file is the part of that machine that must NOT be a
 * machine's judgement, because it is not a judgement at all — it is arithmetic.
 * The AI's turn comes afterwards and only when this file says the tree is
 * mechanically sound.
 *
 * THE DIVISION OF LABOUR IS THE WHOLE DESIGN:
 *
 *   This file decides things that are TRUE or NOT TRUE, and a wrong answer is
 *   always detectable and always reproducible:
 *     - does the PR head's exported tree contain zero paths outside
 *       bin/public-manifest.txt?
 *     - does it contain zero paths from the D-035 strip set (.brain/, .ledger/,
 *       .opencode-ci/, docs/decisions*, …)?
 *     - does bin/scan-credentials.php find zero session strings in it?
 *     - did every required CI check on the PR finish successfully?
 *
 *   The opencode agent decides the one thing arithmetic cannot: whether a tree
 *   that passes all of the above is actually SAFE and UNDERSTOOD to publish.
 *
 * A mechanical failure therefore needs no model call. The workflow stops at
 * `VERDICT: BLOCKED` and requests changes with the failing lines as the reason.
 * Spending quota — and, worse, letting a model talk — about a tree that contains
 * `.brain/` is the failure mode this split exists to make impossible.
 *
 * IT FAILS CLOSED, EVERYWHERE. A missing tree, an unreadable manifest, an
 * unparseable check rollup, a pending check, a check whose conclusion this file
 * has never heard of — all of them are FAIL. The one thing that must never
 * happen here is an unknown input quietly reading as approval.
 *
 * EXIT CODES (also printed by --help):
 *   0  CLEAN    — every check passed; the AI may now be asked for a judgement
 *   1  BLOCKED  — at least one check failed; do NOT ask the model, do not approve
 *   2  usage error — bad arguments, missing script, unreadable checks file
 *
 * USAGE:
 *   php bin/verify-public-pr.php [--tree=DIR] [--checks=FILE] [--required=A,B,C]
 *                                [--quiet] [--help]
 *
 *   --tree=DIR       The tree to gate. Defaults to the repository this script
 *                    lives in — which on the GitHub runner IS the PR head,
 *                    because the workflow checks out the head sha explicitly.
 *   --checks=FILE    A JSON file holding the output of
 *                    `gh pr view --json statusCheckRollup`. This exists so the
 *                    whole script is exercisable with no GitHub and no network;
 *                    see the CLEAN/BLOCKED transcript in docs/decisions.md.
 *                    Without it the script runs `gh` itself.
 *   --required=A,B   Check names that must be PRESENT as well as successful. A
 *                    required check that never ran is a failure, not an absence:
 *                    a renamed or deleted gate would otherwise read as "all the
 *                    gates that exist are green".
 *   --quiet          Print only the CHECK lines and the VERDICT line.
 *
 * ZERO REGEX, ON PURPOSE. The repository's zero-regex rule is enforced over
 * src/Teleframe/** and src/Core/**; bin/ is exempt. This file still uses none,
 * because every match it needs is `str_starts_with` / `str_contains` / exact
 * string compare, and a second dialect to review is a second dialect to get
 * wrong.
 */
$root = dirname(__DIR__);
$scriptDir = $root.'/bin';

/** @var list<array{0:string,1:string,2:string}> $results */
$results = [];

// ---------------------------------------------------------------------------
// Arguments.
// ---------------------------------------------------------------------------

$treeDir = $root;
$checksFile = null;
$requiredNames = [];
$quiet = false;

$usage = <<<'TXT'
verify-public-pr.php — the mechanical gate before an AI review on a public PR.

  php bin/verify-public-pr.php [--tree=DIR] [--checks=FILE] [--required=A,B,C] [--quiet]

  --tree=DIR       tree to gate (default: the repository containing this script)
  --checks=FILE    JSON from `gh pr view --json statusCheckRollup`; without it gh is run
  --required=A,B   check names that must be PRESENT as well as successful
  --quiet          print only the CHECK lines and the VERDICT

  exit 0 = CLEAN   1 = BLOCKED   2 = usage error
TXT;

foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--help' || $arg === '-h') {
        echo $usage, "\n";
        exit(0);
    }
    if ($arg === '--quiet') {
        $quiet = true;

        continue;
    }
    if (str_starts_with($arg, '--tree=')) {
        $treeDir = rtrim(substr($arg, 7), '/');
        if ($treeDir === '') {
            fwrite(STDERR, "verify-public-pr: --tree must not be empty\n");
            exit(2);
        }

        continue;
    }
    if (str_starts_with($arg, '--checks=')) {
        $checksFile = substr($arg, 9);
        if ($checksFile === '') {
            fwrite(STDERR, "verify-public-pr: --checks must not be empty\n");
            exit(2);
        }

        continue;
    }
    if (str_starts_with($arg, '--required=')) {
        $requiredNames = array_values(array_filter(
            array_map('trim', explode(',', substr($arg, 11))),
            static fn (string $n): bool => $n !== ''
        ));

        continue;
    }
    fwrite(STDERR, "verify-public-pr: unknown argument: {$arg}\n\n{$usage}\n");
    exit(2);
}

if (! is_dir($treeDir)) {
    fwrite(STDERR, "verify-public-pr: --tree is not a directory: {$treeDir}\n");
    exit(2);
}

// VALIDATED HERE, NOT WHERE IT IS USED. A bad --checks path is a usage error,
// and a usage error must not cost a 600s credential scan and a 5086-file walk
// before it is discovered — especially in CI, where that is runner minutes
// spent discovering a typo.
if ($checksFile !== null && ! is_readable($checksFile)) {
    fwrite(STDERR, "verify-public-pr: --checks file is missing or unreadable: {$checksFile}\n");
    exit(2);
}

// ---------------------------------------------------------------------------
// Running a child command, bounded.
//
// A `timeout` is used when the binary exists, so a hung git or a hung credential
// scan becomes a named failure (exit 124) instead of a wedged CI job. When the
// binary is absent the command still runs: a missing `timeout` must not turn
// into a missing gate, and the caller reports the child's own exit code either
// way.
// ---------------------------------------------------------------------------

/**
 * @param  list<string>  $cmd
 * @return array{code:int,out:string,err:string,timedOut:bool}
 */
function runBounded(array $cmd, int $seconds, ?string $cwd = null): array
{
    $full = $cmd;
    $haveTimeout = is_executable('/usr/bin/timeout') || is_executable('/bin/timeout');
    if ($haveTimeout) {
        array_unshift($full, 'timeout', '-k', '5', (string) $seconds);
    }

    $descriptors = [0 => ['file', '/dev/null', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
    $proc = @proc_open($full, $descriptors, $pipes, $cwd);
    if (! is_resource($proc)) {
        return ['code' => 127, 'out' => '', 'err' => 'cannot execute: '.($cmd[0] ?? '?'), 'timedOut' => false];
    }

    $out = stream_get_contents($pipes[1]);
    $err = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $code = proc_close($proc);

    return [
        'code' => $code,
        'out' => (string) $out,
        'err' => (string) $err,
        'timedOut' => $code === 124,
    ];
}

/** Collapse a child's multi-line output into a bounded, pasteable detail block. */
function detailLines(string $text, int $max = 12): array
{
    $lines = [];
    foreach (explode("\n", $text) as $line) {
        $line = rtrim($line);
        if ($line === '' || $line === '.' || str_starts_with($line, '===')) {
            continue;
        }
        $lines[] = $line;
        if (count($lines) >= $max) {
            break;
        }
    }

    return $lines;
}

/** First non-empty trimmed line of a child's output — used as a one-line reason. */
function firstLine(string $text): string
{
    foreach (explode("\n", $text) as $line) {
        $line = trim($line);
        if ($line !== '') {
            return $line;
        }
    }

    return '(no output)';
}

function record(string $name, bool $passed, string $reason, array $details = []): void
{
    global $results, $quiet;
    $results[] = ['name' => $name, 'passed' => $passed, 'reason' => $reason, 'details' => $details];

    printf("CHECK  %-16s %s  %s\n", $name, $passed ? 'PASS' : 'FAIL', $reason);
    if (! $passed && ! $quiet) {
        foreach ($details as $line) {
            echo '        ', $line, "\n";
        }
    }
}

// ---------------------------------------------------------------------------
// CHECK 1 — public surface: zero paths outside the manifest, zero strip-set
// paths.
//
// The gate itself is bin/check-public-surface.php, run in --tree mode. This
// file does NOT reimplement it: two implementations of the strip set would
// drift, and the drift would be invisible because both would still pass. Its
// exit code already means both counts are zero — a strip-set hit is counted in
// the same violation total — so PASS here means "0 outside the manifest AND 0
// strip-set paths", which is what the reason line says out loud.
// ---------------------------------------------------------------------------

$surfaceScript = $scriptDir.'/check-public-surface.php';
if (! is_file($surfaceScript)) {
    fwrite(STDERR, "verify-public-pr: the gate is missing: {$surfaceScript}\n");
    exit(2);
}

$surface = runBounded(['php', $surfaceScript, '--tree='.$treeDir], 300, $root);
$surfaceOk = $surface['code'] === 0;
$surfaceReason = $surfaceOk
    ? firstLine($surface['out'])
    : 'public surface gate exited '.$surface['code'].' on '.$treeDir;

record(
    'public-surface',
    $surfaceOk,
    $surfaceReason,
    $surfaceOk ? [] : array_merge(detailLines($surface['out']), detailLines($surface['err']))
);

// ---------------------------------------------------------------------------
// CHECK 2 — credentials: bin/scan-credentials.php exits 0 on this tree.
//
// Exit 3 is treated as FAILURE, not as a pass with a caveat. Exit 3 means the
// scan did not reach what it was supposed to scan — a truncated clone, a
// missing history — and an incomplete scan is not evidence of a clean tree. In
// the GitHub runner the tree is a full-depth checkout precisely so this scan
// has something complete to read.
// ---------------------------------------------------------------------------

$credScript = $scriptDir.'/scan-credentials.php';
if (! is_file($credScript)) {
    fwrite(STDERR, "verify-public-pr: the credential guard is missing: {$credScript}\n");
    exit(2);
}

$cred = runBounded(['php', $credScript, '--root='.$treeDir], 600, $root);
$credOk = $cred['code'] === 0;
$credReason = $credOk
    ? 'no session string in the tree or its history'
    : ($cred['timedOut']
        ? 'scan-credentials.php did not finish inside 600s (incomplete, so not clean)'
        : 'credential guard exited '.$cred['code'].': '.firstLine($cred['out'].$cred['err']));

record(
    'credentials',
    $credOk,
    $credReason,
    $credOk ? [] : array_merge(detailLines($cred['out']), detailLines($cred['err']))
);

// ---------------------------------------------------------------------------
// CHECK 3 — CI checks on the PR are all successful.
//
// Source is `gh pr view --json statusCheckRollup`, or the same JSON read from a
// file so this is testable with no network. Two distinct failure modes are
// counted separately because they have different causes:
//   - NOT GREEN: a check concluded failure/timed_out/cancelled/… .
//   - REQUIRED MISSING: a name passed to --required is not in the rollup at
//     all, which usually means the gate was renamed or deleted rather than that
//     the code passed.
//
// UNRECOGNISED CONCLUSIONS FAIL. A rollup shape this file has never seen is not
// evidence of success; the whole value of this gate is that it is boringly
// literal about what green means.
// ---------------------------------------------------------------------------

$rollupRaw = null;
$checksSource = '';
if ($checksFile !== null) {
    $rollupRaw = file_get_contents($checksFile);
    if ($rollupRaw === false) {
        fwrite(STDERR, "verify-public-pr: --checks file became unreadable: {$checksFile}\n");
        exit(2);
    }
    $checksSource = $checksFile;
} else {
    $gh = runBounded(
        ['gh', 'pr', 'view', '--json', 'statusCheckRollup'],
        120,
        $root
    );
    if ($gh['code'] !== 0) {
        $err = trim($gh['err']) !== '' ? firstLine($gh['err']) : ('exit '.$gh['code']);
        record('ci-checks', false, 'could not read the PR check rollup: '.$err);
        $rollupRaw = null;
    } else {
        $rollupRaw = $gh['out'];
        $checksSource = 'gh pr view --json statusCheckRollup';
    }
}

if ($rollupRaw !== null) {
    $decoded = json_decode($rollupRaw, true);
    if (! is_array($decoded)) {
        record('ci-checks', false, 'the check rollup is not valid JSON ('.($checksSource !== '' ? $checksSource : 'gh').')');
    } else {
        // Accept all three shapes so a hand-written fixture is as good as gh's
        // real output: the bare array, {"statusCheckRollup":[...]}, or the whole
        // `gh pr view` document.
        if (isset($decoded['statusCheckRollup']) && is_array($decoded['statusCheckRollup'])) {
            $entries = $decoded['statusCheckRollup'];
        } elseif (array_is_list($decoded)) {
            $entries = $decoded;
        } else {
            $entries = [];
        }

        $notGreen = [];
        $seen = [];
        foreach ($entries as $entry) {
            if (! is_array($entry)) {
                $notGreen[] = '(malformed rollup entry)';

                continue;
            }
            $name = '';
            foreach (['name', 'context', 'workflowName'] as $key) {
                if (isset($entry[$key]) && is_string($entry[$key]) && $entry[$key] !== '') {
                    $name = $entry[$key];

                    break;
                }
            }
            if ($name === '') {
                $name = '(unnamed check)';
            }

            // StatusContext reports `state`; CheckRun reports `conclusion` plus
            // `status`. Whichever is present, lowercased, decides.
            $conclusion = '';
            foreach (['conclusion', 'state'] as $key) {
                if (isset($entry[$key]) && is_string($entry[$key]) && $entry[$key] !== '') {
                    $conclusion = strtolower($entry[$key]);

                    break;
                }
            }
            $status = isset($entry['status']) && is_string($entry['status'])
                ? strtolower($entry['status'])
                : '';

            $green = ['success', 'neutral', 'skipped'];
            if ($status !== '' && ! in_array($status, ['completed'], true)) {
                $notGreen[] = $name.' is '.$status.' (not finished)';

                continue;
            }
            if (in_array($conclusion, $green, true)) {
                $seen[] = $name;

                continue;
            }
            $notGreen[] = $conclusion === ''
                ? $name.' reported no conclusion'
                : $name.' concluded '.$conclusion;
        }

        $missing = [];
        foreach ($requiredNames as $want) {
            $found = false;
            foreach ($seen as $got) {
                if ($got === $want || str_contains($got, $want)) {
                    $found = true;

                    break;
                }
            }
            // A required check that ran and failed is already in $notGreen; it
            // is not also reported as missing, because "renamed" and "red" are
            // different diagnoses.
            if (! $found) {
                $stillRunning = false;
                foreach ($notGreen as $line) {
                    if (str_contains($line, $want)) {
                        $stillRunning = true;

                        break;
                    }
                }
                if (! $stillRunning) {
                    $missing[] = $want;
                }
            }
        }

        $problems = array_merge($notGreen, $missing);
        $details = [];
        foreach ($notGreen as $line) {
            $details[] = 'not green: '.$line;
        }
        foreach ($missing as $line) {
            $details[] = 'required check never ran: '.$line;
        }

        if ($problems !== []) {
            record('ci-checks', false, count($problems).' check problem(s) on this PR', $details);
        } else {
            record(
                'ci-checks',
                true,
                count($entries).' check(s) green'
                    .($requiredNames === [] ? '' : ', all '.count($requiredNames).' required check(s) present')
            );
        }
    }
}

// ---------------------------------------------------------------------------
// Verdict.
//
// One line, and the only line the workflow branches on. BLOCKED exits 1 so a
// plain `php bin/verify-public-pr.php` in any CI step fails too — the workflow
// additionally reads the line, because it must distinguish BLOCKED from the
// model saying REJECT.
// ---------------------------------------------------------------------------

$blocked = false;
foreach ($results as $r) {
    if (! $r['passed']) {
        $blocked = true;

        break;
    }
}

if (! $quiet && $results === []) {
    echo "verify-public-pr: no check ran — refusing to report a verdict\n";
    exit(2);
}

echo $blocked ? "VERDICT: BLOCKED\n" : "VERDICT: CLEAN\n";
exit($blocked ? 1 : 0);

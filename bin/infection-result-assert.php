#!/usr/bin/env php
<?php

/**
 * ASSERT ON THE RESULT OF A MUTATION RUN, NOT ONLY ON ITS EXIT CODE.
 *
 * WHY THIS EXISTS (2026-10-18)
 *
 * `composer mutants:gate` checks exactly one thing: Covered MSI against a ratchet. That
 * is necessary and it is not sufficient, because two failure modes were MEASURED this
 * cycle that a passing MSI cannot see:
 *
 *   1. SKIPPED MUTANTS. A scope can quietly stop running most of its mutants and still
 *      report a healthy MSI, because MSI is computed over the mutants Infection ACTUALLY
 *      RAN. Measured across four directories:
 *
 *          src/Core/Entities      454 total,   0 skipped → MSI describes 100% of source
 *          src/Teleframe/Bus      466 total, 172 skipped → MSI describes 63%
 *          src/Schema generators 1963 total,1633 skipped → MSI describes 17%
 *
 *      The console line prints "Escaped: N" and says nothing about the other 1,633. So
 *      a scope can go from measuring its whole source to measuring a sixth of it, and
 *      the number in CI can still be green.
 *
 *   2. DISAGREEING RUNS. On an unchanged scope, three runs gave 299 / 234 / 231 killed
 *      — a 68-mutant swing, 23% of the measured population — caused by an exhausted
 *      tmpfs making test subprocesses die, which Infection books as KILLS. A single
 *      run cannot detect this; comparing against a committed baseline can.
 *
 * `bin/infection-preflight.php` covers the INPUTS. This covers the OUTPUT.
 *
 * WHAT IT ASSERTS
 *
 *   - accounting closes:      Killed + Escaped + TimedOut + Skipped + NotCovered == Total
 *   - nothing errored:        Errored == 0 and Syntax Errors == 0
 *   - the run is not hollow:  Skipped must stay under a share of Total
 *   - nothing regressed:      Killed must not fall below a committed baseline
 *
 * The last two are the ones that would have caught the measured failures. Both are
 * thresholds rather than absolutes, because the point is to make a large silent change
 * FAIL, not to make ordinary movement fail — the score is known to drift by ±3 with
 * machine load (measured, 46.60), and a gate that cries wolf gets switched off.
 *
 * @return int 0 = all assertions hold, 1 = at least one does not
 */
$summaryPath = $argv[1] ?? __DIR__.'/../infection-summary.json';

if (! is_file($summaryPath)) {
    fwrite(STDERR, "  MUTATION RESULT REFUSED — no summary at {$summaryPath}\n");
    fwrite(STDERR, "  A run that produced no artifact is not a result.\n");

    exit(1);
}

$raw = (string) file_get_contents($summaryPath);

// The summary is a small `Key: value` text file, not JSON. Parsed with strtok so there
// is no dependency on a JSON extension the format does not use.
$counts = [];
foreach (explode("\n", $raw) as $line) {
    $line = trim($line);
    if ($line === '') {
        continue;
    }
    $pos = strpos($line, ':');
    if ($pos === false) {
        continue;
    }
    $key = trim(substr($line, 0, $pos));
    $value = trim(substr($line, $pos + 1));
    if (strspn($value, '0123456789') === strlen($value)) {
        $counts[$key] = (int) $value;
    }
}

// ── the keys this assertion needs must all be present ───────────────────────────
$required = [
    'Total', 'Killed by Test Framework', 'Killed by Static Analysis', 'Errored',
    'Syntax Errors', 'Escaped', 'Timed Out', 'Skipped', 'Ignored', 'Not Covered',
];

$missing = [];
foreach ($required as $key) {
    if (! array_key_exists($key, $counts)) {
        $missing[] = $key;
    }
}
if ($missing !== []) {
    fwrite(STDERR, "  MUTATION RESULT REFUSED — summary is missing keys:\n");
    foreach ($missing as $m) {
        fwrite(STDERR, "    x {$m}\n");
    }
    fwrite(STDERR, "  A partial summary cannot be asserted on.\n\n");

    exit(1);
}

$total = $counts['Total'];
$killed = $counts['Killed by Test Framework'] + $counts['Killed by Static Analysis'];
$escaped = $counts['Escaped'];
$timedOut = $counts['Timed Out'];
$skipped = $counts['Skipped'];
$errored = $counts['Errored'];
$syntaxEr = $counts['Syntax Errors'];
$notCov = $counts['Not Covered'];

$failures = [];

// ── 1. accounting closes ─────────────────────────────────────────────────────────
// Every mutant must land in exactly one bucket. A summary that does not add up means a
// status was introduced that this gate has never seen, and silently passing it would
// mean the assertion set is out of date rather than that the code is fine.
$accounted = $killed + $escaped + $timedOut + $skipped + $notCov;
if ($accounted !== $total) {
    $failures[] = sprintf(
        'accounting does not close: killed %d + escaped %d + timedOut %d + skipped %d + notCovered %d = %d, but Total is %d',
        $killed,
        $escaped,
        $timedOut,
        $skipped,
        $notCov,
        $accounted,
        $total,
    );
}

// ── 2. nothing errored ──────────────────────────────────────────────────────────
if ($errored > 0) {
    $failures[] = "{$errored} mutants ERRORED — a mutant that errors is neither a kill nor a miss, and hides real state";
}
if ($syntaxEr > 0) {
    $failures[] = "{$syntaxEr} SYNTAX ERRORS — the mutated source did not parse, so the run measured nothing useful";
}

// ── 3. the run must not be hollow ───────────────────────────────────────────────
// The measured failure: src/Schema measured 1,963 mutants and RAN 330 of them, and the
// MSI still read 71%. A scope that stops covering its source is a silent regression.
$maxSkipShare = (float) (getenv('TELEFRAME_INFECTION_MAX_SKIPPED_SHARE') ?: '0.05');
if ($total > 0 && $skipped > 0) {
    $share = $skipped / $total;
    if ($share > $maxSkipShare) {
        $failures[] = sprintf(
            'SKIPPED %d of %d mutants (%.1f%%) — above the %.1f%% limit. The MSI describes only %d of %d mutants, so a green number here describes a subset. Measured in this repo: src/Schema skipped 1633 of 1963 and still read 71%%.',
            $skipped,
            $total,
            $share * 100,
            $maxSkipShare * 100,
            $total - $skipped,
            $total,
        );
    }
}

// ── 4. nothing regressed against a committed baseline ──────────────────────────
// Not a gate on the MSI — the MSI ratchet already exists. This catches the OTHER
// direction: a run that kills far fewer mutants than last time, which is what a
// resource-starved subprocess looks like (299 → 234 → 231 on unchanged code).
// The baseline is SCOPED, and the scope must be named rather than inferred.
//
// The first version compared `basename(dirname($summaryPath))` against the baseline's
// `scope` field. Those are different things — a summary path's directory is
// ".scratch/sum", a scope label is "entity" — so the comparison could never be true and
// the regression check SILENTLY NEVER RAN. It was caught only because the fixture I fed
// it was built to fail; without that fixture a dead check ships as a passing gate.
//
// So the scope is a REQUIRED argument, and its absence is a FAILURE rather than a
// reason to skip. A check that cannot distinguish "no baseline yet" from "not
// applicable" will eventually be trusted to mean "checked and fine".
$baselinePath = __DIR__.'/../infection-baseline.json';
$minKillShare = (float) (getenv('TELEFRAME_INFECTION_MIN_KILL_SHARE') ?: '0.90');

$scope = $argv[2] ?? (getenv('TELEFRAME_INFECTION_SCOPE') ?: '');

if ($scope === '') {
    $failures[] = 'no scope supplied (pass argv[2] or set TELEFRAME_INFECTION_SCOPE) — without it the committed baseline cannot be matched and the kill-count floor is not enforced';
} elseif (! is_file($baselinePath)) {
    $failures[] = "no baseline at {$baselinePath} for scope \"{$scope}\" — the kill-count floor is not enforced";
} else {
    $baselines = json_decode((string) file_get_contents($baselinePath), true);

    if (! is_array($baselines) || ! isset($baselines['scopes'])) {
        $failures[] = "baseline at {$baselinePath} is malformed — it needs a 'scopes' object";
    } elseif (! isset($baselines['scopes'][$scope])) {
        $failures[] = "no baseline for scope \"{$scope}\" in {$baselinePath} — the kill-count floor is not enforced";
    } else {
        $baseline = $baselines['scopes'][$scope];
        if (! isset($baseline['killed'], $baseline['total'])) {
            $failures[] = "baseline for scope \"{$scope}\" is malformed — it needs total and killed";
        } elseif ((int) $baseline['total'] === $total) {
            $baselineKilled = (int) $baseline['killed'];
            if ($baselineKilled > 0) {
                $floor = (int) floor($baselineKilled * $minKillShare);
                if ($killed < $floor) {
                    $failures[] = sprintf(
                        'KILLED %d against a baseline of %d on the SAME %d-mutant scope — under the %.0f%% floor of %d. A drop like that is the signature of a run whose subprocesses died (see bin/infection-preflight.php), not of weaker tests. Measured here: 299 / 234 / 231 on unchanged code.',
                        $killed,
                        $baselineKilled,
                        $total,
                        $minKillShare * 100,
                        $floor,
                    );
                }
            }
        }
        // A different Total means the scope itself changed. That is a deliberate decision,
        // recorded by regenerating the baseline — not something to fail on here.
    }
}

$measured = $total - $skipped;

if ($failures !== []) {
    fwrite(STDERR, "\n  MUTATION RESULT REFUSED — the MSI ratchet passed, but the result does not hold\n\n");
    foreach ($failures as $f) {
        fwrite(STDERR, "    x {$f}\n");
    }
    fwrite(STDERR, sprintf(
        "\n  Total %d · killed %d · escaped %d · timedOut %d · skipped %d · measured %d\n\n",
        $total,
        $killed,
        $escaped,
        $timedOut,
        $skipped,
        $measured,
    ));

    exit(1);
}

printf(
    "  result holds: total %d · killed %d · escaped %d · timedOut %d · skipped %d · MSI measured over %d mutants (%.1f%% of the scope)\n\n",
    $total,
    $killed,
    $escaped,
    $timedOut,
    $skipped,
    $measured,
    $total > 0 ? ($measured / $total) * 100 : 0.0,
);

exit(0);

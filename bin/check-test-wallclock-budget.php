<?php

/**
 * TEST WALL-CLOCK BUDGET GATE.
 *
 * WHY
 *
 * The operator's complaint, verbatim: "tests taking much time doing repeated things
 * is not tests that lets progress advancees since the first reason of tests was
 * fast determination of any side effect of changes and now we stuck in time".
 *
 * The measurement behind this gate is in `ci/shard-timings-raw.tsv`, and it is
 * stark: seven areas (Ingest 2201s, Schema 1759s, Console 689s, Mirror 440s,
 * Bus 364s, Handler 337s, Laravel 202s) are ~83% of all wall time, while
 * TWENTY-EIGHT areas together are under 4%. A 2.4h serial suite is not a
 * feedback loop.
 *
 * The dominant cause was measured, not guessed: the migration dial (61 files in
 * src/Laravel/Migrations) is re-run by `migrateIngestSurface()` on EVERY test
 * METHOD, so it executes on the order of 21,000 times in one area.
 * `vendor/bin/phpunit tests/Ingest/EntityAggregatorTest.php` — FIVE trivial
 * tests — took 27.1 seconds. For comparison `tests/Core/EntityParserTest.php`
 * runs 28 tests in 0.057s. That ~2700x difference is the dial.
 *
 * WHY A GATE AND NOT A DOCUMENT
 *
 * This repo has direct evidence that written standards decay: the rtrim rule
 * (D6) sat in `.brain/evidence/2026-10-17-research-content-media.md` from round
 * one and was never implemented, and `AGENTS.md` had to be amended by hand to
 * forbid running `tests/Ingest` as a directory. A number nobody checks is not a
 * standard.
 *
 * DESIGN — why it is not a "run the suite and time it" gate
 *
 * Timing the suite inside this gate is exactly the mistake the standard exists
 * to prevent: it would spend 2.4 hours to produce a number, which is the very
 * cost we are removing, and `AGENTS.md` forbids running the full suite locally.
 * So this gate does NOT execute tests. It enforces the BUDGETS as declared
 * data, and their provenance:
 *
 *   - every area in tests/ must have a budget
 *   - a budget must declare whether it is MEASURED or ASSUMED, and carry the
 *     measurement date when measured
 *   - an ASSUMED budget that has never been replaced by a measurement FAILS —
 *     because an unmeasured budget is not a budget
 *
 * That is the same rule `bin/check-identity-invariants.php` uses: a gate that
 * cannot check is not a passing gate.
 *
 * The actual wall-clock enforcement stays where it belongs — in CI, once, on
 * the shard runner that already exists (`bin/run-phpunit-shard.php`).
 *
 * EXIT CODES
 *
 *   0  every area is budgeted, and no unmeasured budget remains
 *   1  a budget is missing, or an ASSUMED budget has never been measured
 *   2  usage error
 *   3  INCOMPLETE — the budget file or the tests tree could not be read
 */

declare(strict_types=1);

$root = dirname(__DIR__);
$budgetFile = $root.'/ci/shard-budgets.json';

/** @var list<string> $problems */
$problems = [];

/** @var list<string> $incomplete */
$incomplete = [];

if (! is_file($budgetFile)) {
    fwrite(STDERR, sprintf("usage: no budget file at %s\n", $budgetFile));

    exit(3);
}

/** @var mixed $raw */
$raw = json_decode((string) file_get_contents($budgetFile), true);
if (! is_array($raw)) {
    fwrite(STDERR, sprintf("INCOMPLETE: %s is not valid JSON: %s\n", $budgetFile, json_last_error_msg()));

    exit(3);
}

$areas = glob($root.'/tests/*', GLOB_ONLYDIR);
if ($areas === false) {
    fwrite(STDERR, "INCOMPLETE: could not enumerate tests/\n");

    exit(3);
}
$onDisk = array_map(static fn (string $p): string => basename($p), $areas);
sort($onDisk);

$budgets = $raw['areas'] ?? null;
if (! is_array($budgets)) {
    fwrite(STDERR, "INCOMPLETE: budget file has no \"areas\" object\n");

    exit(3);
}

$declared = array_keys($budgets);
sort($declared);

// ---------------------------------------------------------------------------
// Every area on disk must have a budget. A budget for an area that no longer
// exists is drift in the other direction and is reported too.
// ---------------------------------------------------------------------------

foreach ($onDisk as $area) {
    if (! array_key_exists($area, $budgets)) {
        $problems[] = sprintf(
            'area "%s" has NO budget — a suite that is not budgeted is a suite nobody decided was acceptable',
            $area,
        );
    }
}

foreach ($declared as $area) {
    if (! in_array($area, $onDisk, true)) {
        $problems[] = sprintf('budget declared for "%s" but tests/%s does not exist', $area, $area);
    }
}

// ---------------------------------------------------------------------------
// Each budget must be well-formed, and an ASSUMED budget must have been
// replaced by a real measurement.
// ---------------------------------------------------------------------------

$measured = 0;
$assumedUnmeasured = 0;

foreach ($budgets as $area => $budget) {
    if (! is_array($budget)) {
        $problems[] = sprintf('budget for "%s" is not an object', (string) $area);

        continue;
    }

    $name = (string) $area;

    $kind = $budget['kind'] ?? null;
    $VALID = ['measured', 'assumed', 'operator-gated', 'no-suite'];
    if (! in_array($kind, $VALID, true)) {
        $problems[] = sprintf(
            'budget for "%s" needs "kind" to be one of %s (got %s)',
            $name,
            implode(' / ', $VALID),
            var_export($kind, true),
        );

        continue;
    }

    // `no-suite` is only honest if the directory really has no tests. Declaring
    // it for a directory that HAS tests would be the cheapest way to disable
    // this gate, so it is verified rather than trusted.
    if ($kind === 'no-suite') {
        $hasTests = false;
        foreach (glob($root.'/tests/'.$name.'/*.php') ?: [] as $file) {
            if (str_contains((string) file_get_contents($file), 'function test')) {
                $hasTests = true;

                break;
            }
        }
        if ($hasTests) {
            $problems[] = sprintf(
                'budget for "%s" declares kind=no-suite but tests/%s CONTAINS TESTS — the '
                .'cheapest possible way to disable this gate is refused',
                $name,
                $name,
            );
        }

        continue;
    }

    if ($kind === 'operator-gated') {
        $blockedBy = $budget['blocked_by'] ?? null;
        if (! is_string($blockedBy) || trim($blockedBy) === '') {
            $problems[] = sprintf(
                'budget for "%s" is operator-gated but does not name "blocked_by" — an '
                .'unexplained permanent exemption is how a gate dies quietly',
                $name,
            );
        }

        continue;
    }

    $source = $budget['source'] ?? null;
    if (! is_string($source) || trim($source) === '') {
        $problems[] = sprintf('budget for "%s" needs a "source" naming where the number came from', $name);
    }

    if ($kind === 'measured') {
        $measured++;
        $at = $budget['measured_at'] ?? null;
        if (! is_string($at) || trim($at) === '') {
            $problems[] = sprintf(
                'budget for "%s" is measured but carries no "measured_at" date — an undated '
                .'measurement decays silently, which is the failure this gate exists to stop',
                $name,
            );
        }
    } else {
        $assumedUnmeasured++;
        $problems[] = sprintf(
            'budget for "%s" is ASSUMED and has never been measured. An unmeasured budget is '
            .'not a budget: measure the area once and record seconds + kind=measured + measured_at.',
            $name,
        );
    }
}

// ---------------------------------------------------------------------------
// Report
// ---------------------------------------------------------------------------

printf("test wall-clock budget gate\n");
printf("  areas on disk: %d   budgets declared: %d   measured: %d   unmeasured: %d\n\n", count($onDisk), count($declared), $measured, $assumedUnmeasured);

foreach ($problems as $problem) {
    printf("  FAIL  %s\n", $problem);
}

foreach ($incomplete as $note) {
    printf("  INCOMPLETE  %s\n", $note);
}

echo "\n";

if ($incomplete !== []) {
    echo "RESULT: INCOMPLETE — this run proves nothing.\n";

    exit(3);
}

if ($problems !== []) {
    echo "RESULT: FAIL — the suite's wall-clock cost is not under a declared, measured budget.\n";

    exit(1);
}

echo "RESULT: PASS\n";

exit(0);

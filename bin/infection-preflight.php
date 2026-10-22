#!/usr/bin/env php
<?php

// Documented override (see file header): skip the degraded-input check entirely.
if (getenv('TELEFRAME_INFECTION_IGNORE_DEGRADED') === '1') {
    fwrite(STDERR, "  preflight OVERRIDDEN via TELEFRAME_INFECTION_IGNORE_DEGRADED=1 - result may be unreliable\n\n");
    exit(0);
}

/**
 * REFUSE TO REPORT A MUTATION SCORE IF THE MEASUREMENT'S INPUTS ARE DEGRADED.
 *
 * WHY THIS EXISTS (2026-10-18)
 *
 * Two environmental conditions were measured corrupting Infection results on the same
 * code, in OPPOSITE directions:
 *
 *   1. A loaded machine. 34 mutants that kill in ~1 s cross the 10 s `timeout` under
 *      load and are booked as Timed Out. Observed: Killed 419 → 417 on an unchanged
 *      scope. The score goes DOWN — the gate understates its own subject.
 *
 *   2. An exhausted tmpfs (inodes, not bytes: 29 free of 1,048,576). `sys_get_temp_dir()`
 *      then fails inside test subprocesses, those subprocesses die, PHPUnit reports
 *      failures, and Infection books them as KILLS. Observed on one unchanged scope:
 *      Killed 299 / 234 / 231 across three runs — a 68-mutant swing, 23% of the
 *      measured population. The score goes UP.
 *
 * (2) is the dangerous one. **Infrastructure noise inflates the score**, because a
 * subprocess that dies of ENOSPC is indistinguishable from a mutant that was caught.
 * A gate that reports a better number when the machine is broken is worse than no gate:
 * it rewards being unhealthy.
 *
 * So this preflight refuses to let a run start — rather than letting it start and
 * reporting whatever comes out. Failing BEFORE the run costs one command. Failing after
 * costs you a number you believed.
 *
 * WHAT IT CHECKS
 *
 *   - free inodes on the temp filesystem, because that is the failure that was measured;
 *   - free bytes on the same filesystem;
 *   - concurrent phpunit/infection processes, because load moves the timeout boundary.
 *
 * OVERRIDE: `TELEFRAME_INFECTION_IGNORE_DEGRADED=1` skips the check. It exists for the
 * case where you have measured that your machine is fine and the heuristic is wrong.
 * It is an override, not a fix, and using it silently defeats the gate.
 *
 * @return int 0 = clear to run, 1 = degraded, do not trust a score from this run
 */
$thresholdInodes = (int) (getenv('TELEFRAME_INFECTION_MIN_INODES') ?: 10000);
$thresholdBytes = (int) (getenv('TELEFRAME_INFECTION_MIN_BYTES') ?: 1073741824); // 1 GiB
$maxConcurrent = (int) (getenv('TELEFRAME_INFECTION_MAX_CONCURRENT') ?: 2);

/**
 * An integer environment override, where 0 is a MEANINGFUL value, not "unset".
 */
function tf_infection_env_int(string $name, int $default): int
{
    $raw = getenv($name);

    if ($raw === false || $raw === '') {
        return $default;
    }

    if (! tf_infection_is_integer($raw)) {
        fwrite(STDERR, sprintf("  %s=\"%s\" is not an integer; using %d\n", $name, $raw, $default));

        return $default;
    }

    return (int) $raw;
}

/**
 * Is this string an optionally-signed run of digits? NO regex — `preg_*` is banned in
 * this repo outside src/Laravel, src/Schema and src/Bot, and phpstan enforces it.
 */
function tf_infection_is_integer(string $raw): bool
{
    $body = $raw;
    if ($body !== '' && ($body[0] === '-' || $body[0] === '+')) {
        $body = substr($body, 1);
    }

    if ($body === '') {
        return false;
    }

    for ($i = 0, $n = strlen($body); $i < $n; $i++) {
        // The one place a digit is recognised, written out rather than delegated.
        if (strpos('0123456789', $body[$i]) === false) {
            return false;
        }
    }

    return true;
}

/**
 * Split on runs of whitespace, zero-regex, dropping empty leading/trailing fields.
 *
 * @return list<string>
 */
function tf_infection_fields(string $line): array
{
    $fields = [];
    $current = '';
    $n = strlen($line);

    for ($i = 0; $i < $n; $i++) {
        $c = $line[$i];
        if ($c === ' ' || $c === "\t" || $c === "\n" || $c === "\r") {
            if ($current !== '') {
                $fields[] = $current;
                $current = '';
            }

            continue;
        }
        $current .= $c;
    }

    if ($current !== '') {
        $fields[] = $current;
    }

    return $fields;
}

$tmpDir = sys_get_temp_dir();
$problems = [];

// ── free bytes on the filesystem holding the temp dir ────────────────────────────
// disk_free_space() is the portable call; statfs() is not always compiled in, which is
// why this is not used despite being the tidier API.
$freeBytes = @disk_free_space($tmpDir);
if ($freeBytes === false) {
    $problems[] = "cannot determine free space on {$tmpDir}";
} elseif ($freeBytes < $thresholdBytes) {
    $problems[] = sprintf(
        'only %.2f GiB free on %s (need %.2f GiB)',
        $freeBytes / 1073741824,
        $tmpDir,
        $thresholdBytes / 1073741824,
    );
}

// ── free inodes: THE failure that was measured, so this one is not optional ───────
// On 2026-10-18 the byte count was fine and the INODE count was not (29 free of
// 1,048,576), which is why a byte check alone would have missed it entirely.
// df -i is used because PHP offers no inode-free-space call on the platforms here.
$dfInodes = @shell_exec('df -i '.escapeshellarg($tmpDir).' 2>/dev/null | tail -1');
$freeInodes = null;
if (is_string($dfInodes) && $dfInodes !== '') {
    $fields = tf_infection_fields(trim($dfInodes));
    // Format: Filesystem Inodes IUsed IFree IUse% Mounted  → index 3 is IFree.
    if (isset($fields[3]) && ctype_digit($fields[3])) {
        $freeInodes = (int) $fields[3];
    }
}
if ($freeInodes === null) {
    // Not fatal on its own: an unreadable inode count is an unknown, and refusing to
    // run on an unknown would make the gate unusable on a box without df.
    $problems[] = "could not read the free-inode count for {$tmpDir} — the false-kill condition cannot be ruled out";
} elseif ($freeInodes < $thresholdInodes) {
    $problems[] = sprintf(
        'only %d free inodes on %s (need %d) — this is the condition that produced FALSE KILLS on 2026-10-18',
        $freeInodes,
        $tmpDir,
        $thresholdInodes,
    );
}

// ── concurrent test processes, because load moves the timeout boundary ───────────
// COUNTED ONLY IN THIS REPO.
//
// Measured 2026-10-18: the first version of this check matched ANY process whose
// command line contained `vendor/bin/phpunit`, and this machine runs lanes for at
// least one OTHER repository (pubg-sell.ir) at the same time. The gate therefore
// refused to run because of another project's test suite — which is a false
// positive, because those processes contend for CPU but cannot make THIS repo's
// subprocess write a false result.
//
// Scoped to this repo's path, so the check means "something that can corrupt this
// measurement is running here", which is the thing worth asserting.
$exec = @shell_exec(sprintf(
    'pgrep -af %s 2>/dev/null | grep -c %s',
    escapeshellarg('vendor/bin/(phpunit|infection)'),
    escapeshellarg(dirname(__DIR__)),
));
$concurrent = $exec === null ? 0 : (int) trim($exec);

if ($concurrent > $maxConcurrent) {
    $problems[] = sprintf(
        '%d concurrent phpunit/infection processes in THIS repo (max %d) — load moves the 10s timeout boundary and understates the score',
        $concurrent,
        $maxConcurrent,
    );
}

// ── multi-scope support ─────────────────────────────────────────────────────
// An optional scope argument names the per-scope config the run will use. The
// preflight stays machine-level, but naming the scope lets it refuse a run
// whose scope has no config/phpunit file instead of failing mid-measurement.
$scope = $argv[1] ?? (getenv('TELEFRAME_INFECTION_SCOPE') ?: '');
if ($scope !== '' && $scope !== 'all') {
    $root = dirname(__DIR__);
    foreach (["{$root}/infection/{$scope}.json5", "{$root}/infection/{$scope}/phpunit.xml"] as $required) {
        if (! is_file($required)) {
            $problems[] = "scope \"{$scope}\" is missing {$required}";
        }
    }
} elseif ($scope === 'all') {
    $root = dirname(__DIR__);
    foreach (['entity', 'bus', 'schema', 'ingest'] as $s) {
        foreach (["{$root}/infection/{$s}.json5", "{$root}/infection/{$s}/phpunit.xml"] as $required) {
            if (! is_file($required)) {
                $problems[] = "scope \"{$s}\" is missing {$required}";
            }
        }
    }
}

if ($problems !== []) {
    fwrite(STDERR, '\\n  MUTATION MEASUREMENT REFUSED — inputs are degraded\\n\\n');
    foreach ($problems as $p) {
        fwrite(STDERR, "    x {$p}\n");
    }
    fwrite(STDERR, "\n  A score taken in this state is not a measurement:\n");
    fwrite(STDERR, "    - exhausted inodes book subprocess deaths as KILLS, inflating the score\n");
    fwrite(STDERR, "    - load books slow kills as TIMED OUT, deflating it\n\n");
    fwrite(STDERR, "  Free space, wait, or re-run with TMPDIR on a healthy filesystem.\n");
    fwrite(STDERR, "  Override only if you have measured this heuristic to be wrong:\n");
    fwrite(STDERR, "    TELEFRAME_INFECTION_IGNORE_DEGRADED=1 <command>\n\n");

    exit(1);
}

printf("  preflight clear: tmp=%s inodes=%s bytes=%.1fG concurrent=%d\n\n",
    $tmpDir,
    $freeInodes === null ? '?' : (string) $freeInodes,
    ($freeBytes === false ? 0.0 : $freeBytes) / 1073741824,
    $concurrent,
);

exit(0);

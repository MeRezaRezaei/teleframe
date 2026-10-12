<?php

declare(strict_types=1);

/**
 * Reap orphaned phpunit processes left behind by an interrupted test run.
 *
 * WHY THIS EXISTS. The shell tool an agent drives has a hard 120s ceiling.
 * When it fires it kills the SHELL but not the phpunit child, which is then
 * reparented to init (ppid 1) and left running forever. Two such orphans from
 * this project were found alive 6h48m after they were started, each
 * deadlocked in a futex wait at 0% CPU, holding no sockets and no database
 * handles. They cost nothing per se, but they accumulate: every interrupted
 * suite leaks one, and a leaked phpunit holds a Testbench container, an
 * sqlite handle or a redis connection, which is what actually makes the NEXT
 * run slow. An unbounded pile of them is the difference between a suite that
 * finishes in 30s and a machine that never settles.
 *
 * A process is ORPHANED and killable when all of the following hold:
 *   - it is a phpunit process for THIS repository (not a CI runner, not
 *     another project's worktree, not a php process serving something else),
 *   - its parent is init (ppid 1), so the shell that launched it is gone,
 *   - it has consumed no CPU for a grace period, so a genuinely long but
 *     progressing run is never killed.
 *
 * The CPU-time test is the important one. A working suite burns CPU; a
 * deadlocked one does not. Comparing CPU time against wall clock is what
 * separates "slow" from "stuck" without guessing.
 *
 * Usage:
 *   php bin/reap-orphan-tests.php            # list what is killable, kill nothing
 *   php bin/reap-orphan-tests.php --kill     # actually kill them
 *   php bin/reap-orphan-tests.php --idle=600 # stuck means <600s of CPU in >1800s wall (default)
 *
 * Provenance: two orphans (ppid 1, futex_do_wait, 6h48m, 0% CPU) observed on
 * this repository on 2026-09-29, both from `vendor/bin/phpunit tests/Core/MTProto/
 * tests/Control/` started at 15:01 and still alive at 21:49.
 */

$repoRoot = dirname(__DIR__);
$kill = in_array('--kill', $argv, true);
$idleSeconds = 600;
$wallSeconds = 1800;

foreach ($argv as $arg) {
    if (str_starts_with($arg, '--idle=')) {
        $idleSeconds = max(1, (int) substr($arg, 7));
    }
    if (str_starts_with($arg, '--wall=')) {
        $wallSeconds = max(1, (int) substr($arg, 7));
    }
}

if (!function_exists('proc_open')) {
    fwrite(STDERR, "proc_open unavailable; cannot inspect processes\n");
    exit(2);
}

$self = getmypid();
$output = [];
$status = 0;
$handle = proc_open(['ps', '-eo', 'pid=,ppid=,etimes=,times=,args='], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
if (!is_resource($handle)) {
    fwrite(STDERR, "could not run ps\n");
    exit(2);
}
$output = stream_get_contents($pipes[1]);
fclose($pipes[1]);
fclose($pipes[2]);
proc_close($handle);

$selfCwd = @readlink('/proc/'.getmypid().'/cwd') ?: $repoRoot;
$killable = [];

foreach (explode("\n", (string) $output) as $line) {
    $line = trim($line);
    if ($line === '') {
        continue;
    }
    if (!preg_match('/^(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s*(.*)$/', $line, $m)) {
        continue;
    }
    [, $pid, $ppid, $elapsed, $cpuSeconds, $args] = $m;
    $pid = (int) $pid;

    if ($pid === $self || $args === trim($args) && $args === '') {
        continue;
    }
    if (!str_contains($args, 'phpunit')) {
        continue;
    }
    if ((int) $ppid !== 1) {
        continue; // still has a shell: a live run, not an orphan
    }
    if ((int) $elapsed < $wallSeconds) {
        continue; // too young to call stuck
    }
    if ((int) $cpuSeconds >= $idleSeconds) {
        continue; // has burned real CPU in its lifetime: slow, not stuck
    }

    // Confirm it is THIS repository, so another project's worktree is never touched.
    $cwd = @readlink('/proc/'.$pid.'/cwd');
    if ($cwd === false || realpath($cwd) !== realpath($selfCwd)) {
        continue;
    }

    $killable[] = ['pid' => $pid, 'elapsed' => (int) $elapsed, 'cpu' => (int) $cpuSeconds, 'args' => $args];
}

if ($killable === []) {
    echo "no orphaned test processes (idle: {$idleSeconds}s cpu, wall: {$wallSeconds}s)\n";
    exit(0);
}

foreach ($killable as $p) {
    $hours = intdiv($p['elapsed'], 3600);
    $mins = intdiv($p['elapsed'] % 3600, 60);
    printf(
        "%s pid=%d alive=%dh%02dm cpu=%ds  %s\n",
        $kill ? 'KILL ' : 'STALE',
        $p['pid'],
        $hours,
        $mins,
        $p['cpu'],
        $p['args'],
    );
    if ($kill) {
        exec('kill -9 '.$p['pid'].' 2>/dev/null');
    }
}

printf("\n%d orphaned test process(es) %s\n", count($killable), $kill ? 'killed' : 'listed (re-run with --kill)');
exit($status);

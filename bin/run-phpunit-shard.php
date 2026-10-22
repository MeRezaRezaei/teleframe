<?php

declare(strict_types=1);

/**
 * RUN ONE CI SHARD. `php bin/run-phpunit-shard.php <shard-name>`
 *
 * WHY A RUNNER AND NOT A LITERAL PATH LIST IN THE YAML
 * ---------------------------------------------------
 * The shard paths live in ci/phpunit-shards.json and nowhere else. That file is
 * what bin/verify-shard-partition.php proves to be a total, disjoint partition
 * of the suite, so it is the only place a path may be written. Spelling the
 * paths a second time in .gitlab-ci.yml would create a second source of truth
 * that nothing proves — and the second source is the one CI would actually run,
 * so the proof would be certifying a file the pipeline ignores. That is the
 * shape of the failure this whole change exists to remove.
 *
 * So this script resolves a shard NAME to its paths, and refuses loudly when
 * the name is unknown, empty, or when the partition is broken. A mistyped
 * matrix entry therefore fails its own job instead of quietly running nothing.
 *
 * EXIT CODES
 *   0  the shard ran and PHPUnit was green
 *   1  PHPUnit was red — the shard's real verdict, passed through untouched
 *   2  the shard name is unknown / empty, or the partition proof failed
 *
 * Note the deliberate asymmetry: 1 and 2 are different. 1 means "the code is
 * wrong"; 2 means "the gate is broken and proved nothing". Collapsing them
 * would let a broken shard list read as an ordinary test failure.
 */
$root = dirname(__DIR__);
chdir($root);

const EXIT_OK = 0;
const EXIT_RED = 1;
const EXIT_USAGE = 2;

$name = $argv[1] ?? '';

if ($name === '' || in_array($name, ['--help', '-h'], true)) {
    fwrite(STDERR, "usage: php bin/run-phpunit-shard.php <shard-name>\n"
        ."Shard names come from ci/phpunit-shards.json.\n");

    exit(EXIT_USAGE);
}

$configPath = $root.'/ci/phpunit-shards.json';

if (! is_file($configPath)) {
    fwrite(STDERR, "FATAL: ci/phpunit-shards.json is missing — there are no shards to run.\n");

    exit(EXIT_USAGE);
}

$config = json_decode((string) file_get_contents($configPath), true);
$paths = null;

foreach (($config['shards'] ?? []) as $shard) {
    if (($shard['name'] ?? null) === $name) {
        $paths = array_values((array) $shard['paths']);

        break;
    }
}

if ($paths === null) {
    $known = array_map(static fn (array $s): string => (string) ($s['name'] ?? '?'), $config['shards'] ?? []);

    fwrite(STDERR, sprintf(
        "FATAL: no shard named \"%s\" in ci/phpunit-shards.json.\n".
        "       Known shards: %s\n".
        "       A shard name that does not resolve must FAIL — running the whole suite here\n".
        "       would hide the mistake behind a green job.\n",
        $name,
        $known === [] ? '(none)' : implode(', ', $known),
    ));

    exit(EXIT_USAGE);
}

if ($paths === []) {
    fwrite(STDERR, sprintf("FATAL: shard \"%s\" has an empty path list.\n", $name));

    exit(EXIT_USAGE);
}

// ── PROVE THE PARTITION BEFORE RUNNING ANY OF IT ────────────────────────────
// Cheap (collection only, no execution) and it means a broken shard list is
// caught on EVERY shard job rather than only on whichever one a reader happens
// to open. A shard that cannot prove it is running its share does not run.
$proof = [PHP_BINARY, $root.'/bin/verify-shard-partition.php', '--quiet'];
$descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
$process = proc_open($proof, $descriptors, $pipes, $root);

if (! is_resource($process)) {
    fwrite(STDERR, "FATAL: could not run bin/verify-shard-partition.php — refusing to run a shard I cannot prove.\n");

    exit(EXIT_USAGE);
}

$proofOut = (string) stream_get_contents($pipes[1]);
$proofErr = (string) stream_get_contents($pipes[2]);
fclose($pipes[1]);
fclose($pipes[2]);
$proofStatus = proc_close($process);

if ($proofStatus !== 0) {
    fwrite(STDERR, "FATAL: the shard partition is BROKEN, so this shard is not a gate.\n");
    fwrite(STDERR, $proofErr !== '' ? $proofErr : $proofOut);

    exit(EXIT_USAGE);
}

fwrite(STDOUT, $proofOut);

// ── RUN THE SHARD ───────────────────────────────────────────────────────────
$command = array_merge(
    [PHP_BINARY, '-d', 'memory_limit=1G', $root.'/vendor/bin/phpunit', '--no-coverage'],
    $paths,
);

// CI publishes a JUnit report per shard. It is what makes "one honest verdict"
// possible beyond the pipeline colour: the rollup job merges these and counts
// the tests, so a shard that produced no report cannot be mistaken for a pass.
if (getenv('CI') !== false) {
    $command[] = '--log-junit';
    $command[] = 'phpunit-shard-'.$name.'.xml';
}

fwrite(STDOUT, "\n=== shard ".$name." ===\n");
fwrite(STDOUT, 'phpunit '.implode(' ', $paths)."\n\n");

$process = proc_open($command, $descriptors, $pipes, $root);

if (! is_resource($process)) {
    fwrite(STDERR, 'FATAL: could not start PHPUnit for shard '.$name."\n");

    exit(EXIT_USAGE);
}

fwrite(STDOUT, (string) stream_get_contents($pipes[1]));
fwrite(STDERR, (string) stream_get_contents($pipes[2]));
fclose($pipes[1]);
fclose($pipes[2]);

$status = proc_close($process);

if ($status !== 0) {
    // Reported, never smoothed over. The exit code is passed through as-is so
    // the job is red for the reason PHPUnit was red.
    fwrite(STDERR, sprintf("\nSHARD %s IS RED (phpunit exit %d)\n", $name, $status));
}

exit($status === 0 ? EXIT_OK : EXIT_RED);

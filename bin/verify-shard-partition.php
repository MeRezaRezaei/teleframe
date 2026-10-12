<?php

declare(strict_types=1);
use Symfony\Component\Yaml\Yaml;

/**
 * THE PROOF THAT THE CI SHARDS PARTITION THE SUITE. Exit 0 = total, 1 = not.
 *
 * WHY THIS FILE EXISTS AT ALL
 * ---------------------------
 * A CI shard list is a list of paths. The failure mode it invites is not a red
 * build; it is a GREEN build that quietly runs less than the suite. If one
 * directory is left out of every shard, no shard fails, no shard reports an
 * error, and the pipeline says the suite passed. That is worse than a slow job,
 * because it is invisible: a slow job is obviously slow.
 *
 * This repository has already shipped two gates that passed while checking
 * nothing — 355 tests in five unregistered directories, and six ingest tests
 * that asserted zero assertions while green. So the partition is not asserted in
 * a comment. It is asserted HERE, by COMPARING SETS, and the sets are produced
 * by PHPUnit's own collector rather than by a glob this file invented:
 *
 *   1. Run `phpunit --list-tests` with NO path arguments. That is the whole
 *      suite as the committed phpunit.xml.dist defines it — the exact set the
 *      un-sharded `composer verify` would have run.
 *   2. Run `phpunit --list-tests <paths...>` once per shard, over the shard's
 *      own paths from ci/phpunit-shards.json.
 *   3. Assert the union of the shard sets EQUALS the whole set, and that the
 *      shards are pairwise DISJOINT.
 *
 * Step 3 is the whole contract, in both directions:
 *   - DROPPED  -> a test in the whole set that is in no shard  => reported.
 *   - DUPLICATED -> a test in two shards => reported, by name and by shard.
 *
 * Neither is detectable by counting directories, which is why counting is not
 * what this does: a directory count is happy as long as the paths are spelled
 * plausibly, and a plausible misspelling of a path is exactly how a test goes
 * missing.
 *
 * `--list-tests` performs collection only. It does not execute anything, so
 * this is safe to call from inside a PHPUnit run (which is what
 * tests/Concerns/ShardPartitionTest.php does) and safe to call on a red suite.
 *
 * USAGE
 *   php bin/verify-shard-partition.php            # prove, print a report
 *   php bin/verify-shard-partition.php --quiet    # prove, print only the verdict
 *
 * EXIT CODES
 *   0  the shards are a total, disjoint partition of the suite
 *   1  the partition is broken (a test is dropped, duplicated, or unclaimed)
 *   2  usage error, or the shard config / PHPUnit could not be read at all
 */
$root = dirname(__DIR__);
chdir($root);

const EXIT_OK = 0;
const EXIT_BROKEN = 1;
const EXIT_USAGE = 2;

// symfony/yaml is needed below to parse the CI definitions — it is what makes
// the `owned_elsewhere` claim checkable rather than a promise. It ships as a
// transitive dependency of laravel/framework, so it is always installed here,
// but this script is run directly by a CI job and by PHPUnit and neither has
// loaded Composer's autoloader yet.
if (! class_exists(Yaml::class)) {
    $autoload = __DIR__.'/../vendor/autoload.php';

    if (! is_file($autoload)) {
        fwrite(STDERR, "FATAL: vendor/autoload.php is missing — run `composer install` first.\n");

        exit(EXIT_USAGE);
    }

    require_once $autoload;
}

// The CI-dialect vocabulary — which files hold CI, how a job's commands are
// spelled in each dialect, and how both flatten to one shape. It lives in its
// own file because tests/Concerns/ShardPartitionTest.php asserts on the SAME
// jobs in the SAME two dialects, and a second copy of that vocabulary is a
// second thing to forget the next time CI moves. That file has already been
// paid for once: it read `.gitlab-ci.yml` by name until commit ddbdef18 moved
// CI to GitHub Actions and both readers silently found nothing.
require_once __DIR__.'/ci-definitions.php';

/**
 * @return array{0: list<string>, 1: string} the test identifiers and any stderr
 */
function listTests(array $paths, string $root): array
{
    $command = [PHP_BINARY, '-d', 'memory_limit=1G', $root.'/vendor/bin/phpunit', '--no-coverage', '--list-tests'];

    foreach ($paths as $path) {
        $command[] = $path;
    }

    $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
    $process = proc_open($command, $descriptors, $pipes, $root);

    if (! is_resource($process)) {
        return [[], 'could not start: '.implode(' ', $command)];
    }

    $stdout = (string) stream_get_contents($pipes[1]);
    $stderr = (string) stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $status = proc_close($process);

    if ($status !== 0) {
        return [[], 'phpunit --list-tests exited '.$status.': '.trim($stderr."\n".$stdout)];
    }

    // The output is a flat, fixed-shape list: one ` - Fully\Qualified\Name::method`
    // line per collected test, after a two-line banner. There is no reason to
    // reach for a regex, and the repository bans preg_* outside src/Laravel,
    // src/Schema and src/Bot — this file is bin/, so a plain parser is both
    // correct and cheaper than arguing about the allow-list.
    $tests = [];
    foreach (explode("\n", $stdout) as $line) {
        $line = rtrim($line, "\r");

        if (! str_starts_with($line, ' - ')) {
            continue;
        }

        $identifier = trim(substr($line, 3));

        if ($identifier !== '') {
            $tests[] = $identifier;
        }
    }

    return [$tests, ''];
}

/**
 * @return array{shards: array<string, list<string>>, errors: list<string>}
 */
function readShardConfig(string $root): array
{
    $path = $root.'/ci/phpunit-shards.json';

    if (! is_file($path)) {
        return ['shards' => [], 'errors' => ['ci/phpunit-shards.json does not exist']];
    }

    $raw = (string) file_get_contents($path);
    $decoded = json_decode($raw, true);

    if (! is_array($decoded) || ! isset($decoded['shards']) || ! is_array($decoded['shards'])) {
        return ['shards' => [], 'errors' => ['ci/phpunit-shards.json is not a {"shards": …} document']];
    }

    $shards = [];
    $errors = [];

    foreach ($decoded['shards'] as $shard) {
        if (! is_array($shard) || ! isset($shard['name'], $shard['paths']) || ! is_array($shard['paths'])) {
            $errors[] = 'every shard needs a "name" (string) and a "paths" (list of strings)';

            continue;
        }

        $name = (string) $shard['name'];
        $paths = array_values(array_map('strval', $shard['paths']));

        if ($paths === []) {
            $errors[] = sprintf('shard "%s" has an empty "paths" list — an empty shard is a shard that can never be red', $name);

            continue;
        }

        if (isset($shards[$name])) {
            $errors[] = sprintf('shard "%s" is declared twice', $name);

            continue;
        }

        foreach ($paths as $relative) {
            if (! str_starts_with($relative, 'tests/')) {
                $errors[] = sprintf('shard "%s" lists "%s", which is not under tests/', $name, $relative);
            }

            if (! file_exists($root.'/'.$relative)) {
                // A misspelled path is the single most likely way a test goes
                // missing, so it is named here rather than left to show up as a
                // mysteriously smaller count somewhere downstream.
                $errors[] = sprintf('shard "%s" lists "%s", which does not exist on disk', $name, $relative);
            }
        }

        $shards[$name] = $paths;
    }

    if ($shards === [] && $errors === []) {
        $errors[] = 'ci/phpunit-shards.json declares no shards at all';
    }

    return ['shards' => $shards, 'errors' => $errors];
}

/**
 * @return array{shards: array<string, list<string>>, errors: list<string>}
 */
function partition(string $root): array
{
    [$whole, $wholeError] = listTests([], $root);

    if ($wholeError !== '') {
        return ['shards' => [], 'errors' => ['the WHOLE suite could not be collected — '.$wholeError]];
    }

    if ($whole === []) {
        return ['shards' => [], 'errors' => ['the WHOLE suite collected ZERO tests, so "the union equals the whole" would be trivially true. Refusing to certify that.']];
    }

    ['shards' => $declared, 'errors' => $errors] = readShardConfig($root);

    if ($declared === []) {
        return ['shards' => [], 'errors' => array_merge($errors, ['no usable shards to compare against'])];
    }

    // owner[test] = [shard names claiming it]
    $owner = [];
    $collected = [];

    foreach ($declared as $name => $paths) {
        [$tests, $error] = listTests($paths, $root);

        if ($error !== '') {
            $errors[] = sprintf('shard "%s" could not be collected — %s', $name, $error);

            continue;
        }

        $collected[$name] = count($tests);

        foreach ($tests as $test) {
            $owner[$test][] = $name;
        }
    }

    // ── THE `owned_elsewhere` LIST — every test is owned by SOMEONE ───────────
    // The sharded suite does not own the whole suite: tests/MySql and tests/Pg
    // belong to the mysql-tests and pg-tests jobs, which give them a real engine
    // (a mysql:8.4 / postgres:17 service plus the matching driver). Measured, not
    // assumed: with no TELEFRAME_PG in the environment the Pg suite runs 11
    // tests, 0 assertions, 11 ERRORS — and tests/MySql runs 60 tests and 2090
    // assertions with 14 errors and 8 failures rather than skipping cleanly. So
    // listing either in a shard would manufacture a RED SHARD whose message is
    // "no database": an environment failure wearing the costume of a code
    // failure, which is the specific misreading this repository has been bitten
    // by twice.
    //
    // But excluding them silently would be far worse, and that is why this is a
    // DECLARED, CHECKED list rather than a hole in the partition:
    //
    //   * the omitted tests are COLLECTED and COUNTED, not ignored;
    //   * each omitted area names the CI job that owns it;
    //   * and that job's commands — GitLab `script:` or Actions `run:` — are
    //     parsed out of whichever CI definitions exist on disk, to confirm the
    //     job really does invoke PHPUnit on that area.
    //
    // So if `pg-tests` were ever deleted, or stopped running tests/Pg, THIS
    // FAILS. An omission that outlives its owner is the failure mode; an omission
    // that is checked against its owner is just a division of labour.
    $elsewhere = readElsewhereList($root, $errors);
    $elsewhereTests = [];

    foreach ($elsewhere as $entry) {
        [$tests, $error] = listTests($entry['paths'], $root);

        if ($error !== '') {
            $errors[] = sprintf('owned_elsewhere entry "%s" could not be collected — %s', $entry['paths'][0], $error);

            continue;
        }

        foreach ($tests as $test) {
            $elsewhereTests[$test] = $entry['job'];
        }
    }

    // An omission that names a job which does not run it is a silent drop with
    // extra steps, so the owning job's script is checked, not trusted.
    foreach ($elsewhere as $entry) {
        foreach (verifyOwnerRuns($root, $entry) as $problem) {
            $errors[] = $problem;
        }
    }

    // ── TOTAL: every collected test is owned by exactly one shard OR by a ─────
    // declared, verified other job. Nothing is dropped in either direction.
    $dropped = [];

    foreach ($whole as $test) {
        if (! isset($owner[$test]) && ! isset($elsewhereTests[$test])) {
            $dropped[] = $test;
        }
    }

    foreach ($dropped as $test) {
        $errors[] = sprintf('DROPPED: no shard runs %s', $test);
    }

    // An omission may not OVER-claim: a test in `owned_elsewhere` that no shard
    // runs AND that the named job does not actually collect would mean the
    // entry has drifted and is now covering for a real drop.
    foreach ($elsewhereTests as $test => $job) {
        if (! in_array($test, $whole, true)) {
            $errors[] = sprintf('owned_elsewhere claims %s for %s, but the whole suite does not collect it', $test, $job);
        }
    }

    // ── DISJOINT: no test is claimed twice ──────────────────────────────────
    $duplicated = [];

    foreach ($owner as $test => $claimants) {
        if (count($claimants) > 1) {
            $duplicated[$test] = $claimants;
        }
    }

    foreach ($duplicated as $test => $claimants) {
        sort($claimants);
        $errors[] = sprintf('DUPLICATED: %s is run by %s', $test, implode(' AND ', $claimants));
    }

    // A test may not be run by a shard AND claimed by another job: that is the
    // suite running twice, which is how a slow job becomes a slow job times two.
    foreach ($elsewhereTests as $test => $job) {
        if (isset($owner[$test])) {
            $errors[] = sprintf('DUPLICATED: %s is run by shard %s AND claimed by job %s', $test, $owner[$test][0], $job);
        }
    }

    // ── No shard may collect a test the whole suite does not know about ─────
    // Guards the OTHER direction of drift: a shard whose path resolves outside
    // the configured suites would inflate the union past the whole set and hide
    // a real drop somewhere else.
    $wholeSet = array_flip($whole);

    foreach (array_keys($owner) as $test) {
        if (! isset($wholeSet[$test])) {
            $errors[] = sprintf('EXTRA: a shard collects %s, which the whole suite does not contain', $test);
        }
    }

    return ['shards' => $collected, 'errors' => $errors];
}

/**
 * The declared `owned_elsewhere` list: areas this suite does not shard, and the
 * CI job that owns each one.
 *
 * @param  list<string>  $errors
 * @return list<array{paths: list<string>, job: string}>
 */
function readElsewhereList(string $root, array &$errors): array
{
    $path = $root.'/ci/phpunit-shards.json';

    if (! is_file($path)) {
        return [];
    }

    $config = json_decode((string) file_get_contents($path), true);
    $declared = is_array($config) ? ($config['owned_elsewhere'] ?? []) : [];

    if (! is_array($declared)) {
        $errors[] = 'ci/phpunit-shards.json: "owned_elsewhere" must be a list';

        return [];
    }

    $out = [];

    foreach ($declared as $entry) {
        if (! is_array($entry) || ! isset($entry['paths'], $entry['job'])) {
            $errors[] = 'every owned_elsewhere entry needs "paths" (list) and "job" (a CI job name)';

            continue;
        }

        $paths = array_values(array_map('strval', (array) $entry['paths']));
        $job = (string) $entry['job'];

        if ($paths === [] || $job === '') {
            $errors[] = 'an owned_elsewhere entry has an empty "paths" or "job"';

            continue;
        }

        foreach ($paths as $relative) {
            if (! file_exists($root.'/'.$relative)) {
                $errors[] = sprintf('owned_elsewhere lists "%s", which does not exist on disk', $relative);
            }
        }

        $out[] = ['paths' => $paths, 'job' => $job];
    }

    return $out;
}

/**
 * Confirm the owning job really invokes PHPUnit on the area it claims.
 *
 * Parsed with a real YAML parser rather than a substring search, because the
 * check has to be about the job's commands and not about the word appearing
 * anywhere in the file — a comment mentioning pg-tests must not satisfy this.
 *
 * The claim is resolved against EVERY CI definition found. Where more than one
 * defines the same job id the entry passes only if one of them satisfies it in
 * full; where none does, every definition that defined the job is reported, so
 * a stale claim names the files it was checked against instead of failing on
 * whichever one happened to be looked at first.
 *
 * @return list<string> the problems found; empty means the owner checks out
 */
function verifyOwnerRuns(string $root, array $entry): array
{
    if (! class_exists(Yaml::class)) {
        return [sprintf(
            'owned_elsewhere names job "%s" but symfony/yaml is not available, so the claim could not be verified',
            $entry['job'],
        )];
    }

    $definitions = ciDefinitions($root);

    if ($definitions === []) {
        return [sprintf(
            'no readable CI definition was found (.github/workflows/*.yml or .gitlab-ci.yml), so owned_elsewhere job "%s" cannot be verified — counted as unowned rather than as verified',
            $entry['job'],
        )];
    }

    $defining = [];
    $searched = [];

    foreach ($definitions as $definition) {
        $searched[] = $definition['path'];

        if (array_key_exists($entry['job'], $definition['jobs'])) {
            $defining[] = $definition;
        }
    }

    if ($defining === []) {
        return [sprintf(
            'owned_elsewhere says job "%s" owns %s, but no such job exists in %s — those tests are now owned by NOBODY',
            $entry['job'],
            implode(', ', $entry['paths']),
            implode(', ', $searched),
        )];
    }

    $problems = [];

    foreach ($defining as $definition) {
        $found = ownerProblems($entry, $definition['path'], $definition['commands'][$entry['job']] ?? '');

        // One definition satisfying the claim is enough — that is what "the job
        // exists somewhere in CI" means, and it is not a loosening: the job
        // still has to run phpunit on every claimed path to get here.
        if ($found === []) {
            return [];
        }

        $problems = array_merge($problems, $found);
    }

    return $problems;
}

/**
 * The ownership assertions themselves, against one CI definition's command text.
 *
 * `script:` (GitLab) and `run:` (Actions) are already flattened into this one
 * string by bin/ci-definitions.php, so these assertions are dialect-blind and
 * unchanged in strength: the claiming job must invoke phpunit, and it must
 * name every claimed path.
 *
 * @return list<string>
 */
function ownerProblems(array $entry, string $path, string $commands): array
{
    $problems = [];

    if (! str_contains($commands, 'phpunit')) {
        $problems[] = sprintf(
            'job "%s" in %s does not invoke phpunit at all, so it cannot own %s',
            $entry['job'],
            $path,
            implode(', ', $entry['paths']),
        );
    }

    foreach ($entry['paths'] as $claimed) {
        if (! str_contains($commands, $claimed)) {
            $problems[] = sprintf(
                'job "%s" in %s is named as the owner of %s but its commands never mention that path — the claim is stale',
                $entry['job'],
                $path,
                $claimed,
            );
        }
    }

    return $problems;
}

// ── entry point ─────────────────────────────────────────────────────────────

$quiet = in_array('--quiet', $argv, true);

if (in_array('--help', $argv, true) || in_array('-h', $argv, true)) {
    fwrite(STDOUT, "usage: php bin/verify-shard-partition.php [--quiet]\n\n"
        ."Proves that the shards in ci/phpunit-shards.json partition the suite exactly:\n"
        ."every test the committed phpunit.xml.dist collects is run by exactly one shard.\n\n"
        ."Exit 0 total · 1 broken · 2 usage/unreadable\n");

    exit(EXIT_USAGE);
}

$result = partition($root);

foreach ($result['errors'] as $error) {
    fwrite(STDERR, 'FAIL  '.$error."\n");
}

if ($result['errors'] !== []) {
    fwrite(STDERR, sprintf(
        "\nSHARD PARTITION BROKEN — %d problem(s). A shard that silently loses tests is worse\n".
        "than a slow job, because a slow job is obviously slow.\n",
        count($result['errors']),
    ));

    exit(EXIT_BROKEN);
}

if (! $quiet) {
    ksort($result['shards']);
    fwrite(STDOUT, "SHARD PARTITION OK — every collected test is run by exactly one shard\n\n");

    foreach ($result['shards'] as $name => $count) {
        fwrite(STDOUT, sprintf("  %-28s %5d tests\n", $name, $count));
    }
}

exit(EXIT_OK);

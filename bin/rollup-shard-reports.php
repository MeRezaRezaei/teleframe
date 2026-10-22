<?php

declare(strict_types=1);

/**
 * COUNT WHAT THE SHARDS ACTUALLY RAN, AND COMPARE IT WITH WHAT THE SUITE IS.
 *
 * WHY A GREEN PIPELINE IS NOT ENOUGH
 * ----------------------------------
 * `bin/verify-shard-partition.php` proves the shard LIST is a total partition.
 * That is a statement about a JSON file. This is a statement about what happened:
 * it reads the JUnit report each shard published and counts the tests that
 * really ran.
 *
 * The gap it closes is the one this repository has already shipped twice — a gate
 * that was green while checking nothing. A shard whose PHPUnit invocation
 * collected nothing exits 0. Its job is green. Its artifact is missing or empty.
 * Nothing else in the pipeline notices. So the count is compared against the
 * number the suite collects, and a shortfall is a FAILURE here.
 *
 * WHY IT RUNS WHEN A SHARD IS RED
 * -------------------------------
 * Because the interesting sentence — "the shards together ran N of N tests" — is
 * most useful precisely when something is already red, where it separates "a real
 * defect" from "a shard that quietly ran half the suite". It does not soften the
 * shard's verdict: each shard's own exit code is the pipeline status.
 *
 * WHY A SKIPPED TEST IS NOT A TEST THAT RAN
 * -----------------------------------------
 * At the XML layer a skipped test and a passing test are the same shape. PHPUnit's
 * JUnit logger emits a FULL `<testcase>` element for a skipped test and hangs a
 * `<skipped/>` child off it, so counting `<testcase>` elements counts skips as if
 * they had run. That made `$reports['tests']` a number this job was willing to
 * certify as "RAN" while a third of it could have been skipped — and skips
 * produced no verdict at all. Measured on a full 8-shard run: 4520 tests, 2
 * failures, 16 skipped, every one of the 16 certified as having run.
 *
 * THE FIX IS NOT `tests - skipped`, and the reason is the whole design of this
 * file. Subtracting every skip would make this job permanently red, because this
 * repository legitimately skips tests that no CI job can run: the shard container
 * has no ext-pcntl, no ext-amqp, no RabbitMQ, no Redis, no live Telegram
 * credentials and no built tgweb bundle. A gate that is red on every pipeline for
 * ever is a gate that gets switched off, which is the exact recurrence the
 * `owned_elsewhere` block below already reasons about and the recurrence this
 * repository has already paid for once.
 *
 * So skips get the SAME treatment as the tests owned by other jobs: an explicit,
 * CHECKED ownership list (`skippedElsewhere()`), every entry naming a suite, the
 * owner it belongs to, and why that suite cannot run here. A skipped testcase whose
 * class is not in that list is UNATTRIBUTED and FAILS the rollup, naming the
 * class::method. That is what makes the subtraction sound rather than a fudge: the
 * honest count `ran = tests - skipped` is only trustworthy when the skipped term is
 * a closed, reviewed set — so the list is the gate, and the arithmetic is honest
 * because the arithmetic cannot be gamed from the other side.
 *
 * EXIT CODES
 *   0  the shards ran the whole suite (some of it may still be RED — reported here)
 *   1  the shards did NOT run the whole suite, no reports were published, or a
 *      test skipped without a declared owner
 *   2  usage error
 */
$root = dirname(__DIR__);
chdir($root);

const EXIT_OK = 0;
const EXIT_SHORT = 1;
const EXIT_USAGE = 2;

if (in_array('--help', $argv, true) || in_array('-h', $argv, true)) {
    fwrite(STDOUT, "usage: php bin/rollup-shard-reports.php\n\n"
        ."Reads phpunit-shard-*.xml and asserts the shards together ran every test the\n"
        ."committed phpunit.xml.dist collects. A skipped testcase is NOT counted as run,\n"
        ."and a skip with no declared owner in skippedElsewhere() fails the rollup.\n"
        ."Exit 0 complete · 1 short or unattributed skip · 2 usage\n");

    exit(EXIT_USAGE);
}

/**
 * The skipped_elsewhere ownership list, for SKIPS rather than for whole areas.
 *
 * Same pattern, same reason, and the same failure mode it prevents, as the
 * `owned_elsewhere` list read from ci/phpunit-shards.json: a test this job cannot
 * run must be DECLARED, and the declaration has to say who owns it and why. An
 * undeclared skip fails the rollup naming class::method, so the list cannot rot
 * into a blanket amnesty — adding a skip to the suite means adding it here, with a
 * reason, in the same diff.
 *
 * `class` is matched against the JUnit `<testcase class="…">` attribute, which is
 * the FQCN — the same string `phpunit --list-tests` prints before the `::`, so an
 * entry can be copied straight out of that output. `suite` names the owner and is
 * what the rollup PRINTS next to the count, so a reader of the CI log learns which
 * lane a missing test belongs to without opening this file.
 *
 * WHY EACH ENTRY IS HERE — every one is an environment fact about the shard
 * container in .github/workflows/verify-php.yml (image php:8.4-cli plus
 * `docker-php-ext-install bcmath gmp zip pdo_pgsql sockets intl`), not a preference:
 *
 *   ext-pcntl-fork
 *     The shard job never installs pcntl, and the official php:8.4-cli image does
 *     not ship it (measured in the image: `extension_loaded('pcntl')` is false,
 *     `posix_kill`/`pcntl_fork` do not exist). posix IS present, so only the pcntl
 *     half of each guard trips. These tests need a real forked child to bound a
 *     timeout, which is the property under test.
 *   openssl-cli
 *     StreamSocketPeerNameTest mints a throwaway certificate with the openssl CLI
 *     and skips when the CLI or a temp dir is unavailable. The image ships
 *     /usr/bin/openssl, so this entry usually reports 0 — it is declared anyway
 *     because the rollup's contract is "declared or red", and a runner without the
 *     CLI would otherwise turn a legitimately environment-gated skip into a red
 *     nobody can fix.
 *   ext-posix
 *     SupervisorTest observes a real child process with posix_kill. posix IS
 *     present in php:8.4-cli and in the shard job's extension list, so this entry
 *     normally observes 0 — declared for the same reason as openssl-cli, so that a
 *     runner without ext-posix yields a named line rather than an unexplained red.
 *   ext-amqp / rabbitmq
 *     ext-amqp is not installed (it needs a pecl build the shard job does not do)
 *     and there is no RabbitMQ service container. AmqpUploadQueue's constructor
 *     calls assertLoaded(), so the whole queue suite is unreachable here.
 *   redis-service
 *     No Redis service container. LaravelRedisAdapterTest skips when no redis
 *     answers on the configured host.
 *   live-telegram
 *     Live DC sockets, the live vault and the warm-lane proofs all need credentials
 *     (.env / TELEGRAM_USER_SESSION) that this repository must not carry — see the
 *     hard rule in AGENTS.md. A credential-gated skip is the correct outcome.
 *   pinned-defect
 *     LiveEndpointPolicyCatalogTest skips itself while a KNOWN defect stands, naming
 *     the defect and the fix in the skip reason. It is a placeholder, not an
 *     environment limit, so it gets its own owner string: the fix is to delete the
 *     dead allowlist entry, and the rollup printing it is the reminder.
 *   mysql-live-gate
 *     RepeatedEntityConvergesTest needs a live MySQL 8.4 MVCC snapshot, which the
 *     sqlite default engine cannot express. tests/MySql is already owned elsewhere
 *     for the same reason; this one lives under tests/Schema and so is declared here.
 *   no-host-application
 *     MirrorCatalogDistPackagingTest's whole point is that it runs with no Laravel
 *     host booted; inside a booted test process there is nothing to shadow.
 *   tgweb-bundle
 *     TelegramWebControllerTest needs the built tgweb bundle. A clean CI checkout has
 *     none and the shard job does not build it.
 *   optional-dev-dependency
 *     temporal/sdk is require-DEV, so it is present under `composer install` and
 *     absent from a --no-dev install. Declared because either state is legitimate and
 *     neither should turn the rollup red.
 *   pcntl-signal (SIGTERM legs)
 *     DaemonTest / IngestCommandTest / DoctorHonestyTest need pcntl_signal and
 *     pcntl_async_signals, which are part of ext-pcntl and therefore absent for the
 *     same reason as ext-pcntl-fork. Listed separately because the guard text differs
 *     and a reader grepping for 'pcntl/posix' should land on it.
 *   live-egress-absent
 *     ClientOfflineContractTest skips only when a live connection to Telegram was
 *     ACTUALLY established — the skip means the offline contract could not be
 *     asserted. Declared so the log shows it as a known condition rather than an
 *     unexplained one when a runner does have egress.
 *
 * An entry matching zero skipped tests is NOT a failure: it means the capability
 * this job lacks happened not to be exercised, or the guard simply was not
 * reached. That is reported as a single line and nothing more. The list is an
 * allowlist of skips that MAY be unattributed, not a quota.
 *
 * ONE DELIBERATE NON-ENTRY, recorded because it reads as a miss on a grep:
 * tests/Schema/FidelityAuditIsCurrentTest.php mentions markTestSkipped() once, at
 * line 869, inside a docblock — and it is there to say the class deliberately does
 * NOT skip. Its own comment: "it is deliberately NOT a `markTestSkipped()`. A
 * skipped test reports no verdict, so a permanently skipped drift check and a
 * permanently green drift check are indistinguishable in the CI rollup". Adding it
 * here would re-open the exact hole this list exists to close.
 *
 * @return array<string, array{suite: string, why: string, classes: list<string>}>
 */
function skippedElsewhere(): array
{
    $ext = 'ext-pcntl-fork: the shard job never installs pcntl (php:8.4-cli does not ship it; docker-php-ext-install lists bcmath gmp zip pdo_pgsql sockets intl), so pcntl_fork does not exist and the forked child these tests need to bound a timeout cannot be made';

    return [
        'ext-pcntl-fork' => [
            'suite' => 'fork-bound transport/CI probes — needs a real child process',
            'why' => $ext,
            'classes' => [
                'MeRezaRezaei\Teleframe\Core\Tests\MTProto\ClientReadTimeoutEvictionTest',
                'MeRezaRezaei\Teleframe\Tests\Core\MTProto\Transport\RelayAuthW02Test',
                'MeRezaRezaei\Teleframe\Tests\Core\MTProto\Transport\RelayServerTest',
                'MeRezaRezaei\Teleframe\Tests\Core\MTProto\Transport\RelayTcpGuardW10Test',
                'MeRezaRezaei\Teleframe\Tests\Core\MTProto\Transport\StreamSocketReadTimeoutTest',
                'MeRezaRezaei\Teleframe\Tests\Core\MTProto\Transport\VaultMtprotoAnswererHandshakeBudgetTest',
                'MeRezaRezaei\Teleframe\Tests\Core\MTProto\Transport\VaultMtprotoAnswererReadTimeoutTest',
                'MeRezaRezaei\Teleframe\Tests\Core\MTProto\Transport\VaultMtprotoAnswererTest',
                'MeRezaRezaei\Teleframe\Tests\Core\MTProto\Transport\WebSocketObfuscated2LinkTest',
                'MeRezaRezaei\Teleframe\Tests\Core\MTProto\Transport\WebSocketRelayServerTest',
                'MeRezaRezaei\Teleframe\Tests\Laravel\Console\RelayCommandTest',
                'MeRezaRezaei\Teleframe\Tests\Mirror\MirrorFactWriterParentFirstOrderingTest',
            ],
        ],
        'pcntl-signal' => [
            'suite' => 'SIGTERM / bounded-probe daemon probes — need pcntl_signal + pcntl_async_signals',
            'why' => $ext.' (posix IS present, so only the pcntl half of each guard trips)',
            'classes' => [
                'MeRezaRezaei\Teleframe\Laravel\Tests\DoctorHonestyTest',
                'MeRezaRezaei\Teleframe\Tests\Console\IngestCommandTest',
                'MeRezaRezaei\Teleframe\Tests\Daemon\DaemonTest',
            ],
        ],
        'openssl-cli' => [
            'suite' => 'TLS peer-name probe — mints a throwaway certificate with the openssl CLI',
            'why' => 'the openssl CLI (or a writable temp dir) is required to mint the test certificate; php:8.4-cli ships /usr/bin/openssl, so this entry normally observes 0 skips and reports the capability as present',
            'classes' => [
                'MeRezaRezaei\Teleframe\Tests\Core\MTProto\Transport\StreamSocketPeerNameTest',
            ],
        ],
        'ext-posix' => [
            'suite' => 'supervisor liveness probe — needs ext-posix to observe a real child',
            'why' => 'SupervisorTest uses posix_kill to observe a real child process. ext-posix is present in php:8.4-cli and is not in the shard job\'s extension list, so this entry normally observes 0; it is declared so a runner without it reports a named condition instead of an unexplained red',
            'classes' => [
                'MeRezaRezaei\Teleframe\Tests\Supervisor\SupervisorTest',
            ],
        ],
        'ext-amqp' => [
            'suite' => 'AMQP upload queue — needs ext-amqp and a RabbitMQ broker',
            'why' => 'ext-amqp is a pecl build the shard job does not perform (measured absent in php:8.4-cli), and there is no RabbitMQ service container. AmqpUploadQueue::assertLoaded() runs in the constructor, so the whole class is unreachable rather than individually gated',
            'classes' => [
                'MeRezaRezaei\Teleframe\Tests\Store\Queue\AmqpUploadQueueDurabilityTest',
                'MeRezaRezaei\Teleframe\Tests\Store\Queue\AmqpUploadQueueTest',
            ],
        ],
        'redis-service' => [
            'suite' => 'live Redis adapter — needs a reachable redis',
            'why' => 'no Redis service container exists in .github/workflows/verify-php.yml, so no redis answers on the configured host',
            'classes' => [
                'MeRezaRezaei\Teleframe\Tests\Bus\LaravelRedisAdapterTest',
                'MeRezaRezaei\Teleframe\Tests\Bus\RedisDoubleDifferentialTest',
            ],
        ],
        'live-telegram' => [
            'suite' => 'live Telegram / vault / warm-lane proofs — need real credentials',
            'why' => 'a live Telegram DC socket, the live vault roundtrip, the Redis-two hot-reload proof and the vault-terminal validation all need TELEGRAM_USER_SESSION + TELEGRAM_API_ID/HASH or an explicit opt-in gate. This repository must not carry those (AGENTS.md: session strings are credentials and .env is never committed), so in CI they skip by design',
            'classes' => [
                'MeRezaRezaei\Teleframe\Laravel\Tests\LiveSessionAdapterTest',
                'MeRezaRezaei\Teleframe\Tests\Live\LiveBackupVaultTest',
                'MeRezaRezaei\Teleframe\Tests\Ingest\HotReloadLiveTest',
                'MeRezaRezaei\Teleframe\Tests\Laravel\Console\VaultTerminalLiveTest',
            ],
        ],
        'pinned-defect' => [
            'suite' => 'pinned known defects — a placeholder that names the fix',
            'why' => 'LiveEndpointPolicyCatalogTest skips ITSELF while a known defect stands and prints the defect and its fix in the skip reason. It is not an environment limit: the fix is to delete the dead allowlist entry or correct it to the real method name. Listed separately so the rollup log keeps naming it',
            'classes' => [
                'MeRezaRezaei\Teleframe\Tests\Teleframe\Warm\LiveEndpointPolicyCatalogTest',
            ],
        ],
        'mysql-live-gate' => [
            'suite' => 'live MySQL MVCC convergence proof — needs a real MySQL 8.4 server',
            'why' => 'sqlite has no MVCC snapshot isolation, so the stale read this test depends on is not expressible on the default engine. tests/MySql is already owned by the mysql-tests job; this file lives under tests/Schema and so is declared here',
            'classes' => [
                'MeRezaRezaei\Teleframe\Tests\Schema\Mirror\RepeatedEntityConvergesTest',
            ],
        ],
        'no-host-application' => [
            'suite' => 'standalone dist-packaging probe — asserts no host application is booted',
            'why' => 'MirrorCatalogDistPackagingTest asserts its paths resolve against the package root and never a booted host application; inside the sharded PHPUnit process an application IS booted, so there is nothing to shadow and the test skips itself',
            'classes' => [
                'MeRezaRezaei\Teleframe\Tests\Schema\MirrorCatalogDistPackagingTest',
            ],
        ],
        'tgweb-bundle' => [
            'suite' => 'tgweb surface — needs the built bundle',
            'why' => 'TelegramWebControllerTest needs the tgweb bundle, and a clean CI checkout has none: the shard job does not run bin/build-tgweb.sh',
            'classes' => [
                'MeRezaRezaei\Teleframe\Tests\Laravel\Http\TelegramWebControllerTest',
            ],
        ],
        'optional-dev-dependency' => [
            'suite' => 'temporal SDK schedule client — needs temporal/sdk',
            'why' => 'temporal/sdk is require-DEV (composer.json:45), so it is present after `composer install` and absent after a --no-dev install. Both states are legitimate, so the skip is declared rather than left to fail the rollup',
            'classes' => [
                'MeRezaRezaei\Teleframe\Tests\Store\SdkTemporalScheduleClientTest',
            ],
        ],
        'live-egress-absent' => [
            'suite' => 'live-mode wire probe — skips only when egress actually succeeded',
            'why' => 'ClientOfflineContractTest skips itself when a live connection to Telegram was ESTABLISHED, because then it cannot assert the offline contract. It is a safety valve on a runner that has egress, not a routine CI condition, and it is declared so that condition is named rather than unexplained',
            'classes' => [
                'MeRezaRezaei\Teleframe\Core\Tests\MTProto\ClientOfflineContractTest',
            ],
        ],
        'mysql-track' => [
            'suite' => 'MySQL track reachability - needs a reachable MySQL 8.4 server',
            'why' => 'MysqlTrackIsReachableTest skips itself when no MySQL server answers on the configured host and TELEFRAME_MYSQL is not set to force it; declared so an unreachable track is a named condition rather than an unexplained red',
            'classes' => [
                'MeRezaRezaei\Teleframe\Tests\MySql\MysqlTrackIsReachableTest',
            ],
        ],
    ];
}

/**
 * @param  array<string, array{suite: string, why: string, classes: list<string>}>  $ownership
 * @return array{tests: int, failures: int, errors: int, skipped: int, files: list<string>, skippedDetail: array<string, array{count: int, suite: ?string, tests: list<string>}>, unattributed: list<string>}
 */
function readReports(string $root, array $ownership): array
{
    $totals = ['tests' => 0, 'failures' => 0, 'errors' => 0, 'skipped' => 0];
    $files = glob($root.'/phpunit-shard-*.xml') ?: [];
    $detail = [];
    $unattributed = [];

    // Invert the ownership list once: FQCN -> suite key.
    $owner = [];
    foreach ($ownership as $key => $entry) {
        foreach ($entry['classes'] as $class) {
            $owner[$class] = $key;
        }
    }

    foreach ($files as $file) {
        $previous = libxml_use_internal_errors(true);
        $document = new DOMDocument;
        $loaded = $document->load($file);
        libxml_use_internal_errors($previous);

        if (! $loaded) {
            fwrite(STDERR, 'WARN  '.$file." is not readable XML — counting it as ZERO tests.\n");

            continue;
        }

        foreach ($document->getElementsByTagName('testcase') as $case) {
            $totals['tests']++;

            // A JUnit <testcase> carries at most one of these, and its presence
            // is the failure. Counting the ELEMENTS rather than trusting an
            // attribute is deliberate: a report that reports its own totals can
            // be wrong in exactly the way this job exists to catch.
            $totals['failures'] += $case->getElementsByTagName('failure')->length;
            $totals['errors'] += $case->getElementsByTagName('error')->length;
            $skippedHere = $case->getElementsByTagName('skipped')->length;
            $totals['skipped'] += $skippedHere;

            if ($skippedHere === 0) {
                continue;
            }

            // The <testcase> is the SAME element for a skip and a pass; the
            // <skipped/> child is the only difference, which is why this has to
            // be a separate question rather than an arithmetic afterthought.
            $class = (string) ($case->getAttribute('class') ?: $case->getAttribute('classname'));
            $method = (string) ($case->getAttribute('name') ?: '(unnamed)');
            $key = $owner[$class] ?? null;

            if ($key === null) {
                $unattributed[] = $class.'::'.$method;

                continue;
            }

            $detail[$key]['count'] = ($detail[$key]['count'] ?? 0) + 1;
            $detail[$key]['suite'] = $ownership[$key]['suite'];
            $detail[$key]['tests'][] = $class.'::'.$method;
        }
    }

    ksort($detail);

    $totals['files'] = array_map('basename', $files);
    sort($totals['files']);
    $totals['skippedDetail'] = $detail;
    $totals['unattributed'] = $unattributed;

    return $totals;
}

/**
 * How many tests the committed configuration collects. Collection only, no
 * execution, so this is cheap and safe on a red tree.
 *
 * @param  list<string>  $ownedElsewhere  areas NOT run by shards (see below)
 * @return array{total: int, ownedElsewhere: int}
 */
function collectableTests(string $root, array $ownedElsewhere): array
{
    $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
    $process = proc_open(
        [PHP_BINARY, '-d', 'memory_limit=1G', $root.'/vendor/bin/phpunit', '--no-coverage', '--list-tests'],
        $descriptors,
        $pipes,
        $root,
    );

    if (! is_resource($process)) {
        fwrite(STDERR, "FATAL: could not list the suite's tests — refusing to certify a count I could not obtain.\n");

        exit(EXIT_USAGE);
    }

    $stdout = (string) stream_get_contents($pipes[1]);
    $stderr = (string) stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $status = proc_close($process);

    if ($status !== 0) {
        fwrite(STDERR, "FATAL: phpunit --list-tests exited {$status}: ".trim($stderr)."\n");

        exit(EXIT_USAGE);
    }

    $all = [];
    $owned = 0;

    foreach (explode("\n", $stdout) as $line) {
        if (! str_starts_with(rtrim($line, "\r"), ' - ')) {
            continue;
        }

        $test = trim(substr(rtrim($line, "\r"), 3));

        if ($test === '') {
            continue;
        }

        $all[] = $test;

        foreach ($ownedElsewhere as $path) {
            if (str_contains($test, $path)) {
                $owned++;

                break;
            }
        }
    }

    // The shards are responsible for the whole suite MINUS the areas declared
    // `owned_elsewhere` in ci/phpunit-shards.json, which belong to the
    // mysql-tests / pg-tests jobs (they need a real engine service). Comparing
    // the shard count against the UNADJUSTED total would make this job fail by
    // exactly the number of owned tests on every pipeline, for ever — a gate
    // that is always red teaches people to ignore it, which is worse than no
    // gate. Those tests are not forgotten: bin/verify-shard-partition.php
    // checks that the job this one names really runs them.
    return ['total' => count($all), 'ownedElsewhere' => $owned];
}

/**
 * The `owned_elsewhere` paths, read from the shard config.
 *
 * @return list<string>
 */
function ownedElsewherePaths(string $root): array
{
    $path = $root.'/ci/phpunit-shards.json';

    if (! is_file($path)) {
        return [];
    }

    $config = json_decode((string) file_get_contents($path), true);
    $paths = [];

    foreach ((array) (is_array($config) ? ($config['owned_elsewhere'] ?? []) : []) as $entry) {
        foreach ((array) ($entry['paths'] ?? []) as $relative) {
            // Match on the namespace, which is what --list-tests prints:
            // `tests/MySql` -> `MeRezaRezaei\Teleframe\Tests\MySql\`.
            $relative = (string) $relative;
            $namespace = str_replace('/', '\\', trim($relative, '/'));

            if (str_starts_with($namespace, 'tests')) {
                $namespace = substr($namespace, strlen('tests'));
                $paths[] = 'Tests'.str_replace('\\', '\\', $namespace);
            }
        }
    }

    return $paths;
}

$ownedElsewhere = ownedElsewherePaths($root);
$collectable = collectableTests($root, $ownedElsewhere);
$expected = $collectable['total'] - $collectable['ownedElsewhere'];
$ownership = skippedElsewhere();
$reports = readReports($root, $ownership);

// ── THE COUNT OF TESTS THAT ACTUALLY PRODUCED A VERDICT ─────────────────────
// `$reports['tests']` counts <testcase> ELEMENTS, and PHPUnit's JUnit logger emits
// a full element for a skipped test with a <skipped/> child — so at the XML layer a
// skip and a pass are the same shape. `$ran` is therefore the honest number, and it
// is the one the headline below prints: a skipped test produced NO verdict, so it
// did not run, and calling it ran is the whole defect this section fixes.
$ran = $reports['tests'] - $reports['skipped'];
$attributedSkips = 0;
foreach ($reports['skippedDetail'] as $entry) {
    $attributedSkips += $entry['count'];
}
$unattributedSkips = count($reports['unattributed']);
// What the shards are answerable for: every collected test the shards own, minus
// the ones declared as skips-with-an-owner. Printed next to $ran so the two numbers
// are visibly different quantities and not two spellings of one.
$expectedRan = $expected - $attributedSkips;

fwrite(STDOUT, "=== CI SHARD ROLLUP ===\n\n");
fwrite(STDOUT, sprintf("  shard reports found : %d\n", count($reports['files'])));
fwrite(STDOUT, sprintf("  tests the suite collects : %d\n", $collectable['total']));
fwrite(STDOUT, sprintf("  of those, owned by other jobs (not sharded) : %d\n", $collectable['ownedElsewhere']));
fwrite(STDOUT, sprintf("  so the shards are responsible for : %d\n", $expected));
fwrite(STDOUT, sprintf("  tests the shards REPORTED : %d\n", $reports['tests']));
fwrite(STDOUT, sprintf("  of those, skipped (no verdict produced) : %d\n", $reports['skipped']));
fwrite(STDOUT, sprintf("  of those, skipped with a DECLARED owner : %d\n", $attributedSkips));
fwrite(STDOUT, sprintf("  tests the shards RAN     : %d   (of %d answerable)\n", $ran, $expectedRan));
fwrite(STDOUT, sprintf("  failures %d · errors %d · unattributed skips %d\n\n", $reports['failures'], $reports['errors'], $unattributedSkips));

foreach ($reports['files'] as $file) {
    fwrite(STDOUT, '    '.$file."\n");
}

fwrite(STDOUT, "\n");

// ── Say what skipped, and who owns it. Never a bare number. ─────────────────
// A count of 16 skips with no names is indistinguishable from a count of 16 tests
// somebody quietly switched off. Each declared suite is printed with its owner
// string and its why, so a reader of the CI log learns both WHICH tests produced no
// verdict and WHICH lane owns them, without opening this file.
if ($reports['skippedDetail'] !== []) {
    fwrite(STDOUT, "  SKIPPED — declared, with an owner:\n");

    foreach ($reports['skippedDetail'] as $key => $entry) {
        fwrite(STDOUT, sprintf("    %-4d %s\n", $entry['count'], (string) $entry['suite']));
        fwrite(STDOUT, sprintf("          owner: %s\n", $key));
        fwrite(STDOUT, sprintf("          why:    %s\n", $ownership[$key]['why']));

        foreach ($entry['tests'] as $test) {
            fwrite(STDOUT, '            - '.$test."\n");
        }
    }

    fwrite(STDOUT, "\n");
}

// A declared owner that observed NOTHING is reported, not failed. It is NOT
// evidence that the capability was present — the guard may simply not have been
// reached — so it is stated as what was measured (observed nothing) and no more.
// Reported as ONE line rather than a block per entry, because on a normal run most
// of the list observes nothing and a block each would bury the numbers above.
$observed = array_keys($reports['skippedDetail']);
$idle = array_values(array_diff(array_keys($ownership), $observed));
if ($idle !== []) {
    fwrite(STDOUT, sprintf(
        "  note: %d declared skip owner(s) skipped nothing this run: %s\n"
        ."        Not a failure, and not proof the capability was present — the guard may\n"
        ."        simply not have been reached. Listed so a stale entry is visible; drop one\n"
        ."        from skippedElsewhere() when its suite can no longer skip.\n\n",
        count($idle),
        implode(', ', $idle),
    ));
}

fwrite(STDOUT, "\n");

// ── No reports at all is the failure this whole job exists to catch ──────────
if ($reports['files'] === []) {
    fwrite(STDERR, "FAIL  no shard published a JUnit report.\n"
        ."      Every shard can be green while running nothing — six jobs that each\n"
        ."      collected zero tests is a green pipeline that proved nothing.\n");

    exit(EXIT_SHORT);
}

if ($expected === 0) {
    fwrite(STDERR, "FATAL: the suite collects ZERO tests, so any count would 'match'. Refusing to certify that.\n");

    exit(EXIT_USAGE);
}

// ── THE SKIP GATE, and it is the gate the subtraction depends on ────────────
// `$ran === $expectedRan` is arithmetically identical to `tests === expected`, so
// it cannot catch a skip on its own and is not claimed to. What catches one is the
// OWNERSHIP CHECK below: a skipped testcase whose class is not in skippedElsewhere()
// is a test that produced no verdict and that nobody has claimed, and it fails here
// naming class::method. That is what makes the honest arithmetic trustworthy — the
// subtracted term is a closed, reviewed set, so `ran` cannot be quietly inflated by
// converting failures or coverage into skips.
//
// This is deliberately NOT "subtract every skip and compare". Doing that would make
// the rollup permanently red, because this repository legitimately skips what no CI
// job can run — and a permanently red gate is one people learn to ignore, which is
// strictly worse than a known hole. The declared list is the price of honesty here,
// and it is checked on every run rather than trusted.
if ($unattributedSkips > 0) {
    fwrite(STDERR, sprintf(
        "FAIL  %d test(s) skipped with NO declared owner in skippedElsewhere().\n"
        ."      A skip produces no verdict, so this rollup will not certify it as a test\n"
        ."      that ran. Each one below is either a test this job cannot reach — in which\n"
        ."      case it belongs in the list, with its suite and why — or a test that started\n"
        ."      skipping, which is a finding in its own right.\n"
        ."      Do NOT fix this by adding these classes to the list reflexively: read each\n"
        ."      skip reason first, and if it is a live gate the shard job could actually\n"
        ."      satisfy (a service container, an extension install), fix the job instead.\n",
        $unattributedSkips,
    ));

    foreach ($reports['unattributed'] as $test) {
        fwrite(STDERR, '        - '.$test."\n");
    }

    exit(EXIT_SHORT);
}

// ── The count must MATCH, not merely be non-zero ────────────────────────────
// `>=` would be the tempting comparison and it is the wrong one: a suite that
// grew by 200 tests would keep passing a `>=` check while the shards ran none of
// them. A shard list that has drifted behind the suite must FAIL, because the
// moment it stops matching, the new tests are decoration again — which is how 355
// tests once sat in this repository unregistered and green.
if ($reports['tests'] !== $expected) {
    fwrite(STDERR, sprintf(
        "FAIL  the shards reported %d tests; the suite collects %d. Short by %d.\n"
        ."      A shortfall here means tests exist that no shard executed. That is invisible\n"
        ."      everywhere else in the pipeline, and it is worse than a slow job.\n"
        ."      Re-run bin/verify-shard-partition.php to see whether the shard list has drifted.\n",
        $reports['tests'],
        $expected,
        $expected - $reports['tests'],
    ));

    exit(EXIT_SHORT);
}

fwrite(STDOUT, sprintf(
    "OK  the shards together reported every test the suite collects; %d of them RAN\n"
    ."    and %d skipped with a declared owner (named above).\n\n",
    $ran,
    $attributedSkips,
));

// ── The honest part: say what the shards found, out loud ────────────────────
if ($reports['failures'] > 0 || $reports['errors'] > 0) {
    fwrite(STDOUT, sprintf(
        "NOTE  %d failure(s) and %d error(s) across the shards. Those are REAL and belong to the\n"
        ."      lanes that own them — reported here, never smoothed over, never skipped to make\n"
        ."      the sharding look successful. Each shard's own exit code is the pipeline verdict.\n\n",
        $reports['failures'],
        $reports['errors'],
    ));
}

exit(EXIT_OK);

<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Concerns;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * THE CI SHARDS ARE A TOTAL PARTITION, AND THE PIPELINE ACTUALLY RUNS THEM.
 *
 * `bin/verify-shard-partition.php` proves the arithmetic: every test the
 * committed phpunit.xml.dist collects is run by exactly one shard in
 * ci/phpunit-shards.json. On its own that is not enough, and this file exists
 * because of the gap it leaves.
 *
 * A total partition proves the shards COVER the suite. It says nothing about
 * whether the PIPELINE RUNS them. Those are different claims, and the second has
 * its own silent failure: a shard declared in the JSON and absent from the CI
 * matrix covers tests that nothing executes. The pipeline is green, the partition
 * proof is green, and the tests nobody ran are invisible to both. So this file
 * asserts the WIRING, not the arithmetic — and it reads the wiring out of the
 * real YAML rather than trusting that a future edit kept it.
 *
 * THE FIVE CLAIMS
 *
 *   1. THE PARTITION IS TOTAL AND DISJOINT. Delegates to the same script CI runs,
 *      so there is ONE implementation of the proof rather than two that can
 *      disagree. Fails on a dropped test, a duplicated test, a path that does not
 *      exist, or an empty shard.
 *
 *   2. THE CI MATRIX RUNS EXACTLY THE SHARDS THE CONFIG DECLARES. Read from the
 *      real matrix — GitLab's `parallel.matrix`, Actions' `strategy.matrix` — so
 *      no more, no fewer: a shard in the JSON with no matrix entry is a suite
 *      that exists and never executes, and a matrix entry with no shard is a job
 *      that fails on a name that does not resolve.
 *
 *   3. NO SHARD IS PERMITTED TO FAIL QUIETLY. By default any failed job reds the
 *      pipeline, and `allow_failure: true` (GitLab) / `continue-on-error: true`
 *      (Actions, on the job OR on any single step) are the keywords that turn a
 *      red shard into a green pipeline. `exit_codes` is the same escape with more
 *      steps. So is a fail-fast matrix, which cancels the sibling shards and
 *      destroys the very reports the rollup exists to count. All are banned by
 *      assertion rather than by convention.
 *
 *   4. THE SHARDS REALLY ARE INVOKED, AND THE ROLLUP REALLY COUNTS THEM. A shard
 *      job that stopped calling bin/run-phpunit-shard.php would collect nothing
 *      and still be green, and so would a rollup that stopped merging the shard
 *      reports. Both are asserted, because "the job exists" is not "the job
 *      checks anything" — the failure this repository has shipped twice.
 *
 *   5. THE ROLLUPS RUNS ON A RED PIPELINE. The rollup's job is to say "the
 *      shards together ran N of N tests", and on a red pipeline that sentence is
 *      the most useful thing in the log.
 *
 * WHY THE CI IS READ DIALECT-BLIND, AND WHY IT IS NOT THIS FILE'S VOCABULARY
 * -------------------------------------------------------------------------
 * This file used to parse exactly one path, `.gitlab-ci.yml`, and look for
 * GitLab keys that stopped existing when commit ddbdef18 moved CI to GitHub
 * Actions. The result was four failures here — and, worse, the one assertion
 * that would have caught them (`assertNotSame([], $inMatrix, …)`) firing on an
 * EMPTY read. A gate that finds nothing and asserts nothing has not passed; it
 * has stopped looking, and it looks exactly like a pass.
 *
 * So the CI surface is DISCOVERED (`.github/workflows/*.{yml,yaml}` plus
 * `.gitlab-ci.yml`, whichever exist) and both dialects are flattened onto one
 * shape, and that vocabulary lives in bin/ci-definitions.php — the same file
 * bin/verify-shard-partition.php reads. One vocabulary, two claimants. A second
 * copy is a second thing to forget at the next CI move, and the last move is the
 * proof that this is not a hypothetical cost.
 *
 * The claims below are deliberately NOT weakened for the new dialect. Each one
 * that GitLab expressed with a single keyword is now asserted against EVERY
 * spelling its dialect offers, and one of them (`fail-fast`) is asserted against
 * its own safe default, because Actions defaults it to the dangerous value.
 *
 * This is a PHPUnit test rather than only a CI script on purpose: it lives in the
 * suite, so a broken partition fails the same run that was supposed to prove it,
 * on a developer's machine as well as on the runner.
 */
final class ShardPartitionTest extends TestCase
{
    /**
     * bin/ is not on Composer's autoload path — it holds runnable gates, not a
     * library — so the shared CI-dialect reader is loaded explicitly. It is the
     * same file bin/verify-shard-partition.php loads.
     */
    private static bool $readerLoaded = false;

    public static function setUpBeforeClass(): void
    {
        if (self::$readerLoaded) {
            return;
        }

        require_once dirname(__DIR__, 2).'/bin/ci-definitions.php';
        self::$readerLoaded = true;
    }

    private const CONFIG = 'ci/phpunit-shards.json';

    /** The job whose matrix enumerates the shards. */
    private const SHARD_JOB = 'verify-shards';

    private const RUNNER = 'bin/run-phpunit-shard.php';

    private const PROOF = 'bin/verify-shard-partition.php';

    private const ROLLUP = 'verify-shard-rollup';

    #[Test]
    public function the_shards_are_a_total_and_disjoint_partition_of_the_suite(): void
    {
        $root = dirname(__DIR__, 2);

        $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $process = proc_open(
            [PHP_BINARY, $root.'/'.self::PROOF],
            $descriptors,
            $pipes,
            $root,
        );

        self::assertIsResource($process, 'the partition proof must be runnable — it is the gate for the gates');

        $stdout = (string) stream_get_contents($pipes[1]);
        $stderr = (string) stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $status = proc_close($process);

        self::assertSame(
            0,
            $status,
            'the CI shards do not partition the suite — a dropped test is worse than a slow job, because a '
            .'slow job is obviously slow:'.PHP_EOL.$stderr.$stdout,
        );
    }

    #[Test]
    public function the_ci_matrix_runs_exactly_the_shards_the_config_declares(): void
    {
        $declared = $this->declaredShardNames();
        $inMatrix = $this->shardNamesInMatrix();

        $missingFromCi = array_values(array_diff($declared, $inMatrix));
        $missingFromConfig = array_values(array_diff($inMatrix, $declared));

        self::assertSame(
            [],
            $missingFromCi,
            'these shards are declared in '.self::CONFIG.' but have NO entry in the `'.self::SHARD_JOB
            .'` matrix, so they cover tests that nothing runs: '.implode(', ', $missingFromCi),
        );

        self::assertSame(
            [],
            $missingFromConfig,
            'the `'.self::SHARD_JOB.'` matrix runs shards that '.self::CONFIG.' does not declare, so those '
            .'jobs fail on a name that does not resolve: '.implode(', ', $missingFromConfig),
        );
    }

    #[Test]
    public function no_shard_is_permitted_to_fail_without_failing_the_pipeline(): void
    {
        $jobs = $this->jobDefinitions(self::SHARD_JOB);

        foreach ($jobs as $found) {
            $where = sprintf('`%s` in %s', self::SHARD_JOB, $found['path']);

            foreach (\ciJobSoftFailures($found['job']) as $escape) {
                self::fail(sprintf(
                    '%s: %s. That is how a red shard becomes a green pipeline — the "weakening an assertion '
                    .'to reach green" failure wearing a CI costume. Remove it, or record in the commit '
                    .'message why this shard is allowed to lie.',
                    $where,
                    $escape,
                ));
            }

            // A shard job decides the suite verdict, so it must run when the
            // pipeline runs — otherwise it contributes nothing to a red pipeline
            // and cannot contribute one to a green pipeline either.
            $condition = \ciJobCondition($found['job']);

            self::assertNotContains(
                $condition,
                ['always', 'onfailure', 'failure'],
                $where.' is conditioned `'.($condition ?? 'null').'`. A shard job decides the suite verdict, '
                .'so it must run when the pipeline runs — otherwise it contributes nothing to a red pipeline '
                .'and cannot contribute one to a green pipeline either.',
            );

            // Every shard carries its own timeout; a job with no timeout inherits
            // the runner default, which on a shared runner is "forever". That is
            // how the shard that cannot finish becomes the shard that never
            // reports — the exact failure this change was made to end.
            self::assertNotNull(
                \ciJobTimeout($found['job']),
                $where.' has no timeout, so a shard that hangs holds a runner slot until the runner default '
                .'kills it. An unbounded shard is a shard that cannot say red.',
            );

            // A fail-fast matrix cancels its siblings, destroying exactly the
            // shard reports the rollup counts. Absence is NOT a pass: the
            // Actions default is the dangerous value, so a matrix that never
            // mentions `fail-fast` is reported too.
            $failsFast = \ciJobFailsFast($found['job']);

            if ($failsFast !== null) {
                self::assertFalse(
                    $failsFast,
                    $where.' runs a fail-fast matrix. The first failing leg cancels the siblings, so the '
                    .'reports that survive describe fewer shards than the matrix declared. The rollup then '
                    .'counts a partial run and the pipeline is red for a reason that looks like flakiness. '
                    .'Set `fail-fast: false`.',
                );
            }
        }
    }

    #[Test]
    public function the_shard_job_really_invokes_the_runner_and_publishes_its_report(): void
    {
        $root = dirname(__DIR__, 2);

        self::assertFileExists($root.'/'.self::RUNNER, 'the shard runner is what turns a shard name into a PHPUnit invocation');
        self::assertFileExists($root.'/'.self::PROOF, 'the partition proof must exist as a runnable script');

        foreach ($this->jobDefinitions(self::SHARD_JOB) as $found) {
            $where = sprintf('`%s` in %s', self::SHARD_JOB, $found['path']);

            self::assertStringContainsString(
                self::RUNNER,
                $found['commands'],
                $where.' never invokes '.self::RUNNER.', so every shard job it creates would collect '
                .'nothing, exit 0, and be green. A job that runs no tests is not a passing job.',
            );

            // Without a report published on EVERY outcome the rollup has nothing
            // to merge, and the one check that catches a shard silently running
            // zero tests goes with it. GitLab uploads on `on_success` by default
            // and Actions skips an unguarded step after an earlier failure — so
            // both dialects lose the failing shard's report without being asked.
            self::assertTrue(
                \ciJobPublishesAlways($found['job']),
                $where.' publishes its JUnit report only on success, so '.self::ROLLUP.' cannot merge it and '
                .'the "did the shards actually run the suite" check has no data to work on. The default '
                .'(`on_success` / an unguarded upload step) uploads nothing from a RED shard — so the rollup, '
                .'which is the job that would notice a shard ran too few tests, would be blind to exactly the '
                .'shard that needs looking at.',
            );
        }
    }

    #[Test]
    public function the_rollup_job_merges_the_shard_reports_and_runs_even_when_a_shard_is_red(): void
    {
        $rollups = $this->jobDefinitions(self::ROLLUP);

        self::assertNotSame([], $rollups, self::ROLLUP.' must exist: it is the one job that states the suite verdict in one place');

        foreach ($rollups as $found) {
            $where = sprintf('`%s` in %s', self::ROLLUP, $found['path']);

            // `when: always` (GitLab) / `if: always()` (Actions) is REQUIRED here
            // and is the one place it belongs: the rollup's job is to say "the
            // shards together ran N of N tests", and on a red pipeline that
            // sentence is the most useful thing in the log. It does not mask the
            // shard's own red verdict — that verdict is the pipeline status, and
            // claim 3 above forbids this file from softening it.
            self::assertSame(
                'always',
                \ciJobCondition($found['job']),
                $where.' must run unconditionally (`when: always` / `if: always()`). Without it the rollup is '
                .'skipped on a red pipeline — which is precisely when the operator most needs to know whether '
                .'the shards covered the suite.',
            );

            self::assertContains(
                self::SHARD_JOB,
                \ciJobNeeds($found['job']),
                $where.' does not depend on '.self::SHARD_JOB.', so it cannot be reporting on the shards.',
            );

            // A dependency that does not FETCH the artifacts orders the two jobs
            // and nothing more, and the rollup would then count reports it never
            // received — which is indistinguishable from "the shards ran nothing".
            self::assertTrue(
                \ciJobDownloadsArtifacts($found['job']),
                $where.' does not fetch '.self::SHARD_JOB.'\'s artifacts, so it has no shard reports to '
                .'merge. It will report on zero shards and that reads exactly like a suite that ran nothing.',
            );

            // And the rollup must actually run the counting script, or the
            // dependency it just proved is decorative.
            self::assertStringContainsString(
                'bin/rollup-shard-reports.php',
                $found['commands'],
                $where.' never invokes bin/rollup-shard-reports.php, so it downloads the shard reports and '
                .'counts nothing. The rollup is the only job that states the suite verdict in one place.',
            );
        }
    }

    /**
     * @return list<string>
     */
    private function declaredShardNames(): array
    {
        $root = dirname(__DIR__, 2);
        $path = $root.'/'.self::CONFIG;

        self::assertFileExists($path);

        $config = json_decode((string) file_get_contents($path), true);

        self::assertIsArray($config, self::CONFIG.' must be a JSON object');
        self::assertArrayHasKey('shards', $config, self::CONFIG.' must declare a "shards" list');

        $names = [];
        foreach ($config['shards'] as $shard) {
            self::assertIsArray($shard);
            self::assertArrayHasKey('name', $shard, 'every shard needs a name');
            $names[] = (string) $shard['name'];
        }

        sort($names);

        return $names;
    }

    /**
     * Every CI definition on disk, parsed in whichever dialect wrote it.
     *
     * A CI move is a fact about the repository, not about this file, so the
     * paths are discovered rather than named. The assertion on the result is
     * the load-bearing part: a reader that finds nothing must FAIL, or the four
     * claims below quietly stop being checked while still reporting success.
     *
     * @return list<array{path: string, dialect: string, jobs: array<string, array>, commands: array<string, string>}>
     */
    private function ciDefinitions(): array
    {
        $definitions = \ciDefinitions(dirname(__DIR__, 2));

        self::assertNotSame(
            [],
            $definitions,
            'no CI definition could be read from .github/workflows/*.{yml,yaml} or .gitlab-ci.yml — without one '
            .'of those, every claim in this file is vacuous. That is the failure this file exists to prevent, '
            .'so it is reported rather than passed over.',
        );

        return $definitions;
    }

    /**
     * Every place the given job id is defined, across every CI definition.
     *
     * A job defined twice — once with an escape hatch on it — is a real hole,
     * and reading only the first definition would report the clean one.
     *
     * @return list<array{path: string, dialect: string, job: array, commands: string}>
     */
    private function jobDefinitions(string $jobId): array
    {
        $found = \ciJobDefinitions($this->ciDefinitions(), $jobId);

        self::assertNotSame(
            [],
            $found,
            sprintf(
                'no CI job named `%s` was found in %s — without it there are no shards, and a claim about a '
                .'job that does not exist is not a claim that passed.',
                $jobId,
                implode(', ', array_column($this->ciDefinitions(), 'path')),
            ),
        );

        return $found;
    }

    /**
     * The shard names the pipeline will actually run, read from the real matrix.
     *
     * GitLab spells it `parallel.matrix[*].SHARD`; Actions spells it
     * `strategy.matrix.shard`. Both are read by ciJobMatrixShardNames(), which
     * is shared with nothing else and exists precisely so the spelling lives in
     * one place.
     *
     * The non-empty assertion is not decoration. This method returned `[]` for
     * every shard after CI moved to Actions, and the test that consumed it
     * passed — because it compared two empty arrays and called that agreement.
     * A gate that finds nothing must say so.
     *
     * @return list<string>
     */
    private function shardNamesInMatrix(): array
    {
        $names = [];

        foreach ($this->jobDefinitions(self::SHARD_JOB) as $found) {
            $names = array_merge($names, \ciJobMatrixShardNames($found['job']));
        }

        $names = array_values(array_unique($names));
        sort($names);

        self::assertNotSame(
            [],
            $names,
            'no shard matrix found for `'.self::SHARD_JOB.'` in any CI definition — neither '
            .'`parallel.matrix[*].SHARD` (GitLab) nor `strategy.matrix.shard` (Actions) yielded a name. The '
            .'parse is wrong, not the repo, and this test will not read an empty matrix as agreement.',
        );

        return $names;
    }
}

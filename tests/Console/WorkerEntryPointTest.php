<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Console;

use MeRezaRezaei\Teleframe\Store\Schedules\Worker\ContainerResolver;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * bin/teleframe-worker — the OPERATOR SURFACE, proved by running the script.
 *
 * THE DEFECT THIS KILLS. This entry point is the only process that turns an
 * armed schedule into something that can run, and it has exactly one safe
 * posture when an operator names no container: hold
 * `ContainerResolver::refusing()` (bin/teleframe-worker:313) and SAY SO on
 * every surface — the help text, `--describe`, the polling banner, and every
 * refusal path. A worker that registers five activity types and can execute
 * NONE of them is the failure mode the whole seam design exists to make
 * visible (ContainerResolver.php:26-27: "must say which seam is missing, not
 * silently no-op on every tick"). The in-process properties of that default are
 * already covered elsewhere: tests/Store/WorkerTickReachabilityTest.php proves
 * a tick refuses, tests/Store/WorkerSeamBindingsTest.php proves the adapter
 * class. This file deliberately covers the OTHER half — that a human running
 * the binary is TOLD, by the binary, in the binary's own words.
 *
 * WHY SUBPROCESS AND NOT A CALL. Every assertion here is on real stdout, real
 * stderr and a real exit code, because the property under test is what the
 * operator SEES: which stream a disclosure landed on (stdout for `--describe`,
 * stderr for a refusal), and whether a misconfiguration exits 0 or 1. A
 * double of `tf_worker_refuse()` would agree with whatever the script does,
 * which is the failure this file is written against. So each case runs
 * `php bin/teleframe-worker` for real, with a `timeout` INSIDE the command
 * string (bin/teleframe-worker is a long-running process by design —
 * bin/teleframe-worker:78-81) plus a wall-clock guard in PHP: a hung worker
 * FAILS this suite rather than hanging it.
 *
 * EVERY INVOCATION CARRIES `--describe` OR `--help`. The bare path (no
 * `--describe`) is the polling one, and it blocks on a Temporal server this
 * repo does not have. It is excluded on purpose; the ONE case that would
 * exercise the polling banner is not attempted here, and saying so beats a
 * flaky test. The banner's wording is therefore unproven by this file — see
 * the honest-limits note on `describe_with_no_flags_…` below.
 *
 * ZERO REGEX, like the script it drives: `str_contains` / `str_starts_with` /
 * `explode` / `trim` only. The repository's ban on PCRE in the engine is a
 * phpstan `disallowedFunctionCalls` rule over `src/`, but matching the script's
 * own idiom here means a future reader never has to wonder which of the two
 * languages this file is written in — and keeps this file's own text honest
 * about the rule it is asserting.
 *
 * HONEST LIMITS.
 *  - This file reads the WORKING TREE, so it is only as stable as
 *    `bin/teleframe-worker` and `TemporalWorker::servedActivities()` are at the
 *    moment it runs. Other agents edit those; a red run here may be their
 *    in-flight work, not a regression.
 *  - It proves disclosure, never execution. A green run means the worker
 *    described itself honestly; it does not mean a tick can run, and the
 *    script says the same in the very sentences asserted below.
 *  - The `--bootstrap=PATH` route is only tested on its REFUSAL paths. Booting
 *    a real Laravel host is bin/prove-host-install.php's job; this file cannot
 *    stand up a host without inventing one, and a hand-rolled fake would prove
 *    the fake.
 *
 * @see bin/teleframe-worker
 * @see src/Teleframe/Store/Schedules/Worker/ContainerResolver.php
 */
final class WorkerEntryPointTest extends TestCase
{
    /** Seconds the `timeout` INSIDE each command string allows. */
    private const INNER_TIMEOUT = 15;

    /** Seconds this file waits for a child before declaring it hung. */
    private const WALL_LIMIT = 25.0;

    /**
     * A seam name, as a literal, on purpose: these are the PUBLIC bind keys
     * this repo promises never to rename (same rule as
     * `teleclient.backfill.scope-resolver`). A rename should break a test that
     * reads them as a wire contract, not silently follow along via a constant.
     */
    private const SHARED_SERVICE_SEAM = 'teleclient.tstore.service';

    /** @var list<string> throwaway files this test created in the system temp dir */
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            @unlink($file);
        }

        $this->tempFiles = [];
    }

    #[Test]
    public function help_exits_zero_and_documents_every_flag(): void
    {
        $run = $this->runWorker('--help');

        self::assertSame(0, $run['exit'], '--help must exit 0; stderr was: '.$run['stderr']);
        self::assertSame('', $run['stderr'], '--help writes to stdout, not stderr.');

        // Every flag the operator can pass, named in the text. A flag the help
        // does not mention is a flag nobody can discover, and this list is
        // the surface's own contract with the person holding the shell.
        foreach ([
            '--task-queue',
            '--app',
            '--call',
            '--bootstrap',
            '--seam-file',
            '--buckets',
            '--describe',
            '--help',
        ] as $flag) {
            self::assertStringContainsString($flag, $run['stdout'], '--help does not document '.$flag.'.');
        }

        // The refusing default, stated in the help text and not only at run
        // time — an operator who reads `--help` and sees a bare flag list has
        // learned nothing about the posture they are about to inherit.
        self::assertStringContainsString(
            'With neither --bootstrap nor --seam-file',
            $run['stdout'],
        );
        self::assertStringContainsString('this worker holds the REFUSING resolver', $run['stdout']);
        self::assertStringContainsString('all five activity types fail on', $run['stdout']);
        self::assertStringContainsString('each refusing by name', $run['stdout']);
        self::assertStringContainsString('Pass EXACTLY ONE of', $run['stdout']);

        // The limit, stated up front: binding a seam is not server dispatch.
        // Asserted in the two halves the operator actually sees, because the
        // heredoc hard-wraps mid-sentence (bin/teleframe-worker:239-240): the
        // newline is IN the text, so a single-line assertion would be asserting
        // a reflow the operator never gets to see.
        self::assertStringContainsString('Binding a seam is not proof a server', $run['stdout']);
        self::assertStringContainsString('dispatches a tick', $run['stdout']);
        self::assertStringContainsString('no live Temporal server', $run['stdout']);
    }

    #[Test]
    public function describe_with_no_flags_says_loudly_that_every_tick_will_fail(): void
    {
        $run = $this->runWorker('--describe');

        self::assertSame(0, $run['exit'], '--describe must exit 0; stderr was: '.$run['stderr']);
        self::assertSame('', $run['stderr']);

        $out = $run['stdout'];

        // THE POSTURE, BY NAME. "No container was given" is the DEFAULT, so it
        // must be impossible to miss: the resolver is named with its fully
        // qualified class and the factory that refused is named with it.
        self::assertStringContainsString('Seam posture: NO CONTAINER WAS GIVEN.', $out);
        self::assertStringContainsString(ContainerResolver::class.'::refusing()', $out);
        self::assertStringContainsString('which binds no factory for any seam', $out);

        // The consequence, in the operator's terms: every activity type, every
        // tick. The COUNT is cross-checked against the list the same run
        // printed, so a sixth activity cannot arrive with a stale "five".
        [$claimed, $names] = $this->activityTypesLine($out);
        self::assertSame(5, $claimed, 'the count in "Activity types served" is the count of activity types.');
        self::assertCount($claimed, $names, 'the printed count disagrees with the printed list.');
        self::assertSame([
            'teleframe.app.run-call',
            'teleframe.notify.check-no-show',
            'teleframe.store.backup-dump',
            'teleframe.store.hot-cache-prune',
            'teleframe.store.registry-sweep',
        ], $names);
        self::assertStringContainsString(
            'Consequence: ALL '.$claimed.' activity types above FAIL ON EVERY TICK.',
            $out,
            'the consequence sentence must count the activities this run actually printed.',
        );
        self::assertStringContainsString('throws naming the seam it needs', $out);
        self::assertStringContainsString('returning null would let a tick report success', $out);

        // THE FIX, as two mutually exclusive routes and no third option.
        self::assertStringContainsString('pass EXACTLY ONE of', $out);
        self::assertStringContainsString('--bootstrap=PATH', $out);
        self::assertStringContainsString('--seam-file=PATH', $out);

        // THE HONESTY ASSERTION. The default must be LOUD, never a quiet
        // success, and the script must not imply it could serve a tick: there
        // is no server here. This is the sentence that stops a green-looking
        // `--describe` from being read as "the worker works".
        self::assertStringContainsString('no live Temporal server', $out);
        self::assertStringNotContainsString(
            'seam is bound',
            $out,
            'with no container flag nothing is bound, so no sentence may claim otherwise.',
        );
    }

    #[Test]
    public function describe_reports_the_sweep_bucket_default_the_operator_asked_for(): void
    {
        $named = $this->runWorker('--describe', '--buckets=alpha, beta ,');

        self::assertSame(0, $named['exit'], 'stderr was: '.$named['stderr']);
        self::assertStringContainsString(
            'Sweep default buckets: alpha, beta.',
            $named['stdout'],
            'the list must be comma-split, each element trimmed, empty elements dropped.',
        );
        // The trailing empty element of "alpha, beta ," must not survive as a
        // gap in the list, and neither must an interior one.
        self::assertStringNotContainsString('beta ,', $named['stdout']);
        self::assertStringNotContainsString(', ,', $named['stdout']);
        self::assertStringNotContainsString(', .', $named['stdout']);

        // A FALLBACK, and the script says so: a payload naming its own buckets
        // wins, which is the only reason a worker-level default is safe to set.
        self::assertStringContainsString(
            'a payload that names buckets uses its own instead',
            $named['stdout'],
        );

        // And with no flag the answer is the COMMAND's own empty default, named
        // in the command's own terms, so the operator can match it against what
        // `teleframe:tstore sync` does with no --buckets.
        $none = $this->runWorker('--describe');

        self::assertSame(0, $none['exit'], 'stderr was: '.$none['stderr']);
        self::assertStringContainsString('Sweep default buckets: none.', $none['stdout']);
        self::assertStringContainsString('sweeps whatever the dialog walk finds', $none['stdout']);
        self::assertStringContainsString(
            'running php artisan teleframe:tstore sync with no --buckets',
            $none['stdout'],
        );
        self::assertStringNotContainsString('Sweep default buckets: alpha', $none['stdout']);
    }

    #[Test]
    public function a_missing_bootstrap_path_is_a_named_refusal_not_a_silent_fallback(): void
    {
        $missing = '/nonexistent/path/xyz';
        $run = $this->runWorker('--describe', '--bootstrap='.$missing);

        self::assertSame(1, $run['exit'], 'a path that resolves to nothing must exit 1, not 0.');
        self::assertSame('', $run['stdout'], 'a refusal must not print a describe report.');

        $err = $run['stderr'];

        // Named, with the path the operator typed AND the path that was
        // actually looked for — the second is the useful one when the mistake
        // was a host directory with no bootstrap/app.php in it.
        self::assertStringContainsString($missing, $err);
        self::assertStringContainsString('does not resolve to a readable PHP file', $err);
        self::assertStringContainsString('Expected '.$missing, $err);
        self::assertStringContainsString('or the bootstrap/app.php inside it', $err);

        // The consequence, so the operator does not read "it started anyway".
        self::assertStringContainsString('No seam is bound and every activity tick will fail.', $err);

        // It must NOT look like a successful describe, on either stream.
        self::assertStringNotContainsString('Seam posture', $err);
        self::assertStringNotContainsString('Activity types served', $run['stdout']);
    }

    #[Test]
    public function giving_both_container_routes_is_a_named_refusal(): void
    {
        // Both paths are nonexistent ON PURPOSE. The mutual-exclusion check sits
        // at bin/teleframe-worker:290 and both path resolutions sit below it
        // (:315 and :377), so which refusal comes back is itself the assertion:
        // if someone ever moves the check, this test sees the resolution
        // refusal instead and names the real defect.
        $bootstrap = '/nonexistent/bootstrap';
        $seamFile = '/nonexistent/seams.php';
        $run = $this->runWorker('--describe', '--bootstrap='.$bootstrap, '--seam-file='.$seamFile);

        self::assertSame(1, $run['exit'], 'two answers to one question must exit 1.');
        self::assertSame('', $run['stdout']);

        $err = $run['stderr'];

        self::assertStringContainsString('--bootstrap='.$bootstrap, $err);
        self::assertStringContainsString('--seam-file='.$seamFile, $err);
        self::assertStringContainsString('were BOTH given', $err);
        self::assertStringContainsString('exactly one may be passed', $err);
        self::assertStringContainsString('Nothing has been bound and no worker has started.', $err);

        // The mutual-exclusion refusal, NOT a path-resolution one: this is the
        // ordering the whole case turns on.
        self::assertStringNotContainsString('does not resolve to a readable PHP file', $err);
        self::assertStringNotContainsString('is not a readable PHP file', $err);
    }

    #[Test]
    public function a_seam_file_that_returns_something_else_is_refused_rather_than_downgraded(): void
    {
        $cases = [
            // label => [body, the type the refusal must NAME, the sentence naming the expected class]
            'int' => ['return 42;', 'returned int', 'it must RETURN a '],
            'object' => ['return new stdClass;', 'returned stdClass', 'it must RETURN a '],
            // The THROW branch used to be the odd one out: it named the
            // throwable and never the expected type, so a host author whose seam
            // file explodes — the operator who most needs the pointer — got
            // "threw RuntimeException" and nothing else. bin/teleframe-worker
            // now names ContainerResolver in that branch too, and the asymmetry
            // is gone; all three cases assert the same sentence.
            'throws' => [
                'throw new RuntimeException(\'host wiring exploded\');',
                'threw RuntimeException',
                'The file must RETURN a ',
            ],
        ];

        foreach ($cases as $label => [$body, $namesTheType, $expectedClassSentence]) {
            $file = $this->writeTempPhp($body);
            $run = $this->runWorker('--describe', '--seam-file='.$file);

            self::assertSame(1, $run['exit'], 'a '.$label.' seam file must exit 1.');
            self::assertSame('', $run['stdout'], 'a '.$label.' seam file must print no describe report.');

            $err = $run['stderr'];

            // The actual value, by debug type. "Something else" is useless to an
            // operator; "returned stdClass" is a one-line fix.
            self::assertStringContainsString($namesTheType, $err, 'the '.$label.' refusal must name what came back.');

            // And the downgrade that must NOT have happened, said out loud. The
            // whole point of refusing here: falling back to the refusing
            // resolver would start a worker that reports itself as configured
            // while failing every tick (bin/teleframe-worker:388-394).
            self::assertStringContainsString(
                'This is NOT downgraded to the refusing resolver',
                $err,
                'the '.$label.' refusal must say it did not quietly fall back.',
            );
            self::assertStringContainsString('quietly does nothing', $err);

            // Every branch names the expected class as well as what came back.
            // A host author whose seam file explodes is the operator who most
            // needs the pointer, so that case is not allowed to be the one that
            // only names the throwable.
            self::assertStringContainsString(
                $expectedClassSentence.ContainerResolver::class,
                $err,
                'the '.$label.' refusal must name the expected resolver class.',
            );
        }
    }

    #[Test]
    public function a_seam_file_returning_a_real_resolver_is_accepted_and_reported(): void
    {
        // The POSITIVE case, and the reason --seam-file is more than a refusal
        // factory: a resolver that came from a file must be reported as bound
        // seams, with the complement named so the operator knows what is still
        // unreachable. The bound object is a bare stdClass on purpose — the
        // script type-checks the RESOLVER, not the collaborator, and
        // ContainerResolver::resolve() is never called by --describe.
        //
        // The class goes in as a BARE name, not a quoted one: `'Foo'::bar()`
        // is legal PHP but `new 'Foo'` is a ParseError, and the difference is
        // exactly the kind of thing a hand-written seam file gets wrong.
        $file = $this->writeTempPhp(sprintf(
            'return (new %1$s)->bind(%2$s, fn (): object => new stdClass);',
            '\\'.ContainerResolver::class,
            var_export(self::SHARED_SERVICE_SEAM, true),
        ));

        $run = $this->runWorker('--describe', '--seam-file='.$file);

        self::assertSame(0, $run['exit'], 'a real resolver must be accepted; stderr was: '.$run['stderr']);
        self::assertSame('', $run['stderr']);

        $out = $run['stdout'];

        // The route taken, named: no Laravel container, a hand-over instead.
        self::assertStringContainsString('Seam posture: NO LARAVEL CONTAINER', $out);
        self::assertStringContainsString('the resolver was handed over by --seam-file='.$file, $out);

        // What is bound, counted AND named. A count without a list (or the
        // reverse) is the report this file exists to prevent.
        self::assertStringContainsString(
            'Seams that resolver bound (1): '.self::SHARED_SERVICE_SEAM,
            $out,
        );

        // And the complement, from the real seam catalog, each with its own
        // refusal sentence: four seams remain, and a tick for each still fails
        // by name. Prune and sync share the service seam, so the catalog is
        // five seams across four activities plus the backup target.
        self::assertStringContainsString(
            'Seams NOT bound (4): teleclient.app.service, teleclient.notify.watchdog, '
            .'teleclient.store.backup.service, teleclient.store.backup.target',
            $out,
        );
        self::assertStringContainsString(
            '"teleclient.app.service" is unbound: an activity tick that needs it will REFUSE BY NAME',
            $out,
        );

        // Still honest: a bound seam is not a dispatched tick.
        self::assertStringContainsString(
            'Binding a seam is NOT proof a server dispatches a tick',
            $out,
        );
        self::assertStringContainsString('no live Temporal server', $out);
    }

    /**
     * The no-route disclosure must be DERIVED FROM THE RESOLVER, never printed
     * regardless of it.
     *
     * FALSIFICATION RECORD. This test exists because the first version of the
     * report did the opposite and the gap was found by trying to break it, not
     * by reading it: the no-route branch was reached on `$route === null`
     * alone, so it printed "binds no factory for any seam" and "ALL 5 activity
     * types … FAIL ON EVERY TICK" over whatever resolver the worker actually
     * held. Editing the entry point's default to bind a seam — one line, the
     * kind of edit a future slice makes when adding a fallback — left every one
     * of this file's tests GREEN while the operator-facing report stated the
     * opposite of the worker's behaviour.
     *
     * A disclosure that cannot disagree with the code is not a disclosure. The
     * assertion below is therefore about the two SENTENCES being
     * conditional: with the real default the refusing prose appears, and the
     * prose that asserts a bound seam must not.
     */
    #[Test]
    public function the_no_route_disclosure_is_conditional_on_the_resolver_really_being_empty(): void
    {
        $run = $this->runWorker('--describe');

        self::assertSame(0, $run['exit'], 'stderr was: '.$run['stderr']);

        $out = $run['stdout'];

        // The real default: refusing, and said so.
        self::assertStringContainsString('NO CONTAINER WAS GIVEN.', $out);
        self::assertStringContainsString('which binds no factory for any seam', $out);

        // The guard branch must be INERT for the real default — it describes a
        // state this entry point cannot be in, and it must not leak into the
        // output when it is not the state.
        self::assertStringNotContainsString('NO CONTAINER ROUTE WAS GIVEN, BUT THE RESOLVER HAS', $out,
            'the guard branch fired against the refusing default, so its conditions are wrong');
        self::assertStringNotContainsString('Treat the sentence below as unverified', $out,
            'the guard branch fired against the refusing default');

        // The negative space: nothing anywhere claims a seam is bound. This is
        // the assertion that would have caught the falsified default, because a
        // bound seam prints its name on the "bound:" line.
        self::assertStringNotContainsString('BOUND:', $out,
            'the refusing default binds nothing, so no line may name a bound seam');
        self::assertStringNotContainsString('container seam(s) bound', $out);
    }

    #[Test]
    public function a_seam_file_with_no_seams_bound_says_the_route_was_accepted_and_serves_nothing(): void
    {
        // A resolver WAS handed over, and it serves NOTHING. The operator must
        // not be able to read "I passed --seam-file" as "the worker can run a
        // tick" — so every tick still fails, naming its seam.
        //
        // It must ALSO not read the other way, as it did before this was fixed:
        // an earlier version branched on the resolver being empty rather than on
        // whether a route was passed, so this case printed "NO CONTAINER WAS
        // GIVEN" — byte-identical to passing no flag at all — and erased the one
        // fact the operator ran --describe to confirm. The route label is
        // therefore part of what is asserted here.
        $file = $this->writeTempPhp(sprintf(
            'return %s::refusing();',
            '\\'.ContainerResolver::class,
        ));

        $run = $this->runWorker('--describe', '--seam-file='.$file);

        self::assertSame(0, $run['exit'], 'an empty resolver is legal input; stderr was: '.$run['stderr']);
        self::assertSame('', $run['stderr']);

        $out = $run['stdout'];

        // The route is named, and the file that carried it.
        self::assertStringContainsString('the resolver was handed over by --seam-file='.$file, $out);
        self::assertStringNotContainsString('NO CONTAINER WAS GIVEN.', $out,
            'a route WAS given; reporting it as absent would tell the operator their flag was ignored');
        self::assertStringNotContainsString('which binds no factory for any seam', $out,
            'that sentence describes the DEFAULT refusing posture, which is not the posture this worker is in');

        // The bound count is stated as NONE, not as an empty list that reads as
        // a success banner.
        self::assertStringContainsString('Seams that resolver bound (0): NONE', $out);

        // And the consequence is spelled out: nothing can run.
        self::assertStringContainsString('FAIL ON EVERY TICK', $out);
        self::assertStringContainsString('refusing by name', $out);
        self::assertStringContainsString('no live Temporal server', $out);

        // No claim that any seam is bound, from any branch.
        self::assertStringNotContainsString('Seams NOT bound', $out);
        self::assertStringNotContainsString('1 container seam(s) bound', $out);
    }

    /**
     * The activity-type list this run printed, as [claimed count, names].
     *
     * Parsed out of stdout rather than asserted as a constant, because the
     * cross-check IS the point: the sentence that says "ALL n activity types
     * fail" has to count the list the same run printed, or a new activity can
     * arrive with a disclosure that no longer covers it.
     *
     * @return array{0: int, 1: list<string>}
     */
    private function activityTypesLine(string $stdout): array
    {
        $line = '';

        foreach (explode("\n", $stdout) as $candidate) {
            if (str_starts_with($candidate, 'Activity types served (')) {
                $line = $candidate;

                break;
            }
        }

        self::assertNotSame('', $line, 'the describe report has no "Activity types served" line.');

        $open = (int) strpos($line, '(');
        $close = (int) strpos($line, '): ');

        self::assertGreaterThan(0, $open, 'unparsable activity line: '.$line);
        self::assertGreaterThan($open, $close, 'unparsable activity line: '.$line);

        $counted = (int) substr($line, $open + 1, $close - $open - 1);
        $names = explode(', ', substr($line, $close + 3));

        return [$counted, $names];
    }

    /**
     * Write a throwaway PHP file into the SYSTEM temp dir — never the repo — and
     * remember it for tearDown(). Each file `require`s nothing: the script
     * under test loads its own autoloader, so these must stand alone.
     */
    private function writeTempPhp(string $body): string
    {
        $path = sys_get_temp_dir().'/teleframe-worker-seam-'.uniqid().'.php';

        file_put_contents($path, "<?php\n".$body."\n");

        $this->tempFiles[] = $path;

        return $path;
    }

    /**
     * Run the real binary and return its exit code, stdout and stderr.
     *
     * Three independent bounds, because a subprocess suite is only safe if a
     * stuck child cannot take the run with it: `timeout` INSIDE the command
     * string (the shell's own guarantee), a wall-clock deadline on the read
     * loop below, and a non-blocking select so the PHP process itself never
     * parks in fread(). Whichever fires first, the test FAILS with a named
     * reason — a hung worker is a defect in the script, not a skipped case.
     *
     * @return array{exit: int, stdout: string, stderr: string}
     */
    private function runWorker(string ...$args): array
    {
        $command = implode(' ', array_merge(
            ['timeout', '-k', '5', (string) self::INNER_TIMEOUT, escapeshellarg(PHP_BINARY)],
            array_map(escapeshellarg(...), array_merge(
                [dirname(__DIR__, 2).'/bin/teleframe-worker'],
                $args,
            )),
        ));

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptors, $pipes, dirname(__DIR__, 2));

        self::assertIsResource($process, 'could not start: '.$command);

        fclose($pipes[0]);
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $stdout = '';
        $stderr = '';
        $deadline = microtime(true) + self::WALL_LIMIT;

        while (true) {
            $open = [];

            foreach ([1, 2] as $fd) {
                if (! feof($pipes[$fd])) {
                    $open[] = $pipes[$fd];
                }
            }

            if ($open === []) {
                break;
            }

            $left = $deadline - microtime(true);

            if ($left <= 0) {
                proc_terminate($process, 9);
                proc_close($process);
                self::fail(sprintf(
                    'bin/teleframe-worker %s did not exit within %.0fs — it hung.',
                    implode(' ', $args),
                    self::WALL_LIMIT,
                ));
            }

            $write = null;
            $except = null;
            $ready = @stream_select(
                $open,
                $write,
                $except,
                (int) $left,
                (int) (fmod($left, 1.0) * 1000000),
            );

            if ($ready === false) {
                break;
            }

            foreach ($open as $stream) {
                $chunk = fread($stream, 8192);

                if ($chunk === false || $chunk === '') {
                    continue;
                }

                if ($stream === $pipes[1]) {
                    $stdout .= $chunk;
                } else {
                    $stderr .= $chunk;
                }
            }
        }

        fclose($pipes[1]);
        fclose($pipes[2]);

        $exit = proc_close($process);

        if ($exit === 124) {
            self::fail(sprintf(
                'bin/teleframe-worker %s hit the %ds in-command `timeout` — it hung.',
                implode(' ', $args),
                self::INNER_TIMEOUT,
            ));
        }

        if ($exit < 0) {
            self::fail(sprintf(
                'bin/teleframe-worker %s was killed by a signal (exit %d) — it hung.',
                implode(' ', $args),
                $exit,
            ));
        }

        return ['exit' => $exit, 'stdout' => $stdout, 'stderr' => $stderr];
    }
}

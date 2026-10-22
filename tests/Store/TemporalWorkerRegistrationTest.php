<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Store;

use MeRezaRezaei\Teleframe\App\AppCall;
use MeRezaRezaei\Teleframe\App\AppCallSpec;
use MeRezaRezaei\Teleframe\App\AppDefinition;
use MeRezaRezaei\Teleframe\App\AppException;
use MeRezaRezaei\Teleframe\App\AppMode;
use MeRezaRezaei\Teleframe\Store\Schedules\Worker\CheckNoShowActivity;
use MeRezaRezaei\Teleframe\Store\Schedules\Worker\CheckNoShowWorkflow;
use MeRezaRezaei\Teleframe\Store\Schedules\Worker\ContainerResolver;
use MeRezaRezaei\Teleframe\Store\Schedules\Worker\RunAppCallActivity;
use MeRezaRezaei\Teleframe\Store\Schedules\Worker\StoreBackupDumpActivity;
use MeRezaRezaei\Teleframe\Store\Schedules\Worker\StorePruneActivity;
use MeRezaRezaei\Teleframe\Store\Schedules\Worker\StoreSyncActivity;
use MeRezaRezaei\Teleframe\Store\Schedules\Worker\TemporalWorker;
use MeRezaRezaei\Teleframe\Store\Schedules\Worker\WorkflowTypeCatalog;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Temporal\WorkerFactory;

/**
 * TemporalWorker::register() — proved against the REAL SDK.
 *
 * The defect: `SdkTemporalScheduleClient` arms a real schedule whose action is
 * `StartWorkflowAction::new($type)` with a RAW STRING type, and before this
 * slice NOTHING in the tree implemented those strings. So this suite's job is
 * to make that class of defect impossible to reintroduce silently: every type
 * the three spec producers emit must be registered, under EXACTLY that name, by
 * the SDK's OWN readers — not by a hand-rolled double that agrees with
 * whatever the code does.
 *
 * The SDK IS installed here (require-DEV), so `WorkerFactory::create()` and a
 * real `Worker` are available without a server: newWorker() builds a local
 * router and prototype repositories and dials nothing. That is what makes the
 * SDK-ABSENT path testable too — it is reached through the injectable seam
 * below rather than by uninstalling anything.
 *
 * WHAT IS NOT PROVEN HERE: server-side dispatch. Nothing in this repo has run
 * against a live Temporal server, so "a tick arrives at this handler" remains
 * untested and is stated as such in the worker's own describe().
 */
final class TemporalWorkerRegistrationTest extends TestCase
{
    #[Test]
    public function the_sdk_is_present_in_this_checkout_so_the_registration_half_is_exercised(): void
    {
        // Guards the rest of the suite: if temporal/sdk were absent, the
        // registration tests would pass vacuously through a stub. Say so out
        // loud instead of letting a green run mean nothing.
        self::assertTrue(TemporalWorker::sdkInstalled(), 'temporal/sdk is require-DEV; without it this file proves nothing');
    }

    #[Test]
    public function it_registers_the_workflow_type_the_backup_spec_emits(): void
    {
        $registered = $this->registeredWorkflowTypes();

        self::assertArrayHasKey('teleframe:tstore backup', $registered,
            'BackupScheduleSpec arms this exact type; nothing implementing it means the dump never runs');
    }

    #[Test]
    public function it_registers_the_workflow_type_the_notify_command_emits(): void
    {
        $registered = $this->registeredWorkflowTypes();

        self::assertArrayHasKey('teleclient.notify.check-no-show', $registered,
            'BackupScheduleSpec AND NotifyCommand both arm this type');
    }

    #[Test]
    public function it_registers_one_app_call_type_per_declared_pair(): void
    {
        // The app-call type embeds the app and call names, so it cannot be a
        // single static registration. Two pairs must yield TWO types, under
        // their exact names — one per declared call.
        $registered = $this->registeredWorkflowTypes([['echo', 'health'], ['echo', 'ping']]);

        self::assertArrayHasKey('telegram.app.run-call echo health', $registered);
        self::assertArrayHasKey('telegram.app.run-call echo ping', $registered);
        self::assertCount(7, $registered, 'five fixed types (one of them an alias) plus exactly two app-call types, no more');
    }

    #[Test]
    public function it_registers_no_app_call_type_when_none_is_declared(): void
    {
        // The negative half. A worker that served a wildcard app-call type
        // would tick "successfully" for an app that does not exist; serving
        // nothing is the honest state, and describe() says so.
        $registered = $this->registeredWorkflowTypes();

        self::assertCount(5, $registered);
        self::assertStringNotContainsString('telegram.app.run-call', implode(' ', array_keys($registered)));
    }

    #[Test]
    public function the_app_call_type_it_registers_is_byte_identical_to_the_one_app_call_spec_emits(): void
    {
        // The producer and the worker must agree on the string, or the worker
        // registers a type the schedule never fires (or vice versa). This reads
        // the real producer through a real AppDefinition rather than restating
        // its format.
        $registered = $this->registeredWorkflowTypes([['echo', 'health']]);

        foreach (self::appCallSpecEmittedTypes() as $produced) {
            self::assertArrayHasKey(
                $produced,
                $registered,
                'AppCallSpec emits a workflow type this worker does not register — the string is a fiction again',
            );
        }
    }

    #[Test]
    public function the_catalog_serves_exactly_the_types_the_tree_produces(): void
    {
        // The producer side, exhaustively. A FOURTH spec producer added later
        // must fail here, not arm a type nothing implements and ship.
        $catalog = new WorkflowTypeCatalog;

        foreach (self::appCallSpecEmittedTypes() as $produced) {
            self::assertTrue($catalog->serves($produced), "the catalog does not serve $produced");
        }

        self::assertTrue($catalog->serves('teleframe:tstore backup'));
        self::assertTrue($catalog->serves('teleclient.notify.check-no-show'));
        self::assertTrue($catalog->serves('teleframe:tstore prune'));
        self::assertTrue($catalog->serves('teleframe:tstore sync'));
    }

    #[Test]
    public function the_catalog_does_not_claim_a_type_no_producer_emits(): void
    {
        // The other direction: a catalog that says "served" for everything
        // would make the disclosure worthless.
        $catalog = new WorkflowTypeCatalog;

        self::assertFalse($catalog->serves('acme.weekly-report'));
        self::assertFalse($catalog->serves('telegram.app.run-call'));
        self::assertFalse($catalog->serves('telegram.app.run-call '));
        self::assertFalse($catalog->serves('telegram.app.run-call only-one-token'));
        self::assertFalse($catalog->serves('telegram.app.run-call a b c'));
        self::assertFalse($catalog->serves('telegram.app.run-call  call'));
    }

    #[Test]
    public function it_registers_every_activity_type_the_workflows_stub(): void
    {
        // A workflow that yields an activity type the worker does not register
        // fails on its first tick — the same silent-until-runtime shape.
        $registered = $this->registeredActivityTypes();

        self::assertArrayHasKey(StoreBackupDumpActivity::TYPE, $registered);
        self::assertArrayHasKey(CheckNoShowActivity::TYPE, $registered);
        self::assertArrayHasKey(RunAppCallActivity::TYPE, $registered);
        self::assertArrayHasKey(StorePruneActivity::TYPE, $registered,
            'StorePruneWorkflow stubs this on its first tick; a workflow whose activity is unregistered fails there');
        self::assertArrayHasKey(StoreSyncActivity::TYPE, $registered,
            'StoreSyncWorkflow stubs this on its first tick; a workflow whose activity is unregistered fails there');
    }

    #[Test]
    public function served_types_and_the_registry_agree(): void
    {
        $worker = new TemporalWorker('tstore', [['echo', 'health']]);

        $registered = $this->registeredWorkflowTypes([['echo', 'health']]);
        ksort($registered);

        self::assertSame(
            array_keys($registered),
            $worker->servedTypes(),
            'servedTypes() and the SDK registry disagree, so the disclosure text would describe a worker that does not exist',
        );
    }

    #[Test]
    public function it_refuses_a_client_where_a_worker_is_required(): void
    {
        // register() takes a WORKER. A host that hands it a schedule client —
        // the natural mistake, since the client is what arms the schedule — gets
        // told the difference by name rather than a silent no-op.
        $worker = new TemporalWorker('tstore');

        $refused = null;

        try {
            $worker->register(new \stdClass);
        } catch (\RuntimeException $e) {
            $refused = $e->getMessage();
        }

        self::assertNotNull($refused);
        self::assertStringContainsString('registerWorkflowTypes', $refused);
        self::assertStringContainsString('a schedule CLIENT arms schedules and a worker SERVES them', $refused);
    }

    #[Test]
    public function with_the_sdk_absent_it_refuses_by_name_and_registers_nothing(): void
    {
        // THE LOUD-ABSENCE PROPERTY. temporal/sdk is require-DEV, so a host
        // without it must be told there is NO WORKER — not handed an empty
        // worker that polls a queue serving nothing.
        //
        // The availability PROBE is what makes this testable: the SDK is
        // genuinely installed in this checkout, so without the seam the guard
        // could never be exercised and would rot. A double that throws if the
        // code registers anything proves the guard fires BEFORE registration,
        // not merely that the guard exists.
        $worker = new TemporalWorker('tstore', [['echo', 'health']], sdkProbe: static fn (): bool => false);

        $refused = null;

        try {
            $worker->register($this->explodingWorker());
        } catch (\RuntimeException $e) {
            $refused = $e->getMessage();
        }

        self::assertNotNull($refused, 'register() must refuse when the SDK is absent, never return an empty worker');
        self::assertStringContainsString('no worker', $refused);
        self::assertStringContainsString('composer require temporal/sdk', $refused,
            'the refusal must name the install, or the operator looks for a container binding that does not exist');
        self::assertStringContainsString('php bin/teleframe-worker', $refused,
            'the refusal must name the entry point that exists once the SDK is installed');
    }

    #[Test]
    public function run_also_refuses_before_touching_the_sdk(): void
    {
        // run() is the entry a host actually calls, so the guard has to be on
        // the same path — a guard only on register() would still let a host
        // reach the SDK factory on a host that has no SDK.
        $worker = new TemporalWorker('tstore', sdkProbe: static fn (): bool => false);

        $refused = null;

        try {
            // A factory that throws if reached: if the guard were missing or
            // came after newWorker(), this is the failure a host would see
            // instead of the install instruction.
            $worker->run(new class
            {
                public function newWorker(string $queue = 'default'): object
                {
                    throw new \LogicException('the SDK factory was reached, which it must not be');
                }
            });
        } catch (\RuntimeException $e) {
            $refused = $e->getMessage();
        }

        self::assertNotNull($refused);
        self::assertStringContainsString('composer require temporal/sdk', $refused);
        self::assertStringNotContainsString('SDK factory was reached', (string) $refused,
            'the guard must fire BEFORE the SDK is touched');
    }

    #[Test]
    public function describe_says_the_sdk_state_and_never_claims_a_tick_fires(): void
    {
        $text = (new TemporalWorker('tstore', [['echo', 'health']]))->describe();

        self::assertStringContainsString('tstore', $text);
        self::assertStringContainsString('telegram.app.run-call echo health', $text);
        self::assertStringContainsString('no live Temporal server', $text,
            'a worker describe() that reads as proof of firing is the false green in the worker itself');
        self::assertStringContainsString('live dispatch is untested', $text);
    }

    #[Test]
    public function describe_says_plainly_when_no_app_call_is_declared(): void
    {
        $text = (new TemporalWorker('tstore'))->describe();

        self::assertStringContainsString('No app calls declared', $text);
        self::assertStringContainsString('will tick into a type nothing implements', $text);
        self::assertStringContainsString('--app=name --call=name', $text,
            'the text must name the flag that fixes it, not only the problem');
    }

    #[Test]
    public function describe_says_loudly_when_the_sdk_is_absent(): void
    {
        $text = (new TemporalWorker('tstore', sdkProbe: static fn (): bool => false))->describe();

        self::assertStringContainsString('The Temporal PHP SDK is NOT installed', $text);
        self::assertStringContainsString('composer require temporal/sdk', $text);
    }

    #[Test]
    public function it_constructs_with_no_container_and_resolves_nothing(): void
    {
        // The standalone-smoke property, in miniature: a worker is a long-lived
        // process, so a constructor that dialled Temporal or resolved a
        // container service would open a connection on `php artisan list`.
        $worker = new TemporalWorker('tstore');

        self::assertSame('tstore', $worker->taskQueue());
        self::assertSame([], $worker->resolver()->seams(), 'no seam is resolved by constructing');
    }

    #[Test]
    public function a_container_free_activity_refuses_by_name_instead_of_pretending_to_work(): void
    {
        // The activity half of the same honesty. With no seam bound, a tick
        // must fail naming the seam — returning an empty result would make an
        // unarmed backup look like a clean one.
        $activity = new StoreBackupDumpActivity(ContainerResolver::refusing());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('teleclient.store.backup.service');

        $activity->dump([]);
    }

    #[Test]
    public function the_app_call_activity_refuses_a_tick_that_names_no_call(): void
    {
        // A payload with no app/call is a schedule whose routing is broken; it
        // must not be handed to AppRuntime as app '' and read as "unknown app".
        $activity = new RunAppCallActivity(ContainerResolver::refusing());

        $this->expectException(AppException::class);
        $this->expectExceptionMessage('app=(empty)');

        $activity->runCall('', '', []);
    }

    #[Test]
    public function it_serves_both_spellings_of_the_no_show_type(): void
    {
        // THE DRIFT THIS PACKAGE ALREADY HAD. The two no-show producers
        // disagree with each other AND with themselves:
        //   BackupScheduleSpec.php:62 → 'teleclient.notify.check-no-show'
        //   NotifyCommand.php:230     → 'teleframe.notify.check-no-show'
        //     …while NotifyCommand's own docblock (line 46) says the former.
        //
        // A worker serving only one spelling would leave the other seam's
        // schedule ticking into nothing — the exact false success this slice
        // removes. So BOTH are registered against the same handler, and the
        // drift is recorded on WorkflowTypeCatalog::CHECK_NO_SHOW_ALIAS rather
        // than settled here: the two producers belong to two different slices.
        $registered = $this->registeredWorkflowTypes();

        self::assertArrayHasKey('teleclient.notify.check-no-show', $registered,
            'the backup seam arms this spelling');
        self::assertArrayHasKey('teleframe.notify.check-no-show', $registered,
            'the notify seam arms this spelling; a worker serving only the other one leaves that seam dead');

        // Same handler class, so the alias cannot drift into different work.
        self::assertArrayHasKey('teleframe.notify.check-no-show', WorkflowTypeCatalog::typeAliases());
        self::assertSame(
            CheckNoShowWorkflow::class,
            WorkflowTypeCatalog::typeAliases()['teleframe.notify.check-no-show'],
            'the alias must point at the SAME workflow class as the primary type',
        );
    }

    #[Test]
    public function every_schedule_type_the_tree_produces_is_served_or_acknowledged_as_a_known_gap(): void
    {
        // THE PIN AGAINST A FIFTH PRODUCER.
        //
        // The defect this slice fixes is a spec producer emitting a workflow
        // type that nothing implements, and nobody noticing because the
        // publisher's status line was green. So the producer side is read FROM
        // SOURCE — every `'activity' => '…'` literal handed to a schedule
        // publisher — and each one must be either served by this worker or
        // listed in WorkflowTypeCatalog's ACKNOWLEDGED_UNSERVED with a reason.
        //
        // A new spec producer with a type in neither list fails HERE, on the
        // commit that added it, rather than arming a schedule nothing serves
        // and shipping.
        $catalog = new WorkflowTypeCatalog;
        $unaccounted = [];

        foreach (self::producedScheduleTypes() as $type) {
            $served = $catalog->serves($type);
            $known = $catalog->acknowledgedUnservedReason($type);

            if (! $served && $known === null) {
                $unaccounted[] = $type;
            }
        }

        self::assertSame([], $unaccounted, sprintf(
            'These schedule types are produced by the tree and are NEITHER served by this package\'s worker '
            .'NOR listed as a known gap in WorkflowTypeCatalog::ACKNOWLEDGED_UNSERVED: %s. Each one arms a '
            .'server-side schedule whose every tick starts a workflow type nothing implements — exactly the '
            .'false success this catalog exists to remove. Serve it, or acknowledge it as a known gap with '
            .'its reason.',
            implode(', ', $unaccounted),
        ));
    }

    #[Test]
    public function the_acknowledged_gap_mechanism_still_works_although_the_list_is_now_empty(): void
    {
        // THE PIN AGAINST THE ORIGINAL DEFECT CLASS, kept alive after the list
        // emptied. `teleframe:tstore prune` and `teleframe:tstore sync` were the
        // whole content of ACKNOWLEDGED_UNSERVED; they are served now, so the
        // shipped list is EMPTY. An empty list is not the same property as a
        // removed mechanism, and this test is what says so.
        //
        // A workflow type is a STRING with no compiler: a producer that names a
        // type no worker implements gets no error anywhere. That is how the
        // original defect happened. So the disclosure must still catch it, and
        // the KNOWN-GAP reason must still reach the operator — the two ways a
        // "not served" verdict can be reported. The `$unserved` map is how a gap
        // is recorded, and it can only make the verdict LOUDER: it never turns
        // an unserved type into a served one (asserted below).
        $catalog = new WorkflowTypeCatalog([
            'acme.weekly-report' => 'the reporting package owns this workflow; this package implements none of it',
        ]);

        $verdict = $catalog->verdict('acme.weekly-report');

        self::assertStringContainsString('NOT SERVED', $verdict,
            'a recorded gap is still a dead schedule, and the verdict must say so');
        self::assertStringContainsString('KNOWN GAP', $verdict,
            'a gap nobody thought about reads like a typo; the reason has to travel with the verdict');
        self::assertStringContainsString('the reporting package owns this workflow', $verdict,
            'the recorded reason must be the one printed, not merely that some reason exists');
        self::assertStringContainsString('every tick of this schedule will fail', $verdict);

        self::assertFalse($catalog->serves('acme.weekly-report'));
        self::assertNull(
            (new WorkflowTypeCatalog)->acknowledgedUnservedReason('acme.weekly-report'),
            'the reason is a recorded fact about this type, not a property of a catalog instance',
        );
    }

    #[Test]
    public function a_recorded_gap_can_never_promote_a_type_to_served(): void
    {
        // The one direction the `$unserved` seam must not have. A gap is the
        // stronger statement, so it is checked FIRST in serves() — even against
        // a type that is otherwise a fixed type, an alias, or a well-formed
        // app-call type. Without this, "empty the list" and "the mechanism is
        // load-bearing" would be two different claims, and only one would hold.
        $catalog = new WorkflowTypeCatalog([
            'teleframe:tstore backup' => 'forced gap for this assertion',
            'teleframe.notify.check-no-show' => 'forced gap for this assertion',
            WorkflowTypeCatalog::appCallType('echo', 'health') => 'forced gap for this assertion',
        ]);

        self::assertFalse($catalog->serves('teleframe:tstore backup'));
        self::assertFalse($catalog->serves('teleframe.notify.check-no-show'));
        self::assertFalse($catalog->serves(WorkflowTypeCatalog::appCallType('echo', 'health')));

        // …and the default catalog, with nothing recorded, still serves them —
        // so the guard above is the recording and not a broken predicate.
        self::assertTrue((new WorkflowTypeCatalog)->serves('teleframe:tstore backup'));
        self::assertTrue((new WorkflowTypeCatalog)->serves('teleframe.notify.check-no-show'));
    }

    #[Test]
    public function every_type_the_tree_produces_is_now_served_with_nothing_acknowledged(): void
    {
        // The same producer enumeration as above, with the acknowledgement
        // escape hatch taken away: because the list is empty, EVERY type the
        // tree arms must be served by this worker. This is strictly stronger
        // than the served-or-acknowledged pin, and it is the state the list
        // emptied into.
        $catalog = new WorkflowTypeCatalog;

        foreach (self::producedScheduleTypes() as $type) {
            self::assertNull($catalog->acknowledgedUnservedReason($type),
                "$type is still recorded as a known gap; either serve it or state the reason, not both");
            self::assertTrue($catalog->serves($type),
                "$type is produced by the tree and served by nothing — an armed schedule whose every tick fails");
        }
    }

    /**
     * Every workflow type a schedule spec producer emits, read from the SOURCE
     * TREE rather than restated here.
     *
     * Only the four producer files are scanned, and the pattern is the literal
     * that becomes a schedule's workflow type. The two `TstoreCommand` sites
     * pass a value THROUGH (`$payload['activity']`), which is the backup
     * spec's string arriving by another route, not a producer of its own.
     *
     * @return list<string>
     */
    private static function producedScheduleTypes(): array
    {
        $root = dirname(__DIR__, 2);
        $producers = [
            'src/Teleframe/App/AppCallSpec.php',
            'src/Teleframe/Store/Backup/BackupScheduleSpec.php',
            'src/Teleframe/Store/Schedules/StoreMaintenanceScheduleSpec.php',
            'src/Teleframe/Notify/NotifyCommand.php',
        ];

        $types = [];

        foreach ($producers as $producer) {
            $path = $root.'/'.$producer;
            self::assertFileExists($path, "a producer this suite scans no longer exists: $producer");

            $source = (string) file_get_contents($path);

            foreach (self::activityLiteralsIn($source) as $literal) {
                // The app-call family has no fixed members: its literal is a
                // PREFIX plus a concat, so probe the family with real names
                // rather than asserting on a template with a dangling space.
                $types[] = str_starts_with($literal, WorkflowTypeCatalog::APP_CALL_PREFIX)
                    ? WorkflowTypeCatalog::appCallType('probe-app', 'probe-call')
                    : trim($literal);
            }
        }

        return $types;
    }

    /**
     * The first single-quoted literal on an `'activity' =>` line.
     *
     * No regex: the line is split on the arrow, then the text between the
     * first pair of single quotes is taken. Stopping at the CLOSING quote is
     * what makes a concat tail (`'telegram.app.run-call '.$app->name`) yield
     * the template rather than being chopped at its first dot.
     *
     * @return list<string>
     */
    private static function activityLiteralsIn(string $source): array
    {
        $literals = [];

        foreach (explode("\n", $source) as $line) {
            if (! str_contains($line, "'activity' =>")) {
                continue;
            }

            $value = trim(explode('=>', $line, 2)[1] ?? '');
            $open = strpos($value, "'");

            if ($open === false) {
                continue;
            }

            $close = strpos($value, "'", $open + 1);

            if ($close === false || $close === $open + 1) {
                continue;
            }

            $literals[] = substr($value, $open + 1, $close - $open - 1);
        }

        return $literals;
    }

    // ---------------------------------------------------------------- doubles --

    /**
     * The workflow types the REAL SDK registered on a REAL Worker, keyed by the
     * name the SDK derived — from the `#[WorkflowMethod(name: …)]` attribute
     * for the fixed types, and from the name this worker built the prototype
     * with for the app-call family.
     *
     * @param  list<array{0: string, 1: string}>  $appCalls
     * @return array<string, true>
     */
    private function registeredWorkflowTypes(array $appCalls = []): array
    {
        $factory = WorkerFactory::create();
        $worker = (new TemporalWorker('tstore', $appCalls))->register($factory->newWorker('tstore'));

        $types = [];

        foreach ($worker->getWorkflows() as $name => $prototype) {
            $types[(string) $name] = true;
        }

        return $types;
    }

    /**
     * @return array<string, true>
     */
    private function registeredActivityTypes(): array
    {
        $factory = WorkerFactory::create();
        $worker = (new TemporalWorker('tstore'))->register($factory->newWorker('tstore'));

        $types = [];

        foreach ($worker->getActivities() as $name => $prototype) {
            $types[(string) $name] = true;
        }

        return $types;
    }

    /**
     * A worker stand-in that FAILS LOUDLY if the code registers anything on it.
     *
     * Used with the SDK-absent probe: it turns "the guard fired" into a
     * stronger claim — the guard fired BEFORE any registration was attempted,
     * so the SDK-absent path cannot half-succeed.
     */
    private function explodingWorker(): object
    {
        return new class
        {
            public function registerWorkflowTypes(string ...$class): self
            {
                throw new \LogicException('registered a type on a host with no SDK');
            }

            public function registerActivity(string $type, ?callable $factory = null): self
            {
                throw new \LogicException('registered an activity on a host with no SDK');
            }
        };
    }

    /**
     * The workflow types AppCallSpec::installSpec() REALLY emits, read through
     * a real AppDefinition — not a restatement of its format.
     *
     * @return list<string>
     */
    private static function appCallSpecEmittedTypes(): array
    {
        $app = AppDefinition::define(
            name: 'echo',
            mode: AppMode::Bot,
            calls: [AppCall::every('health', 60, 'health')],
        );

        $types = [];

        foreach ((new AppCallSpec)->installSpec($app) as $entry) {
            $types[] = (string) $entry['activity'];
        }

        return $types;
    }
}

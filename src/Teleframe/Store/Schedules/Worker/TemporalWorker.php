<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Store\Schedules\Worker;

use Closure;
use Temporal\WorkerFactory;

/**
 * TemporalWorker — the worker that makes the schedule type strings REAL.
 *
 * THE DEFECT THIS CLASS EXISTS TO KILL. `SdkTemporalScheduleClient` arms a real
 * server-side Temporal schedule whose action is `StartWorkflowAction::new($type)`,
 * where `$type` is a RAW STRING. Before this class, `grep -rln "WorkerFactory|
 * registerWorkflowTypes|@WorkflowInterface|@ActivityInterface" src/` returned
 * ZERO files: no worker bootstrap, no `#[WorkflowInterface]`, no
 * `#[ActivityInterface]`, no entry point. So a host that set
 * `TELEFRAME_STORE_BACKUP_SCHEDULES=temporal` and installed `temporal/sdk`
 * got a green line — `armed %s recurring every %ds` — for a schedule whose
 * every tick started a workflow type nothing implemented.
 *
 * That regression is specific: before commit 729452d1 the driver could only
 * throw a NAMED RuntimeException telling the operator to bind a client. That
 * loud, honest failure was replaced with a silent false success. The whole
 * point of the temporal seam is that arming means something, so it now means
 * something again, and what it does not mean is stated in the publisher's own
 * status text (see WorkflowTypeCatalog::disclosure()).
 *
 * WHAT IT REGISTERS — @see WorkflowTypeCatalog for the authoritative list:
 *   - workflow type `teleframe:tstore backup`        → StoreBackupWorkflow
 *   - workflow type `teleclient.notify.check-no-show` → CheckNoShowWorkflow
 *   - workflow type `teleframe.notify.check-no-show`  → CheckNoShowWorkflow TOO:
 *     the notify seam and the backup seam spell the same work differently
 *     (@see WorkflowTypeCatalog::CHECK_NO_SHOW_ALIAS), and serving only one
 *     would leave the other seam's schedule ticking into nothing.
 *   - workflow type `teleframe:tstore prune`         → StorePruneWorkflow, and
 *     `teleframe:tstore sync` → StoreSyncWorkflow. Both were KNOWN GAPs on
 *
 *     @see WorkflowTypeCatalog::ACKNOWLEDGED_UNSERVED until they were served;
 *     they are the two halves of StoreMaintenanceScheduleSpec's payload, so
 *     leaving them out made the whole tstore maintenance seam arming-only.
 *   - workflow type `telegram.app.run-call {app} {call}` → AppRunCallWorkflow,
 *     registered ONCE PER DECLARED (app, call) PAIR, because the type embeds
 *     both names and Temporal resolves a workflow by the name the server sends.
 *   - activities `teleframe.store.backup-dump`, `teleframe.notify.check-no-show`,
 *     `teleframe.app.run-call`, `teleframe.store.hot-cache-prune`,
 *     `teleframe.store.registry-sweep`.
 *
 * THE SWEEP'S BUCKET LIST IS A DEFAULT, NOT AN OVERRIDE. Of those five, only
 * the registry sweep takes an input the operator can configure: the list the
 * command reads from `--buckets=`. A schedule cannot hand one over — a
 * schedule's payload is fixed server-side at arm time and no argument rides a
 * TICK — so this worker's `$defaultBuckets` supplies it, and ONLY for a sweep
 * whose own schedule payload names no `buckets`. A payload that names a list
 * wins outright; the worker list is never consulted, and a schedule naming an
 * EMPTY list means "register nothing extra" even on a worker started with a
 * default. Every tick reports which of the three it was as `buckets_source`,
 * because two of the three produce the same empty list.
 * @see StoreSyncActivity
 *
 * THE SDK IS OPTIONAL AND ITS ABSENCE IS LOUD. `temporal/sdk` is require-DEV in
 * this package, on purpose — the engine must install and run without Temporal.
 * So NO SDK class appears in a signature or property (`object` + duck typing),
 * this file LOADS FINE with the SDK absent, and every path that needs the SDK
 * goes through TemporalClientResolver::sdkInstalled() and then a NAMED
 * refusal. It never returns null, never registers zero types quietly, and
 * never reports success having served nothing.
 *
 * NOTHING DIALS OR RESOLVES AT CONSTRUCTION. A worker is a long-running
 * process, so a constructor that dialled Temporal or resolved a container
 * service would open a connection on a `php artisan list`. The WorkerFactory is
 * built in run() and the ContainerResolver only fires on the first activity
 * tick. Construction is inert: task queue string, type list, resolver, bucket
 * list — no I/O. The bucket list in particular is COPIED, not resolved: it is a
 * plain list of strings, so a worker booted with a default does not read config,
 * touch a container, or reach a service to have one.
 *
 * NOT PROVEN: nothing here has been run against a LIVE Temporal server. What
 * IS proven, by tests/Store/TemporalWorkerRegistrationTest.php against the real
 * SDK's own WorkflowReader / ActivityReader and a real Worker instance, is that
 * each workflow type string this package arms is registered under EXACTLY that
 * name by this class, and that the SDK-absent path refuses by name with no
 * container and no server. "A server dispatches a tick to the handler and the
 * activity runs" is not verified here. Treat live behaviour as untested.
 *
 * Plain-PHP constructible, no container, zero regex.
 */
final class TemporalWorker
{
    /**
     * The documented way a host starts this worker. Named in the publisher's
     * disclosure text and in the refusal message, so "no worker exists" is
     * never the answer an operator is given.
     */
    public const ENTRY_POINT = 'php bin/teleframe-worker';

    /** The SDK class the registration path needs. Named as a string, never as a type. */
    private const WORKER_FACTORY = 'Temporal\\WorkerFactory';

    /**
     * The availability probe seam. Receives nothing and returns bool.
     *
     * @var null|Closure(): bool
     */
    private readonly ?Closure $sdkProbe;

    /**
     * @param  list<array{0: string, 1: string}>  $appCalls  the host's declared (app, call) pairs
     * @param  string  $taskQueue  the queue the schedules were armed on
     * @param  null|Closure(): bool  $sdkProbe  override for the SDK-availability check (tests / exotic hosts); null = class_exists
     * @param  list<string>  $defaultBuckets  the operator's `--buckets` list, as a sweep DEFAULT — see below
     *
     * WHY `$defaultBuckets` IS THE SIXTH PARAMETER AND NOT THE FIFTH. It is
     * appended AFTER `$sdkProbe` on purpose: `bin/standalone-smoke.php:686`
     * constructs this worker with FIVE POSITIONAL arguments —
     * `new TemporalWorker('tstore', [], new ContainerResolver, new
     * WorkflowTypeCatalog, static fn (): bool => false)` — to exercise the
     * SDK-absent refusal. Inserting a parameter in front of `$sdkProbe` would
     * silently make that fifth argument a bucket list, hand the probe `null`,
     * and leave the gate testing the real `class_exists` while believing it
     * tested a false probe. That file is a gate, not a call site to update, so
     * the signature is what bends.
     *
     * WHAT THIS LIST IS, PRECISELY. A Temporal schedule's payload is fixed
     * server-side when the schedule is armed (SdkTemporalScheduleClient.php:254
     * builds `StartWorkflowAction::new($activity)`) and no argument can be
     * attached to a TICK, so the payload a schedule sweeps is whatever its
     * install-time payload said. The sweep this package ships —
     * `StoreMaintenanceScheduleSpec::installSpec()`'s `tstore-registry-sweep` —
     * says `interval_seconds` and `activity` and nothing else, so it names NO
     * buckets. This is the one place a host can still introduce the operator's
     * list for a scheduled tick, because the WORKER constructs the activity.
     *
     * It is a DEFAULT, never an override: a schedule whose own payload names
     * `buckets` uses THAT list and this one is never read
     * (@see StoreSyncActivity::bucketInput()). And with neither naming a list,
     * the sweep runs with an empty one — the same as `php artisan teleframe:
     * tstore sync` with no `--buckets`. It is a plain list of strings, copied
     * here: nothing resolves, dials or reads config to build it.
     */
    public function __construct(
        private readonly string $taskQueue = 'default',
        private readonly array $appCalls = [],
        private readonly ContainerResolver $resolver = new ContainerResolver,
        private readonly WorkflowTypeCatalog $types = new WorkflowTypeCatalog,
        ?Closure $sdkProbe = null,
        private readonly array $defaultBuckets = [],
    ) {
        $this->sdkProbe = $sdkProbe;
    }

    /**
     * The workflow type strings THIS worker serves, sorted.
     *
     * The answer a host needs before arming anything, and the answer
     * WorkflowTypeCatalog::serves() consults indirectly. Never empty unless the
     * SDK is missing (@see self::sdkInstalled()).
     *
     * @return list<string>
     */
    public function servedTypes(): array
    {
        $types = WorkflowTypeCatalog::fixedTypes();

        foreach (array_keys(WorkflowTypeCatalog::typeAliases()) as $alias) {
            $types[] = $alias;
        }

        foreach ($this->appCalls as [$app, $call]) {
            $types[] = WorkflowTypeCatalog::appCallType($app, $call);
        }

        sort($types);

        return $types;
    }

    /**
     * The activity type strings this worker serves, sorted.
     *
     * @return list<string>
     */
    public static function servedActivities(): array
    {
        $types = [
            StoreBackupDumpActivity::TYPE,
            CheckNoShowActivity::TYPE,
            RunAppCallActivity::TYPE,
            StorePruneActivity::TYPE,
            StoreSyncActivity::TYPE,
        ];

        sort($types);

        return $types;
    }

    /** The task queue this worker polls. */
    public function taskQueue(): string
    {
        return $this->taskQueue;
    }

    /** The declared (app, call) pairs, as type strings. @return list<string> */
    public function appCallTypes(): array
    {
        $types = [];

        foreach ($this->appCalls as [$app, $call]) {
            $types[] = WorkflowTypeCatalog::appCallType($app, $call);
        }

        sort($types);

        return $types;
    }

    public function resolver(): ContainerResolver
    {
        return $this->resolver;
    }

    public function types(): WorkflowTypeCatalog
    {
        return $this->types;
    }

    /**
     * The sweep bucket list this worker hands the registry sweep as its
     * DEFAULT, exactly as constructed.
     *
     * Public so `bin/teleframe-worker --describe` and a test can report the
     * effective default without reflection, and so a host can assert what it
     * built. Empty means "no worker default", which is NOT the same claim as
     * "a sweep sweeps nothing" — a schedule payload naming buckets overrides
     * this, and with neither set the sweep takes the command's own empty
     * default. @see StoreSyncActivity::SOURCE_COMMAND_DEFAULT
     *
     * @return list<string>
     */
    public function defaultBuckets(): array
    {
        return $this->defaultBuckets;
    }

    /**
     * Is the optional runtime dependency present?
     *
     * Same test as TemporalClientResolver::sdkInstalled() — `class_exists` on
     * the factory, not a `use` statement, so this file is loadable without it.
     * The gRPC extension is NOT required to REGISTER types (the host process
     * only needs it to open a channel), so it is deliberately not part of this
     * test; TemporalClientResolver keeps the stricter check for the SCHEDULE
     * side, which does dial.
     */
    public static function sdkInstalled(): bool
    {
        return class_exists(self::WORKER_FACTORY);
    }

    /**
     * Is the SDK available TO THIS WORKER?
     *
     * The static class_exists() check is the real answer for a host, and this
     * is that same check unless a probe was injected.
     *
     * WHY THE PROBE EXISTS. temporal/sdk is require-DEV in THIS package, so it
     * is installed in the checkout the suite runs in — which means the
     * SDK-absent path is otherwise untestable without uninstalling the
     * dependency, and an untested guard is a guard that rots. This is the same
     * seam shape TemporalClientResolver uses for its `$sdkFactory`, for the
     * same reason: the environment must not decide what a test asserts.
     */
    public function sdkAvailable(): bool
    {
        return $this->sdkProbe !== null ? (bool) ($this->sdkProbe)() : self::sdkInstalled();
    }

    /**
     * Register every served type on a worker instance and return it.
     *
     * The object is TYPED `object` and driven by `method_exists`, so this file
     * stays loadable (and this class stays constructible) with the SDK absent.
     * The `$worker` argument is the seam a test passes — the real SDK's
     * `Temporal\Worker\Worker` from `WorkerFactory::newWorker()` — so
     * registration is exercised against the SDK's OWN prototypes rather than a
     * hand-rolled double.
     *
     * @param  object  $worker  anything exposing registerWorkflowTypes/registerActivity/getWorkflows
     * @return object the same worker
     *
     * @throws \RuntimeException when the SDK is absent, or the worker is not a worker
     */
    public function register(object $worker): object
    {
        if (! $this->sdkAvailable()) {
            throw new \RuntimeException(self::sdkMissingMessage());
        }

        if (! method_exists($worker, 'registerWorkflowTypes') || ! method_exists($worker, 'registerActivity')) {
            throw new \RuntimeException(sprintf(
                'TemporalWorker::register() needs a Temporal worker (something with registerWorkflowTypes() and '
                .'registerActivity()); got %s. Pass the result of %s::newWorker($taskQueue), not a client — '
                .'a schedule CLIENT arms schedules and a worker SERVES them; this package needs both.',
                $worker::class,
                self::WORKER_FACTORY,
            ));
        }

        // The two fixed types go through the SDK's own reader, which reads the
        // #[WorkflowMethod(name: …)] attribute — so the registered name is the
        // attribute's, not a string this class repeats.
        $worker->registerWorkflowTypes(
            StoreBackupWorkflow::class,
            CheckNoShowWorkflow::class,
            StorePruneWorkflow::class,
            StoreSyncWorkflow::class,
        );

        // The app-call family CANNOT go through registerWorkflowTypes(): the
        // type embeds the app and call names, so its WorkflowMethod attribute
        // carries no single name. Each declared pair is registered under its own
        // type string by building the same WorkflowPrototype the SDK's reader
        // would, with the name the SERVER will send.
        $this->registerAppCallTypes($worker);

        // A producer that spells the same work differently gets its own
        // registration of the SAME handler, under the string IT arms. See
        // WorkflowTypeCatalog::CHECK_NO_SHOW_ALIAS for the drift this covers:
        // the notify seam and the backup seam disagree on the no-show type, so
        // a worker serving only one of them would leave the other seam's
        // schedule ticking into nothing.
        $this->registerAliases($worker);

        $worker->registerActivity(
            StoreBackupDumpActivity::class,
            fn (): object => new StoreBackupDumpActivity($this->resolver),
        );
        $worker->registerActivity(
            CheckNoShowActivity::class,
            fn (): object => new CheckNoShowActivity($this->resolver),
        );
        $worker->registerActivity(
            RunAppCallActivity::class,
            fn (): object => new RunAppCallActivity($this->resolver),
        );
        $worker->registerActivity(
            StorePruneActivity::class,
            fn (): object => new StorePruneActivity($this->resolver),
        );
        $worker->registerActivity(
            StoreSyncActivity::class,
            // The ONLY activity registration that carries worker state: the
            // operator's `--buckets` list is the one sweep input a scheduled
            // tick cannot supply for itself, because a schedule's payload is
            // fixed server-side at arm time (@see the constructor). Passed here
            // rather than read in the activity, so nothing is resolved and the
            // list is a plain copy.
            fn (): object => new StoreSyncActivity($this->resolver, $this->defaultBuckets),
        );

        return $worker;
    }

    /**
     * Register one AppRunCallWorkflow per declared (app, call) pair, under that
     * pair's exact workflow type string.
     *
     * Deliberately does NOT fall back to a wildcard: a type string this package
     * does not know the (app, call) of is not a schedule this worker can serve,
     * and pretending otherwise is the false success this class removes. A host
     * that declares a call after the worker booted gets a schedule that ticks
     * into a type nothing implements — which is what TemporalSchedulePublisher's
     * status now says out loud.
     */
    private function registerAppCallTypes(object $worker): void
    {
        if ($this->appCalls === [] || ! method_exists($worker, 'getWorkflows')) {
            return;
        }

        /** @var object $workflows */
        $workflows = $worker->getWorkflows();

        foreach ($this->appCallTypes() as $type) {
            // A FRESH prototype per type: the name is the identity Temporal
            // dispatches on, so reusing one object would register every pair
            // under whichever name was built first.
            //
            // add($proto, false) THROWS on a duplicate name, which is the right
            // answer: two declarations of the same (app, call) is a host
            // configuration error, and silently keeping the first would hide it.
            $workflows->add($this->appCallPrototype($type), false);
        }
    }

    /**
     * Register each alias type against the workflow class the catalog names.
     *
     * Same mechanism as the app-call family — a fresh prototype carrying the
     * alias string — because an alias is a NAME, and the name is what Temporal
     * dispatches on.
     */
    private function registerAliases(object $worker): void
    {
        if (! method_exists($worker, 'getWorkflows')) {
            return;
        }

        /** @var object $workflows */
        $workflows = $worker->getWorkflows();

        foreach (WorkflowTypeCatalog::typeAliases() as $alias => $class) {
            $workflows->add(
                new ($this->prototypeClass())(
                    $alias,
                    new \ReflectionMethod($class, 'run'),
                    new \ReflectionClass($class),
                ),
                false,
            );
        }
    }

    /**
     * A WorkflowPrototype carrying this worker's AppRunCallWorkflow under the
     * given type name.
     *
     * Built through the SDK's own class with a name argument rather than
     * repeated in a class attribute, because the name is DATA — it embeds the
     * app and call names, and a static `#[WorkflowMethod(name:)]` could only
     * ever serve one of them.
     */
    private function appCallPrototype(string $type): object
    {
        $class = new \ReflectionClass(AppRunCallWorkflow::class);

        return new ($this->prototypeClass())(
            $type,
            new \ReflectionMethod(AppRunCallWorkflow::class, 'run'),
            $class,
        );
    }

    /**
     * The SDK's WorkflowPrototype, as a class-string resolved at CALL time.
     *
     * A string rather than a `use`, so this file still loads with the SDK
     * absent; @see self::sdkAvailable() for when it is actually constructed.
     *
     * @return class-string
     */
    private function prototypeClass(): string
    {
        return 'Temporal\\Internal\\Declaration\\Prototype\\WorkflowPrototype';
    }

    /**
     * Build the SDK's WorkerFactory, register every served type on its worker,
     * and run the poll loop.
     *
     * The ONLY method that touches the SDK. Everything above it is inert, so a
     * `php artisan list` or a container resolution never opens a channel.
     *
     * @param  null|object  $factory  override for the SDK factory (tests / exotic hosts); null = build it
     * @return int the SDK's exit code
     *
     * @throws \RuntimeException when the SDK is absent
     */
    public function run(?object $factory = null): int
    {
        if (! $this->sdkAvailable()) {
            throw new \RuntimeException(self::sdkMissingMessage());
        }

        $factory ??= WorkerFactory::create();

        $worker = $this->register($factory->newWorker($this->taskQueue));

        return (int) $factory->run();
    }

    /**
     * What this worker would register, as one honest sentence. Printed by
     * `bin/teleframe-worker --describe`, which is how an operator checks the
     * worker side without a server.
     */
    public function describe(): string
    {
        $types = $this->servedTypes();
        $activities = self::servedActivities();

        $lines = [
            sprintf('Temporal worker task queue: %s', $this->taskQueue),
            sprintf('Workflow types served (%d): %s', count($types), $types === [] ? 'none' : implode(', ', $types)),
            sprintf('Activity types served (%d): %s', count($activities), implode(', ', $activities)),
        ];

        // Stated because the sweep's own report is a COUNT: without this line an
        // operator cannot tell "the worker was started with a bucket list" from
        // "nothing named any", and those two produce different registry rows.
        // It claims only where the list comes from, never that a sweep will
        // find anything.
        if ($this->defaultBuckets === []) {
            $lines[] = 'Sweep default buckets: none. A teleframe:tstore sync sweep whose schedule payload names no '
                .'buckets uses none, and sweeps whatever the dialog walk finds. That is the same behaviour as '
                .'running php artisan teleframe:tstore sync with no --buckets.';
        } else {
            $lines[] = sprintf(
                'Sweep default buckets: %s. Only a teleframe:tstore sync sweep whose schedule payload names no '
                .'buckets uses this list; a payload that names buckets uses its own instead.',
                implode(', ', $this->defaultBuckets),
            );
        }

        if ($this->appCalls === []) {
            $lines[] = 'No app calls declared: NO telegram.app.run-call workflow type is registered, so a '
                .'schedule armed for one will tick into a type nothing implements. Declare the (app, call) '
                .'pairs the worker should serve (bin/teleframe-worker --app=name --call=name).';
        }

        if (! $this->sdkAvailable()) {
            $lines[] = 'The Temporal PHP SDK is NOT installed, so this worker cannot run: '
                .self::sdkMissingMessage();
        } else {
            $lines[] = 'The Temporal PHP SDK is installed. Nothing above is proof that a server accepts a '
                .'tick: this repo has no live Temporal server, so live dispatch is untested.';
        }

        return implode("\n", $lines);
    }

    /**
     * The refusal an operator actually needs when the SDK is absent. It names
     * the package and the composer require, because "no SDK" sent people
     * looking for a container binding that does not exist.
     *
     * It does NOT claim a worker is running: with the SDK absent, arming a
     * schedule produces a type no worker in this package can serve, and the
     * publisher's status says so per schedule.
     */
    public static function sdkMissingMessage(): string
    {
        return 'the Temporal PHP SDK (temporal/sdk) is not installed, so this package has no worker and '
            .'cannot serve any workflow type. temporal/sdk is an OPTIONAL runtime dependency of this package '
            .'(it is require-DEV only, on purpose). Install it with `composer require temporal/sdk`, then start '
            .'the worker with `'.self::ENTRY_POINT.'`. Until then, use driver "null" with the documented cron '
            .'line, or your own scheduler — a schedule armed for a workflow type nothing implements fails on '
            .'every tick, and no status line in this package calls that a success.';
    }
}

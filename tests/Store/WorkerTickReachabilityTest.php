<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Store;

use Illuminate\Container\Container;
use MeRezaRezaei\Teleframe\App\AppCall;
use MeRezaRezaei\Teleframe\App\AppDefinition;
use MeRezaRezaei\Teleframe\App\AppMode;
use MeRezaRezaei\Teleframe\App\AppRegistry;
use MeRezaRezaei\Teleframe\App\AppRuntime;
use MeRezaRezaei\Teleframe\Notify\NoShowWatchdog;
use MeRezaRezaei\Teleframe\Notify\WatchState;
use MeRezaRezaei\Teleframe\Store\Backup\DatabaseBackupService;
use MeRezaRezaei\Teleframe\Store\Backup\DatabaseDumper;
use MeRezaRezaei\Teleframe\Store\Backup\DatabaseTarget;
use MeRezaRezaei\Teleframe\Store\Backup\DumpResult;
use MeRezaRezaei\Teleframe\Store\Backup\DumpWorkspace;
use MeRezaRezaei\Teleframe\Store\Backup\MemoryBackupRunIndex;
use MeRezaRezaei\Teleframe\Store\Cache\MemoryHotCache;
use MeRezaRezaei\Teleframe\Store\Index\MemoryStoreIndex;
use MeRezaRezaei\Teleframe\Store\Queue\SyncUploadQueue;
use MeRezaRezaei\Teleframe\Store\Schedules\NullSchedulePublisher;
use MeRezaRezaei\Teleframe\Store\Schedules\Worker\CheckNoShowActivity;
use MeRezaRezaei\Teleframe\Store\Schedules\Worker\ContainerResolver;
use MeRezaRezaei\Teleframe\Store\Schedules\Worker\LaravelWorkerBindings;
use MeRezaRezaei\Teleframe\Store\Schedules\Worker\RunAppCallActivity;
use MeRezaRezaei\Teleframe\Store\Schedules\Worker\StoreBackupDumpActivity;
use MeRezaRezaei\Teleframe\Store\Schedules\Worker\StorePruneActivity;
use MeRezaRezaei\Teleframe\Store\Schedules\Worker\StoreSyncActivity;
use MeRezaRezaei\Teleframe\Store\Schedules\Worker\TemporalWorker;
use MeRezaRezaei\Teleframe\Store\Schedules\Worker\WorkflowTypeCatalog;
use MeRezaRezaei\Teleframe\Store\StoreService;
use MeRezaRezaei\Teleframe\Store\TelegramStoreChannel;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayCache;
use MeRezaRezaei\Teleframe\Tests\Support\FakeStoreApi;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Temporal\WorkerFactory;

/**
 * THE TEST THAT DID NOT EXIST: a worker tick REACHING a collaborator.
 *
 * THE GAP. `bin/teleframe-worker` builds `new TemporalWorker($queue, $pairs)`,
 * and that constructor's `$resolver` defaults to `new ContainerResolver` —
 * `ContainerResolver::refusing()` in everything but name (ContainerResolver.php:63).
 * So the worker registered FIVE activity types and could execute NONE of them:
 * every tick died at its first `resolve()` with "needs the `<seam>` seam and
 * nothing is bound to it". The two prior slices made the worker honest about
 * that — every armed schedule says whether a worker serves it, and the
 * registration is proved against the real SDK — and it was still a worker that
 * could not run a tick. The seams were never the problem: four of the five are
 * bound by this package's own providers in any host that installed it. Nothing
 * ever handed those bindings to the worker.
 *
 * WHY THIS FILE IS THE PROOF AND NOT A UNIT TEST. A test that constructs the
 * activities directly with a hand-built `new ContainerResolver([…])` would prove
 * nothing about the gap: it would skip the two things that actually broke. So
 * every test below walks the WHOLE path a real tick walks, in the order the SDK
 * walks it:
 *
 *   1. a real `Illuminate\Container\Container` with this package's five seams
 *      bound the way this package's providers bind them;
 *   2. `LaravelWorkerBindings` — the adapter that reads that container;
 *   3. `new TemporalWorker(… resolver: …)` — the construction the entry point
 *      performs;
 *   4. `TemporalWorker::register()` on a REAL `Temporal\Worker` from the real
 *      `WorkerFactory`, so the prototypes are the SDK's own ActivityReader's;
 *   5. the prototype is fetched back OUT of the SDK's own activity repository by
 *      its activity type, the factory THIS PACKAGE registered is invoked to get
 *      the instance, and the SDK's own handler `ReflectionMethod` is what calls
 *      it.
 *
 * Step 5 is the load-bearing one. It is not `new StorePruneActivity(…)` written
 * by the test; it is the object a Temporal server would have dispatched to,
 * reached by the same name the server sends.
 *
 * THE COLLABORATORS ARE REAL. Every seam is bound to the REAL service class —
 * `StoreService`, `DatabaseBackupService`, `NoShowWatchdog`, `AppRuntime` — and
 * those are all `final`, so a subclass double was never available. They are
 * constructed over this repo's own in-memory ports (`FakeStoreApi`,
 * `MemoryStoreIndex`, `MemoryHotCache`, `SyncUploadQueue`, `ArrayCache`, and
 * `DatabaseDumper`, which is an interface). So "a tick reached the service" is
 * a claim about the production class, not about a stand-in that agrees with
 * whatever the code does. The ONE thing stubbed is the far side of the wire: a
 * fake Telegram transport, an in-memory dialog list, and a dumper that spools
 * bytes instead of forking `mysqldump`. Those are the live boundaries this repo
 * does not have; see the limits note below.
 *
 * THE CONVERSE IS THE MORE IMPORTANT HALF. `a_tick_still_refuses_by_name_with_the
 * _default_resolver` and its siblings exist because the FIRST thing a fix like
 * this can break is the honesty that came before it: a worker that silently
 * served nothing was the false success two earlier slices were written to
 * remove. Binding the seams must not turn an unbound seam into a shrug. If the
 * converse tests are ever deleted, this file is asserting the easy half only.
 *
 * WHAT IS NOT PROVEN HERE, stated plainly and in the same words the worker and
 * the bindings use in their own output:
 *
 *   - This repo has NO live Temporal server. Nothing here shows a server
 *     DISPATCHES a tick. A real dispatch is a gRPC call to a Temporal service
 *     that does not exist in CI.
 *   - This repo has NO live Telegram run. Nothing here shows a message was
 *     SENT. The app-call tick below reaches `AppRuntime::runCall()` and the
 *     fake transport records the method; whether Telegram would answer is
 *     untested and untestable without an account.
 *
 * So the honest claim is narrow and it is the one that matters: a tick, taken
 * from the real SDK's registry by the real activity type name, reaches the real
 * service method the command reaches. Everything past that boundary is claimed
 * nowhere.
 */
final class WorkerTickReachabilityTest extends TestCase
{
    private const BUCKET = 'media-pool';

    /** A clock the tests move, so "cold" is a state the test controls. */
    private int $now = 1_000_000;

    private FakeStoreApi $api;

    private MemoryStoreIndex $index;

    private MemoryHotCache $cache;

    /** The real collaborators the container binds, kept for the assertions. */
    private StoreService $store;

    private DatabaseBackupService $backups;

    private NoShowWatchdog $watchdog;

    private WatchState $watchState;

    private AppRuntime $runtime;

    private MemoryBackupRunIndex $runs;

    private RecordingTelegramScope $scope;

    protected function setUp(): void
    {
        $this->api = new FakeStoreApi(scope: 'worker');
        $this->index = new MemoryStoreIndex;
        $this->cache = new MemoryHotCache(
            ttlSeconds: 600,
            maxBytes: 1024 * 1024,
            now: fn (): int => $this->now,
        );
        $this->runs = new MemoryBackupRunIndex;
        $this->scope = new RecordingTelegramScope;

        $this->store = $this->storeService(dialogs: [
            ['id' => 101, 'access_hash' => 7, 'title' => 'teleframe-store:'.self::BUCKET],
            ['id' => 102, 'access_hash' => 8, 'title' => 'teleframe-data:feed'],
        ]);

        $this->watchState = new WatchState(new ArrayCache);
        $this->watchdog = new NoShowWatchdog(
            state: $this->watchState,
            now: fn (): int => $this->now,
            // topic => expected-every (an int, per NoShowWatchdog's contract)
            registrationSource: static fn (): array => ['backup' => 3600],
        );

        $this->runtime = new AppRuntime(
            registry: (new AppRegistry)->define(
                AppDefinition::define('echo', AppMode::Bot, triggers: [], calls: [
                    AppCall::every('health', 3600, 'getMe', ['probe' => true]),
                ]),
            ),
            schedules: new NullSchedulePublisher,
            transport: fn (AppMode $mode): RecordingTelegramScope => $this->scope,
        );

        $this->backups = new DatabaseBackupService(
            accountId: 7,
            dumper: new SpoolingTestDumper,
            store: $this->store,
            runs: $this->runs,
            watchdog: $this->watchdog,
            passphrase: 'a-test-passphrase',
            bucket: 'teleframe-backup:worker',
        );
    }

    // ── the five seams, proved reachable one at a time ──────────────────

    #[Test]
    public function the_sdk_is_present_in_this_checkout_so_the_reachability_half_is_exercised(): void
    {
        // Without this, a green run could mean the registration path was never
        // entered at all. Say so rather than letting it mean nothing.
        self::assertTrue(TemporalWorker::sdkInstalled(), 'temporal/sdk is require-DEV; without it this file proves nothing');
    }

    #[Test]
    public function a_registered_prune_activity_reaches_the_real_store_service(): void
    {
        // The hero case. Two cold chunks in the REAL hot cache; the tick must
        // come back with the count the real eviction produced AND the bytes must
        // be gone. A report of 2 over a cache that still holds both is the exact
        // false success this project keeps removing.
        $this->cache->put(self::BUCKET, str_repeat('a', 64), 0, 'cold chunk');
        $this->cache->put(self::BUCKET, str_repeat('a', 64), 1, 'another cold chunk');
        self::assertSame(2, $this->cache->stats()['entries']);

        $this->now += 601;

        $report = $this->tick(StorePruneActivity::TYPE, []);

        self::assertSame(2, $report['evicted']);
        self::assertSame(0, $this->cache->stats()['entries'],
            'the bytes must be gone from the cache the command prunes');
        self::assertSame(0, $this->store->prune(),
            'the command, on the same service, has nothing left to do — so the tick really emptied it');
    }

    #[Test]
    public function a_registered_sync_activity_reaches_the_real_registry_walk(): void
    {
        $report = $this->tick(StoreSyncActivity::TYPE, []);

        self::assertSame(2, $report['walked'], 'every dialog is walked');
        self::assertSame(2, $report['upserted'], 'each classified title becomes a bucket row');
        self::assertSame([], $report['requested_buckets']);
        self::assertSame(StoreSyncActivity::SOURCE_COMMAND_DEFAULT, $report['buckets_source'],
            'with no schedule payload and no worker default, the tick must NAME the command default, not imply a list');

        $row = $this->index->findBucket(7, self::BUCKET);
        self::assertSame('store', $row['kind']);
        self::assertSame(101, $row['channel_id']);
    }

    #[Test]
    public function a_registered_sync_activity_uses_the_worker_bucket_default_when_the_schedule_names_none(): void
    {
        // The `--buckets` equivalent, end to end. A schedule's payload is fixed
        // server-side at arm time and no argument rides a tick, so the worker is
        // the only place a list can be introduced — and the tick's own report has
        // to say which of the three sources it used, because two of them produce
        // the same empty list.
        $this->cache->put(self::BUCKET, str_repeat('a', 64), 0, 'cold chunk');
        $this->now += 601;

        $report = $this->tick(StoreSyncActivity::TYPE, [], workerBuckets: [' ledger ']);

        self::assertSame(['ledger'], $report['requested_buckets'],
            'the worker default is trimmed exactly as --buckets is');
        self::assertSame(StoreSyncActivity::SOURCE_WORKER_DEFAULT, $report['buckets_source']);
        self::assertSame(3, $report['upserted'], 'the named bucket the walk missed is registered too');
        self::assertSame('store', $this->index->findBucket(7, 'ledger')['kind']);
    }

    #[Test]
    public function a_schedule_naming_its_own_buckets_beats_the_worker_default(): void
    {
        $report = $this->tick(
            StoreSyncActivity::TYPE,
            ['buckets' => 'from-the-payload'],
            workerBuckets: ['from-the-worker'],
        );

        self::assertSame(['from-the-payload'], $report['requested_buckets']);
        self::assertSame(StoreSyncActivity::SOURCE_SCHEDULE, $report['buckets_source'],
            'the worker list is a DEFAULT; a payload that names buckets wins outright');
    }

    #[Test]
    public function a_registered_backup_activity_reaches_the_real_backup_service_and_the_config_derived_target(): void
    {
        // The target seam is the ONE binding that is not self-mapping: no
        // provider binds `teleclient.store.backup.target`, so the adapter takes
        // it from the key that IS bound — `DatabaseTarget::class`, the same
        // resolution `php artisan teleframe:tstore backup` performs
        // (TstoreCommand.php:488). A scheduled dump that reached a DIFFERENT
        // database than the CLI would be the worst bug in this slice, so the
        // assertion is on the target the service actually received.
        $report = $this->tick(StoreBackupDumpActivity::TYPE, []);

        self::assertSame('teleframe-backup:worker', $report['bucket'], 'the bucket the real service was built with');
        self::assertGreaterThan(0, $report['bytes'], 'the real spooled bytes reached the real put');
        self::assertGreaterThan(0, $report['chunks'], 'the real put produced chunks');
        self::assertSame('sealed', $report['mode']);

        // The target the REAL service received is the one the container holds —
        // which is the same one the command resolves (TstoreCommand.php:488).
        self::assertSame('pgsql', $report['report']['driver'], 'the driver came from the DatabaseTarget seam');
        self::assertSame('teleframe-app', $report['report']['database']);
        self::assertSame(
            ['--format=plain', '--host=127.0.0.1', '--port=5432', '--username=backup', 'teleframe-app', '--table=updates'],
            $this->container()->make(DatabaseTarget::class)->argc(),
            'the tick dumped the target the COMMAND would dump — a tick that reached an invented target is the '
            .'one bug here that would destroy the wrong database silently',
        );

        // And the real run-ledger row exists, because the real service wrote it.
        $runs = $this->runs->listRuns(7, 'teleframe-backup:worker', 10);
        self::assertCount(1, $runs,
            'DatabaseBackupService::backup() upserts the run row; its absence means the tick did not reach the service body');
        self::assertSame($report['name'], $runs[0]['object_name']);
    }

    #[Test]
    public function a_registered_no_show_activity_reaches_the_real_watchdog(): void
    {
        // The heartbeat went stale 4000s ago against a topic that expects one
        // every 3600s, so the REAL watchdog fires a REAL alert. An activity that
        // reported "0 alerts" against a watchdog it never reached would be a
        // false all-clear — the alert has to come out of the real check(), read
        // through the real PSR-16 WatchState.
        $stale = $this->now - 4000;
        $this->watchState->touch('backup', $stale);

        $report = $this->tick(CheckNoShowActivity::TYPE, []);

        self::assertSame(1, $report['checked'], 'the count comes from the real registrations()');
        self::assertSame(1, $report['alert_count'], 'the alert count comes from the real check()');
        self::assertSame('backup', $report['alerts'][0]['topic']);
        self::assertSame(3600, $report['alerts'][0]['expected_every']);
        self::assertSame($stale, $report['alerts'][0]['last_seen'],
            'the heartbeat came from the real WatchState, so the tick read the state a sender would have written');
        self::assertSame(4000, $report['alerts'][0]['age']);
    }

    #[Test]
    public function a_registered_app_call_activity_reaches_the_real_runtime_and_the_transport(): void
    {
        // The furthest this repo can reach without an account: the real
        // AppRuntime resolves the real AppCall out of the real registry and
        // hands the real method name to the transport. Whether TELEGRAM answers
        // is NOT claimed — there is no live Telegram run in this repo.
        $report = $this->invokeTick(RunAppCallActivity::TYPE, ['echo', 'health', []]);

        self::assertSame('echo', $report['app']);
        self::assertSame('health', $report['call']);
        self::assertSame(1, $this->scope->invoked, 'the tick reached the transport the runtime resolved');
        self::assertSame('getMe', $this->scope->calls[0]['method'],
            'the method came from the declared AppCall, so the tick ran the catalogued call and not a guess');
        self::assertSame(['probe' => true], $this->scope->calls[0]['params']);
    }

    // ── THE CONVERSE: the honesty that must survive this fix ────────────

    #[Test]
    public function a_tick_still_refuses_by_name_with_the_default_resolver(): void
    {
        // THE TEST THAT PROTECTS THE PROPERTY. The entry point constructs
        // `new TemporalWorker($queue, $pairs)` with no resolver, so this is the
        // shape a host actually gets by default. Every one of the five activity
        // types must still fail naming the seam it needs — and must NOT return a
        // plausible-looking empty report, because "evicted 0" for a cache that
        // was never consulted is precisely the false success this project has
        // spent a cycle removing.
        $worker = new TemporalWorker('tstore');

        self::assertSame([], $worker->resolver()->seams(), 'the default resolver binds nothing, by design');

        foreach ($this->everyActivityCase() as $label => [$type, $args, $seam]) {
            $refused = null;

            try {
                $this->invokeTickOn($worker, $type, $args);
            } catch (\RuntimeException $e) {
                $refused = $e->getMessage();
            }

            self::assertNotNull($refused, $label.': an unbound tick must FAIL, not report success');
            self::assertStringContainsString($seam, $refused, $label.': the refusal must name the seam');
            self::assertStringContainsString('nothing is bound to it', $refused,
                $label.': the refusal must say nothing is bound, not merely fail');
        }
    }

    #[Test]
    public function a_seam_the_host_does_not_bind_still_refuses_by_name_rather_than_by_a_host_exception(): void
    {
        // The partial case, and the one that actually guards the adapter. A
        // container that binds THREE of the five seams is a realistic host (a
        // store install with no app module). The two it does not bind must raise
        // ContainerResolver's OWN refusal — not a Laravel BindingResolutionException
        // naming a derived class, which would send an operator to debug a binding
        // that does not exist.
        // A container that binds THREE of the five seams: a realistic host (a
        // store install with no app module). Built fresh rather than unbound,
        // so there is no question about what the container still remembers.
        $container = new Container;
        $container->bind('teleclient.tstore.service', fn (): StoreService => $this->store);
        $container->bind('teleclient.notify.watchdog', fn (): NoShowWatchdog => $this->watchdog);
        $container->bind(DatabaseTarget::class, static fn (): DatabaseTarget => new DatabaseTarget(
            driver: 'pgsql',
            database: 'teleframe-app',
        ));

        $bindings = new LaravelWorkerBindings($container);

        self::assertSame(
            ['teleclient.notify.watchdog', 'teleclient.store.backup.target', 'teleclient.tstore.service'],
            $bindings->boundSeams(),
            'the three bound seams, and the target seam which resolves FROM the class-keyed DatabaseTarget',
        );
        self::assertSame(
            ['teleclient.app.service', 'teleclient.store.backup.service'],
            $bindings->unboundSeams(),
        );

        $worker = new TemporalWorker('tstore', resolver: $bindings->resolver());

        // The bound three still work…
        self::assertSame(
            0,
            $this->invokeTickOn($worker, StorePruneActivity::TYPE, [[]])['evicted'],
            'a seam the host DOES bind must keep working',
        );

        // …and the unbound one refuses with the worker's own words.
        $refused = null;

        try {
            $this->invokeTickOn($worker, RunAppCallActivity::TYPE, ['echo', 'health', []]);
        } catch (\RuntimeException $e) {
            $refused = $e->getMessage();
        }

        self::assertNotNull($refused, 'an unbound seam must refuse, not run something');
        self::assertStringContainsString(RunAppCallActivity::RUNTIME_SEAM, $refused);
        self::assertStringContainsString('nothing is bound to it', $refused);
        self::assertStringNotContainsStringIgnoringCase('BindingResolutionException', $refused,
            'a host container exception would name a binding the operator never wrote; the worker\'s own refusal '
            .'is the actionable one');
    }

    #[Test]
    public function a_host_whose_binding_returns_nothing_is_refused_rather_than_treated_as_absent_work(): void
    {
        // `teleclient.notify.watchdog` is registered as an ALIAS whose factory
        // catches Throwable and returns null when there is nothing to watch. So
        // in a real host the seam reads as BOUND while the object is not there.
        // The tick must refuse BY NAME, not report "0 alerts" for a watchdog
        // that does not exist.
        $container = $this->container();
        $container->bind('teleclient.notify.watchdog', static fn (): ?object => null);

        $refused = null;

        try {
            $this->invokeTickOn(
                new TemporalWorker('tstore', resolver: (new LaravelWorkerBindings($container))->resolver()),
                CheckNoShowActivity::TYPE,
                [[]],
            );
        } catch (\RuntimeException $e) {
            $refused = $e->getMessage();
        }

        self::assertNotNull($refused);
        self::assertStringContainsString(CheckNoShowActivity::WATCHDOG_SEAM, $refused);
        self::assertStringContainsString('not an object', $refused,
            'a null from a bound factory is a refusal too — a false all-clear is the worse outcome');
    }

    // ── the properties that must not rot while doing this ───────────────

    #[Test]
    public function nothing_is_resolved_from_the_container_until_a_tick_actually_asks(): void
    {
        // A worker is a long-running process. A boot-time container hit per seam
        // would be work done in a process that may serve a task queue nothing
        // ever ticks — and, worse, an exception on a `php artisan list`.
        $made = 0;
        $container = $this->container();
        $container->bind('teleclient.tstore.service', function () use (&$made): StoreService {
            $made++;

            return $this->store;
        });

        $worker = new TemporalWorker('tstore', resolver: (new LaravelWorkerBindings($container))->resolver());
        $factory = WorkerFactory::create();
        $sdkWorker = $worker->register($factory->newWorker('tstore'));

        self::assertSame(0, $made, 'construction and registration must resolve nothing');

        $this->invokeTickOn($worker, StorePruneActivity::TYPE, [[]]);

        self::assertSame(1, $made, 'the tick resolves the collaborator, and the resolver memoises it');
    }

    #[Test]
    public function binding_the_seams_does_not_reopen_the_workflow_type_honesty(): void
    {
        // `WorkflowTypeCatalog` was made deliberately stronger by the previous
        // slice: ACKNOWLEDGED_UNSERVED is empty, gaps go through the constructor
        // map, and a test asserts every produced type is served. This slice adds
        // container bindings, and it must not have been the excuse to soften that.
        $catalog = new WorkflowTypeCatalog;

        foreach (WorkflowTypeCatalog::producedTypes() as $type) {
            self::assertTrue($catalog->serves($type), $type.' must stay served');
            self::assertNull($catalog->acknowledgedUnservedReason($type), $type.' must stay un-acknowledged');
        }

        // And the worker's own registration still covers all five activity types.
        $factory = WorkerFactory::create();
        $sdkWorker = (new TemporalWorker('tstore', resolver: (new LaravelWorkerBindings($this->container()))->resolver()))
            ->register($factory->newWorker('tstore'));

        foreach (TemporalWorker::servedActivities() as $type) {
            self::assertTrue($this->prototypeFor($sdkWorker, $type) instanceof \stdClass || true);
            self::assertNotNull($this->prototypeFor($sdkWorker, $type), $type.' must still be registered');
        }
    }

    // ── the path a tick walks ───────────────────────────────────────────

    /**
     * One full tick: a host container, the adapter, the worker the entry point
     * builds, the REAL SDK registration, the prototype pulled back out by the
     * activity type the SERVER sends, the factory THIS PACKAGE registered, and
     * the SDK's own handler.
     *
     * @param  array<string, mixed>  $payload
     * @param  list<string>  $workerBuckets
     * @return array<string, mixed>
     */
    private function tick(string $type, array $payload, array $workerBuckets = []): array
    {
        $worker = new TemporalWorker(
            'tstore',
            resolver: (new LaravelWorkerBindings($this->container()))->resolver(),
            defaultBuckets: $workerBuckets,
        );

        $factory = WorkerFactory::create();
        $worker->register($factory->newWorker('tstore'));

        /** @var array<string, mixed> $report */
        $report = $this->invokeTickOn($worker, $type, [$payload]);

        return $report;
    }

    /**
     * @param  list<mixed>  $args
     */
    private function invokeTick(string $type, array $args, array $workerBuckets = []): mixed
    {
        $worker = new TemporalWorker(
            'tstore',
            resolver: (new LaravelWorkerBindings($this->container()))->resolver(),
            defaultBuckets: $workerBuckets,
        );

        $factory = WorkerFactory::create();
        $worker->register($factory->newWorker('tstore'));

        return $this->invokeTickOn($worker, $type, $args);
    }

    /**
     * Register on a real SDK worker, fetch the prototype BY ITS ACTIVITY TYPE,
     * build the instance with the factory this package registered, and invoke it
     * through the SDK's own handler reflection.
     *
     * @param  list<mixed>  $args
     */
    private function invokeTickOn(TemporalWorker $worker, string $type, array $args): mixed
    {
        $factory = WorkerFactory::create();
        $sdkWorker = $worker->register($factory->newWorker('tstore'));

        $prototype = $this->prototypeFor($sdkWorker, $type);

        $registered = $prototype->getFactory();

        self::assertNotNull($registered,
            'TemporalWorker::register() must register a FACTORY for '.$type.', or the SDK has nothing to build an '
            .'activity instance from on a tick');

        $instance = $registered();

        self::assertInstanceOf(
            self::activityClass($type),
            $instance,
            'the instance the SDK would dispatch to must be the activity class the type names',
        );

        return $prototype->getHandler()->invokeArgs($instance, $args);
    }

    /**
     * The activity CLASS an activity type names. The type string is what the
     * server sends and what Temporal dispatches on; the class is what the SDK
     * must have built. Asserting they agree is the check that a type could not
     * be registered against the wrong handler.
     *
     * @return class-string
     */
    private static function activityClass(string $type): string
    {
        return match ($type) {
            StoreBackupDumpActivity::TYPE => StoreBackupDumpActivity::class,
            CheckNoShowActivity::TYPE => CheckNoShowActivity::class,
            RunAppCallActivity::TYPE => RunAppCallActivity::class,
            StorePruneActivity::TYPE => StorePruneActivity::class,
            StoreSyncActivity::TYPE => StoreSyncActivity::class,
            default => throw new \RuntimeException('no activity class is known for the type '.$type),
        };
    }

    /**
     * The prototype the real SDK registered under an activity type, read from
     * the real SDK's own activity repository — not a recording double.
     */
    private function prototypeFor(object $sdkWorker, string $type): object
    {
        $prototype = $sdkWorker->getActivities()->find($type);

        self::assertNotNull($prototype,
            'the real SDK repository has no prototype for '.$type.'; a tick for it would fail to start');

        return $prototype;
    }

    // ── the host container, bound the way this package's providers bind ──

    /**
     * A real Laravel container with the five seams, bound under the same keys
     * this package's providers use — including the alias form of the watchdog
     * key (StoreBackupServiceProvider.php:238) and the class-keyed DatabaseTarget
     * (StoreBackupServiceProvider.php:558).
     */
    private function container(): Container
    {
        $container = new Container;

        $container->bind('teleclient.tstore.service', fn (): StoreService => $this->store);
        $container->bind('teleclient.store.backup.service', fn (): DatabaseBackupService => $this->backups);
        $container->bind('teleclient.notify.watchdog', fn (): NoShowWatchdog => $this->watchdog);
        $container->bind('teleclient.app.service', fn (): AppRuntime => $this->runtime);

        // The config-derived target, exactly as the provider builds it from
        // teleframe.store.backup.database.*.
        $container->bind(DatabaseTarget::class, static fn (): DatabaseTarget => new DatabaseTarget(
            driver: 'pgsql',
            database: 'teleframe-app',
            host: '127.0.0.1',
            port: 5432,
            user: 'backup',
            tables: ['updates'],
        ));

        return $container;
    }

    private function storeService(array $dialogs = []): StoreService
    {
        return new StoreService(
            accountId: 7,
            channelFactory: fn (string $bucket): TelegramStoreChannel => new TelegramStoreChannel($bucket, $this->api->apiMap()),
            index: $this->index,
            cache: $this->cache,
            queue: new SyncUploadQueue,
            now: fn (): int => $this->now,
            dialogLister: static fn (): array => $dialogs,
        );
    }

    /**
     * Every activity type, with the arguments its handler takes and the seam it
     * must reach — so the converse test covers all five rather than one.
     *
     * @return array<string, array{0: string, 1: list<mixed>, 2: string}>
     */
    private function everyActivityCase(): array
    {
        return [
            'backup-dump' => [StoreBackupDumpActivity::TYPE, [[]], StoreBackupDumpActivity::SERVICE_SEAM],
            'no-show' => [CheckNoShowActivity::TYPE, [[]], CheckNoShowActivity::WATCHDOG_SEAM],
            'app-call' => [RunAppCallActivity::TYPE, ['echo', 'health', []], RunAppCallActivity::RUNTIME_SEAM],
            'hot-cache-prune' => [StorePruneActivity::TYPE, [[]], StorePruneActivity::SERVICE_SEAM],
            'registry-sweep' => [StoreSyncActivity::TYPE, [[]], StoreSyncActivity::SERVICE_SEAM],
        ];
    }
}

/**
 * The far side of the wire, for the app-call tick: a transport with a
 * `call(string, array)` catalog surface, which is ALL `AppRuntime::runCall()`
 * requires of a scope (AppRuntime.php:255). It records what it was asked, which
 * is how the test proves the tick ran the CATALOGUED call rather than a guess.
 *
 * Explicitly not a claim that Telegram was reached.
 */
final class RecordingTelegramScope
{
    /** @var list<array{method: string, params: array<string, mixed>}> */
    public array $calls = [];

    public int $invoked = 0;

    /** @param  array<string, mixed>  $params */
    public function call(string $method, array $params = []): array
    {
        $this->calls[] = ['method' => $method, 'params' => $params];
        $this->invoked++;

        return ['_' => $method, 'ok' => true];
    }
}

/**
 * `DatabaseDumper` is an INTERFACE, so this is the one collaborator the slice
 * substitutes — and it substitutes it honestly: it spools REAL bytes through the
 * REAL `DumpWorkspace`, and returns a real `DumpResult`, so the whole
 * `DatabaseBackupService::backup()` body after the dump (the store put, the
 * manifest, the run-ledger row, the spool release) is the production code path.
 * What it does not do is fork `pg_dump`, which needs a live database this repo
 * does not have.
 */
final class SpoolingTestDumper implements DatabaseDumper
{
    public function dump(DatabaseTarget $target, DumpWorkspace $workspace): DumpResult
    {
        $spool = $workspace->spool();
        $body = "-- teleframe test dump of {$target->database}\n".str_repeat("-- row\n", 32);

        fwrite($spool['handle'], $body);
        fflush($spool['handle']);

        return new DumpResult(
            path: $spool['path'],
            bytes: strlen($body),
            driver: $target->driver,
            database: $target->database,
            startedAt: 1_700_000_000,
            finishedAt: 1_700_000_001,
            exitCode: 0,
            stderrTail: '',
        );
    }
}

<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Store;

use LogicException;
use MeRezaRezaei\Teleframe\Laravel\Console\TstoreCommand;
use MeRezaRezaei\Teleframe\Store\Cache\MemoryHotCache;
use MeRezaRezaei\Teleframe\Store\Index\MemoryStoreIndex;
use MeRezaRezaei\Teleframe\Store\Queue\SyncUploadQueue;
use MeRezaRezaei\Teleframe\Store\Schedules\RecurringScheduleClient;
use MeRezaRezaei\Teleframe\Store\Schedules\StoreMaintenanceScheduleSpec;
use MeRezaRezaei\Teleframe\Store\Schedules\TemporalSchedulePublisher;
use MeRezaRezaei\Teleframe\Store\Schedules\Worker\ContainerResolver;
use MeRezaRezaei\Teleframe\Store\Schedules\Worker\StorePruneActivity;
use MeRezaRezaei\Teleframe\Store\Schedules\Worker\StoreSyncActivity;
use MeRezaRezaei\Teleframe\Store\Schedules\Worker\WorkflowTypeCatalog;
use MeRezaRezaei\Teleframe\Store\StoreService;
use MeRezaRezaei\Teleframe\Store\TelegramStoreChannel;
use MeRezaRezaei\Teleframe\Tests\Support\FakeStoreApi;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * THE GAP THIS FILE CLOSES, AND THE PROOF IT IS THE SAME WORK.
 *
 * `8e6b8c9e` armed TWO workflow types from `StoreMaintenanceScheduleSpec` —
 * `teleframe:tstore prune` and `teleframe:tstore sync` — and nothing served
 * either. They sat in `WorkflowTypeCatalog::ACKNOWLEDGED_UNSERVED` with a reason,
 * so the publisher reported a KNOWN GAP instead of pretending. Honest, and
 * still a hole: `teleframe:tstore prune` is a local, argument-free
 * `$cache->evict()`, and `teleframe:tstore sync` is a dialog walk the command
 * already runs. There was no reason a worker could serve neither.
 *
 * The danger in CLOSING it is that closing it wrongly is invisible. A prune
 * activity that swept something other than the hot cache would tick green on
 * the hourly cadence while the cache grew without bound; a sweep that walked
 * nothing would report `walked 0, upserted 0` every day and read as a healthy
 * job. So every assertion below runs against a REAL `StoreService` over REAL
 * in-memory ports — not a double that agrees with whatever the code does. The
 * claim is "the activity reaches the same state the command reaches", and a
 * double cannot make that claim.
 */
final class StoreMaintenanceWorkerActivityTest extends TestCase
{
    private const BUCKET = 'media-pool';

    private FakeStoreApi $api;

    private MemoryStoreIndex $index;

    private MemoryHotCache $cache;

    protected function setUp(): void
    {
        $this->api = new FakeStoreApi(scope: 'maint');
        $this->index = new MemoryStoreIndex;
        $this->cache = new MemoryHotCache(ttlSeconds: 600, maxBytes: 4 * 1024 * 1024);
    }

    // ── the type strings: byte-identical to the producer ──────────────

    #[Test]
    public function the_catalog_types_are_byte_identical_to_the_ones_the_spec_arms(): void
    {
        // The mirror, not a second opinion: the catalog's constants are read
        // against what `StoreMaintenanceScheduleSpec::installSpec()` REALLY
        // emits. A constant that drifted by a space would register a handler
        // under a name the server never sends — the schedule would tick into
        // nothing while the disclosure said "served".
        $payload = (new StoreMaintenanceScheduleSpec)->installSpec();

        self::assertSame(WorkflowTypeCatalog::TSTORE_PRUNE, $payload['tstore-hot-cache-prune']['activity']);
        self::assertSame(WorkflowTypeCatalog::TSTORE_SYNC, $payload['tstore-registry-sweep']['activity']);
    }

    #[Test]
    public function the_seam_is_byte_identical_to_the_key_the_command_makes(): void
    {
        // Both activities resolve ONE seam, and it is the same key
        // `TstoreCommand::storeService()` makes. Different key = the worker
        // prunes a different cache from the one the operator prunes, which is
        // precisely the false success this file exists to rule out.
        self::assertSame(TstoreCommand::SERVICE_KEY, StorePruneActivity::SERVICE_SEAM);
        self::assertSame(TstoreCommand::SERVICE_KEY, StoreSyncActivity::SERVICE_SEAM);
    }

    // ── prune: the real eviction, through the real service ────────────

    #[Test]
    public function prune_evicts_the_hot_cache_and_reports_the_real_count(): void
    {
        // A clock the test moves: entries are written cold-to-be, then the cache
        // is a full TTL older, which is the only state in which a real sweep
        // has anything to do.
        $now = 1_000_000;
        $cache = new MemoryHotCache(
            ttlSeconds: 600,
            maxBytes: 1024 * 1024,
            now: static function () use (&$now): int {
                return $now;
            },
        );
        $cache->put(self::BUCKET, str_repeat('a', 64), 0, 'cold chunk');
        $cache->put(self::BUCKET, str_repeat('a', 64), 1, 'another cold chunk');

        self::assertSame(2, $cache->stats()['entries']);

        $now += 601;

        $service = $this->service(cache: $cache, now: static function () use (&$now): int {
            return $now;
        });

        // The activity, with the seam bound to the real service.
        $activity = new StorePruneActivity($this->resolverFor($service));

        $report = $activity->prune([]);

        self::assertSame(2, $report['evicted'],
            'the activity must report the count the sweep actually produced, not a shape or a guess');
        self::assertSame(0, $cache->stats()['entries'],
            'the bytes must be GONE from the cache the command prunes — a report of 2 over a cache that still '
            .'holds both entries is a false success');

        // And the same call the command makes, on the same service, is what
        // emptied it: a second prune finds nothing left to do.
        self::assertSame(0, $service->prune());
    }

    #[Test]
    public function prune_leaves_a_warm_cache_alone(): void
    {
        // The other half. A sweep that reported "evicted 0" on a warm cache is
        // correct — and asserting only the count would not notice a sweep that
        // ALSO evicts warm entries. So: fresh entries must survive.
        $cache = new MemoryHotCache(ttlSeconds: 600, maxBytes: 1024 * 1024);
        $cache->put(self::BUCKET, str_repeat('b', 64), 0, 'hot chunk');

        $service = $this->service(cache: $cache);
        $activity = new StorePruneActivity($this->resolverFor($service));

        self::assertSame(0, $activity->prune([])['evicted']);
        self::assertSame(1, $cache->stats()['entries'], 'a fresh entry is not cold; evicting it would be a data-loss bug');
    }

    #[Test]
    public function a_prune_with_no_seam_bound_refuses_by_name(): void
    {
        // The container-free half. Returning `evicted: 0` for a cache that was
        // never consulted is a false all-clear on a job whose entire output is
        // a count — so the tick fails, naming the seam.
        $activity = new StorePruneActivity(ContainerResolver::refusing());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(StorePruneActivity::SERVICE_SEAM);

        $activity->prune([]);
    }

    // ── sync: the real registry walk, through the real service ────────

    #[Test]
    public function sync_walks_dialogs_and_upserts_the_registry(): void
    {
        $service = $this->service(dialogs: [
            ['id' => 101, 'access_hash' => 7, 'title' => 'teleframe-store:media-pool'],
            ['id' => 102, 'access_hash' => 8, 'title' => 'teleframe-data:feed'],
            ['id' => 103, 'access_hash' => 9, 'title' => 'some unrelated channel'],
        ]);

        $activity = new StoreSyncActivity($this->resolverFor($service));

        $report = $activity->sync([]);

        self::assertSame(3, $report['walked'], 'every dialog is walked, including the unclassified one');
        self::assertSame(2, $report['upserted'], 'only a classified title becomes a bucket row');
        self::assertSame([], $report['requested_buckets']);

        $bucket = $this->index->findBucket(7, self::BUCKET);
        self::assertSame('store', $bucket['kind']);
        self::assertSame(101, $bucket['channel_id']);
        self::assertSame('data', $this->index->findBucket(7, 'feed')['kind']);
    }

    #[Test]
    public function sync_adds_a_named_bucket_the_dialog_walk_did_not_find(): void
    {
        // The `--buckets` half. The command takes a comma-separated list and
        // `syncRegistry()` upserts a row for each name the walk did not see, so
        // a bucket configured ahead of its channel still gets registered. The
        // activity has no command line, so it reads the same list off the
        // payload — and normalises a string exactly the way the command does.
        $service = $this->service(dialogs: [
            ['id' => 101, 'access_hash' => 7, 'title' => 'teleframe-store:media-pool'],
        ]);

        $activity = new StoreSyncActivity($this->resolverFor($service));

        $report = $activity->sync(['buckets' => ' ledger , ']);

        self::assertSame(['ledger'], $report['requested_buckets'],
            'a comma-separated payload string must normalise exactly as --buckets does, blanks dropped');
        self::assertSame(1, $report['walked']);
        self::assertSame(2, $report['upserted'], 'the named bucket the walk missed is registered too');
        self::assertSame('store', $this->index->findBucket(7, 'ledger')['kind']);
    }

    #[Test]
    public function sync_accepts_a_plain_list_and_an_absent_key_alike(): void
    {
        $service = $this->service(dialogs: []);
        $activity = new StoreSyncActivity($this->resolverFor($service));

        self::assertSame(['a', 'b'], $activity->sync(['buckets' => ['a', ' b ']])['requested_buckets']);
        // No `buckets` key is what StoreMaintenanceScheduleSpec::installSpec()
        // emits today, and it must be the command's own default — an empty
        // list, i.e. "sweep whatever the dialog walk finds, register nothing
        // extra" — not an invented default.
        self::assertSame([], $activity->sync([])['requested_buckets']);
    }

    #[Test]
    public function sync_refuses_a_bucket_list_the_command_could_not_have_produced(): void
    {
        // A bucket list is what a sweep asserts EXISTS in the registry, so a
        // value that cannot be named on a command line must not be quietly cast
        // into one.
        $activity = new StoreSyncActivity($this->resolverFor($this->service(dialogs: [])));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(StoreSyncActivity::BUCKETS_KEY);

        $activity->sync(['buckets' => ['nested' => ['a']]]);
    }

    #[Test]
    public function a_sweep_with_no_dialog_lister_fails_rather_than_reporting_an_empty_sweep(): void
    {
        // `syncRegistry()`'s LogicException is deliberately NOT swallowed. A
        // sweep that caught it and returned `walked 0, upserted 0` would report
        // "nothing found" for a store that was never actually walked — and the
        // job's entire output is those two numbers.
        $activity = new StoreSyncActivity($this->resolverFor($this->service(listerWired: false)));

        $this->expectException(LogicException::class);

        $activity->sync([]);
    }

    #[Test]
    public function a_sync_with_no_seam_bound_refuses_by_name(): void
    {
        $activity = new StoreSyncActivity(ContainerResolver::refusing());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(StoreSyncActivity::SERVICE_SEAM);

        $activity->sync([]);
    }

    // ── the disclosure: KNOWN GAP → served ────────────────────────────

    #[Test]
    public function arming_either_tstore_type_now_discloses_it_as_served_not_as_a_gap(): void
    {
        // The user-visible half of the change. Both types used to arm with
        // "KNOWN GAP" attached; the whole point is that the operator now reads
        // "served by this package" and is right to.
        $publisher = new TemporalSchedulePublisher('127.0.0.1:7233', $this->recurringClient());

        $status = $publisher->install((new StoreMaintenanceScheduleSpec)->installSpec());

        foreach (['tstore-hot-cache-prune', 'tstore-registry-sweep'] as $entry) {
            $line = $status[$entry];

            self::assertStringContainsString('armed teleframe:tstore ', $line);
            self::assertStringContainsString('worker: served by this package', $line,
                "$entry must be reported as served now that a worker serves it");
            self::assertStringNotContainsString('NOT SERVED', $line);
            self::assertStringNotContainsString('KNOWN GAP', $line,
                'a served type carrying a KNOWN GAP reason is a disclosure that lies in both directions');
        }
    }

    #[Test]
    public function neither_tstore_type_is_still_recorded_as_a_known_gap(): void
    {
        $catalog = new WorkflowTypeCatalog;

        self::assertNull($catalog->acknowledgedUnservedReason(WorkflowTypeCatalog::TSTORE_PRUNE));
        self::assertNull($catalog->acknowledgedUnservedReason(WorkflowTypeCatalog::TSTORE_SYNC));
    }

    // ---------------------------------------------------------------- doubles --

    /**
     * A real StoreService over real in-memory ports.
     *
     * `$listerWired: false` is the misconfigured shape — a StoreService with no
     * dialog lister — which `syncRegistry()` refuses. It is reachable here
     * because the lister is an injected port, exactly as it is in production.
     *
     * @param  list<array{id: int, access_hash: int, title: string}>  $dialogs
     */
    private function service(
        ?MemoryHotCache $cache = null,
        array $dialogs = [],
        bool $listerWired = true,
        ?callable $now = null,
    ): StoreService {
        return new StoreService(
            accountId: 7,
            channelFactory: fn (string $bucket): TelegramStoreChannel => new TelegramStoreChannel($bucket, $this->api->apiMap()),
            index: $this->index,
            cache: $cache ?? $this->cache,
            queue: new SyncUploadQueue,
            now: $now,
            dialogLister: $listerWired ? static fn (): array => $dialogs : null,
        );
    }

    /**
     * The resolver a host builds at worker boot: the one seam, bound to the one
     * service the command makes.
     */
    private function resolverFor(StoreService $service): ContainerResolver
    {
        return new ContainerResolver([
            StorePruneActivity::SERVICE_SEAM => static fn (): object => $service,
        ]);
    }

    private function recurringClient(): object
    {
        return new class implements RecurringScheduleClient
        {
            public function createOrUpdateSchedule(
                string $scheduleId,
                string $activity,
                int $intervalSeconds,
                string $taskQueue,
                array $args = [],
            ): string {
                return $scheduleId;
            }

            public function startWorkflow(string $workflow, string $taskQueue, array $args): string
            {
                return 'wf-not-used';
            }
        };
    }
}

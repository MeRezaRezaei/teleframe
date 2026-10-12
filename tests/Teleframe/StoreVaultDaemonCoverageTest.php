<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Teleframe;

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;
use MeRezaRezaei\Teleframe\Core\Contracts\SignalSink;
use MeRezaRezaei\Teleframe\Daemon\AccountPoller;
use MeRezaRezaei\Teleframe\Store\Index\Models\TfStoreBucket;
use MeRezaRezaei\Teleframe\Store\Index\Models\TfStoreMedia;
use MeRezaRezaei\Teleframe\Store\Index\Models\TfStoreObject;
use MeRezaRezaei\Teleframe\Store\Queue\SyncUploadQueue;
use MeRezaRezaei\Teleframe\Store\Queue\UploadQueueContract;
use MeRezaRezaei\Teleframe\Store\Schedules\NullSchedulePublisher;
use MeRezaRezaei\Teleframe\Store\Schedules\SchedulePublisher;
use MeRezaRezaei\Teleframe\Tests\Schema\TestCase;
use MeRezaRezaei\Teleframe\Tests\Store\ShippedStoreSchema;
use MeRezaRezaei\Teleframe\Vault\VaultConfig;

/**
 * W-22: AccountPoller, SchedulePublisher, UploadQueueContract, TfStore*
 * models and VaultConfig previously had ZERO direct test references (the
 * W-03 suite wiring was done; these named classes were still untested).
 *
 *  - AccountPoller: the supervised-poller policy inversion — rethrow except
 *    when stop() won the race (clean break). Pure errorLoopAction contract.
 *  - VaultConfig: env-driven default-account resolution (config() wins, env
 *    fallback, identity chain) — exercised with putenv bounds.
 *  - SchedulePublisher / UploadQueueContract: the seam contract itself is
 *    pinned via the shipped null/sync drivers (install shape, enqueue id,
 *    consume drain count, isAsync flag).
 *  - TfStore* Eloquent models: table/keys/timestamps/guarded + JSON casts on
 *    a real sqlite schema.
 */
final class StoreVaultDaemonCoverageTest extends TestCase
{
    // Eloquent mass-assignment guarding is process-global; the TfStore*
    // model tests unguard() inside withStoreTables. Assert no unguarded
    // state leaks past this suite into later in-process suites.
    protected function tearDown(): void
    {
        self::assertFalse(Model::isUnguarded(), 'mass-assignment guard must be restored');
        parent::tearDown();
    }

    // ---- AccountPoller: supervised error policy ----

    private static function poller(): AccountPoller
    {
        return (new class extends AccountPoller {})->withSignalSink(
            new class implements SignalSink
            {
                public function gapDetected(string $kind, array $context): void {}

                public function resynced(array $state, int $accountId): void {}
            },
        );
    }

    public function test_account_poller_rethrows_non_stop_errors(): void
    {
        $poller = self::poller();

        $action = (function (): string {
            return $this->errorLoopAction(new \RuntimeException('flood wait'));
        })->call($poller);

        self::assertSame('rethrow', $action);
    }

    public function test_account_poller_breaks_when_stop_won_the_race(): void
    {
        $poller = self::poller();
        $poller->stop(); // stop() flips $running = false → the only permitted break

        $action = (function (): string {
            return $this->errorLoopAction(new \RuntimeException('whatever'));
        })->call($poller);

        self::assertSame('break', $action);
    }

    public function test_account_poller_fresh_instance_resets_running_flag(): void
    {
        // Loop entrance (pollBot/pollUser) sets running=true on EVERY run;
        // AccountWorker constructs a fresh AccountPoller per cycle, so a
        // stopped poller is never reused. A fresh instance must behave as
        // running (rethrow), not inherit the previous stop.
        $stopped = self::poller();
        $stopped->stop();
        self::assertSame('break', (function (): string {
            return $this->errorLoopAction(new \RuntimeException('x'));
        })->call($stopped));

        $fresh = self::poller();
        self::assertSame('rethrow', (function (): string {
            return $this->errorLoopAction(new \RuntimeException('x'));
        })->call($fresh));
    }

    // ---- VaultConfig: default-account resolution ----

    public function test_vault_default_account_prefers_config(): void
    {
        config()->set('teleframe.vault.default_account', 'config-account');

        self::assertSame('config-account', VaultConfig::defaultAccountId());

        config()->set('teleframe.vault.default_account', null);
    }

    public function test_vault_default_account_falls_back_to_env(): void
    {
        config()->set('teleframe.vault.default_account', null);
        $prev = getenv('TELEFRAME_DEFAULT_ACCOUNT_ID');
        putenv('TELEFRAME_DEFAULT_ACCOUNT_ID=env-account');

        try {
            self::assertSame('env-account', VaultConfig::defaultAccountId());
        } finally {
            putenv('TELEFRAME_DEFAULT_ACCOUNT_ID='.($prev === false ? '' : $prev));
        }
    }

    public function test_vault_default_account_prefers_env_over_identity_chain(): void
    {
        config()->set('teleframe.vault.default_account', null);
        $prev = getenv('TELEFRAME_DEFAULT_ACCOUNT_ID');
        putenv('TELEFRAME_DEFAULT_ACCOUNT_ID=42');

        try {
            self::assertSame('42', VaultConfig::defaultAccountId());
        } finally {
            putenv('TELEFRAME_DEFAULT_ACCOUNT_ID='.($prev === false ? '' : $prev));
        }
    }

    // ---- Queue + schedule seams pinned via shipped drivers ----

    public function test_sync_queue_enqueue_returns_job_id(): void
    {
        $queue = new SyncUploadQueue;

        // confine() refuses a path that resolves nowhere, before any open or
        // unlink — so the job must name a real file. The bytes never matter.
        $source = tempnam(sys_get_temp_dir(), 'vault-');
        self::assertIsString($source);

        try {
            $id = $queue->enqueue([
                'account_id' => 1,
                'bucket' => 'main',
                'name' => 'dir/file.bin',
                'mode' => 'sealed',
                'source_path' => $source,
                'size' => 3,
                'chunk_shas' => [],
            ]);

            self::assertSame('1', $id); // sync job id = account_id
        } finally {
            @unlink($source);
        }
    }

    public function test_sync_queue_consume_is_noop_drain(): void
    {
        // The sync driver has nothing pending: consume() drains 0 jobs
        // (an async worker asking a sync driver finds none).
        $queue = new SyncUploadQueue;
        $handled = [];

        $consumed = $queue->consume(function (array $payload) use (&$handled): void {
            $handled[] = $payload['name'];
        }, max: 5);

        self::assertSame(0, $consumed);
        self::assertSame([], $handled);
    }

    public function test_sync_queue_is_not_async(): void
    {
        self::assertFalse((new SyncUploadQueue)->isAsync());
    }

    public function test_null_schedule_publisher_describes_and_installs(): void
    {
        $publisher = new NullSchedulePublisher;

        // install() returns per-schedule 'skipped' state (schedules off), and
        // describe() must SAY that: it is the first text an operator reads when
        // a job is not firing. The old wording ("tstore maintenance schedules
        // are off") also named config keys no provider reads, so it is gone.
        self::assertSame(
            ['sweep' => 'skipped (null schedule driver)'],
            $publisher->install(['sweep' => ['every' => '1h']]),
        );
        self::assertStringContainsString('Nothing is scheduled', $publisher->describe());

        // The old assertion here was `assertStringNotContainsString(
        // 'teleframe.tstore.schedules', …)`, which was correct while that key
        // was read by nothing — naming an unwired key is a lie, and refusing to
        // name it was the honest choice. GitLab pipeline #232 caught this test
        // going red when 8e6b8c9e gave the key a real arming path, because the
        // rule had changed underneath the assertion.
        //
        // The invariant is now the STRONGER one, and it is worth stating why:
        // withhold the key  -> the operator has no way to switch it on
        // name the key     -> the operator can, provided the arming path is named too
        // So: every driver is named, AND each one is paired with the command
        // that actually arms its seam, AND the text says plainly that setting a
        // driver alone arms nothing. tests/Store/SchedulePublisherDescriptionTest
        // already machine-checks that each named key has a real reader; what is
        // pinned HERE is that no arming instruction is given without its command,
        // which is the half that reader-oracle cannot see.
        $described = $publisher->describe();

        foreach ([
            'teleframe.app.schedules' => 'teleframe:app calls --install',
            'teleframe.store.backup.schedule.publisher' => 'teleframe:tstore schedule',
            'teleframe.tstore.schedules' => 'teleframe:tstore schedules --install',
            'teleframe.notify.schedules' => 'teleframe:notify schedules --install',
        ] as $key => $command) {
            self::assertStringContainsString($key, $described, "{$key} is armable now and must be named");
            self::assertStringContainsString(
                $command,
                $described,
                "{$key} is named without the command that arms it, which is a worse lie than silence",
            );
        }

        self::assertStringContainsString(
            'arms NOTHING',
            $described,
            'the text must say that a driver alone does not arm a schedule, or a reader will assume it does',
        );
    }

    public function test_queue_and_schedule_seams_are_contract_bound(): void
    {
        // The production driver satisfies the seam contract (swappable behind
        // Teleframe::store()->put() and W8 tstore schedules).
        self::assertInstanceOf(UploadQueueContract::class, new SyncUploadQueue);
        self::assertInstanceOf(SchedulePublisher::class, new NullSchedulePublisher);
    }

    // ---- TfStore* Eloquent models (real sqlite schema) ----

    private function withStoreTables(callable $fn): void
    {
        // The schema is the SHIPPED migration, executed — not transcribed
        // here. This method used to hand-build all three tables, and the
        // tf_store_objects it built was a fiction: primary(['account_id',
        // 'name']) and no `bucket` column at all, where the shipped DDL is
        // primary(['account_id','bucket','name']) plus a composite foreign
        // key to tf_store_buckets. Code and test agreed with each other and
        // both disagreed with production, so this suite was green while
        // proving a table shape nobody ships. A second hand-typed copy of
        // the DDL is the disease; deriving it is the cure. Provenance:
        // 2026_09_20_000100_create_tf_store_tables.php.
        $previous = Container::getInstance();

        try {
            $fn(ShippedStoreSchema::capsule());
        } finally {
            ShippedStoreSchema::restoreFacadeApplication($previous);
        }
    }

    public function test_tf_store_models_are_non_incrementing_without_timestamps(): void
    {
        self::assertFalse((new TfStoreBucket)->getIncrementing());
        self::assertFalse((new TfStoreBucket)->usesTimestamps());
        self::assertSame('tf_store_buckets', (new TfStoreBucket)->getTable());

        self::assertFalse((new TfStoreObject)->getIncrementing());
        self::assertFalse((new TfStoreObject)->usesTimestamps());
        self::assertSame('tf_store_objects', (new TfStoreObject)->getTable());

        self::assertFalse((new TfStoreMedia)->getIncrementing());
        self::assertFalse((new TfStoreMedia)->usesTimestamps());
        self::assertSame('tf_store_media', (new TfStoreMedia)->getTable());
    }

    public function test_tf_store_object_round_trips_json_casts(): void
    {
        $this->withStoreTables(function (Capsule $capsule): void {
            TfStoreObject::unguard();

            try {
                // The bucket row must exist first: the shipped DDL declares a
                // composite FK (account_id, bucket) → tf_store_buckets. The
                // old hand-built schema had no such FK and no bucket column,
                // so nothing here was ever constrained.
                $bucket = new TfStoreBucket;
                $bucket->account_id = 1;
                $bucket->bucket = 'main';
                $bucket->channel_id = 999;
                $bucket->access_hash = -12345;
                $bucket->kind = 'store';
                $bucket->created_at_unix = 1000;
                $bucket->save();

                $object = new TfStoreObject;
                $object->account_id = 1;
                $object->bucket = 'main'; // NOT NULL in the shipped DDL
                $object->name = 'dir/file.bin';
                $object->size = 12;
                $object->mode = 'sealed';
                $object->chunks = [['n' => 0, 'offset' => 0, 'size' => 12, 'sha256' => 'abc']];
                $object->metadata = ['ver' => 'TFSTORE1'];
                $object->created_at_unix = 1000;
                $object->updated_at_unix = 2000;
                $object->save();

                // Address the row by its FULL natural key. The old query
                // omitted `bucket`, which was only safe because the fiction
                // had no such column — against the real DDL it is an
                // ambiguous lookup, not a keyed one.
                $fresh = TfStoreObject::query()
                    ->where('account_id', 1)
                    ->where('bucket', 'main')
                    ->where('name', 'dir/file.bin')
                    ->first();

                self::assertNotNull($fresh);
                self::assertSame(12, (int) $fresh->size);
                self::assertSame('sealed', $fresh->mode);
                self::assertSame([['n' => 0, 'offset' => 0, 'size' => 12, 'sha256' => 'abc']], $fresh->chunks);
                self::assertSame(['ver' => 'TFSTORE1'], $fresh->metadata);

                // And the drifted column itself round-trips: `bucket` is
                // part of the primary key, so losing it would silently
                // collapse two distinct rows into one lookup.
                self::assertSame('main', $fresh->bucket);
            } finally {
                TfStoreObject::reguard();
                TfStoreBucket::reguard();
            }
        });
    }

    public function test_tf_store_object_natural_key_separates_buckets(): void
    {
        $this->withStoreTables(function (Capsule $capsule): void {
            TfStoreObject::unguard();
            TfStoreBucket::unguard();

            try {
                // The same object name in two buckets of one account. Under
                // the OLD hand-built primary(['account_id','name']) this
                // second insert was a primary-key violation, and under
                // primary(['account_id','bucket','name']) it is a distinct
                // row. The fiction made the shipped key unrepresentable.
                foreach (['main', 'backup'] as $name) {
                    $bucket = new TfStoreBucket;
                    $bucket->account_id = 1;
                    $bucket->bucket = $name;
                    $bucket->channel_id = 1;
                    $bucket->access_hash = 1;
                    $bucket->kind = 'store';
                    $bucket->created_at_unix = 1000;
                    $bucket->save();
                }

                foreach (['main', 'backup'] as $name) {
                    $object = new TfStoreObject;
                    $object->account_id = 1;
                    $object->bucket = $name;
                    $object->name = 'shared/name.bin';
                    $object->size = 1;
                    $object->mode = 'sealed';
                    $object->chunks = [];
                    $object->metadata = ['bucket' => $name];
                    $object->created_at_unix = 1000;
                    $object->updated_at_unix = 2000;
                    $object->save();
                }

                self::assertSame(2, TfStoreObject::query()->where('name', 'shared/name.bin')->count());

                $main = TfStoreObject::query()
                    ->where('account_id', 1)->where('bucket', 'main')->where('name', 'shared/name.bin')
                    ->first();
                $backup = TfStoreObject::query()
                    ->where('account_id', 1)->where('bucket', 'backup')->where('name', 'shared/name.bin')
                    ->first();

                self::assertNotNull($main);
                self::assertNotNull($backup);
                self::assertNotSame($main->getKey().$main->bucket, $backup->getKey().$backup->bucket);
                self::assertSame(['bucket' => 'main'], $main->metadata);
                self::assertSame(['bucket' => 'backup'], $backup->metadata);
            } finally {
                TfStoreObject::reguard();
                TfStoreBucket::reguard();
            }
        });
    }

    public function test_tf_store_bucket_round_trips_int_casts(): void
    {
        $this->withStoreTables(function (Capsule $capsule): void {
            TfStoreBucket::unguard();

            try {
                $bucket = new TfStoreBucket;
                $bucket->account_id = 1;
                $bucket->bucket = 'main';
                $bucket->channel_id = 999;
                $bucket->access_hash = -12345;
                $bucket->kind = 'store';
                $bucket->created_at_unix = 1000;
                $bucket->save();

                $fresh = TfStoreBucket::query()->where('account_id', 1)->where('bucket', 'main')->first();

                self::assertSame(999, (int) $fresh->channel_id);
                self::assertSame(-12345, (int) $fresh->access_hash);
                self::assertSame('store', $fresh->kind);
            } finally {
                TfStoreBucket::reguard();
            }
        });
    }
}

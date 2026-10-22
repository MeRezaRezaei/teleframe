<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Bus;

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Bus\RedisConnectionContract;
use MeRezaRezaei\Teleframe\Bus\RedisStreamSink;
use MeRezaRezaei\Teleframe\Bus\StreamSchema;
use MeRezaRezaei\Teleframe\Laravel\Services\UpdatePollerService;
use MeRezaRezaei\Teleframe\Tests\Ingest\IngestTestCase;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayRedis;
use PDO;

/**
 * S1 / D-039 — THE ORDERING PROOF: no update is published to
 * `tg:stream:updates` until the row that represents it is COMMITTED.
 *
 * ## WHAT THIS FILE IS AGAINST, MEASURED
 *
 * `UpdatePollerService::processUpdate()` hands the raw update straight to
 * `RedisStreamSink::handle()`, which `xadd`s and returns. No database is
 * touched on that path at all. The row is created later, by the very
 * consumer that would read the id. So today an update is PUBLISHED BEFORE
 * ITS ROW EXISTS, and a crash in between loses it with no trace.
 *
 * ## THE BACKING STORE IS FILE-BACKED SQLITE, AND THAT IS THE WHOLE POINT
 *
 * `tests/Ingest/IngestTestCase.php` boots `sqlite :memory:`. On `:memory:`
 * **every connection is a DIFFERENT DATABASE**, so a second connection
 * opened to prove "the row is committed" opens an empty database, sees
 * nothing, and the assertion is satisfied by a database that never received
 * the write — green and proving nothing. A `:memory:` version of this test
 * is the single assertion in S1 that can be vacuously green, and it is why
 * this file overrides `defineIngestDatabase()` with a real file in
 * `sys_get_temp_dir()`.
 *
 * Two CONTROLS below make that non-vacuous in the other direction too: the
 * independent connection is shown to see a row the app connection committed,
 * and shown NOT to see a row sitting inside an open transaction. So the
 * probe reads COMMIT, not merely a write, and it reads the same file.
 *
 * ## THE SEAM
 *
 * `RedisConnectionContract::xadd()` — injected, so the proof needs no
 * production change. In production it is reached via
 * `DaemonCommand::busAdapter()`; here the decorator wraps the in-memory
 * double and opens its OWN PDO handle to the SAME FILE inside `xadd()`,
 * before the entry is recorded.
 *
 * ## OWED AND DELIBERATELY NOT WRITTEN — THE INVERSE OF THIS FILE
 *
 * **Owed:** make the producer's `ingest()` throw, and assert `xadd()` was
 * NEVER called — the publish counter is 0. That is the inverse of this test:
 * a *successful* ingest must publish exactly once, and a *failed* ingest must
 * publish not at all.
 *
 * It is omitted rather than shipped green for the wrong reason, and that call
 * is deliberate. **There is no ingest on the producer path to fail.**
 * `processUpdate()` hands the update to `RedisStreamSink::handle()` and
 * returns; the only way to make "ingest" throw today is to substitute a
 * throwing `UpdateSinkInterface`, and that would assert that *the stub* throws
 * rather than that *production code* does. A test that is green on a stub it
 * supplied itself is worse than no test at all — it reads as coverage of an
 * obligation nobody has actually met, and it would be the first thing an
 * implementer trusts in this file.
 *
 * It becomes writable the moment the producer gains its ingest seam (plan S1,
 * step 2 — ingest and commit first, then publish the committed key). Write it
 * against that real seam, never against a substituted double. The omission is
 * recorded here, on the file that will own it, rather than in a document
 * nobody reads at the moment they go looking for it.
 */
final class ProducerPublishesOnlyAfterRowCommitsTest extends IngestTestCase
{
    private const ACCOUNT = 7;

    /** A second tenant used only by the "same file" control. */
    private const CANARY_ACCOUNT = 8;

    /** File-backed SQLite — see the class docblock. */
    private string $dbPath;

    protected function ingestTenantIds(): array
    {
        return [self::ACCOUNT, self::CANARY_ACCOUNT];
    }

    /**
     * Override the trait's `sqlite :memory:` with a real file.
     *
     * A second connection on `:memory:` is a second database, which makes
     * the ordering proof unfalsifiable. That is the one way this file could
     * be green and prove nothing, so it is removed rather than noted.
     *
     * @param  Application  $app
     */
    protected function defineIngestDatabase($app): void
    {
        $app['config']->set('database.default', 'ingest');
        $app['config']->set('database.connections.ingest', [
            'driver' => 'sqlite',
            'database' => $this->dbPath,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
    }

    protected function setUp(): void
    {
        // Assigned BEFORE parent::setUp(): Testbench creates the application
        // (and therefore reads this config) inside parent::setUp().
        $this->dbPath = sys_get_temp_dir().'/teleframe-bus-ordering-'.getmypid().'.sqlite';
        @unlink($this->dbPath);

        parent::setUp();

        // `tf_updates.account_id` is a FK to `telegram_accounts.user_id` —
        // the Telegram user id, not the vault row's autoincrement `id`
        // (retargeted by 2026_10_04_000200). So the tenant ids declared
        // above are exactly what this row needs.
        DB::table('tf_updates')->insert([
            'account_id' => self::CANARY_ACCOUNT,
            'seq' => 1,
            'position' => 0,
            'constructor' => 'message',
        ]);
    }

    protected function tearDown(): void
    {
        DB::purge('ingest');
        @unlink($this->dbPath);

        parent::tearDown();
    }

    public function test_the_producer_path_publishes_only_after_the_row_is_committed(): void
    {
        $probe = $this->probeRedis(new ArrayRedis);

        // ---- CONTROL 1: the probe reads the SAME FILE, not another database.
        //
        // Without this, a mis-pointed probe path produces exactly the same
        // red as the defect, and the two cannot be told apart.
        self::assertSame(
            [['seq' => 1, 'position' => 0]],
            $probe->committedKeys(self::CANARY_ACCOUNT),
            'the independent connection must see a row the application connection committed',
        );

        // ---- CONTROL 2: the probe sees COMMIT, not a write in an open txn.
        //
        // This is what makes the later assertion mean "committed" rather
        // than "written": if the probe could see uncommitted rows, the
        // ordering proof would be satisfied by a mere write.
        DB::beginTransaction();
        DB::table('tf_updates')->insert([
            'account_id' => self::ACCOUNT,
            'seq' => 999,
            'position' => 0,
            'constructor' => 'message',
        ]);
        self::assertSame(
            [],
            $probe->committedKeys(self::ACCOUNT),
            'an uncommitted row must be invisible to the probe, or this file proves nothing about commit',
        );
        DB::rollBack();
        self::assertSame([], $probe->committedKeys(self::ACCOUNT), 'rollback is visible');

        // ---- Drive ONE update through the producer path.
        //
        // processUpdate() is the named seam (UpdatePollerService.php:723);
        // RedisStreamSink is the sink DaemonCommand hands the worker.
        $poller = new UpdatePollerService(new RedisStreamSink($probe, self::ACCOUNT));
        $poller->processUpdate($this->messageUpdate(), (string) self::ACCOUNT);

        self::assertCount(1, $probe->publishProbes, 'exactly one publish was attempted');
        $observed = $probe->publishProbes[0];

        // ---- THE INVARIANT (D-039).
        self::assertNotSame(
            [],
            $observed['committedRows'],
            'RED: the update was published to tg:stream:updates while ZERO tf_updates rows were committed '
            .'and visible from a second, independent connection. The producer must ingest and COMMIT first '
            .'(D-039/D-040), and only then publish the committed key.',
        );

        self::assertTrue(
            $observed['namedRowVisible'],
            'RED: the published entry does not name a committed row.',
        );

        self::assertSame(
            $observed['committedRows'][0],
            $observed['namedKey'],
            'the published entry must name the committed row (account_id, seq, position) — D-039',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function messageUpdate(): array
    {
        return [
            '_' => 'updateNewMessage',
            'message' => [
                '_' => 'message',
                'id' => 30,
                'peer_id' => ['_' => 'peerUser', 'user_id' => 5],
                'date' => 1_700_000_030,
                'message' => 'hello from the producer path',
                'out' => false,
            ],
        ];
    }

    /**
     * The ordering probe.
     *
     * Wraps the double, and inside `xadd()` — BEFORE the entry is recorded
     * — opens its OWN PDO handle to the SAME FILE and reports what the
     * committed truth looked like at that instant. The entry it was handed
     * is reported alongside, so the test can ask whether the entry names a
     * row that was already there.
     */
    private function probeRedis(ArrayRedis $inner): object
    {
        return new class($inner, $this->dbPath, self::ACCOUNT) implements RedisConnectionContract
        {
            /** @var list<array<string, mixed>> */
            public array $publishProbes = [];

            public function __construct(
                private readonly ArrayRedis $inner,
                private readonly string $dbPath,
                private readonly int $accountId,
            ) {}

            public function xadd(string $stream, string $id, array $fields, array $options = []): string
            {
                if ($stream === StreamSchema::STREAM) {
                    $this->publishProbes[] = $this->probeAtPublishTime($fields);
                }

                return $this->inner->xadd($stream, $id, $fields, $options);
            }

            /**
             * What the committed database looked like at the moment of the
             * append, read through a SEPARATE connection.
             *
             * @param  array<string, string>  $fields
             * @return array<string, mixed>
             */
            private function probeAtPublishTime(array $fields): array
            {
                $committed = $this->committedKeys($this->accountId);

                $namedSeq = $fields['seq'] ?? null;
                $namedPosition = $fields['position'] ?? null;

                $named = ($namedSeq === null || $namedPosition === null)
                    ? null
                    : ['seq' => (int) $namedSeq, 'position' => (int) $namedPosition];

                return [
                    'publishedFields' => array_keys($fields),
                    'committedRows' => $committed,
                    'namedKey' => $named,
                    'namedRowVisible' => $named !== null && in_array($named, $committed, true),
                ];
            }

            /**
             * The `(seq, position)` keys COMMITTED for `$accountId`, as seen
             * by a connection this class opened itself.
             *
             * @return list<array{seq: int, position: int}>
             */
            public function committedKeys(int $accountId): array
            {
                $pdo = new PDO('sqlite:'.$this->dbPath);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $pdo->exec('PRAGMA busy_timeout = 2000');

                $statement = $pdo->prepare(
                    'SELECT seq, position FROM tf_updates WHERE account_id = ? ORDER BY seq, position'
                );
                $statement->execute([$accountId]);

                $keys = [];
                foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $keys[] = ['seq' => (int) $row['seq'], 'position' => (int) $row['position']];
                }

                return $keys;
            }

            // ---- pass-through to the in-memory double ----

            public function xpending(string $stream, string $group, ?string $start = null, ?string $end = null, int $count = 100): array
            {
                return $this->inner->xpending($stream, $group, $start, $end, $count);
            }

            public function xautoclaim(string $stream, string $group, string $consumer, int $minIdleMs, string $start = '0-0', int $count = 100): array
            {
                return $this->inner->xautoclaim($stream, $group, $consumer, $minIdleMs, $start, $count);
            }

            public function xrange(string $stream, string $start, string $end, int $count = 100): array
            {
                return $this->inner->xrange($stream, $start, $end, $count);
            }

            public function xrevrange(string $stream, string $end, string $start, int $count = 100): array
            {
                return $this->inner->xrevrange($stream, $end, $start, $count);
            }

            public function xdel(string $stream, array $ids): int
            {
                return $this->inner->xdel($stream, $ids);
            }

            public function hdel(string $key, string ...$fields): int
            {
                return $this->inner->hdel($key, ...$fields);
            }

            public function command(string $command, array $arguments = []): mixed
            {
                return $this->inner->command($command, $arguments);
            }

            public function xreadgroup(string $group, string $consumer, array $streams, string $minId = '>'): array
            {
                return $this->inner->xreadgroup($group, $consumer, $streams, $minId);
            }

            public function xack(string $stream, string $group, array $ids): int
            {
                return $this->inner->xack($stream, $group, $ids);
            }

            public function hgetall(string $key): array
            {
                return $this->inner->hgetall($key);
            }

            public function hset(string $key, string $field, string $value): int
            {
                return $this->inner->hset($key, $field, $value);
            }

            public function publish(string $channel, string $payload): int
            {
                return $this->inner->publish($channel, $payload);
            }

            public function subscribe(string $channel, callable $handler): void
            {
                $this->inner->subscribe($channel, $handler);
            }

            public function del(string ...$keys): int
            {
                return $this->inner->del(...$keys);
            }

            public function llen(string $key): int
            {
                return $this->inner->llen($key);
            }

            public function hget(string $key, string $field): ?string
            {
                return $this->inner->hget($key, $field);
            }

            public function expire(string $key, int $seconds): bool
            {
                return $this->inner->expire($key, $seconds);
            }
        };
    }
}

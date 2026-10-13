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
use RuntimeException;

/**
 * S1 — THE CRASH TEST. This is the test that would have caught the defect.
 *
 * ## THE DEFECT, IN ONE LINE
 *
 * On the producer path `RedisStreamSink::handle()` is the ONLY thing that
 * happens: it `xadd`s the raw payload and returns. No database is touched.
 * So a Redis outage at that moment loses the update outright — there is no
 * committed row to recover from, and no trace of the loss anywhere.
 *
 * Under the inversion (D-039/D-040) the row commits FIRST, then the publish
 * is attempted. So an outage in the publish seam must leave:
 *
 *   - the committed row INTACT and readable, and
 *   - NOTHING on the stream.
 *
 * The update is then replayable from truth on the next producer cycle. That
 * is the whole point of making the database the system of record.
 *
 * ## FILE-BACKED SQLITE, AND WHY AGAIN
 *
 * The assertion is "the committed row survived". `:memory:` would give a
 * second connection a DIFFERENT DATABASE — which trivially "has no row" and
 * would make the surviving-row assertion pass for the wrong reason while the
 * committed-row assertion elsewhere fails vacuously. So this file boots the
 * real dial on a file and reads the row back through its OWN PDO handle.
 *
 * The read is deliberately made AFTER the exception has been caught and the
 * application connection purged: the row must be durable, not merely still
 * visible to the connection that wrote it inside an open transaction.
 *
 * ## THE INVERSE CASE IS OWED AND UNWRITABLE YET
 *
 * The mirror of this test — a failed INGEST publishing NOTHING, with the
 * publish counter at 0 — is owed, and is recorded in full on
 * `ProducerPublishesOnlyAfterRowCommitsTest`, which owns the producer path it
 * would live on. Short version: there is no ingest on the producer path to
 * fail, so the only available simulation substitutes a throwing
 * `UpdateSinkInterface` and asserts the stub throws rather than that
 * production code does. It becomes writable when the producer gains its
 * ingest seam (plan S1, step 2). Do not add it here by faking the failure —
 * that is the exact substitution that was rejected.
 */
final class PublishFailureLeavesTheCommittedRowIntactTest extends IngestTestCase
{
    private const ACCOUNT = 7;

    /** File-backed SQLite — see the class docblock. */
    private string $dbPath;

    protected function ingestTenantIds(): array
    {
        return [self::ACCOUNT];
    }

    /**
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
        $this->dbPath = sys_get_temp_dir().'/teleframe-bus-crash-'.getmypid().'.sqlite';
        @unlink($this->dbPath);

        parent::setUp();
    }

    protected function tearDown(): void
    {
        DB::purge('ingest');
        @unlink($this->dbPath);

        parent::tearDown();
    }

    /**
     * A Redis outage at the publish seam must cost the PUBLISH, never the
     * committed row.
     */
    public function test_a_publish_failure_keeps_the_committed_row_and_publishes_nothing(): void
    {
        $redis = new ArrayRedis;
        $outage = $this->outageOnPublish($redis);

        $poller = new UpdatePollerService(new RedisStreamSink($outage, self::ACCOUNT));

        $threw = null;

        try {
            $poller->processUpdate($this->messageUpdate(), (string) self::ACCOUNT);
        } catch (RuntimeException $e) {
            $threw = $e;
        }

        // ---- The outage must be VISIBLE, not swallowed.
        //
        // A silent swallow would be worse than the current defect: the
        // caller would think the update was published and move on.
        self::assertNotNull(
            $threw,
            'the publish failure was swallowed — the caller cannot know the update was not published',
        );
        self::assertStringContainsString('redis', strtolower($threw->getMessage()));

        // ---- HALF THE ASSERTION: nothing was published.
        //
        // The decorator throws BEFORE delegating, modelling a connection
        // that died mid-command: the entry never reached the server.
        self::assertSame(
            [],
            $redis->streamEntries(StreamSchema::STREAM),
            'nothing may be on the stream when the publish failed',
        );

        // ---- THE OTHER HALF: the committed row survived.
        //
        // Read through a fresh connection to the same FILE, after the app
        // connection has been purged, so this is durability rather than
        // connection-local visibility.
        DB::purge('ingest');
        $rows = $this->committedUpdateRows(self::ACCOUNT);

        self::assertNotSame(
            [],
            $rows,
            'RED: the publish outage took the update with it. Today xadd is the ONLY thing the producer path '
            .'does, so a Redis outage at that moment loses the update outright — there is no committed row to '
            .'recover from and no trace of the loss. Under the inversion the row commits FIRST (D-039/D-040), '
            .'so this assertion must find the row durable.',
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
     * The committed `(seq, position)` keys for `$accountId`, read through a
     * connection this class opened itself.
     *
     * @return list<array{seq: int, position: int, constructor: string}>
     */
    private function committedUpdateRows(int $accountId): array
    {
        $pdo = new PDO('sqlite:'.$this->dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('PRAGMA busy_timeout = 2000');

        $statement = $pdo->prepare(
            'SELECT seq, position, constructor FROM tf_updates WHERE account_id = ? ORDER BY seq, position'
        );
        $statement->execute([$accountId]);

        $rows = [];
        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $rows[] = [
                'seq' => (int) $row['seq'],
                'position' => (int) $row['position'],
                'constructor' => (string) $row['constructor'],
            ];
        }

        return $rows;
    }

    /**
     * A connection whose publish seam is DOWN: `xadd` on the updates stream
     * throws before delegating, so the entry never lands.
     */
    private function outageOnPublish(ArrayRedis $inner): RedisConnectionContract
    {
        return new class($inner) implements RedisConnectionContract
        {
            public function __construct(private readonly ArrayRedis $inner) {}

            public function xadd(string $stream, string $id, array $fields, array $options = []): string
            {
                if ($stream === StreamSchema::STREAM) {
                    throw new RuntimeException('redis connection reset while publishing to '.$stream);
                }

                return $this->inner->xadd($stream, $id, $fields, $options);
            }

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

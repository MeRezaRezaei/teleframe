<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Bus;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use LogicException;
use MeRezaRezaei\Teleframe\Bus\IngestConsumer;
use MeRezaRezaei\Teleframe\Bus\RedisStreamSink;
use MeRezaRezaei\Teleframe\Bus\RouteTable;
use MeRezaRezaei\Teleframe\Bus\StreamSchema;
use MeRezaRezaei\Teleframe\Mirror\Models\TfMessage;
use MeRezaRezaei\Teleframe\Teleclient;
use MeRezaRezaei\Teleframe\Tests\Ingest\IngestTestCase;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayRedis;

/**
 * Plan Task 2 end-to-end: stream entries → consumer group read → route
 * decision (default ingest into sqlite truth / forward to target stream)
 * → ack, with poison payloads dead-lettered. Reuses the P2 ingest boot
 * (IngestTestCase + RunsMigrations) so default-path rows land in real
 * migrated tables.
 */
final class IngestConsumerTest extends IngestTestCase
{
    private const ACCOUNT = 7;

    private const USER_ID = 501558149;

    /**
     * The tenant this fixture writes under.
     *
     * MEASURED, not assumed: every payload here carries
     * `'account_id' => (string) self::ACCOUNT`, i.e. 7.
     *
     * It was undeclared, and with foreign-key enforcement on (19 constraints) one
     * of the three ingests in each test was refused at the database rather than at
     * the code under test:
     *
     *   Failed asserting that two arrays are identical.
     *   -    'processed' => 3,
     *   +    'processed' => 2,
     *
     * which is 4 failures in CI shard-4. The silent part is the dangerous part: the
     * consumer still reported success for the two it could write, so the report
     * simply had one fewer row in it and nothing said a constraint had fired.
     *
     * The fix is HERE rather than in `seedIngestTenant`, because that helper's
     * contract is deliberate — "each test declares the tenants it means to use, so a
     * test that writes under an unplanted id fails loudly instead of being rescued."
     * This test was writing under an unplanted id, and the loud failure is the
     * mechanism working.
     */
    protected function ingestTenantIds(): array
    {
        return [self::ACCOUNT];
    }

    private ArrayRedis $redis;

    private IngestConsumer $consumer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->redis = new ArrayRedis;
        $this->consumer = new IngestConsumer(
            $this->redis,
            $this->app->make(Teleclient::class),
        );
    }

    public function test_empty_batch_reports_zero(): void
    {
        $stats = $this->consumer->consumeOnce();

        self::assertSame(['processed' => 0, 'forwarded' => 0], $stats);
    }

    public function test_end_to_end_default_ingest_forward_and_poison_dead_letter(): void
    {
        $table = new RouteTable($this->redis);
        $table->set('updateNewMessage*', 'tg:target:messages');

        $sink = new RedisStreamSink($this->redis, self::ACCOUNT);

        // 1) storeable message update → default path → sqlite truth
        $sink->handle($this->messageUpdate(), (string) self::ACCOUNT);

        // 2) routed update → forwarded verbatim to the target stream
        $routed = ['_' => 'updateNewMessage', 'message' => ['_' => 'message', 'id' => 7]];
        $sink->handle($routed, (string) self::ACCOUNT);

        // 3) poison payload → dead-letter + ack
        $this->redis->xadd(StreamSchema::STREAM, '*', [
            'account_id' => (string) self::ACCOUNT,
            'update' => 'this is not json',
            'ts' => (string) time(),
        ]);

        $stats = $this->consumer->consumeOnce();

        self::assertSame(['processed' => 3, 'forwarded' => 1], $stats);

        // Default path stored the message under the tenant
        self::assertSame(
            1,
            DB::table('tf_messages')->where('account_id', self::ACCOUNT)->where('id', 30)->count(),
            'default path landed a curated message row',
        );

        // Forwarded entry kept the original payload untouched
        $targets = $this->redis->streamEntries('tg:target:messages');
        self::assertCount(1, $targets);
        self::assertSame((string) self::ACCOUNT, $targets[0][1]['account_id']);
        self::assertSame($routed, StreamSchema::decode($targets[0][1]['update'])['update']);

        // Poison landed on the dead-letter stream, not in truth
        $dead = $this->redis->streamEntries(StreamSchema::DL);
        self::assertCount(1, $dead);
        self::assertSame('this is not json', $dead[0][1]['update']);

        // The whole batch is acked — nothing stays pending for a retry
        $pending = $this->redis->xreadgroup(
            StreamSchema::GROUP,
            StreamSchema::CONSUMER,
            [StreamSchema::STREAM => 10],
            '0',
        );
        self::assertSame([], $pending);

        // A second pass has nothing new to do
        self::assertSame(['processed' => 0, 'forwarded' => 0], $this->consumer->consumeOnce());
    }

    public function test_persistent_ingest_failure_dead_letters_on_the_third_cycle_and_unwedges_the_group(): void
    {
        $sink = new RedisStreamSink($this->redis, self::ACCOUNT);

        // Decodes fine, but carries no '_' constructor node — ingest()
        // throws deterministically (InvalidArgumentException) on every attempt.
        $sink->handle(['message_id' => 404], (string) self::ACCOUNT);

        // A healthy entry appended after the throwing one must not be
        // stranded behind it in the same batch.
        $sink->handle($this->userUpdate(), (string) self::ACCOUNT);

        // Cycle 1: the throwing entry stays pending; the healthy tail lands
        self::assertSame(['processed' => 1, 'forwarded' => 0], $this->consumer->consumeOnce());
        self::assertSame([], $this->redis->streamEntries(StreamSchema::DL));

        // The healthy tail is acked even though it stores nothing: the `user`
        // constructor has no curated mirror surface — null, gracefully acked.
        $client = $this->app->make(Teleclient::class);
        self::assertNull($client->user(self::ACCOUNT, self::USER_ID));

        // Cycle 2: retry throws again — still pending, still no dead-letter
        self::assertSame(['processed' => 0, 'forwarded' => 0], $this->consumer->consumeOnce());
        self::assertSame([], $this->redis->streamEntries(StreamSchema::DL));

        // Cycle 3: third consecutive strike → dead-lettered with the
        // original payload plus reason/error, acked, group free to move
        self::assertSame(['processed' => 1, 'forwarded' => 0], $this->consumer->consumeOnce());

        $dead = $this->redis->streamEntries(StreamSchema::DL);
        self::assertCount(1, $dead);
        self::assertSame('ingest-failed', $dead[0][1]['reason']);
        self::assertStringContainsString("payload carries no '_' constructor node", $dead[0][1]['error']);
        self::assertSame(
            ['message_id' => 404],
            StreamSchema::decode($dead[0][1]['update'])['update'],
        );
        self::assertSame((string) self::ACCOUNT, $dead[0][1]['account_id']);

        // Nothing stays pending for this consumer
        self::assertSame([], $this->redis->xreadgroup(
            StreamSchema::GROUP,
            StreamSchema::CONSUMER,
            [StreamSchema::STREAM => 10],
            '0',
        ));

        // The group keeps moving after the wedge is broken
        $sink->handle($this->userUpdate(), (string) self::ACCOUNT);
        self::assertSame(['processed' => 1, 'forwarded' => 0], $this->consumer->consumeOnce());
    }

    public function test_transient_ingest_path_failure_is_retried_without_dead_lettering(): void
    {
        $attempts = 0;
        $stored = [];
        $consumer = new IngestConsumer(
            $this->redis,
            $this->app->make(Teleclient::class),
            static function (Model $root, int $accountId) use (&$attempts, &$stored): void {
                $attempts++;
                if ($attempts < 3) {
                    throw new \RuntimeException('transient hiccup after the row committed');
                }
                $stored[] = $accountId;
            },
        );

        $sink = new RedisStreamSink($this->redis, self::ACCOUNT);
        $sink->handle($this->messageUpdate(), (string) self::ACCOUNT);

        // Two transient failures: the entry is retried, never dead-lettered
        self::assertSame(['processed' => 0, 'forwarded' => 0], $consumer->consumeOnce());
        self::assertSame(['processed' => 0, 'forwarded' => 0], $consumer->consumeOnce());
        self::assertSame([], $this->redis->streamEntries(StreamSchema::DL));

        // Third attempt succeeds → handled, acked, hook observed the landing
        self::assertSame(['processed' => 1, 'forwarded' => 0], $consumer->consumeOnce());
        self::assertSame([self::ACCOUNT], $stored);
        self::assertSame([], $this->redis->streamEntries(StreamSchema::DL));

        self::assertSame([], $this->redis->xreadgroup(
            StreamSchema::GROUP,
            StreamSchema::CONSUMER,
            [StreamSchema::STREAM => 10],
            '0',
        ));
    }

    public function test_on_stored_fires_for_default_path_only(): void
    {
        $table = new RouteTable($this->redis);
        $table->set('updateNewMessage*', 'tg:target:messages');

        $seen = [];
        $consumer = new IngestConsumer(
            $this->redis,
            $this->app->make(Teleclient::class),
            static function (Model $root, int $accountId) use (&$seen): void {
                $seen[] = [get_class($root), $accountId];
            },
        );

        $sink = new RedisStreamSink($this->redis, self::ACCOUNT);
        $sink->handle($this->messageUpdate(), (string) self::ACCOUNT);
        $sink->handle(['_' => 'updateNewMessage', 'message' => ['_' => 'message', 'id' => 9]], (string) self::ACCOUNT);

        $consumer->consumeOnce();

        self::assertSame([[TfMessage::class, self::ACCOUNT]], $seen);
    }

    public function test_routed_entries_fan_into_the_on_routed_seam(): void
    {
        $table = new RouteTable($this->redis);
        $table->set('updateNewMessage*', 'tg:target:messages');
        $sink = new RedisStreamSink($this->redis, self::ACCOUNT);
        $sink->handle(['_' => 'updateNewMessage', 'message' => ['_' => 'message', 'id' => 9]], (string) self::ACCOUNT);

        $routed = [];
        $consumer = new IngestConsumer(
            $this->redis,
            $this->app->make(Teleclient::class),
            null,
            static function (array $update, int $accountId) use (&$routed): void {
                $routed[] = [$update['_'], $accountId];
            },
        );

        $stats = $consumer->consumeOnce();

        self::assertSame(['processed' => 1, 'forwarded' => 1], $stats);
        self::assertSame([['updateNewMessage', self::ACCOUNT]], $routed);
        self::assertCount(1, $this->redis->streamEntries('tg:target:messages'));
    }

    public function test_mirror_ingester_seam_replaces_the_legacy_default_path(): void
    {
        $sink = new RedisStreamSink($this->redis, self::ACCOUNT);
        $mirrored = [];
        $consumer = new IngestConsumer(
            $this->redis,
            $this->app->make(Teleclient::class),
            null,
            null,
            static function (array $update, int $accountId) use (&$mirrored): void {
                $mirrored[] = [$update['_'], $accountId];
            },
        );

        $sink->handle($this->userUpdate(), (string) self::ACCOUNT);
        $stats = $consumer->consumeOnce();

        self::assertSame(['processed' => 1, 'forwarded' => 0], $stats);
        self::assertSame([['user', self::ACCOUNT]], $mirrored, 'mirror seam received the update — not the legacy ingest path');

        // The legacy path would have written the P2 user row; the seam
        // defers entirely to the ingester (mirror pipeline), so no P2 row.
        $client = $this->app->make(Teleclient::class);
        self::assertNull($client->user(self::ACCOUNT, self::USER_ID));
    }

    public function test_reclaim_stale_pending_from_a_crashed_consumer(): void
    {
        $table = new RouteTable($this->redis);
        $table->set('updateNewMessage*', 'tg:target:messages');

        $sink = new RedisStreamSink($this->redis, self::ACCOUNT);
        $sink->handle(['_' => 'updateNewMessage', 'message' => ['_' => 'message', 'id' => 7]], (string) self::ACCOUNT);

        // A different consumer name delivers the entry — and "dies" before
        // acking — exactly like a crashed process leaving a crash orphan.
        $entryId = array_key_first($this->redis->xreadgroup(
            StreamSchema::GROUP,
            'crashed-1',
            [StreamSchema::STREAM => 10],
            '>',
        )[StreamSchema::STREAM]);
        self::assertIsString($entryId);

        // Too fresh: the orphan is not reclaimed yet, and the ingest-1
        // consumer cannot see 'crashed-1's pending entry on either read.
        self::assertSame(['processed' => 0, 'forwarded' => 0], $this->consumer->consumeOnce());

        // The orphan ages past the reclaim idle threshold (60s).
        $this->redis->warpTime(61_000);

        // XPENDING + XAUTOCLAIM: the stale entry is claimed onto ingest-1,
        // routed by the live route table, acked exactly once.
        $stats = $this->consumer->consumeOnce();
        self::assertSame(['processed' => 1, 'forwarded' => 1], $stats, 'stale orphan reclaimed, routed and acked');
        self::assertCount(1, $this->redis->streamEntries('tg:target:messages'), 'claimed entry forwarded exactly once');

        // No pending remains for anyone in the group.
        self::assertSame([], $this->redis->xreadgroup(StreamSchema::GROUP, StreamSchema::CONSUMER, [StreamSchema::STREAM => 10], '0'));
        self::assertSame([], $this->redis->xreadgroup(StreamSchema::GROUP, 'crashed-1', [StreamSchema::STREAM => 10], '0'));
    }

    public function test_reclaim_does_not_double_handle_own_pending_entries(): void
    {
        $sink = new RedisStreamSink($this->redis, self::ACCOUNT);
        $sink->handle($this->messageUpdate(), (string) self::ACCOUNT);

        // Deliver onto the group, then age the pending entry past the
        // reclaim threshold WITHOUT acking — a crash mid-batch.
        $this->redis->xreadgroup(StreamSchema::GROUP, StreamSchema::CONSUMER, [StreamSchema::STREAM => 10], '>');
        $this->redis->warpTime(61_000);

        $stats = $this->consumer->consumeOnce();

        // The own-pending batch and the claimed batch agree on the same id
        // — the claim dedupe must not process the entry twice.
        self::assertSame(['processed' => 1, 'forwarded' => 0], $stats);
        self::assertSame(
            1,
            DB::table('tf_messages')->where('account_id', self::ACCOUNT)->where('id', 30)->count(),
            'reclaimed pending entry stored exactly once',
        );
    }

    public function test_replay_dead_letter_republishes_retryable_entries_onto_the_main_stream(): void
    {
        $table = new RouteTable($this->redis);
        $table->set('updateNewMessage*', 'tg:target:messages');

        // Retryable entry (failed ingest, reason tagged) — must come back.
        $routed = ['_' => 'updateNewMessage', 'message' => ['_' => 'message', 'id' => 55]];
        $this->redis->xadd(StreamSchema::DL, '*', [
            'account_id' => (string) self::ACCOUNT,
            'update' => StreamSchema::encode([
                'account_id' => self::ACCOUNT,
                'update' => $routed,
                'ts' => time(),
            ]),
            'ts' => (string) time(),
            'reason' => 'ingest-failed',
            'error' => 'transient hiccup',
        ]);

        // True poison — replay must leave it on the DL for forensics.
        $this->redis->xadd(StreamSchema::DL, '*', [
            'account_id' => (string) self::ACCOUNT,
            'update' => 'this is not json',
            'ts' => (string) time(),
        ]);

        self::assertSame(1, $this->consumer->replayDeadLetter(), 'only the retryable entry is re-published');

        // Back on the main stream, same payload, meta tags stripped.
        $main = $this->redis->streamEntries(StreamSchema::STREAM);
        self::assertCount(1, $main);
        self::assertSame((string) self::ACCOUNT, $main[0][1]['account_id']);
        self::assertArrayNotHasKey('reason', $main[0][1]);
        self::assertArrayNotHasKey('error', $main[0][1]);
        self::assertSame($routed, StreamSchema::decode($main[0][1]['update'])['update']);

        // The consumer group re-routes the replayed entry through the live
        // route table — the same route, the same handler.
        self::assertSame(['processed' => 1, 'forwarded' => 1], $this->consumer->consumeOnce());
        self::assertCount(1, $this->redis->streamEntries('tg:target:messages'));

        // Poison stays on the DL; a second replay has nothing to move.
        $dead = $this->redis->streamEntries(StreamSchema::DL);
        self::assertCount(1, $dead);
        self::assertSame('this is not json', $dead[0][1]['update']);
        self::assertSame(0, $this->consumer->replayDeadLetter());
    }

    /**
     * Dead-letter replay used to be impossible against a real Redis, and the
     * test that documented it asserted the failure rather than the capability:
     * it wrapped a connection with no xrange()/xdel() and expected
     * LogicException('xrange()/xdel()'). A green suite, a permanently poisoned
     * dead-letter stream, and no operator remedy.
     *
     * The adapter now speaks both verbs, so this asserts the CAPABILITY: a
     * dead-lettered entry is read back, re-published onto the live stream, and
     * removed from the dead-letter.
     */
    public function test_replay_dead_letter_reads_back_republishes_and_removes_the_entry(): void
    {
        $this->redis->xadd(StreamSchema::DL, '*', [
            'account_id' => '7',
            'update' => StreamSchema::encode(['account_id' => 7, 'update' => ['kind' => 'poison'], 'ts' => 1]),
            'ts' => '1',
            'reason' => 'ingest-failed',
        ]);

        $consumer = new IngestConsumer($this->redis, $this->app->make(Teleclient::class));
        $replayed = $consumer->replayDeadLetter();

        $this->assertSame(1, $replayed, 'the dead-lettered entry must be replayed, not refused');

        $backOnStream = array_keys($this->redis->xrange(StreamSchema::STREAM, '-', '+', 10));
        $this->assertCount(1, $backOnStream, 'a replayed entry returns to the live stream');

        $this->assertSame([], $this->redis->xrange(StreamSchema::DL, '-', '+', 10), 'a replayed entry leaves the dead-letter');

        $replayedFields = $this->redis->xrange(StreamSchema::STREAM, '-', '+', 10)[$backOnStream[0]];
        $this->assertArrayNotHasKey('reason', $replayedFields, 'the dead-letter tag does not travel back to the live stream');
    }

    public function test_persisted_strikes_survive_a_restart_and_dead_letter_over_the_cap_on_the_first_failure(): void
    {
        $sink = new RedisStreamSink($this->redis, self::ACCOUNT);

        // Decodes fine, but carries no '_' constructor node — ingest()
        // throws deterministically on every attempt.
        $sink->handle(['message_id' => 404], (string) self::ACCOUNT);

        // Two in-process failures: two strikes persisted for the entry id.
        self::assertSame(['processed' => 0, 'forwarded' => 0], $this->consumer->consumeOnce());
        self::assertSame(['processed' => 0, 'forwarded' => 0], $this->consumer->consumeOnce());
        self::assertSame([], $this->redis->streamEntries(StreamSchema::DL));

        $entryId = $this->redis->streamEntries(StreamSchema::STREAM)[0][0];
        self::assertSame('2', $this->redis->hget(StreamSchema::STRIKES_KEY, $entryId), 'strike count lives in the Redis hash, not process memory');

        // "Restart": a brand-new consumer against the same Redis. It gets its
        // OWN consumer name now, so the unacked tail its predecessor left
        // pending is recovered by XAUTOCLAIM rather than read back as "my
        // own pending" — which is the same code path that stops a SLOW live
        // worker from being robbed, and it costs one idle window.
        $restarted = new IngestConsumer($this->redis, $this->app->make(Teleclient::class));
        $this->redis->warpTime(61_000);

        // One failure after the restart is enough: persisted 2 + 1 = 3 →
        // the poison message goes straight to the dead-letter queue.
        self::assertSame(['processed' => 1, 'forwarded' => 0], $restarted->consumeOnce());

        $dead = $this->redis->streamEntries(StreamSchema::DL);
        self::assertCount(1, $dead);
        self::assertSame('ingest-failed', $dead[0][1]['reason']);

        // The group is unwedged: nothing stays pending, and a healthy
        // entry flows through.
        self::assertSame([], $this->redis->xreadgroup(StreamSchema::GROUP, StreamSchema::CONSUMER, [StreamSchema::STREAM => 10], '0'));
        $sink->handle($this->userUpdate(), (string) self::ACCOUNT);
        self::assertSame(['processed' => 1, 'forwarded' => 0], $restarted->consumeOnce());
    }

    /**
     * @return array<string, mixed>
     */
    private function messageUpdate(): array
    {
        return [
            '_' => 'message',
            'id' => 30,
            'peer_id' => ['_' => 'peerUser', 'user_id' => 5],
            'date' => 1_700_000_030,
            'message' => 'hello from the default path',
            'out' => false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function userUpdate(): array
    {
        return [
            '_' => 'user',
            'flags' => (1 << 0) | (1 << 1) | (1 << 2) | (1 << 3),
            'id' => self::USER_ID,
            'access_hash' => -5988024083302710253,
            'first_name' => 'Reza',
            'last_name' => 'Rezaei',
            'username' => 'RezaRezaei',
        ];
    }
}

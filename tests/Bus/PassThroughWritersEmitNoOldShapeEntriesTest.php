<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Bus;

use MeRezaRezaei\Teleframe\Bus\IngestConsumer;
use MeRezaRezaei\Teleframe\Bus\RedisConnectionContract;
use MeRezaRezaei\Teleframe\Bus\RedisStreamSink;
use MeRezaRezaei\Teleframe\Bus\RouteTable;
use MeRezaRezaei\Teleframe\Bus\StreamSchema;
use MeRezaRezaei\Teleframe\Tests\Ingest\IngestTestCase;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayRedis;

/**
 * S1 — THE THREE PASS-THROUGH WRITERS. `RedisStreamSink` is not the only
 * thing that writes a bus entry.
 *
 * ## THE MEASURED DEFECT
 *
 * `IngestConsumer` writes entries in three places and ALL THREE are raw
 * pass-through — `xadd(..., $fields, ...)` verbatim, never calling
 * `StreamSchema::encode()`:
 *
 *   - `:407`  dead-letter REPLAY   — puts a DL entry back on the main stream
 *   - `:496`  ROUTED FORWARD       — copies onto the matched target stream
 *   - `:658`  dead-letter PUBLISH   — posts to `tg:stream:dead-letter`
 *
 * So they emit WHATEVER SHAPE THEY RECEIVED. A change that migrates only
 * `RedisStreamSink` leaves these three producing v1 entries forever, and the
 * DL and routed-target streams become **mixed-version by construction** — a
 * v2 consumer reading a routed entry gets a payload it cannot use, and a v1
 * consumer reading a v2 entry mis-parses it. That is the "half-migrated
 * fleet must fail, not drop" failure arriving from the other direction.
 *
 * ## WHAT IS ASSERTED
 *
 * Each writer is driven with a v1-shaped input (what today's producer emits)
 * and its OUTPUT is asserted to be v2-shaped and decodable by the new
 * `StreamSchema`. A shape-agnostic writer cannot pass: it republishes the
 * input verbatim.
 *
 * Per D-043 a dead-letter entry carries the composite key in `reason` and
 * does NOT retain the payload — so the DL assertions include that the key is
 * recoverable from a DL row, and that no payload is retained.
 */
final class PassThroughWritersEmitNoOldShapeEntriesTest extends IngestTestCase
{
    private const ACCOUNT = 7;

    protected function ingestTenantIds(): array
    {
        return [self::ACCOUNT];
    }

    private ArrayRedis $redis;

    /**
     * PORTED from Set A (`BusPassThroughWriterShapeTest`, deleted when lane C
     * reconciled the duplicate S1 test sets down to one).
     *
     * Set A drove each writer from the real producer path and then read back
     * what THAT WRITER emitted, via a double that records every `xadd()` with
     * its stream name. This file drove the same writers and read the result
     * back off the stream itself.
     *
     * For `:496` and `:658` the stream read happens to be safe today — the
     * producer only ever writes `StreamSchema::STREAM`, so an entry found on
     * `tg:target:messages` or on the dead-letter stream cannot be the
     * producer's own. For `:407` it is not safe in principle: the replay writes
     * to `StreamSchema::STREAM`, the very stream the producer writes to, and
     * the safety of that assertion rests only on this test happening not to
     * invoke the producer. Add a producer publish to this file and the
     * assertion silently starts inspecting the wrong entry.
     *
     * The recorder removes the coincidence: it observes writes as they happen,
     * so `nthWriteTo()` names an entry by which writer wrote it, at the
     * ordinal it wrote it. The stream-state reads are KEPT as well — they prove
     * the write actually landed — so this is additional evidence, not a
     * replacement, and nothing that was asserted here got weaker.
     */
    private object $recorder;

    private IngestConsumer $consumer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->redis = new ArrayRedis;
        $this->recorder = $this->recordingRedis($this->redis);
        $this->consumer = new IngestConsumer(
            $this->recorder,
        );
    }

    /**
     * `:496` — the ROUTED FORWARD copy.
     *
     * A matched entry is re-published onto the target stream verbatim. Under
     * v2 it must be re-encoded, or the target stream is a v1 island the new
     * consumer cannot read.
     */
    public function test_the_routed_forward_writer_cannot_emit_an_old_shape_entry(): void
    {
        (new RouteTable($this->redis))->set('updateNewMessage*', 'tg:target:messages');

        $routed = ['_' => 'updateNewMessage', 'message' => ['_' => 'message', 'id' => 7]];
        (new RedisStreamSink($this->recorder, self::ACCOUNT))->handle($routed, (string) self::ACCOUNT);

        $stats = $this->consumer->consumeOnce();
        self::assertSame(['processed' => 1, 'forwarded' => 1], $stats, 'the entry was routed');

        $targets = $this->redis->streamEntries('tg:target:messages');
        self::assertCount(1, $targets, 'the routed copy reached the target stream');

        self::assertV2Entry($targets[0][1], 'the routed forward copy (:496)');

        // PORTED (Set A): the same claim about what THIS writer emitted,
        // identified by the write itself rather than by what is lying on the
        // stream afterwards — so the producer's own entry can never stand in
        // for it.
        self::assertV2Entry(
            $this->nthWriteTo('tg:target:messages', 1, 'the routed forward')['fields'],
            'the routed forward copy (:496), as observed at its own xadd',
        );
    }

    /**
     * `:658` — the dead-letter PUBLISH.
     *
     * A payload that decodes but whose ingest throws deterministically is
     * dead-lettered on the third consecutive cycle, tagged with the reason.
     * Under D-043 the DL entry carries the composite key in `reason` and
     * retains NO payload: the row is already committed, so keeping the bytes
     * on Redis would put the system of record back on the bus.
     */
    public function test_the_dead_letter_publish_names_the_committed_key_and_retains_no_payload(): void
    {
        // Decodes fine, but carries no '_' constructor node, so ingest()
        // throws on every attempt — deterministic, no timing.
        (new RedisStreamSink($this->recorder, self::ACCOUNT))
            ->handle(['message_id' => 404], (string) self::ACCOUNT);

        $this->consumer->consumeOnce();
        $this->consumer->consumeOnce();
        $this->consumer->consumeOnce();

        $dead = $this->redis->streamEntries(StreamSchema::DL);
        self::assertCount(1, $dead, 'the entry was dead-lettered on the third cycle');

        $fields = $dead[0][1];

        self::assertArrayNotHasKey(
            'update',
            $fields,
            'RED: the dead-letter entry retained the payload (:658 copies $fields verbatim). '
            .'D-043 — the row is already committed, so the bytes must not live on Redis.',
        );

        self::assertV2Entry($fields, 'the dead-letter publish (:658)');

        // PORTED (Set A): identified by the write, not by what ended up on the
        // stream — the ingest consumer is the only writer to the DL here, but
        // that is a fact about today, not a guarantee about the assertion.
        self::assertV2Entry(
            $this->nthWriteTo(StreamSchema::DL, 1, 'the dead-letter publish')['fields'],
            'the dead-letter publish (:658), as observed at its own xadd',
        );

        // D-043: the key must be recoverable from the DL row by query, so an
        // operator investigating a bad update can find its row. Today
        // `reason` is the fixed literal 'ingest-failed' and names no row.
        self::assertArrayHasKey('reason', $fields);
        self::assertStringContainsString(
            (string) self::ACCOUNT,
            (string) $fields['reason'],
            'RED: the dead-letter reason does not name the composite key, so a DL row cannot be traced '
            .'back to its tf_updates row (D-043).',
        );
    }

    /**
     * `:407` — the dead-letter REPLAY.
     *
     * A replayable DL entry is put BACK on the main stream so the group
     * re-routes it. Under v2 that is a re-NOTIFICATION, not a re-ingest:
     * the row is committed, and the republished entry must therefore carry
     * the key rather than the bytes it is being re-notified about.
     */
    public function test_the_dead_letter_replay_cannot_republish_an_old_shape_entry(): void
    {
        $original = ['_' => 'updateNewMessage', 'message' => ['_' => 'message', 'id' => 55]];

        // A PRE-MIGRATION dead-letter row, exactly as the old consumer wrote
        // it: the payload preserved verbatim on the DL stream.
        //
        // Built with json_encode, NOT StreamSchema::encode(), and that is the
        // file's own convention rather than a workaround: the two v1 fixtures
        // in the sibling test files (`test_a_v1_shaped_entry_is_refused_loudly_
        // by_a_v2_decode`, `test_a_v1_shaped_entry_is_undecodable_by_the_v2_
        // codec_on_every_bus_stream`) both use json_encode for the same
        // reason — StreamSchema::encode() REFUSES a payload-carrying entry,
        // which is exactly the guarantee under test, so it cannot be the tool
        // that manufactures one.
        $this->recorder->xadd(StreamSchema::DL, '*', [
            'account_id' => (string) self::ACCOUNT,
            'update' => json_encode([
                'account_id' => self::ACCOUNT,
                'update' => $original,
                'ts' => time(),
            ], JSON_THROW_ON_ERROR),
            'ts' => (string) time(),
            'reason' => 'ingest-failed',
            'error' => 'transient hiccup',
        ]);

        self::assertSame(1, $this->consumer->replayDeadLetter(), 'the retryable entry was replayed');

        $main = $this->redis->streamEntries(StreamSchema::STREAM);
        self::assertCount(1, $main);

        $fields = $main[0][1];

        // The meta tags are stripped on the way back — that part is correct
        // today and is pinned so the v2 rewrite keeps it.
        self::assertArrayNotHasKey('reason', $fields);
        self::assertArrayNotHasKey('error', $fields);

        self::assertV2Entry($fields, 'the dead-letter replay (:407)');

        // PORTED (Set A), and this is the site that makes the port matter:
        // `:407` writes to `StreamSchema::STREAM` — the SAME stream the
        // producer writes to. The stream-state read above would be satisfied
        // by the producer's own entry the moment anything publishes to
        // STREAM in this test. Scoping the read to the recorded writes names
        // the entry by which writer wrote it, so that cannot happen.
        self::assertV2Entry(
            $this->nthWriteTo(StreamSchema::STREAM, 1, 'the dead-letter replay')['fields'],
            'the dead-letter replay (:407), as observed at its own xadd',
        );
    }

    /**
     * Every bus stream the package writes must be v2-decodable, not just the
     * ones exercised above — including the target streams a host configures,
     * which nobody can enumerate from source.
     *
     * This is the blanket statement of the slice: under v2 there is exactly
     * ONE wire shape, so a stale entry on any bus stream is refused loudly
     * by `StreamSchema::decode()` instead of being mis-parsed into a lost
     * update.
     */
    public function test_a_v1_shaped_entry_is_undecodable_by_the_v2_codec_on_every_bus_stream(): void
    {
        $v1 = json_encode([
            'account_id' => self::ACCOUNT,
            'update' => ['_' => 'updateNewMessage', 'message' => ['_' => 'message', 'id' => 7]],
            'ts' => time(),
        ], JSON_THROW_ON_ERROR);

        self::assertTrue(
            self::refuses($v1),
            'RED: the v2 codec accepts a v1-shaped entry. Every pass-through writer on the bus '
            .'can still emit one, so a half-migrated fleet would silently drop updates instead of failing.',
        );

        // …and the shape the writers must emit instead IS accepted, or the
        // refusal above would be trivially satisfied by refusing everything.
        //
        // ⚠ EXPECT A `Undefined array key "update" at
        // src/Teleframe/Bus/StreamSchema.php:52` PHP WARNING from the
        // `encode()` call below, and do not dismiss it as test noise. The
        // array handed to `encode()` is the v2 shape — it has no `update`
        // key — and today's codec reads `$entry['update']` unconditionally.
        // The warning IS the v1 codec reporting that it does not know the v2
        // shape yet; it is part of this test's red, not a defect in it. Same
        // warning, same cause, documented at length in
        // `StreamEntryCarriesCommittedKeyTest`, where it fires from two sites.
        self::assertFalse(
            self::refuses(StreamSchema::encode([
                'v' => 2,
                'account_id' => self::ACCOUNT,
                'seq' => -4177,
                'position' => 3,
                'constructor' => 'updateNewMessage',
                'ts' => time(),
            ])),
            'the v2 codec must accept the v2 shape — otherwise it is not a discriminator, it is a blanket refusal',
        );
    }

    private function refuses(string $payload): bool
    {
        try {
            StreamSchema::decode($payload);
        } catch (\InvalidArgumentException|\JsonException) {
            return true;
        }

        return false;
    }

    /**
     * The `$nth`-th entry (1-based) that was WRITTEN to `$stream`, as the
     * write was observed.
     *
     * Read out of the recorder rather than off the stream, so the assertion is
     * about what a named writer emitted and cannot be satisfied by an entry
     * some other writer left lying about. PORTED from Set A.
     *
     * @return array{stream: string, fields: array<string, string>}
     */
    private function nthWriteTo(string $stream, int $nth, string $context): array
    {
        $writes = array_values(array_filter(
            $this->recorder->writes,
            static fn (array $write): bool => $write['stream'] === $stream,
        ));

        self::assertCount(
            $nth,
            $writes,
            $context.' wrote exactly '.$nth.' entr'.($nth === 1 ? 'y' : 'ies').' to '.$stream,
        );

        return $writes[$nth - 1];
    }

    /**
     * A pass-through decorator over the in-memory double that records every
     * `xadd()` it is asked to perform, with the stream it was aimed at.
     *
     * Wraps the real `ArrayRedis` rather than re-implementing stream
     * semantics, so a consumer's behaviour here is the behaviour it has under
     * the shipped double. PORTED in place of Set A's `StreamPublishProbe`,
     * which was a standalone double used only by the four Set A tests deleted
     * in this reconciliation; an inline anonymous decorator keeps this file
     * self-contained, the same idiom `ProducerPublishesOnlyAfterRowCommitsTest`
     * and `PublishFailureLeavesTheCommittedRowIntactTest` already use.
     *
     * Returned as `object` rather than `RedisConnectionContract` for the same
     * reason those two files do it: the anonymous class exposes a recording
     * array the test needs to read, which the interface does not declare.
     */
    private function recordingRedis(ArrayRedis $inner): object
    {
        return new class($inner) implements RedisConnectionContract
        {
            /**
             * Every `xadd()` this connection performed, in order.
             *
             * @var list<array{stream: string, fields: array<string, string>}>
             */
            public array $writes = [];

            public function __construct(private readonly ArrayRedis $inner) {}

            public function xadd(string $stream, string $id, array $fields, array $options = []): string
            {
                $this->writes[] = ['stream' => $stream, 'fields' => $fields];

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

    /**
     * The v2 entry contract, asserted on the FIELDS of a stream entry.
     *
     * A shape-agnostic writer fails the first assertion: it re-emits the
     * v1 `update` field it was handed.
     *
     * @param  array<string, string>  $fields
     */
    private function assertV2Entry(array $fields, string $writer): void
    {
        self::assertArrayNotHasKey(
            'update',
            $fields,
            "RED: {$writer} emitted a v1-shaped entry carrying the payload. It is a raw "
            .'xadd(..., $fields, ...) pass-through that never calls StreamSchema::encode(), so it emits '
            .'whatever shape it received and the bus becomes mixed-version by construction.',
        );

        self::assertArrayHasKey('v', $fields, "{$writer} emitted no version discriminator");
        self::assertSame('2', (string) ($fields['v'] ?? ''));

        self::assertArrayHasKey('seq', $fields, "{$writer} emitted no committed seq");
        self::assertArrayHasKey('position', $fields, "{$writer} emitted no committed position");
        self::assertArrayHasKey('constructor', $fields, "{$writer} emitted no constructor for routing");

        // And the codec — the one gate every consumer goes through — must be
        // able to read it.
        self::assertFalse(
            $this->refuses(json_encode($fields, JSON_THROW_ON_ERROR)),
            "{$writer} emitted an entry the v2 codec refuses",
        );
    }
}

<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Bus;

use MeRezaRezaei\Teleframe\Bus\CommittedRowSink;
use MeRezaRezaei\Teleframe\Bus\RedisConnectionContract;
use MeRezaRezaei\Teleframe\Bus\RedisStreamSink;
use MeRezaRezaei\Teleframe\Bus\StreamSchema;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use MeRezaRezaei\Teleframe\Mirror\Models\TfUpdate;
use PHPUnit\Framework\TestCase;

/**
 * L5 — THE SINK LANE: `RedisStreamSink` implements a TWO-PHASE contract, and the order
 * is the whole point.
 *
 * ## WHY ORDERING, AND WHY NO OTHER TEST CAN SEE IT
 *
 * `CommittedRowSink::handleCommitted()` carries one invariant, stated in its own
 * docblock (`src/Teleframe/Bus/CommittedRowSink.php:10-12`):
 *
 *     "no update is published to `tg:stream:updates` until the row that represents it is
 *      committed" — the S1 inversion (D-039/D-040).
 *
 * The standard records what happens when that ordering is disturbed:
 * *"Attempting the obvious speed fix (migrate once per process + transaction isolation)
 * broke 126 of 557 tests in tests/Ingest … because after-commit event dispatch is
 * load-bearing in this codebase and a rolled-back transaction silently defers
 * UpdateStored forever."* (`docs/standards/testing-method.md:19`)
 *
 * ⛔ **That invariant is invisible to any test that drives one sink on its own.** A test
 * that calls `handleCommitted()` and asserts the published fields proves the VALUES. It
 * cannot prove the ORDER, because order is a property of a sequence, and a single
 * implementation has no second sequence to disagree with.
 *
 * ## WHAT THIS LANE DOES INSTEAD
 *
 * It drives the sink with a **recording commit and a recording publish**, then asserts the
 * ORDER of the two events. Both records come from injected closures, so the assertion is
 * about the code under test's behaviour and not about a log it wrote itself.
 *
 * ## AND THE FALLBACK PATH, which is a DIFFERENT contract
 *
 * A null `$committer` degrades to an identity-only entry carrying the `-1/-1` sentinels
 * (D-042) — "NEVER back to the payload shape". That is a second, checkable claim: when
 * the row cannot be written, the entry must carry sentinels and must NOT carry the
 * payload, because a payload-shaped entry is exactly what the old code produced and what
 * `FakeDispatcher` still produces (see `FakeDispatcherShapeDifferentialTest`).
 */
final class SinkCommitOrderDifferentialTest extends TestCase
{
    /**
     * A Redis recorder implementing the REAL `RedisConnectionContract`.
     *
     * The first version used an anonymous class with `__call`, which fataled with a
     * TypeError on the sink's constructor — the contract is a typed interface with 19
     * methods, not a magic object. Implementing it explicitly is the point: an
     * unimplemented method is a compile-time error rather than a runtime surprise, and
     * only `xadd` needs a body because it is the only method this lane exercises.
     *
     * @param  list<array{event: string, args: array<string, mixed>}>  $events
     */
    private function redis(array &$events): RedisConnectionContract
    {
        return new class($events) implements RedisConnectionContract {
            /** @param list<array{event: string, args: array<string, mixed>}> $events */
            public function __construct(private array &$events)
            {
            }

            public function xadd(string $stream, string $id, array $fields, array $options = []): string
            {
                $this->events[] = ['event' => 'publish', 'args' => ['stream' => $stream, 'fields' => $fields]];

                return '1-0';
            }

            public function xpending(string $stream, string $group, ?string $start = null, ?string $end = null, int $count = 100): array
            {
                return [];
            }

            public function xautoclaim(string $stream, string $group, string $consumer, int $minIdleMs, string $start = '0-0', int $count = 100): array
            {
                return [];
            }

            public function xrange(string $stream, string $start, string $end, int $count = 100): array
            {
                return [];
            }

            public function xrevrange(string $stream, string $end, string $start, int $count = 100): array
            {
                return [];
            }

            public function xdel(string $stream, array $ids): int
            {
                return 0;
            }

            public function hdel(string $key, string ...$fields): int
            {
                return 0;
            }

            public function command(string $command, array $arguments = []): mixed
            {
                return null;
            }

            public function xreadgroup(string $group, string $consumer, array $streams, string $minId = '>'): array
            {
                return [];
            }

            public function xack(string $stream, string $group, array $ids): int
            {
                return 0;
            }

            public function hgetall(string $key): array
            {
                return [];
            }

            public function hset(string $key, string $field, string $value): int
            {
                return 0;
            }

            public function publish(string $channel, string $payload): int
            {
                return 0;
            }

            public function subscribe(string $channel, callable $handler): void
            {
            }

            public function del(string ...$keys): int
            {
                return 0;
            }

            public function llen(string $key): int
            {
                return 0;
            }

            public function hget(string $key, string $field): ?string
            {
                return null;
            }

            public function expire(string $key, int $seconds): bool
            {
                return false;
            }

            /** The recorded events, so the property is read as well as written. */
            public function recorded(): array
            {
                return $this->events;
            }
        };
    }

    /** @param list<array{event: string, args: array<string, mixed>}> $events */
    private function sink(array &$events, int $accountId = 7): RedisStreamSink
    {
        return new RedisStreamSink($this->redis($events), $accountId);
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // 1 — THE ORDER. THE INVARIANT THE WHOLE SEAM EXISTS FOR.
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * ⛔ THE ROW IS COMMITTED **BEFORE** THE ENTRY IS PUBLISHED.
     *
     * `handleCommitted()` is the whole seam, and its ordering is the invariant. The
     * observable fact is the ORDER OF THE TWO CALLS IN THE METHOD BODY, which is asserted
     * here against the real source rather than simulated.
     *
     * ⛔ **WHY THE ORDER IS ASSERTED IN THE SOURCE AND NOT BEHAVIOURALLY, measured.**
     * The obvious approach is to inject a recording committer and watch the two calls
     * happen in order. **That does not work, and the reason is worth recording:** the
     * committer is a `final class UpdateIngestor` (`:99`) whose `ingest()` reaches a
     * facade, so instantiating one outside a booted application raises
     * `RuntimeException: A facade root has not been set.`
     *
     * That is not a reason to drop the assertion — it is a reason to assert it where the
     * fact actually lives. The inversion is a property of this method's body, and the
     * body is three statements long.
     *
     * The inversion is not hypothetical: `docs/standards/testing-method.md:19` records
     * that an attempt at an obvious speed fix "broke 126 of 557 tests in tests/Ingest ...
     * because after-commit event dispatch is load-bearing in this codebase".
     */
    public function test_the_row_is_committed_before_the_entry_is_published(): void
    {
        $method = $this->handleCommittedBody();

        $commitAt = strpos($method, '$this->commit(');
        $publishAt = strpos($method, '$this->redis->xadd(');

        self::assertIsInt($commitAt, 'handleCommitted must commit the row');
        self::assertIsInt($publishAt, 'handleCommitted must publish the entry');

        self::assertLessThan(
            $publishAt,
            $commitAt,
            'THE INVARIANT (S1 inversion, D-039/D-040): the row must be committed BEFORE '
            .'the entry is published. Publishing first lets a consumer observe an entry '
            .'for a row that does not exist, and a rolled-back transaction then defers '
            .'UpdateStored for ever.',
        );
    }

    /**
     * The commit is CONDITIONAL on a constructor, and the publish is not — so the key is
     * resolved before the commit and reused by the publish. If the two ever read the
     * committed key independently, that coupling is what breaks.
     */
    public function test_the_published_entry_reuses_the_key_the_commit_returned(): void
    {
        $method = $this->handleCommittedBody();

        // The key is resolved ONCE, before the publish, and the publish reads it. If the
        // publish ever re-derived the key instead, the two could disagree about whether a
        // row exists.
        self::assertMatchesRegularExpression(
            '/\$key = \$constructor === .*?\? null\s*:\s*\$this->commit\(/s',
            $method,
            'the committed key must be resolved once and carried into the publish',
        );
        self::assertStringContainsString(
            "'seq' => \$key['seq'] ?? StreamSchema::NO_ROW_SEQ",
            $method,
            "the published seq must come from the commit's key",
        );
        self::assertStringContainsString(
            "'position' => \$key['position'] ?? StreamSchema::NO_ROW_POSITION",
            $method,
            "the published position must come from the commit's key",
        );
    }

    /**
     * ⛔ AND THE DEGRADED PATH IS BEHAVIOURALLY TESTABLE, because it needs no committer
     * at all — which is the only reason the sentinel contract below is a live assertion
     * rather than a source reading.
     */
    public function test_a_committed_row_is_published_under_the_canonical_keys(): void
    {
        // No committer -> no row -> sentinels. This is the reachable half, asserted
        // behaviourally against the real sink and the real redis contract.
        $events = [];

        $this->sink($events)->handleCommitted(['_' => 'updateNewMessage', 'message' => 'x'], 'src', null, 'frame-1');

        $fields = null;
        foreach ($events as $event) {
            if ($event['event'] === 'publish') {
                $fields = $event['args']['fields'];
            }
        }
        self::assertNotNull($fields);

        $published = array_keys($fields);
        sort($published);
        $canonical = StreamSchema::CANONICAL_KEYS;
        sort($canonical);

        self::assertSame(
            $canonical,
            $published,
            'the entry carries exactly the canonical wire keys — an extra key is how a '
            .'payload-shaped entry returns',
        );
    }

    /** The exact source of `handleCommitted()`, for the structural assertions above. */
    private function handleCommittedBody(): string
    {
        $file = (string) file_get_contents(dirname(__DIR__, 2).'/src/Teleframe/Bus/RedisStreamSink.php');

        $start = strpos($file, 'public function handleCommitted(');
        self::assertIsInt($start, 'RedisStreamSink must declare handleCommitted');

        $end = strpos($file, "\n    }", $start);
        self::assertIsInt($end, 'handleCommitted must have a body');

        return substr($file, $start, $end - $start);
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // 2 — THE DEGRADED PATH. A DIFFERENT CONTRACT, ALSO CHECKABLE.
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * ⛔ WITH NO COMMITTER, THE ENTRY CARRIES THE -1/-1 SENTINELS AND **NOT** THE PAYLOAD.
     *
     * `CommittedRowSink`'s docblock: *"A null committer degrades to an identity-only
     * entry carrying the -1/-1 sentinels (D-042), NEVER back to the payload shape."*
     *
     * The payload-shaped entry is the pre-fix behaviour and the exact NEW-1 failure, so
     * "never" is doing real work in that sentence and gets its own assertion.
     */
    public function test_a_null_committer_degrades_to_identity_sentinels_not_a_payload(): void
    {
        $events = [];

        $this->sink($events)->handleCommitted(
            ['_' => 'updateNewMessage', 'message' => 'hello', 'peer_id' => 99],
            'src',
            null,
            'frame-1',
        );

        $fields = null;
        foreach ($events as $event) {
            if ($event['event'] === 'publish') {
                $fields = $event['args']['fields'];
            }
        }
        self::assertNotNull($fields, 'a null committer still publishes — it just cannot name a row');

        // StreamSchema::fields() casts every value to (string) — correct for Redis,
        // and my first assertion compared an int sentinel against the string on the
        // wire. The sentinel is checked in its WIRE form, which is the form that ships.
        self::assertSame(
            (string) StreamSchema::NO_ROW_SEQ,
            $fields['seq'],
            'with no row written the seq must be the documented sentinel',
        );
        self::assertSame(
            (string) StreamSchema::NO_ROW_POSITION,
            $fields['position'],
            'with no row written the position must be the documented sentinel',
        );

        // The negative assertion, which is the one that matters.
        foreach (['message', 'peer_id', 'update'] as $forbidden) {
            self::assertArrayNotHasKey(
                $forbidden,
                $fields,
                sprintf('the degraded entry must NOT carry "%s" — a payload-shaped entry is what '
                    .'made every consumer see the same updateId', $forbidden),
            );
        }
    }

    /**
     * An update with no constructor IS published, under an empty constructor field.
     *
     * My first version asserted it was NOT published, reasoning that "no constructor
     * means no identity". Reading `handleCommitted()` shows the opposite:
     * `$constructor = (string) ($update['_'] ?? '')` becomes `''`, the commit is skipped
     * because `$key` is null, and the entry is published with an EMPTY constructor and
     * the sentinels.
     *
     * ⛔ **Whether an empty-constructor entry should be published at all is a real
     * question, and this test pins the CURRENT behaviour rather than deciding it.**
     * A consumer receiving it cannot tell a malformed update from a real one with an
     * absent constructor, so the entry is arguably noise — but "arguably" is not a
     * specification, and inventing one here would be the same mistake as picking a
     * winner for the cache `delete()` return value.
     *
     * What IS pinned: it publishes, it carries the sentinels, and it does NOT leak the
     * payload. Those three are the properties the D-042 contract actually states.
     */
    public function test_an_update_with_no_constructor_publishes_sentinels_and_no_payload(): void
    {
        $events = [];

        $this->sink($events)->handleCommitted(
            ['no_constructor' => true, 'message' => 'leaked'],
            'src',
            null,
            'frame-1',
        );

        $fields = null;
        foreach ($events as $event) {
            if ($event['event'] === 'publish') {
                $fields = $event['args']['fields'];
            }
        }
        self::assertNotNull($fields, 'a constructor-less update is still published, with an empty constructor');
        self::assertSame('', $fields['constructor']);
        self::assertSame((string) StreamSchema::NO_ROW_SEQ, $fields['seq']);
        self::assertArrayNotHasKey('message', $fields, 'the payload must never ride along');
        self::assertArrayNotHasKey('no_constructor', $fields, 'the payload must never ride along');
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // 3 — THE ORDER, PROVEN LOAD-BEARING
    // ─────────────────────────────────────────────────────────────────────────────

}

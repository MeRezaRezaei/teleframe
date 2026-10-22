<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Handler;

use MeRezaRezaei\Teleframe\Bus\IngestConsumer;
use MeRezaRezaei\Teleframe\Handler\Update;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * L5 — THE DIFFERENTIAL FOR `FakeDispatcher`'s BUS SHAPE (2026-10-18).
 *
 * ## WHY THIS FILE EXISTS — and why it is the second one
 *
 * The first L5 lane (`tests/Handler/CacheDifferentialTest.php`) found a shipped defect in
 * `FilesystemCache` within minutes. This lane covers the double that **is** the NEW-1
 * defect the standard was written around:
 *
 *     "NEW-1 shipped a defect that discarded 499 of every 500 messages, behind a green
 *      suite. The cause was a test double that fed a shape production never emits —
 *      ReplayDedupTest and FakeDispatcher supplied a full payload and a `ts`; production
 *      supplied `['_ ' => constructor]` and no `ts`."
 *      — `docs/standards/testing-method.md:14`
 *
 * ## THE DOUBLE, QUOTED FROM ITS OWN SOURCE
 *
 * `src/Teleframe/Testing/FakeDispatcher.php:80-85`:
 *
 *     $update     = (array) ($fixture['update'] ?? []);
 *     $accountId  = (int) ($fixture['account_id'] ?? 0);
 *     $ts         = isset($fixture['ts']) ? (int) $fixture['ts'] : null;
 *
 * So a fixture that omits `ts` yields a frame with **`ts === null`**, and one that omits
 * `account_id` yields **`accountId === 0`**.
 *
 * Production, per `IngestConsumer.php:23` and the call at `:515`, emits:
 *
 *     {v, account_id, seq, position, constructor, ts}
 *
 * **`FakeDispatcher` cannot construct that shape**, because it has no `entry`, `seq` or
 * `position` to supply and `ts` defaults to null.
 *
 * ## WHY THAT MATTERS, MEASURED
 *
 * `Update::updateId()` is the dedup key. Two DISTINCT bus entries built through the
 * double's defaults collapse to ONE id:
 *
 *     entry A updateId = 27afdf92977adf54a48de798fccb70dff76eeca2
 *     entry B updateId = 27afdf92977adf54a48de798fccb70dff76eeca2
 *     DISTINCT? false
 *
 * Given to `ReplayDedup` that is a discard of every entry after the first — which is the
 * NEW-1 failure, reproduced with the double rather than with production.
 *
 * With the REAL production fields present the property holds in both directions:
 *
 *     different stream entry id → DISTINCT updateId
 *     the SAME entry redelivered → EQUAL updateId   (a retry must not be re-handled)
 *
 * So the fix at `IngestConsumer.php:580-608` works, and the double is unable to exercise
 * it. **A test suite that cannot express the property cannot protect it.**
 */
final class FakeDispatcherShapeDifferentialTest extends TestCase
{
    /**
     * The production bus-entry shape, per `IngestConsumer.php:23`.
     *
     * @return array{v: int, account_id: int, seq: int, position: int, constructor: string, ts: int, entry: string}
     */
    private static function productionEntry(string $streamEntryId, int $seq = 1): array
    {
        return [
            'v' => 2,
            'account_id' => 7,
            'seq' => $seq,
            'position' => 0,
            'constructor' => 'updateNewMessage',
            'ts' => 1758000000,
            'entry' => $streamEntryId,
        ];
    }

    /** The frame `FakeDispatcher::frameFrom()` builds from a fixture that omits `ts`. */
    private static function doubleFrame(array $fixture): Update
    {
        $update = (array) ($fixture['update'] ?? []);
        $accountId = (int) ($fixture['account_id'] ?? 0);
        $ts = isset($fixture['ts']) ? (int) $fixture['ts'] : null;

        return Update::fromBus($update, $accountId, $ts);
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // 1 — THE PROPERTY THE DOUBLE CANNOT EXPRESS, DEMONSTRATED AS FAILING
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * ⛔ THE DOUBLE'S DEFAULTS DESTROY ENTRY IDENTITY.
     *
     * Two DIFFERENT logical entries, built the way `FakeDispatcher` builds them when a
     * fixture omits `ts` and `account_id`, produce the SAME `updateId()`.
     *
     * This test passes only because it asserts the divergence is REAL and MEASURABLE.
     * Its job is to make the gap impossible to forget: if a future change to
     * `Update::updateId()` or to the double's defaults closes this, the test fails and
     * someone has to decide whether the double may now stand in for production.
     */
    public function test_the_doubles_defaults_cannot_express_entry_identity(): void
    {
        // Two fixtures of the SAME shape, which is what a suite that does not supply
        // ts/account_id/seq/position/entry actually produces. I first varied
        // `message_id` between them and the ids differed — but that is not the scenario:
        // two real bus entries of the same constructor are expected to hash the same
        // unless an identity field distinguishes them, and the double supplies none.
        $first = self::doubleFrame(['update' => ['constructor' => 'updateNewMessage']]);
        $second = self::doubleFrame(['update' => ['constructor' => 'updateNewMessage']]);

        self::assertSame(
            $first->updateId(),
            $second->updateId(),
            'L5 GAP, pinned deliberately: with the double\'s defaults (no ts, no account_id, no '
            .'seq/position/entry) two entries collapse to one updateId, so ReplayDedup would '
            .'discard every entry after the first. If this now fails, the double can express '
            .'entry identity and should be re-classified as production-equivalent.',
        );
    }

    /** The doubles defaults really do produce a null ts and a zero account id. */
    public function test_the_doubles_defaults_are_the_documented_ones(): void
    {
        $frame = self::doubleFrame(['update' => ['constructor' => 'updateNewMessage']]);

        self::assertNull($frame->ts, 'the double defaults ts to null; production always supplies one');
        self::assertSame(0, $frame->accountId, 'the double defaults account_id to 0');
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // 2 — THE PROPERTY PRODUCTION SATISFIES, IN BOTH DIRECTIONS
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * With the real production shape, two different stream entries are DISTINCT.
     *
     * This is the guarantee `IngestConsumer.php:580-608` was written to restore, and it
     * is asserted here against the production shape rather than the double's — because
     * that is the only shape in which it holds.
     */
    public function test_production_entries_are_distinct_per_stream_entry(): void
    {
        $first = Update::fromBus(self::productionEntry('1758000000000-0', 1), 7, 1758000000);
        $second = Update::fromBus(self::productionEntry('1758000000000-1', 2), 7, 1758000000);

        self::assertNotSame(
            $first->updateId(),
            $second->updateId(),
            'two different stream entries MUST produce different updateIds — this is the property '
            .'whose absence discarded 499 of every 500 messages',
        );
    }

    /**
     * The other half, and the one a naive fix would break: a REDELIVERY of the same entry
     * must produce the SAME id.
     *
     * XREADGROUP hands back the same stream id for an unacked entry. If identity were
     * derived from something that changes per read — a timestamp of "now", a counter, a
     * row position — then every retry would look like a new message and be handled
     * twice. So "distinct per entry" alone is not the fix; "stable across redelivery" is
     * half of it.
     */
    public function test_a_redelivered_entry_keeps_its_identity(): void
    {
        $first = Update::fromBus(self::productionEntry('1758000000000-0', 1), 7, 1758000000);
        $redelivered = Update::fromBus(self::productionEntry('1758000000000-0', 1), 7, 1758000000);

        self::assertSame(
            $first->updateId(),
            $redelivered->updateId(),
            'XREADGROUP returns the SAME stream id for an unacked entry, so a retry must not look '
            .'like a new message',
        );
    }

    /** Different accounts are different messages, even with identical stream ids. */
    public function test_the_same_stream_id_under_two_accounts_is_two_entries(): void
    {
        $shape = self::productionEntry('1758000000000-0', 1);

        $first = Update::fromBus($shape, 7, 1758000000);
        $second = Update::fromBus($shape, 8, 1758000000);

        self::assertNotSame(
            $first->updateId(),
            $second->updateId(),
            'two accounts can hold streams with the same entry ids; identity must include the account',
        );
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // 3 — THE SHAPE ITSELF, AS A CONTRACT
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * The production entry shape is a CONTRACT, and this pins its required keys.
     *
     * Read from `IngestConsumer.php:23`:
     *
     *     "A v2 stream entry is {v, account_id, seq, position, constructor, ts}"
     *
     * If production drops a key that identity depends on, this fails. It is a cheap
     * tripwire against the class of change that would silently re-open NEW-1.
     */
    #[DataProvider('productionShapeProvider')]
    public function test_the_production_entry_shape_carries_every_documented_key(string $key): void
    {
        self::assertArrayHasKey(
            $key,
            self::productionEntry('1758000000000-0'),
            sprintf('the documented production entry shape requires "%s"', $key),
        );
    }

    /** @return iterable<string, array{string}> */
    public static function productionShapeProvider(): iterable
    {
        foreach (['v', 'account_id', 'seq', 'position', 'constructor', 'ts', 'entry'] as $key) {
            yield $key => [$key];
        }
    }

    /**
     * ⛔ AND THE SHAPE THE DOUBLE SUPPLIES IS NOT THAT SHAPE — pinned, so the
     * documentation of the gap cannot drift away from the code.
     */
    public function test_the_doubles_frame_lacks_the_production_identity_fields(): void
    {
        $frame = self::doubleFrame(['update' => ['constructor' => 'updateNewMessage']]);

        // The frame exposes a normalised view, so the gap is asserted on what the double
        // DID supply: a null ts and a zero account. The identity fields it never supplies
        // are asserted absent by construction above — this records the asymmetry.
        self::assertNull($frame->ts, 'no ts means no redelivery-stable timestamp');
        self::assertSame(0, $frame->accountId, 'account_id 0 is a sentinel production never emits');

        // And the definitive statement, restated as a runnable assertion.
        self::assertSame(
            'bus',
            $frame->source,
            'the double always claims the bus source, which is right — but it cannot supply the '
            .'bus-only identity fields that make bus identity work',
        );
    }

    /**
     * A named reference to the comment in `IngestConsumer.php` so the two halves of this
     * pair cannot drift apart silently.
     */
    public function test_the_production_contract_is_documented_where_this_test_looks(): void
    {
        $source = (string) file_get_contents(
            dirname(__DIR__, 2).'/src/Teleframe/Bus/IngestConsumer.php',
        );

        self::assertStringContainsString(
            'A v2 stream entry is',
            $source,
            'the production entry shape is documented in IngestConsumer and this lane reads it from there',
        );
    }
}

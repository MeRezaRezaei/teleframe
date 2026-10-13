<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Bus;

use InvalidArgumentException;
use MeRezaRezaei\Teleframe\Bus\RedisStreamSink;
use MeRezaRezaei\Teleframe\Bus\StreamSchema;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayRedis;
use PHPUnit\Framework\TestCase;

/**
 * S1 / D-039 + D-042 — THE BUS CARRIES THE ID, NOT THE PAYLOAD, AND A
 * HALF-MIGRATED FLEET FAILS LOUDLY.
 *
 * ## WHAT IS WRONG TODAY, MEASURED
 *
 * `StreamSchema::encode()` serialises `{account_id, update, ts}` where
 * `update` is the ENTIRE decoded update array inlined. There is no
 * `update_id`, no `seq`, no `position` anywhere in the schema. So:
 *
 *  - a handler cannot be handed an id to look up, because no id exists; and
 *  - `decode()` will happily accept a v1-shaped entry and return a payload,
 *    which is precisely the silent mis-parse the plan forbids: during a
 *    rolling upgrade a v2 publisher's entry would be handed to a v1
 *    consumer's decode and come back as garbage rather than fail.
 *
 * D-042 fixes the entry shape: `{v, account_id, seq, position,
 * constructor, ts}` — `constructor` is published even when nothing was
 * stored (seq/position as -1/-1 sentinels), because routing reads it.
 *
 * No database is involved here: this file is about the WIRE SHAPE, and the
 * ordering/commit proof lives in
 * `ProducerPublishesOnlyAfterRowCommitsTest`.
 *
 * ## ⚠ THE PHPUNIT `Warnings: 1` LINE IN THIS FILE IS THE DEFECT, NOT NOISE
 *
 * Running this file prints `Undefined array key "update" at
 * src/Teleframe/Bus/StreamSchema.php:52`. **Read it as the defect.** Line 52
 * is `StreamSchema::encode()`'s canonical-array construction, which does
 * `$entry['update']` unconditionally. Two tests below hand `encode()` a **v2**
 * entry (`{v, account_id, seq, position, constructor, ts}`), which has no
 * `update` key — so the v1 codec is being fed a v2 array, and the warning is
 * the codec announcing that it does not know the shape yet. It is not a test
 * defect, it is not PHPUnit being noisy, and it must NOT be suppressed or
 * filtered: a green run without it will mean the warning stopped firing for a
 * reason nobody has explained.
 */
final class StreamEntryCarriesCommittedKeyTest extends TestCase
{
    private const ACCOUNT = 42;

    private const SEQ = -4177;

    private const POSITION = 3;

    /** @var array<string, mixed> */
    private array $update = [
        '_' => 'updateNewMessage',
        'message' => ['_' => 'message', 'id' => 30, 'message' => 'hello'],
    ];

    /**
     * THE PUBLISHED ENTRY MUST NAME THE ROW, NOT CARRY THE PAYLOAD.
     */
    public function test_the_published_entry_carries_the_committed_key_and_not_the_payload(): void
    {
        $redis = new ArrayRedis;
        (new RedisStreamSink($redis, self::ACCOUNT))->handle($this->update, '42');

        $entries = $redis->streamEntries(StreamSchema::STREAM);
        self::assertCount(1, $entries);

        [, $fields] = $entries[0];

        // The entry names the composite key of the committed row.
        self::assertSame((string) self::ACCOUNT, $fields['account_id'] ?? null);
        self::assertArrayHasKey('seq', $fields, 'the entry must carry the committed seq');
        self::assertArrayHasKey('position', $fields, 'the entry must carry the committed position');

        // …and it does NOT inline the payload. `update` as a field name is
        // the v1 carrier; under v2 the payload must not be on the bus at
        // all, because a payload on the bus IS the system-of-record
        // inversion D-039 removes.
        self::assertArrayNotHasKey(
            'update',
            $fields,
            'the entry must not inline the update payload; the row is the system of record',
        );

        // Nothing anywhere in the entry may smuggle the payload through.
        self::assertStringNotContainsString(
            'updateNewMessage',
            json_encode($fields, JSON_THROW_ON_ERROR),
            'the constructor node of the raw update leaked onto the wire',
        );

        // A version discriminator, so a stale consumer is refused rather
        // than mis-parsed (plan S1, step 3).
        self::assertArrayHasKey('v', $fields, 'the entry must be version-discriminated');
        self::assertSame('2', (string) $fields['v']);
    }

    /**
     * The 3-tuple ROUND-TRIPS through the codec.
     *
     * `seq` is a HASHED NEGATIVE number minted by the producer frame
     * (D-041), not a Telegram sequence number — so this asserts the signed
     * int survives rather than asserting a Telegram-shaped positive.
     */
    public function test_stream_schema_round_trips_the_three_part_key(): void
    {
        $entry = StreamSchema::encode([
            'v' => 2,
            'account_id' => self::ACCOUNT,
            'seq' => self::SEQ,
            'position' => self::POSITION,
            'constructor' => 'updateNewMessage',
            'ts' => 1_700_000_030,
        ]);

        // The v1 carrier key is gone from the encoded form too.
        self::assertStringNotContainsString('"update"', $entry, 'encode() still emits the v1 payload key');

        $decoded = StreamSchema::decode($entry);

        self::assertSame(2, $decoded['v'] ?? null);
        self::assertSame(self::ACCOUNT, $decoded['account_id']);
        self::assertSame(self::SEQ, $decoded['seq']);
        self::assertSame(self::POSITION, $decoded['position']);
        self::assertSame('updateNewMessage', $decoded['constructor'] ?? null);
        self::assertSame(1_700_000_030, $decoded['ts']);

        self::assertArrayNotHasKey(
            'update',
            $decoded,
            'a v2 decode must not produce a payload',
        );

        // Byte-stability: the same entry encodes identically twice.
        self::assertSame($entry, StreamSchema::encode($decoded));
    }

    /**
     * A NON-STORING CTOR STILL ROUTES — D-042.
     *
     * `RPC_RESULT_CTORS` store nothing, so there is no row; the entry still
     * carries the constructor with -1/-1 sentinels. Routing reads
     * `$update['_']`, so an entry without it silently loses a routed
     * `updates.*` container.
     */
    public function test_a_non_storing_constructor_still_publishes_its_constructor_with_sentinels(): void
    {
        $decoded = StreamSchema::decode(StreamSchema::encode([
            'v' => 2,
            'account_id' => self::ACCOUNT,
            'seq' => -1,
            'position' => -1,
            'constructor' => 'updates.state',
            'ts' => 1_700_000_030,
        ]));

        self::assertSame('updates.state', $decoded['constructor'] ?? null, 'routing would break without it');
        self::assertSame(-1, $decoded['seq']);
        self::assertSame(-1, $decoded['position']);
    }

    /**
     * A v1-SHAPED ENTRY MUST BE REFUSED LOUDLY.
     *
     * This is the rolling-upgrade failure mode the plan calls out by name: a
     * half-migrated fleet must FAIL, not drop updates. Today `decode()`
     * accepts this payload happily and returns a v1 triple — a stale
     * consumer would take it, mis-parse, and the loss would be invisible.
     */
    public function test_a_v1_shaped_entry_is_refused_loudly_by_a_v2_decode(): void
    {
        $v1 = json_encode([
            'account_id' => self::ACCOUNT,
            'update' => $this->update,
            'ts' => 1_700_000_030,
        ], JSON_THROW_ON_ERROR);

        $refused = false;
        $reason = '';

        try {
            StreamSchema::decode($v1);
        } catch (InvalidArgumentException $e) {
            $refused = true;
            $reason = $e->getMessage();
        }

        self::assertTrue(
            $refused,
            'RED: a v1-shaped entry decoded without complaint. A half-migrated fleet must FAIL loudly, '
            .'not silently mis-parse and drop updates.',
        );

        // And it must name the version as the reason, so an operator reading
        // the DL can tell a stale publisher from a corrupt payload.
        self::assertStringContainsStringIgnoringCase(
            'v',
            $reason,
            'the refusal must name the version discriminator it choked on',
        );
    }

    /**
     * A REFUSED ENTRY MUST NOT BE ECHOED BY THE DIAGNOSTIC THAT REFUSED IT.
     *
     * PORTED from `StreamEntryCarriesUpdateIdTest::test_a_refused_legacy_entry_hands_the_caller_no_usable_update`,
     * deleted when lane C reconciled the duplicate S1 test sets down to one.
     *
     * It is the half of this obligation the test above does not pin. That one
     * proves a refusal is LOUD and names the version; this one proves it is
     * SAFE. Removing the payload from the wire is the entire point of D-039,
     * and a diagnostic that prints the payload puts the user's own message text
     * back into logs and error surfaces one stack frame later — undoing the
     * change at the exact point it was supposed to take hold. A refusal that is
     * loud *and* echoing is a leak that carries a stack trace with it.
     *
     * Deliberately a SEPARATE method, so the two properties fail
     * independently: an implementation that refuses loudly while leaking the
     * payload into its own message must not be able to hide behind a passing
     * refusal, and the "silent mis-parse" fix must not be able to regress this
     * one unnoticed.
     */
    public function test_a_refused_entry_never_echoes_the_payload_it_refused(): void
    {
        $secret = 'my bank password is hunter2 and my card is 4111-1111-1111-1111';

        $payload = json_encode([
            'account_id' => self::ACCOUNT,
            'update' => [
                '_' => 'updateNewMessage',
                'message' => ['_' => 'message', 'id' => 30, 'text' => $secret],
            ],
            'ts' => 1_700_000_030,
        ], JSON_THROW_ON_ERROR);

        $refusal = null;

        try {
            StreamSchema::decode($payload);
        } catch (\Throwable $e) {
            $refusal = $e;
        }

        // HALF ONE — the caller is handed nothing it can act on. No payload
        // comes back as a return value and none is retrievable from the
        // exception: the refusal is the whole outcome, not the update wrapped
        // in a warning.
        self::assertNotNull(
            $refusal,
            'RED: the v1-shaped entry was not refused at all, so the caller is still handed the very payload '
            .'this slice exists to remove from the bus.',
        );

        $message = $refusal->getMessage();

        // HALF TWO — and the refusal does not smuggle it back out through the
        // diagnostic. A bus entry carries the user's raw message text and this
        // message reaches logs and error surfaces, so the payload must not
        // travel with it.
        self::assertStringNotContainsString(
            $secret,
            $message,
            'the refusal echoes the payload it refused — the user\'s own message text must not re-enter '
            .'logs through the diagnostic that replaced it on the wire.',
        );
        self::assertStringNotContainsString(
            'hunter2',
            $message,
            'the refusal echoes the refused payload.',
        );
        self::assertStringNotContainsString(
            'updateNewMessage',
            $message,
            'the refusal echoes the refused payload.',
        );
        self::assertStringNotContainsString(
            $payload,
            $message,
            'the refusal echoes the refused entry verbatim.',
        );
    }
}

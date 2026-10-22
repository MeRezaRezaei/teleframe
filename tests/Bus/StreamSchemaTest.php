<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Bus;

use InvalidArgumentException;
use JsonException;
use MeRezaRezaei\Teleframe\Bus\StreamSchema;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The bus entry codec, as it stands AFTER the S1 inversion (D-039).
 *
 * ## WHAT CHANGED AND WHY EVERY FIXTURE HERE MOVED
 *
 * A v1 entry was `{account_id, update, ts}` — the ENTIRE decoded update
 * inlined as one string field. A v2 entry is
 * `{v, account_id, seq, position, constructor, ts}`: it NAMES the committed
 * `tf_updates` row and carries no update at all, because the row is the system
 * of record and a payload on the bus is a second copy of truth the database
 * cannot correct.
 *
 * So every fixture below that used to round-trip an `update` array now
 * round-trips a committed key. The codec's obligations are unchanged in kind —
 * stable key order, invalid-UTF-8 substitution, malformed-payload refusal, and
 * the refusal message never echoing the payload — so those assertions were
 * MOVED onto the new shape rather than deleted.
 */
final class StreamSchemaTest extends TestCase
{
    /**
     * A minimal, valid v2 entry, built in CANONICAL key order.
     *
     * The order is not cosmetic: `decode()` returns the canonical order, so a
     * fixture assembled any other way fails `assertSame` on key order rather
     * than on value. Override any key per test.
     */
    private static function entry(array $overrides = []): array
    {
        $values = [
            'v' => StreamSchema::VERSION,
            'account_id' => 42,
            'seq' => 7,
            'position' => 3,
            'constructor' => 'updateNewMessage',
            'ts' => 123,
        ];

        $entry = [];
        foreach (StreamSchema::CANONICAL_KEYS as $key) {
            $entry[$key] = $overrides[$key] ?? $values[$key];
        }

        return $entry;
    }

    public function test_constants_pin_the_wire_names(): void
    {
        self::assertSame('tg:stream:updates', StreamSchema::STREAM);
        self::assertSame('teleclient', StreamSchema::GROUP);
        self::assertSame('ingest-1', StreamSchema::CONSUMER);
        self::assertSame('tg:stream:dead-letter', StreamSchema::DL);
        self::assertSame('tg:bus:reload', StreamSchema::RELOAD_CHANNEL);
        self::assertSame('tg:bus:routes', StreamSchema::ROUTES_KEY);
    }

    public function test_the_entry_version_and_sentinels_are_pinned(): void
    {
        // Bump VERSION only on a deliberate wire change; a consumer speaking a
        // different version must refuse, never guess.
        self::assertSame(2, StreamSchema::VERSION);
        self::assertSame(1, StreamSchema::VERSION_PAYLOAD);

        // -1 and not 0: zero is a legal derived seq/position, -1 is not, so
        // the "nothing was stored" marker is unmistakable in a stream dump.
        self::assertSame(-1, StreamSchema::NO_ROW_SEQ);
        self::assertSame(-1, StreamSchema::NO_ROW_POSITION);
    }

    public function test_the_canonical_key_order_is_declared_once_and_is_the_v2_shape(): void
    {
        self::assertSame(
            ['v', 'account_id', 'seq', 'position', 'constructor', 'ts'],
            StreamSchema::CANONICAL_KEYS,
        );
        self::assertNotContains('update', StreamSchema::CANONICAL_KEYS);
    }

    public function test_encode_uses_stable_key_order_regardless_of_input_order(): void
    {
        $ordered = self::entry();
        $shuffled = array_reverse($ordered, preserve_keys: true);

        $first = StreamSchema::encode($ordered);
        $second = StreamSchema::encode($shuffled);

        self::assertSame($first, $second);
        self::assertSame(
            '{"v":2,"account_id":42,"seq":7,"position":3,"constructor":"updateNewMessage","ts":123}',
            $first,
        );
    }

    public function test_fields_emits_the_canonical_map_as_strings_for_xadd(): void
    {
        $fields = StreamSchema::fields(self::entry());

        // Redis field maps are string=>string; a stream entry's type coercion
        // happens here, once, for every writer on the bus.
        self::assertSame(
            [
                'v' => '2',
                'account_id' => '42',
                'seq' => '7',
                'position' => '3',
                'constructor' => 'updateNewMessage',
                'ts' => '123',
            ],
            $fields,
        );
    }

    public function test_encode_substitutes_invalid_utf8_instead_of_throwing(): void
    {
        // Real Telegram payloads carry binary/invalid UTF-8 (e.g. a constructor
        // name read off a hand-rolled frame). encode() must substitute (U+FFFD)
        // rather than throw — the daemon sink feeds this path and a
        // JsonException would trip the failure ladder.
        $payload = "\xC3\x28"; // invalid UTF-8 sequence
        $entry = self::entry(['constructor' => $payload, 'ts' => 1_758_000_000]);

        $encoded = StreamSchema::encode($entry);

        self::assertStringContainsString("\xEF\xBF\xBD", $encoded); // U+FFFD
        self::assertStringNotContainsString("\xC3\x28", $encoded);
    }

    public function test_roundtrip_preserves_entry(): void
    {
        $entry = self::entry(['account_id' => 77, 'ts' => 1_758_000_000]);

        $decoded = StreamSchema::decode(StreamSchema::encode($entry));

        self::assertSame($entry, $decoded);
    }

    public function test_a_sentinel_entry_round_trips_unchanged(): void
    {
        // A constructor that stored nothing is still published (D-042): routing
        // reads `constructor`, so an entry with no row must survive the codec.
        $entry = self::entry([
            'seq' => StreamSchema::NO_ROW_SEQ,
            'position' => StreamSchema::NO_ROW_POSITION,
            'constructor' => 'rpcResult',
        ]);

        self::assertSame($entry, StreamSchema::decode(StreamSchema::encode($entry)));
    }

    public function test_fields_and_from_fields_are_inverses(): void
    {
        $entry = self::entry();

        self::assertSame($entry, StreamSchema::fromFields(StreamSchema::fields($entry)));
    }

    public function test_from_fields_tolerates_extra_keys_from_a_dead_letter_row(): void
    {
        // reason/error ride on the same field map. An unknown key is not a
        // version problem.
        $fields = StreamSchema::fields(self::entry()) + [
            'reason' => 'ingest-failed account_id=42 seq=7 position=3',
            'error' => 'deadlock detected',
        ];

        self::assertSame(self::entry(), StreamSchema::fromFields($fields));
    }

    /**
     * @return list<array{0: string, 1: class-string<\Throwable>}>
     */
    public static function malformedPayloadProvider(): array
    {
        return [
            'not json at all' => ['this is not json', JsonException::class],
            'json scalar' => ['42', InvalidArgumentException::class],
            'json null' => ['null', InvalidArgumentException::class],
            'json string' => ['"update"', InvalidArgumentException::class],
            'empty object' => ['{}', InvalidArgumentException::class],
            'v2 missing seq' => ['{"v":2,"account_id":1,"position":2,"constructor":"x","ts":2}', InvalidArgumentException::class],
            'v2 missing position' => ['{"v":2,"account_id":1,"seq":2,"constructor":"x","ts":2}', InvalidArgumentException::class],
            'v2 missing constructor' => ['{"v":2,"account_id":1,"seq":2,"position":3,"ts":2}', InvalidArgumentException::class],
            'v2 missing ts' => ['{"v":2,"account_id":1,"seq":2,"position":3,"constructor":"x"}', InvalidArgumentException::class],
            // ABSENCE IS THE DISCRIMINATOR: no `v` at all means the pre-migration
            // payload-carrying version, which this build refuses.
            'v1 payload-carrying entry' => ['{"account_id":1,"update":{"_":"updateNewMessage"},"ts":2}', InvalidArgumentException::class],
            'an explicit future version' => ['{"v":99,"account_id":1,"seq":2,"position":3,"constructor":"x","ts":2}', InvalidArgumentException::class],
        ];
    }

    /**
     * @param  class-string<\Throwable>  $exception
     */
    #[DataProvider('malformedPayloadProvider')]
    public function test_decode_rejects_malformed_payloads(string $payload, string $exception): void
    {
        $this->expectException($exception);

        StreamSchema::decode($payload);
    }

    public function test_encode_refuses_a_payload_carrying_entry(): void
    {
        // Not a coercion. An encoder that quietly DROPPED the payload would hide
        // the very migration this codec exists to make visible, so it refuses.
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('update');

        StreamSchema::encode([
            'account_id' => 1,
            'update' => ['_' => 'updateNewMessage'],
            'ts' => 1,
        ]);
    }

    public function test_version_of_reads_absence_as_the_payload_version(): void
    {
        self::assertSame(StreamSchema::VERSION_PAYLOAD, StreamSchema::versionOf([]));
        self::assertSame(StreamSchema::VERSION_PAYLOAD, StreamSchema::versionOf(['account_id' => 1]));
        self::assertSame(StreamSchema::VERSION_PAYLOAD, StreamSchema::versionOf(['v' => null]));
        self::assertSame(StreamSchema::VERSION_PAYLOAD, StreamSchema::versionOf(['v' => 'not-a-number']));
        self::assertSame(StreamSchema::VERSION, StreamSchema::versionOf(['v' => '2']));
        self::assertSame(StreamSchema::VERSION, StreamSchema::versionOf(['v' => 2]));
    }

    public function test_a_refused_v1_entry_is_named_as_such_not_mis_parsed(): void
    {
        // The message a rolling upgrade produces. It must say which versions are
        // in play and what to do about the entries already on the stream.
        try {
            StreamSchema::decode('{"account_id":1,"update":{"_":"updateNewMessage"},"ts":2}');
            self::fail('expected a refusal');
        } catch (InvalidArgumentException $e) {
            self::assertStringContainsString('v1', $e->getMessage());
            self::assertStringContainsString('v2', $e->getMessage());
        }
    }

    public function test_key_label_is_the_queryable_composite_key(): void
    {
        // D-043: a dead-letter row carries no payload, so this label in `reason`
        // is the only thing that traces a bad entry back to its tf_updates row.
        self::assertSame(
            'account_id=42 seq=7 position=3',
            StreamSchema::keyLabel(self::entry()),
        );

        // A malformed map must still produce something an operator can act on
        // rather than throwing inside the dead-letter path itself.
        self::assertSame(
            'account_id=5 seq=-1 position=-1',
            StreamSchema::keyLabel(['account_id' => 5]),
        );
    }

    /**
     * String concatenation in keyLabel must be exact — Concat/ConcatOperandRemoval
     * mutators would change the format if not caught by these assertions.
     */
    public function test_key_label_concatenation_format_is_exact(): void
    {
        // Test the exact format with various values
        self::assertSame(
            'account_id=0 seq=0 position=0',
            StreamSchema::keyLabel(['account_id' => 0, 'seq' => 0, 'position' => 0]),
        );
        self::assertSame(
            'account_id=-1 seq=-1 position=-1',
            StreamSchema::keyLabel(['account_id' => -1, 'seq' => -1, 'position' => -1]),
        );
        self::assertSame(
            'account_id=999 seq=888 position=777',
            StreamSchema::keyLabel(['account_id' => 999, 'seq' => 888, 'position' => 777]),
        );

        // Test with missing keys (uses NO_ROW_SEQ/NO_ROW_POSITION = -1 as defaults)
        self::assertSame(
            'account_id=5 seq=-1 position=-1',
            StreamSchema::keyLabel(['account_id' => 5]),
        );
        self::assertSame(
            'account_id=0 seq=-1 position=-1',
            StreamSchema::keyLabel([]),
        );
    }

    /**
     * Type casting in canonicalise must be exact — CastInt/CastString mutators
     * would change the behavior if not caught by these assertions.
     */
    public function test_canonicalise_type_casting_is_exact(): void
    {
        // Test that numeric strings are cast to integers
        $entry = [
            'v' => '2', // string input
            'account_id' => '42', // string input
            'seq' => '7', // string input
            'position' => '3', // string input
            'constructor' => 'updateNewMessage',
            'ts' => '123', // string input
        ];

        $canonical = StreamSchema::fromFields($entry);

        self::assertSame(2, $canonical['v']);
        self::assertIsInt($canonical['v']);
        self::assertSame(42, $canonical['account_id']);
        self::assertIsInt($canonical['account_id']);
        self::assertSame(7, $canonical['seq']);
        self::assertIsInt($canonical['seq']);
        self::assertSame(3, $canonical['position']);
        self::assertIsInt($canonical['position']);
        self::assertSame('updateNewMessage', $canonical['constructor']);
        self::assertIsString($canonical['constructor']);
        self::assertSame(123, $canonical['ts']);
        self::assertIsInt($canonical['ts']);
    }

    /**
     * versionOf must correctly handle various input types for the version field.
     * CastInt/CastString mutators would break this if not caught by these assertions.
     */
    public function test_version_of_handles_various_types(): void
    {
        self::assertSame(StreamSchema::VERSION_PAYLOAD, StreamSchema::versionOf([]));
        self::assertSame(StreamSchema::VERSION_PAYLOAD, StreamSchema::versionOf(['v' => null]));
        self::assertSame(StreamSchema::VERSION_PAYLOAD, StreamSchema::versionOf(['v' => '']));
        self::assertSame(StreamSchema::VERSION_PAYLOAD, StreamSchema::versionOf(['v' => 'not-a-number']));
        self::assertSame(StreamSchema::VERSION_PAYLOAD, StreamSchema::versionOf(['v' => '1.5']));

        self::assertSame(StreamSchema::VERSION, StreamSchema::versionOf(['v' => '2']));
        self::assertSame(StreamSchema::VERSION, StreamSchema::versionOf(['v' => 2]));
        self::assertSame(StreamSchema::VERSION, StreamSchema::versionOf(['v' => '2.0']));

        // Edge case: '0' is numeric but (int)'0' === 0, which is not VERSION(2) nor VERSION_PAYLOAD(1)
        // The method returns the raw integer value for any numeric input
        self::assertSame(0, StreamSchema::versionOf(['v' => '0']));
        self::assertSame(0, StreamSchema::versionOf(['v' => 0]));

        // Negative numbers are numeric but not VERSION
        self::assertSame(-1, StreamSchema::versionOf(['v' => '-1']));
        self::assertSame(-1, StreamSchema::versionOf(['v' => -1]));
    }

    /**
     * A malformed entry error must NEVER carry the entry.
     *
     * A bus entry IS the user's message (text, entities, media metadata), and
     * this message reaches a log or an error surface the moment a bad entry
     * hits the consumer. The type and the size are enough to diagnose it.
     */
    public function test_a_rejected_entry_never_echoes_the_payload(): void
    {
        $secret = 'my bank password is hunter2 and my card is 4111-1111-1111-1111';

        foreach (['"'.$secret.'"', '42', 'null', $secret] as $payload) {
            try {
                StreamSchema::decode($payload);
                self::fail('expected a rejection for '.substr($payload, 0, 20));
            } catch (\Throwable $e) {
                self::assertStringNotContainsString($secret, $e->getMessage());
                self::assertStringNotContainsString('hunter2', $e->getMessage());
                self::assertStringNotContainsString('4111', $e->getMessage());
            }
        }
    }

    public function test_a_refused_v1_entry_does_not_echo_the_inlined_update(): void
    {
        // The refusal deliberately does NOT echo what it refused: a v1 entry
        // carries the user's own message text, and this change exists so that
        // text stops travelling on the bus.
        $payload = json_encode([
            'account_id' => 1,
            'update' => ['_' => 'updateNewMessage', 'message' => ['text' => 'my password is hunter2']],
            'ts' => 2,
        ], JSON_THROW_ON_ERROR);

        try {
            StreamSchema::decode($payload);
            self::fail('expected a refusal');
        } catch (InvalidArgumentException $e) {
            self::assertStringNotContainsString('hunter2', $e->getMessage());
        }
    }

    public function test_a_rejected_entry_still_names_the_type_and_the_size(): void
    {
        try {
            StreamSchema::decode('"a-string"');
            self::fail('expected a rejection');
        } catch (\Throwable $e) {
            self::assertStringContainsString('string', $e->getMessage());
            self::assertStringContainsString('10 bytes', $e->getMessage()); // the raw payload '"a-string"' is 10 bytes
        }
    }

    /**
     * Additional string concatenation tests for fields() — Concat/ConcatOperandRemoval
     * mutators would change the string building if not caught.
     */
    public function test_fields_string_concatenation_is_exact(): void
    {
        $entry = [
            'v' => 2,
            'account_id' => 12345,
            'seq' => 67890,
            'position' => 54321,
            'constructor' => 'updateNewMessage',
            'ts' => 1758000000,
        ];

        $fields = StreamSchema::fields($entry);

        // Every field must be a string — explicit concatenation in canonicalise
        self::assertSame('2', $fields['v']);
        self::assertSame('12345', $fields['account_id']);
        self::assertSame('67890', $fields['seq']);
        self::assertSame('54321', $fields['position']);
        self::assertSame('updateNewMessage', $fields['constructor']);
        self::assertSame('1758000000', $fields['ts']);

        // Test with zero values (edge case for string conversion)
        // Note: v must be 2 (VERSION) for a valid v2 entry, canonicalise enforces this
        $zeroEntry = [
            'v' => 2, // canonicalise enforces VERSION=2 for v2 entries
            'account_id' => 0,
            'seq' => 0,
            'position' => 0,
            'constructor' => 'test',
            'ts' => 0,
        ];
        $zeroFields = StreamSchema::fields($zeroEntry);
        self::assertSame('2', $zeroFields['v']); // v is always 2 for valid v2 entries
        self::assertSame('0', $zeroFields['account_id']);
        self::assertSame('0', $zeroFields['seq']);
        self::assertSame('0', $zeroFields['position']);
        self::assertSame('test', $zeroFields['constructor']);
        self::assertSame('0', $zeroFields['ts']);
    }

    /**
     * encode() round-trip with exact string concatenation — Concat/ConcatOperandRemoval
     * mutators would break the JSON output format if not caught.
     */
    public function test_encode_concatenation_format_is_exact(): void
    {
        $entry = [
            'v' => 2,
            'account_id' => 42,
            'seq' => 7,
            'position' => 3,
            'constructor' => 'updateNewMessage',
            'ts' => 123,
        ];

        $encoded = StreamSchema::encode($entry);
        $expected = '{"v":2,"account_id":42,"seq":7,"position":3,"constructor":"updateNewMessage","ts":123}';
        self::assertSame($expected, $encoded);

        // Test with special characters in constructor (escaped in JSON)
        $specialEntry = [
            'v' => 2,
            'account_id' => 1,
            'seq' => 1,
            'position' => 1,
            'constructor' => 'con/structor"with\'special\\chars',
            'ts' => 456,
        ];
        $specialEncoded = StreamSchema::encode($specialEntry);
        // Verify it's valid JSON and contains the escaped constructor
        $decoded = json_decode($specialEncoded, true, 512, JSON_THROW_ON_ERROR);
        self::assertSame($specialEntry['constructor'], $decoded['constructor']);
    }

    /**
     * Boolean logic in versionOf — LogicalAnd/LogicalOr mutators
     * would change the validation logic if not caught.
     */
    public function test_version_of_boolean_logic_is_exact(): void
    {
        // versionOf uses: ($raw === null || ! is_numeric($raw)) — this is OR logic
        // Test each condition independently

        // Case 1: $raw === null → true (returns VERSION_PAYLOAD)
        self::assertSame(StreamSchema::VERSION_PAYLOAD, StreamSchema::versionOf(['v' => null]));

        // Case 2: $raw is not numeric → true (returns VERSION_PAYLOAD)
        self::assertSame(StreamSchema::VERSION_PAYLOAD, StreamSchema::versionOf(['v' => 'abc']));
        self::assertSame(StreamSchema::VERSION_PAYLOAD, StreamSchema::versionOf(['v' => '']));

        // Case 3: $raw is numeric but not VERSION → false AND false → returns raw int
        // This exercises the AND branch: is_numeric=true AND value!=VERSION
        self::assertSame(0, StreamSchema::versionOf(['v' => '0']));
        self::assertSame(-1, StreamSchema::versionOf(['v' => '-1']));
        self::assertSame(99, StreamSchema::versionOf(['v' => '99']));

        // Case 4: $raw === VERSION → false (first condition) OR false (second) → returns VERSION
        self::assertSame(StreamSchema::VERSION, StreamSchema::versionOf(['v' => '2']));
        self::assertSame(StreamSchema::VERSION, StreamSchema::versionOf(['v' => 2]));

        // Case 5: absent key → null → true (returns VERSION_PAYLOAD)
        self::assertSame(StreamSchema::VERSION_PAYLOAD, StreamSchema::versionOf([]));
    }

    /**
     * Method call verification for fromFields -> canonicalise chain — MethodCallRemoval
     * mutators would remove the method call if not caught.
     */
    public function test_from_fields_calls_canonicalise_through_version_of(): void
    {
        // fromFields calls canonicalise which calls versionOf internally
        // Test that the chain works correctly by using a valid v2 entry
        $fields = [
            'v' => '2',
            'account_id' => '42',
            'seq' => '7',
            'position' => '3',
            'constructor' => 'updateNewMessage',
            'ts' => '123',
        ];

        $result = StreamSchema::fromFields($fields);

        // If canonicalise was removed, this would fail or return wrong shape
        self::assertSame(2, $result['v']);
        self::assertSame(42, $result['account_id']);
        self::assertSame(7, $result['seq']);
        self::assertSame(3, $result['position']);
        self::assertSame('updateNewMessage', $result['constructor']);
        self::assertSame(123, $result['ts']);

        // If versionOf was removed, v2 entry would be rejected
        self::assertSame(2, $result['v']);
    }

    public function test_encode_refusal_message_is_exact(): void
    {
        $entry = self::entry();
        $entry['update'] = ['_' => 'updateNewMessage'];

        try {
            StreamSchema::encode($entry);
            self::fail('a payload-carrying entry was encoded');
        } catch (InvalidArgumentException $e) {
            self::assertSame(
                'Bus entry carries the v1 "update" payload key; this encoder only emits v2 '
                .'committed-key entries. Encode the row identity instead.',
                $e->getMessage(),
            );
        }
    }

    public function test_encode_keeps_slashes_unicode_and_substitutes_invalid_bytes(): void
    {
        $encoded = StreamSchema::encode(self::entry([
            'constructor' => "update/Chat \u{00e9} bad:\xC3\x28",
        ]));

        self::assertStringContainsString('update/Chat', $encoded);
        self::assertStringNotContainsString('update\\/Chat', $encoded);
        self::assertStringContainsString("\u{00e9}", $encoded);
        self::assertStringNotContainsString('\\u00e9', $encoded);
        self::assertStringContainsString("\u{FFFD}", $encoded);
    }

    public function test_decode_accepts_a_payload_one_level_under_the_depth_limit(): void
    {
        $payload = str_repeat('[', 511).'0'.str_repeat(']', 511);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Refusing a v1 bus entry');

        StreamSchema::decode($payload);
    }

    public function test_decode_refuses_a_payload_at_the_depth_limit(): void
    {
        $payload = str_repeat('[', 512).'0'.str_repeat(']', 512);

        $this->expectException(JsonException::class);

        StreamSchema::decode($payload);
    }

    public function test_decode_legacy_accepts_a_payload_one_level_under_the_depth_limit(): void
    {
        $payload = str_repeat('[', 511).'0'.str_repeat(']', 511);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Legacy bus entry is missing the "account_id" key.');

        StreamSchema::decodeLegacy($payload);
    }

    public function test_decode_legacy_refuses_a_payload_at_the_depth_limit(): void
    {
        $payload = str_repeat('[', 512).'0'.str_repeat(']', 512);

        $this->expectException(JsonException::class);

        StreamSchema::decodeLegacy($payload);
    }

    public function test_decode_legacy_requires_every_legacy_key(): void
    {
        $complete = ['account_id' => 1, 'update' => ['_' => 'x'], 'ts' => 2];

        foreach (['account_id', 'update', 'ts'] as $key) {
            $payload = $complete;
            unset($payload[$key]);

            try {
                StreamSchema::decodeLegacy((string) json_encode($payload));
                self::fail("a legacy entry without \"{$key}\" was accepted");
            } catch (InvalidArgumentException $e) {
                self::assertSame(
                    "Legacy bus entry is missing the \"{$key}\" key.",
                    $e->getMessage(),
                );
            }
        }
    }

    public function test_decode_legacy_casts_account_id_and_ts_to_int(): void
    {
        $entry = StreamSchema::decodeLegacy((string) json_encode([
            'account_id' => '42',
            'update' => ['_' => 'updateNewMessage'],
            'ts' => '123',
        ]));

        self::assertSame(42, $entry['account_id']);
        self::assertSame(123, $entry['ts']);
    }

    public function test_canonicalise_refusal_message_is_exact(): void
    {
        $payload = (string) json_encode([
            'account_id' => 1,
            'update' => ['_' => 'updateNewMessage'],
            'ts' => 2,
        ]);

        try {
            StreamSchema::decode($payload);
            self::fail('a v1 entry was decoded into a v2 shape');
        } catch (InvalidArgumentException $e) {
            self::assertSame(
                'Refusing a v1 bus entry: this build speaks v2 (committed-key entries carrying '
                .'v, account_id, seq, position, constructor, ts). A v1 entry carries the update '
                .'payload inline and is never mis-parsed into one. Drain the v1 entries already '
                .'on the stream before upgrading the publisher.',
                $e->getMessage(),
            );
        }
    }

    public function test_canonicalise_requires_every_canonical_key(): void
    {
        $complete = self::entry();

        foreach (['account_id', 'seq', 'position', 'constructor', 'ts'] as $key) {
            $fields = $complete;
            unset($fields[$key]);

            try {
                StreamSchema::fromFields($fields);
                self::fail("a v2 entry without \"{$key}\" was accepted");
            } catch (InvalidArgumentException $e) {
                self::assertSame(
                    "Bus entry is missing the \"{$key}\" key.",
                    $e->getMessage(),
                );
            }
        }
    }

    public function test_canonicalise_casts_constructor_to_string(): void
    {
        $result = StreamSchema::fromFields(self::entry([
            'constructor' => 123,
        ]));

        self::assertSame('123', $result['constructor']);
    }
}

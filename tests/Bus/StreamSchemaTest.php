<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Bus;

use InvalidArgumentException;
use MeRezaRezaei\Teleframe\Bus\StreamSchema;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class StreamSchemaTest extends TestCase
{
    public function test_constants_pin_the_wire_names(): void
    {
        self::assertSame('tg:stream:updates', StreamSchema::STREAM);
        self::assertSame('teleclient', StreamSchema::GROUP);
        self::assertSame('ingest-1', StreamSchema::CONSUMER);
        self::assertSame('tg:stream:dead-letter', StreamSchema::DL);
        self::assertSame('tg:bus:reload', StreamSchema::RELOAD_CHANNEL);
        self::assertSame('tg:bus:routes', StreamSchema::ROUTES_KEY);
    }

    public function test_encode_uses_stable_key_order_regardless_of_input_order(): void
    {
        $first = StreamSchema::encode(['update' => ['_' => 'updateNewMessage'], 'ts' => 123, 'account_id' => 42]);
        $second = StreamSchema::encode(['ts' => 123, 'account_id' => 42, 'update' => ['_' => 'updateNewMessage']]);

        self::assertSame($first, $second);
        self::assertStringStartsWith('{"account_id":42,"update":', $first);
    }

    public function test_encode_substitutes_invalid_utf8_instead_of_throwing(): void
    {
        // Real Telegram payloads carry binary/invalid UTF-8 (e.g. message text).
        // encode() must substitute (U+FFFD) rather than throw — the daemon sink
        // feeds this path and a JsonException would trip the failure ladder.
        $payload = "\xC3\x28"; // invalid UTF-8 sequence
        $entry = [
            'account_id' => 1,
            'update' => ['_' => 'updateNewMessage', 'message' => ['text' => $payload]],
            'ts' => 1_758_000_000,
        ];

        $encoded = StreamSchema::encode($entry);

        self::assertStringContainsString("\xEF\xBF\xBD", $encoded); // U+FFFD
        self::assertStringNotContainsString("\xC3\x28", $encoded);
    }

    public function test_roundtrip_preserves_entry(): void
    {
        $entry = [
            'account_id' => 77,
            'update' => ['_' => 'updateNewMessage', 'message' => ['id' => 5, '_' => 'message']],
            'ts' => 1_758_000_000,
        ];

        $decoded = StreamSchema::decode(StreamSchema::encode($entry));

        self::assertSame($entry, $decoded);
    }

    /**
     * @return list<array{0: string, 1: class-string<\Throwable>}>
     */
    public static function malformedPayloadProvider(): array
    {
        return [
            'not json at all' => ['this is not json', \JsonException::class],
            'json scalar' => ['42', InvalidArgumentException::class],
            'json null' => ['null', InvalidArgumentException::class],
            'json string' => ['"update"', InvalidArgumentException::class],
            'empty object' => ['{}', InvalidArgumentException::class],
            'missing update key' => ['{"account_id":1,"ts":2}', InvalidArgumentException::class],
            'update not an array' => ['{"account_id":1,"update":"nope","ts":2}', InvalidArgumentException::class],
            'missing ts key' => ['{"account_id":1,"update":{}}', InvalidArgumentException::class],
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
}

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
     * @param class-string<\Throwable> $exception
     */
    #[DataProvider('malformedPayloadProvider')]
    public function test_decode_rejects_malformed_payloads(string $payload, string $exception): void
    {
        $this->expectException($exception);

        StreamSchema::decode($payload);
    }
}

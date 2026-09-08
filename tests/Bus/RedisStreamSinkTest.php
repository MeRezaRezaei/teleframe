<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Bus;

use MeRezaRezaei\Teleframe\Bus\RedisStreamSink;
use MeRezaRezaei\Teleframe\Bus\StreamSchema;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayRedis;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RedisStreamSinkTest extends TestCase
{
    public function test_handle_appends_entry_to_the_updates_stream_and_returns_true(): void
    {
        $redis = new ArrayRedis();
        $sink = new RedisStreamSink($redis, 42);
        $before = time();

        $update = ['_' => 'updateNewMessage', 'message' => ['_' => 'message', 'id' => 7]];
        $result = $sink->handle($update, '42');

        $after = time();

        self::assertTrue($result);

        $entries = $redis->streamEntries(StreamSchema::STREAM);
        self::assertCount(1, $entries);

        [$id, $fields] = $entries[0];
        self::assertNotSame('', $id);
        self::assertSame('42', $fields['account_id']);
        self::assertSame($update, StreamSchema::decode($fields['update'])['update']);
        $ts = (int) $fields['ts'];
        self::assertGreaterThanOrEqual($before, $ts);
        self::assertLessThanOrEqual($after, $ts);

        $entry = StreamSchema::decode($fields['update']);
        self::assertSame(42, $entry['account_id']);
        self::assertSame($ts, $entry['ts']);
    }

    public function test_handle_returns_true_even_when_source_is_omitted(): void
    {
        $redis = new ArrayRedis();
        $sink = new RedisStreamSink($redis, 1);

        self::assertTrue($sink->handle(['_' => 'updateNewChannelMessage']));
        self::assertCount(1, $redis->streamEntries(StreamSchema::STREAM));
    }

    /**
     * @return list<array{0: string|null, 1: int}>
     */
    public static function accountIdProvider(): array
    {
        return [
            'numeric user source' => ['501558149', 501558149],
            'bot token id:hash' => ['891234567:AAH4t9qM3N2xKpL0vBcXyZ1234567890ab', 891234567],
            'null source' => [null, 0],
            'empty source' => ['', 0],
            'empty bot id before colon' => [':AAH4t9qM3N2xKpL0vBcXyZ', 0],
            'id hash with no numeric id' => ['token:hash', 0],
        ];
    }

    #[DataProvider('accountIdProvider')]
    public function test_account_id_for_derives_tenancy_from_source(?string $source, int $expected): void
    {
        self::assertSame($expected, RedisStreamSink::accountIdFor($source));
    }

    public function test_two_accounts_write_distinct_entries_to_one_stream(): void
    {
        $redis = new ArrayRedis();
        (new RedisStreamSink($redis, 10))->handle(['_' => 'a'], '10');
        (new RedisStreamSink($redis, 20))->handle(['_' => 'b'], '20');

        $entries = $redis->streamEntries(StreamSchema::STREAM);

        self::assertCount(2, $entries);
        self::assertSame('10', $entries[0][1]['account_id']);
        self::assertSame('20', $entries[1][1]['account_id']);
    }
}

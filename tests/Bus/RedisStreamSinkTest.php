<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Bus;

use MeRezaRezaei\Teleframe\Bus\RedisConnectionContract;
use MeRezaRezaei\Teleframe\Bus\RedisStreamSink;
use MeRezaRezaei\Teleframe\Bus\StreamSchema;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayRedis;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RedisStreamSinkTest extends TestCase
{
    public function test_handle_appends_entry_to_the_updates_stream_and_returns_true(): void
    {
        $redis = new ArrayRedis;
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

        // THE ENTRY IS THE FIELD MAP (D-039). There is no inner `update` blob
        // to decode — the entry names the committed row and stops there.
        self::assertArrayNotHasKey('update', $fields);

        $entry = StreamSchema::fromFields($fields);
        self::assertSame(StreamSchema::VERSION, $entry['v']);
        self::assertSame(42, $entry['account_id']);

        $ts = (int) $fields['ts'];
        self::assertGreaterThanOrEqual($before, $ts);
        self::assertLessThanOrEqual($after, $ts);
        self::assertSame($ts, $entry['ts']);

        // The constructor IS published (D-042): routing matches on it, so an
        // entry with no stored row is still routable rather than dropped.
        self::assertSame('updateNewMessage', $entry['constructor']);

        // This sink was constructed with no committer, so nothing was written
        // and the key is the sentinel — NOT zero, which is a legal value.
        self::assertSame(StreamSchema::NO_ROW_SEQ, $entry['seq']);
        self::assertSame(StreamSchema::NO_ROW_POSITION, $entry['position']);

        // The payload itself is gone, body and all.
        self::assertStringNotContainsString('message', json_encode($fields, JSON_THROW_ON_ERROR));
    }

    public function test_an_update_with_no_constructor_is_published_rather_than_refused(): void
    {
        $redis = new ArrayRedis;

        // `UpdateIngestor::ingest()` throws on a payload with no constructor
        // node, and this sink has no committer anyway — but the consumer must
        // still SEE the entry, because the dead-letter path is built on exactly
        // this payload arriving and failing to be ingestible.
        self::assertTrue((new RedisStreamSink($redis, 8))->handle([], '8'));

        $entry = StreamSchema::fromFields($redis->streamEntries(StreamSchema::STREAM)[0][1]);

        self::assertSame(8, $entry['account_id']);
        self::assertSame('', $entry['constructor']);
        self::assertSame(StreamSchema::NO_ROW_SEQ, $entry['seq']);
        self::assertSame(StreamSchema::NO_ROW_POSITION, $entry['position']);
    }

    public function test_handle_returns_true_even_when_source_is_omitted(): void
    {
        $redis = new ArrayRedis;
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

    /**
     * accountIdFor string concatenation and type casting — Concat/ConcatOperandRemoval
     * and CastInt/CastString mutators would change the behavior if not caught.
     */
    public function test_account_id_for_string_operations_are_exact(): void
    {
        // Test exact string extraction from bot token (substr + strpos concatenation)
        self::assertSame(123456789, RedisStreamSink::accountIdFor('123456789:ABCDEFGHIJKLMNOP'));
        self::assertSame(987654321, RedisStreamSink::accountIdFor('987654321:xyz'));

        // Test with longer hash portions
        self::assertSame(111111111, RedisStreamSink::accountIdFor('111111111:'.str_repeat('a', 100)));

        // Test type casting exactness - ctype_digit + (int) casting
        self::assertSame(42, RedisStreamSink::accountIdFor('42'));
        self::assertSame(0, RedisStreamSink::accountIdFor('0'));
        self::assertSame(999999999, RedisStreamSink::accountIdFor('999999999'));

        // Test boundary: leading zeros in numeric string
        self::assertSame(42, RedisStreamSink::accountIdFor('0042'));
        self::assertSame(0, RedisStreamSink::accountIdFor('000'));

        // Test non-numeric prefix returns 0 (substr extraction + ctype_digit check)
        self::assertSame(0, RedisStreamSink::accountIdFor('abc:def'));
        self::assertSame(0, RedisStreamSink::accountIdFor('abc123:def'));
        self::assertSame(0, RedisStreamSink::accountIdFor('-123:def'));
    }

    /**
     * accountIdFor edge cases for CastInt mutator on strpos result.
     */
    public function test_account_id_for_strpos_casting_edge_cases(): void
    {
        // strpos returns 0 when colon is at position 0 - (int)0 === 0
        self::assertSame(0, RedisStreamSink::accountIdFor(':hash'));

        // strpos returns false when not found - (int)false === 0
        self::assertSame(0, RedisStreamSink::accountIdFor('no-colon-here'));

        // Multiple colons - only first matters (strpos finds first occurrence)
        self::assertSame(123, RedisStreamSink::accountIdFor('123:456:789'));
    }

    public function test_two_accounts_write_distinct_entries_to_one_stream(): void
    {
        $redis = new ArrayRedis;
        (new RedisStreamSink($redis, 10))->handle(['_' => 'a'], '10');
        (new RedisStreamSink($redis, 20))->handle(['_' => 'b'], '20');

        $entries = $redis->streamEntries(StreamSchema::STREAM);

        self::assertCount(2, $entries);
        self::assertSame('10', $entries[0][1]['account_id']);
        self::assertSame('20', $entries[1][1]['account_id']);
    }

    /**
     * CastString kill (line 127): a non-string `_` value must be coerced to a
     * string before the constructor-less branch is taken, so the update is
     * published with sentinels instead of being handed (un-cast) to the
     * committer.
     */
    public function test_handle_committed_casts_non_string_constructor_to_string(): void
    {
        $redis = new ArrayRedis;
        $sink = new RedisStreamSink($redis, 9);
        $committer = new UpdateIngestor;

        $result = $sink->handleCommitted(['_' => false], '9', $committer, null);

        self::assertTrue($result);

        $entries = $redis->streamEntries(StreamSchema::STREAM);
        self::assertCount(1, $entries);

        $entry = StreamSchema::fromFields($entries[0][1]);
        self::assertSame('', $entry['constructor']);
        self::assertSame(StreamSchema::NO_ROW_SEQ, $entry['seq']);
        self::assertSame(StreamSchema::NO_ROW_POSITION, $entry['position']);
    }

    /**
     * GreaterThan kill (line 142): maxlen of 0 means "no maxlen option",
     * not "trim to zero".
     */
    public function test_maxlen_zero_omits_the_maxlen_option(): void
    {
        $recorded = null;
        $redis = $this->createStub(RedisConnectionContract::class);
        $redis->method('xadd')->willReturnCallback(function (string $stream, string $id, array $fields, array $options) use (&$recorded): string {
            $recorded = $options;

            return '1-0';
        });

        $sink = new RedisStreamSink($redis, 5, 0);
        self::assertTrue($sink->handle(['_' => 'updateNewMessage']));

        self::assertSame([], $recorded);
    }
}

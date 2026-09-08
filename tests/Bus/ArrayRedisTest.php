<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Bus;

use MeRezaRezaei\Teleframe\Tests\Support\ArrayRedis;
use PHPUnit\Framework\TestCase;

final class ArrayRedisTest extends TestCase
{
    public function test_xadd_auto_id_is_monotonic_and_fields_are_stored(): void
    {
        $redis = new ArrayRedis();

        $first = $redis->xadd('s', '*', ['a' => '1']);
        $second = $redis->xadd('s', '*', ['a' => '2']);
        $explicit = $redis->xadd('s', '9999999999999-0', ['a' => '3']);

        self::assertSame(['a' => '1'], $redis->streamEntries('s')[0][1]);
        self::assertTrue($second > $first, 'auto ids must be strictly increasing');
        self::assertSame('9999999999999-0', $explicit);
        self::assertCount(3, $redis->streamEntries('s'));
    }

    public function test_xreadgroup_new_entries_then_pending_then_ack(): void
    {
        $redis = new ArrayRedis();
        $ids = [
            $redis->xadd('s', '*', ['n' => '1']),
            $redis->xadd('s', '*', ['n' => '2']),
        ];

        $first = $redis->xreadgroup('g', 'c1', ['s' => 1], '>');
        self::assertSame(['s' => [$ids[0] => ['n' => '1']]], $first);

        $second = $redis->xreadgroup('g', 'c1', ['s' => 10], '>');
        self::assertSame(['s' => [$ids[1] => ['n' => '2']]], $second);

        self::assertSame([], $redis->xreadgroup('g', 'c1', ['s' => 10], '>'));

        $pending = $redis->xreadgroup('g', 'c1', ['s' => 10], '0');
        self::assertSame(['s' => [$ids[0] => ['n' => '1'], $ids[1] => ['n' => '2']]], $pending);

        self::assertSame(2, $redis->xack('s', 'g', $ids));
        self::assertSame(0, $redis->xack('s', 'g', $ids));
        self::assertSame([], $redis->xreadgroup('g', 'c1', ['s' => 10], '0'));
    }

    public function test_acked_entries_are_not_redelivered_via_new_entries(): void
    {
        $redis = new ArrayRedis();
        $ids = [
            $redis->xadd('s', '*', ['n' => '1']),
            $redis->xadd('s', '*', ['n' => '2']),
        ];

        $redis->xreadgroup('g', 'c1', ['s' => 2], '>');
        $redis->xack('s', 'g', $ids);

        // Real group semantics: `>` follows the group last-delivered-id,
        // not the pending list — acked history stays consumed.
        self::assertSame([], $redis->xreadgroup('g', 'c1', ['s' => 10], '>'));

        $third = $redis->xadd('s', '*', ['n' => '3']);
        self::assertSame(
            ['s' => [$third => ['n' => '3']]],
            $redis->xreadgroup('g', 'c1', ['s' => 10], '>'),
        );
        self::assertSame([], $redis->xreadgroup('g', 'c2', ['s' => 10], '0'), 'other groups unaffected');
    }

    public function test_hashes_roundtrip(): void
    {
        $redis = new ArrayRedis();

        self::assertSame(1, $redis->hset('h', 'k', 'v1'));
        self::assertSame(0, $redis->hset('h', 'k', 'v2'));
        self::assertSame(['k' => 'v2'], $redis->hgetall('h'));
    }

    public function test_publish_echoes_to_subscribers_and_reports_receivers(): void
    {
        $redis = new ArrayRedis();
        $received = [];
        $redis->subscribe('chan', function (string $channel, string $payload) use (&$received): void {
            $received[] = [$channel, $payload];
        });

        $count = $redis->publish('chan', 'hello');
        $missed = $redis->publish('other', 'ignored');

        self::assertSame(1, $count);
        self::assertSame(0, $missed);
        self::assertSame([['chan', 'hello']], $received);
    }

    public function test_del_and_llen(): void
    {
        $redis = new ArrayRedis();
        $redis->xadd('s', '*', ['a' => '1']);
        $redis->xadd('s', '*', ['a' => '2']);
        $redis->hset('h', 'k', 'v');

        self::assertSame(2, $redis->llen('s'));
        self::assertSame(1, $redis->llen('h'));
        self::assertSame(0, $redis->llen('missing'));

        self::assertSame(2, $redis->del('s', 'h', 'missing'));
        self::assertSame([], $redis->streamEntries('s'));
        self::assertSame([], $redis->hgetall('h'));
    }
}

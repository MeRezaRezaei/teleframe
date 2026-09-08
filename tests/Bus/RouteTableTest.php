<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Bus;

use MeRezaRezaei\Teleframe\Bus\RouteTable;
use MeRezaRezaei\Teleframe\Bus\StreamSchema;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayRedis;
use PHPUnit\Framework\TestCase;

/**
 * Plan Task 2: the hash-backed route map — plain string prefix matching
 * only (trailing-`*`), first matching pattern wins, empty table is the
 * default ingest path.
 */
final class RouteTableTest extends TestCase
{
    public function test_empty_table_matches_null_for_any_update(): void
    {
        $table = new RouteTable(new ArrayRedis());

        self::assertSame([], $table->all());
        self::assertNull($table->match(['_' => 'updateNewMessage']));
        self::assertNull($table->match(['message' => ['id' => 1]]));
        self::assertNull($table->match([]));
    }

    public function test_set_writes_patterns_and_all_roundtrips_in_order(): void
    {
        $redis = new ArrayRedis();
        $table = new RouteTable($redis);

        $table->set('updateNewMessage*', 'tg:target:one');
        $table->set('updateUserStatus*', 'tg:target:status');

        self::assertSame([
            'updateNewMessage*' => 'tg:target:one',
            'updateUserStatus*' => 'tg:target:status',
        ], $table->all());
        self::assertSame([
            'updateNewMessage*' => 'tg:target:one',
            'updateUserStatus*' => 'tg:target:status',
        ], $redis->hashes[StreamSchema::ROUTES_KEY]);
    }

    public function test_set_overwrites_the_target_of_an_existing_pattern(): void
    {
        $table = new RouteTable(new ArrayRedis());

        $table->set('updateNewMessage*', 'tg:target:one');
        $table->set('updateNewMessage*', 'tg:target:two');

        self::assertSame(['updateNewMessage*' => 'tg:target:two'], $table->all());
    }

    public function test_trailing_star_pattern_matches_by_prefix(): void
    {
        $table = new RouteTable(new ArrayRedis());
        $table->set('updateNewMessage*', 'tg:target:one');

        self::assertSame('tg:target:one', $table->match(['_' => 'updateNewMessage']));
        self::assertSame('tg:target:one', $table->match(['_' => 'updateNewMessage', 'message' => ['id' => 5]]));
        self::assertNull($table->match(['_' => 'updateNewChannelMessage']));
        self::assertNull($table->match(['_' => 'update']));
    }

    public function test_exact_pattern_without_star_also_matches_as_prefix(): void
    {
        $table = new RouteTable(new ArrayRedis());
        $table->set('user', 'tg:target:users');

        self::assertSame('tg:target:users', $table->match(['_' => 'user']));
        self::assertSame('tg:target:users', $table->match(['_' => 'userFull']), 'a bare pattern is still a prefix');
        self::assertNull($table->match(['_' => 'updateNewMessage']));
    }

    public function test_first_matching_pattern_in_hash_order_wins(): void
    {
        $table = new RouteTable(new ArrayRedis());

        $table->set('update*', 'tg:target:broad');
        $table->set('updateNewMessage*', 'tg:target:narrow');

        self::assertSame('tg:target:broad', $table->match(['_' => 'updateNewMessage']));
    }

    public function test_bare_star_is_a_catch_all_including_updates_without_constructor(): void
    {
        $table = new RouteTable(new ArrayRedis());

        $table->set('updateNewMessage*', 'tg:target:one');
        self::assertNull($table->match(['message' => ['id' => 1]]));

        $table->set('*', 'tg:target:all');

        self::assertSame('tg:target:all', $table->match(['message' => ['id' => 1]]));
        self::assertSame('tg:target:one', $table->match(['_' => 'updateNewMessage']), 'earlier pattern still first');
    }
}

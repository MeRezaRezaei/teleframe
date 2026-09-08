<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Bus;

use MeRezaRezaei\Teleframe\Bus\HotReloadRouter;
use MeRezaRezaei\Teleframe\Bus\RouteTable;
use MeRezaRezaei\Teleframe\Bus\StreamSchema;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayRedis;
use PHPUnit\Framework\TestCase;

/**
 * Plan Task 2: route swaps without restarts — publish a ping on the
 * reload channel and every listener re-reads the table (ArrayRedis
 * pub/sub is a synchronous loopback, so no daemon is needed here).
 */
final class HotReloadRouterTest extends TestCase
{
    public function test_constructor_snapshots_the_current_table(): void
    {
        $redis = new ArrayRedis();
        (new RouteTable($redis))->set('updateNewMessage*', 'tg:target:one');

        $router = new HotReloadRouter($redis, static function (): void {});

        self::assertSame(['updateNewMessage*' => 'tg:target:one'], $router->routes());
    }

    public function test_refresh_re_reads_the_table_for_polling_consumers(): void
    {
        $redis = new ArrayRedis();
        $router = new HotReloadRouter($redis, static function (): void {});

        self::assertSame([], $router->routes(), 'empty table at construction');

        (new RouteTable($redis))->set('updateNewMessage*', 'tg:target:one');
        self::assertSame([], $router->routes(), 'cache is stale until refresh');

        $fresh = $router->refresh();

        self::assertSame(['updateNewMessage*' => 'tg:target:one'], $fresh);
        self::assertSame($fresh, $router->routes());
    }

    public function test_publish_fires_on_reload_with_the_fresh_table(): void
    {
        $redis = new ArrayRedis();
        $table = new RouteTable($redis);
        $table->set('updateNewMessage*', 'tg:target:one');

        $received = [];
        $router = new HotReloadRouter($redis, static function (array $routes) use (&$received): void {
            $received[] = $routes;
        });

        $router->listen();

        $table->set('updateUserStatus*', 'tg:target:status');
        $redis->publish(StreamSchema::RELOAD_CHANNEL, 'reload');

        self::assertCount(1, $received);
        self::assertSame($table->all(), $received[0]);
        self::assertSame($received[0], $router->routes(), 'cache follows the reload');
    }

    public function test_publish_without_a_listener_reaches_no_one(): void
    {
        $redis = new ArrayRedis();

        self::assertSame(0, $redis->publish(StreamSchema::RELOAD_CHANNEL, 'reload'));
    }
}

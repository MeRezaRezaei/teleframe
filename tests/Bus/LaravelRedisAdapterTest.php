<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Bus;

use Illuminate\Container\Container;
use Illuminate\Redis\Connections\Connection as IlluminateConnection;
use Illuminate\Redis\RedisManager;
use MeRezaRezaei\Teleframe\Bus\LaravelRedisAdapter;
use LogicException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Throwable;

/**
 * Live-redis coverage for LaravelRedisAdapter (plan Phase 3, Task 3) —
 * runs against a real server when reachable (TELECLIENT_REDIS_TEST_HOST
 * / _PORT, default 127.0.0.1:6379), skipped otherwise; both illuminate
 * drivers are exercised (phpredis only when the extension is loaded).
 */
final class LaravelRedisAdapterTest extends TestCase
{
    /** @return list<array{0: string}> */
    public static function driverProvider(): array
    {
        $drivers = [['predis']];
        if (extension_loaded('redis')) {
            $drivers[] = ['phpredis'];
        }

        return $drivers;
    }

    #[DataProvider('driverProvider')]
    public function test_adapter_round_trips_every_contract_method(string $driver): void
    {
        $manager = self::liveManager($driver);
        $adapter = new LaravelRedisAdapter($manager->connection('default'));

        $stream = self::prefix() . ':s';
        $hash = self::prefix() . ':h';
        $adapter->del($stream, $hash);

        // xadd: contract order (id before fields) regardless of driver quirks
        $id1 = $adapter->xadd($stream, '*', ['account_id' => '42', 'update' => '{"x":1}']);
        $id2 = $adapter->xadd($stream, '*', ['account_id' => '42', 'update' => '{"x":2}']);
        self::assertNotSame('', $id1);
        self::assertNotSame('', $id2);

        self::createGroup($manager->connection('default'), $stream);

        // xreadgroup: contract map shape on every driver
        $read = $adapter->xreadgroup('tg-test', 'c1', [$stream => 10]);
        self::assertSame(
            [$id1 => ['account_id' => '42', 'update' => '{"x":1}'], $id2 => ['account_id' => '42', 'update' => '{"x":2}']],
            $read[$stream] ?? null,
        );

        // delivered once per group; ack clears it
        self::assertSame([], $adapter->xreadgroup('tg-test', 'c1', [$stream => 10]));
        self::assertSame(2, $adapter->xack($stream, 'tg-test', [$id1, $id2]));

        // hash semantics
        self::assertSame(1, $adapter->hset($hash, 'route', 'a'));
        self::assertSame(0, $adapter->hset($hash, 'route', 'b'));
        self::assertSame(['route' => 'b'], $adapter->hgetall($hash));

        // llen across key kinds (stream entries, hash fields, missing)
        self::assertSame(2, $adapter->llen($stream));
        self::assertSame(1, $adapter->llen($hash));
        self::assertSame(0, $adapter->llen(self::prefix() . ':nope'));

        // publish without receivers, del
        self::assertSame(0, $adapter->publish(self::prefix() . ':chan', 'x'));
        self::assertSame(1, $adapter->del($hash));
    }

    #[DataProvider('driverProvider')]
    public function test_subscribe_refuses_the_loopback_semantic(string $driver): void
    {
        $manager = self::liveManager($driver);
        $adapter = new LaravelRedisAdapter($manager->connection('default'));

        $this->expectException(LogicException::class);
        $adapter->subscribe('chan', static function (): void {
        });
    }

    /**
     * @return RedisManager&object
     */
    private static function liveManager(string $driver): RedisManager
    {
        $explicit = getenv('TELECLIENT_REDIS_TEST_HOST') !== false;
        $host = getenv('TELECLIENT_REDIS_TEST_HOST') ?: '127.0.0.1';
        $port = (int) (getenv('TELECLIENT_REDIS_TEST_PORT') ?: 6379);

        $manager = new RedisManager(new Container(), $driver, ['default' => ['host' => $host, 'port' => $port]]);

        // Probe beyond TCP: a server that answers PING (a password-protected
        // one is "present" but unusable without credentials).
        try {
            $pong = $manager->connection('default')->command('ping', []);
        } catch (Throwable $e) {
            if ($explicit) {
                self::fail("redis at {$host}:{$port} explicitly configured but not usable: {$e->getMessage()}");
            }
            self::markTestSkipped("no usable redis at {$host}:{$port}: {$e->getMessage()}");
        }

        return $manager;
    }

    private static function createGroup(IlluminateConnection $connection, string $stream): void
    {
        try {
            // Both drivers accept op-first args with a bool MKSTREAM flag
            // (predis XGROUP::setCreateArguments matches on `true === $arguments[4]`,
            // phpredis xGroup's fifth parameter is the flag).
            $connection->command('xgroup', ['CREATE', $stream, 'tg-test', '0', true]);
        } catch (Throwable) {
            // BUSYGROUP: group already exists from an earlier run — fine.
        }
    }

    private static function prefix(): string
    {
        return 'tg-client-test:' . getmypid() . ':' . random_int(1000, 9999);
    }
}

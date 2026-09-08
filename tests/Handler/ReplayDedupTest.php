<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Handler;

use DateInterval;
use MeRezaRezaei\Teleframe\Handler\HandlerRegistry;
use MeRezaRezaei\Teleframe\Handler\Pipeline;
use MeRezaRezaei\Teleframe\Handler\Update;
use MeRezaRezaei\Teleframe\Handler\UpdateDispatcher;
use MeRezaRezaei\Teleframe\Testing\FakeDispatcher;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayContainer;
use Psr\SimpleCache\CacheInterface;
use PHPUnit\Framework\TestCase;

/**
 * Spec 5a §3: replay dedup. A re-ingested payload with the same updateId
 * within the TTL window is dropped before any user handler runs, and the
 * dispatcher counts the drop via ``seenReplays`` — proven through the real
 * pipeline (FakeDispatcher).
 */
final class ReplayDedupTest extends TestCase
{
    public function test_same_payload_dispatched_twice_only_runs_once(): void
    {
        $ran = 0;
        $registry = new HandlerRegistry();
        $registry->onMessage(function (Update $u) use (&$ran): void {
            ++$ran;
        });

        $fake = new FakeDispatcher(
            [
                ['update' => ['_' => 'updateNewMessage', 'message' => 'same'], 'account_id' => 7, 'ts' => 1000],
                ['update' => ['_' => 'updateNewMessage', 'message' => 'same'], 'account_id' => 7, 'ts' => 1000],
            ],
            $registry,
            new ArrayContainer(),
            new TtlArrayCache(),
        );

        $fake->run();

        self::assertSame(1, $ran);
        self::assertCount(1, $fake->dispatched);
        self::assertSame(1, $fake->seenReplays);
    }

    public function test_distinct_payloads_never_collide(): void
    {
        $ran = [];
        $registry = new HandlerRegistry();
        $registry->onMessage(function (Update $u) use (&$ran): void {
            $ran[] = $u->array['message'] ?? '';
        });

        $fake = new FakeDispatcher(
            [
                ['update' => ['_' => 'updateNewMessage', 'message' => 'one'], 'account_id' => 7, 'ts' => 1000],
                ['update' => ['_' => 'updateNewMessage', 'message' => 'two'], 'account_id' => 7, 'ts' => 1000],
            ],
            $registry,
            new ArrayContainer(),
            new TtlArrayCache(),
        );

        $fake->run();

        self::assertSame(['one', 'two'], $ran);
        self::assertCount(2, $fake->dispatched);
        self::assertSame(0, $fake->seenReplays);
    }

    public function test_same_payload_on_different_accounts_never_collides(): void
    {
        $registry = new HandlerRegistry();
        $registry->onMessage(fn () => null);
        $fake = new FakeDispatcher(
            [
                ['update' => ['_' => 'updateNewMessage', 'message' => 'same'], 'account_id' => 7, 'ts' => 1000],
                ['update' => ['_' => 'updateNewMessage', 'message' => 'same'], 'account_id' => 8, 'ts' => 1000],
            ],
            $registry,
            new ArrayContainer(),
            new TtlArrayCache(),
        );

        $fake->run();

        self::assertCount(2, $fake->dispatched);
        self::assertSame(0, $fake->seenReplays);
    }

    public function test_ttl_expiry_reaccepts_the_same_payload(): void
    {
        $cache = new TtlArrayCache();
        $registry = new HandlerRegistry();
        $ran = 0;
        $registry->onMessage(function (Update $u) use (&$ran): void {
            ++$ran;
        });

        $dispatcher = new UpdateDispatcher($registry, new Pipeline(), new ArrayContainer(), $cache, dedupTtl: 5);
        $update = Update::fromBus(['_' => 'updateNewMessage', 'message' => 'same'], 7, 1000);

        $dispatcher->dispatch($update);
        $cache->tick(10);
        $dispatcher->dispatch($update);

        self::assertSame(2, $ran);
        self::assertSame(0, $dispatcher->seenReplays());
    }
}

/**
 * PSR-16 store that actually honors TTLs (tick() advances the clock) — the
 * in-memory defaults ignore TTL, so expiry is proven explicitly here.
 */
final class TtlArrayCache implements CacheInterface
{
    /** @var array<string, array{value: mixed, expires: int}> */
    private array $values = [];

    private int $now;

    public function __construct(int $now = 0)
    {
        $this->now = $now;
    }

    public function tick(int $seconds): void
    {
        $this->now += $seconds;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (! isset($this->values[$key]) || $this->values[$key]['expires'] <= $this->now) {
            return $default;
        }

        return $this->values[$key]['value'];
    }

    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        $ttl = $ttl === null ? 60 : $ttl;
        if ($ttl instanceof DateInterval) {
            $ttl = (int) $ttl->format('%s') + ((int) $ttl->format('%d')) * 86400;
        }

        $this->values[$key] = ['value' => $value, 'expires' => $this->now + $ttl];

        return true;
    }

    public function delete(string $key): bool
    {
        unset($this->values[$key]);

        return true;
    }

    public function clear(): bool
    {
        $this->values = [];

        return true;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $found = [];
        foreach ($keys as $key) {
            $found[$key] = $this->get((string) $key, $default);
        }

        return $found;
    }

    public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set((string) $key, $value, $ttl);
        }

        return true;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            $this->delete((string) $key);
        }

        return true;
    }

    public function has(string $key): bool
    {
        return isset($this->values[$key]) && $this->values[$key]['expires'] > $this->now;
    }
}
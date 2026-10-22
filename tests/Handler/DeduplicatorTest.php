<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Handler;

use MeRezaRezaei\Teleframe\Handler\Deduplicator;
use MeRezaRezaei\Teleframe\Handler\HandlerRegistry;
use MeRezaRezaei\Teleframe\Handler\InMemoryCache;
use MeRezaRezaei\Teleframe\Handler\Update;
use PHPUnit\Framework\TestCase;

final class DeduplicatorTest extends TestCase
{
    public function test_first_update_is_not_duplicate_second_is(): void
    {
        $cache = new InMemoryCache;
        $dedup = new Deduplicator($cache, 60);

        $update = Update::fromBus(['_' => 'updateNewMessage', 'message' => 'hi'], 7, 100);

        self::assertFalse($dedup->isDuplicate($update));
        self::assertTrue($dedup->isDuplicate($update));
    }

    public function test_distinct_payloads_do_not_collide(): void
    {
        $cache = new InMemoryCache;
        $dedup = new Deduplicator($cache, 60);

        $a = Update::fromBus(['_' => 'updateNewMessage', 'message' => 'one'], 7, 100);
        $b = Update::fromBus(['_' => 'updateNewMessage', 'message' => 'two'], 7, 100);

        self::assertFalse($dedup->isDuplicate($a));
        self::assertFalse($dedup->isDuplicate($b));
    }

    public function test_different_accounts_do_not_collide(): void
    {
        $cache = new InMemoryCache;
        $dedup = new Deduplicator($cache, 60);

        $a = Update::fromBus(['_' => 'updateNewMessage', 'message' => 'same'], 7, 100);
        $b = Update::fromBus(['_' => 'updateNewMessage', 'message' => 'same'], 8, 100);

        self::assertFalse($dedup->isDuplicate($a));
        self::assertFalse($dedup->isDuplicate($b));
    }

    public function test_pure_lookup_does_not_record(): void
    {
        $cache = new InMemoryCache;
        $dedup = new Deduplicator($cache, 60);

        $update = Update::fromBus(['_' => 'updateNewMessage', 'message' => 'hi'], 7, 100);

        self::assertFalse($dedup->isSeen($update));
        self::assertFalse($dedup->isSeen($update));
    }

    public function test_mark_seen_records_key(): void
    {
        $cache = new InMemoryCache;
        $dedup = new Deduplicator($cache, 60);

        $update = Update::fromBus(['_' => 'updateNewMessage', 'message' => 'hi'], 7, 100);

        self::assertTrue($dedup->markSeen($update));
        self::assertTrue($dedup->isSeen($update));
    }

    public function test_should_process_inverse_combined(): void
    {
        $cache = new InMemoryCache;
        $dedup = new Deduplicator($cache, 60);

        $update = Update::fromBus(['_' => 'updateNewMessage', 'message' => 'hi'], 7, 100);

        self::assertTrue($dedup->shouldProcess($update));
        self::assertFalse($dedup->shouldProcess($update));
    }

    public function test_key_contains_account_update_and_hash(): void
    {
        $cache = new InMemoryCache;
        $dedup = new Deduplicator($cache, 60);

        $update = Update::fromBus(['_' => 'updateNewMessage', 'message' => 'hi'], 7, 100);
        $key = $dedup->key($update);

        self::assertStringContainsString('7', $key);
        self::assertStringContainsString($update->updateId(), $key);
        self::assertStringStartsWith('tg:bus:dedup', $key);
    }

    public function test_scope_bot_assistant_prefix(): void
    {
        $cache = new InMemoryCache;
        $dedup = new Deduplicator($cache, 60, Deduplicator::SCOPE_BOT_ASSISTANT);

        self::assertSame(Deduplicator::SCOPE_BOT_ASSISTANT, $dedup->getScope());
    }

    public function test_ttl_expiry_reopens_window(): void
    {
        $clock = new class { public int $now = 1000; };
        $cache = new InMemoryCache(fn () => $clock->now);
        $dedup = new Deduplicator($cache, 5);

        $update = Update::fromBus(['_' => 'updateNewMessage', 'message' => 'hi'], 7, 100);

        self::assertFalse($dedup->isDuplicate($update));
        $clock->now = 1010;
        self::assertFalse($dedup->isDuplicate($update));
    }

    public function test_handler_registry_integration(): void
    {
        $registry = new HandlerRegistry(new Deduplicator(new InMemoryCache, 60));
        $update = Update::fromBus(['_' => 'updateNewMessage', 'message' => 'hi'], 7, 100);

        self::assertIsString($registry->dedupKey($update));
        self::assertFalse($registry->isDuplicate($update));
        self::assertTrue($registry->isDuplicate($update));
    }

    public function test_aliases_work(): void
    {
        $cache = new InMemoryCache;
        $dedup = new Deduplicator($cache, 60);

        $update = Update::fromBus(['_' => 'updateNewMessage', 'message' => 'hi'], 7, 100);

        self::assertSame($dedup->key($update), $dedup->dedupKey($update));
        self::assertSame($dedup->key($update), $dedup->deduplicationKey($update));
        self::assertSame($dedup->key($update), $dedup->makeKey($update));
    }
}

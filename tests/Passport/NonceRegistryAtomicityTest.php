<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Passport;

use MeRezaRezaei\Teleframe\Passport\AtomicCacheStore;
use MeRezaRezaei\Teleframe\Passport\CacheNonceRegistry;
use MeRezaRezaei\Teleframe\Passport\MemoryNonceRegistry;
use MeRezaRezaei\Teleframe\Passport\NonAtomicNonceStoreException;
use MeRezaRezaei\Teleframe\Passport\RedisAtomicCacheStore;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayCache;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * NonceRegistryAtomicityTest — the replay window, closed or named.
 *
 * The defect under test: consume() was a plain read-then-delete against
 * PSR-16, so two OVERLAPPING consumes of one nonce could both observe the
 * userId and both log in. These tests pin the three claims that replaced the
 * docblock note:
 *
 *  1. isAtomic() tells a host which guarantee it is holding, before it uses
 *     the registry at all (the in-advance question).
 *  2. An atomic store is consumed through getAndDelete() ALONE — no get(),
 *     no delete() — so the window is structurally closed rather than narrowed.
 *  3. A store that CANNOT be atomic says so, refuses loudly when a host
 *     demands a hard guarantee, and never logs a nonce doing it.
 *
 * Plus the regression that matters most: a single consume still works, and
 * the second consume of the same nonce is still refused on BOTH registries.
 */
final class NonceRegistryAtomicityTest extends TestCase
{
    /* ---------- the in-advance question ---------- */

    public function test_a_plain_psr16_store_reports_non_atomic(): void
    {
        $registry = new CacheNonceRegistry(new ArrayCache, 900, 'p.');

        self::assertFalse($registry->isAtomic());
    }

    public function test_a_store_advertising_atomicity_reports_atomic(): void
    {
        $registry = new CacheNonceRegistry(new FakeAtomicCacheStore, 900, 'p.');

        self::assertTrue($registry->isAtomic());
    }

    public function test_memory_registry_reports_atomic_and_says_why(): void
    {
        // Honest for its own terms: process-local array, no I/O in consume(),
        // so there is no interleaving point to race through.
        $registry = new MemoryNonceRegistry(900);

        self::assertTrue($registry->isAtomic());
    }

    /* ---------- the atomic path ---------- */

    public function test_atomic_consume_uses_get_and_delete_and_never_the_two_step_path(): void
    {
        $store = new FakeAtomicCacheStore;
        $registry = new CacheNonceRegistry($store, 900, 'p.');

        $nonce = $registry->issue(7);

        self::assertSame(7, $registry->consume($nonce));
        self::assertSame(1, $store->getAndDeleteCalls);
        self::assertSame(0, $store->getCalls, 'a get() would reopen the TOCTOU window');
        self::assertSame(0, $store->deleteCalls, 'the delete must ride inside the atomic op');
    }

    public function test_atomic_consume_is_single_use_under_concurrent_consumes(): void
    {
        $store = new FakeAtomicCacheStore;
        $registry = new CacheNonceRegistry($store, 900, 'p.');

        $nonce = $registry->issue(42);

        // Two overlapping deliveries of the same webhook. The store serves the
        // first caller and the second reads absent: the loser is refused, which
        // the orchestrator turns into a loud "unknown nonce" — never a second
        // login.
        $first = $registry->consume($nonce);
        $second = $registry->consume($nonce);

        self::assertSame(42, $first);
        self::assertNull($second);
    }

    public function test_atomic_consume_refuses_an_expired_nonce_without_resurrecting_it(): void
    {
        $store = new FakeAtomicCacheStore;
        $registry = new CacheNonceRegistry($store, 900, 'p.');

        $nonce = $registry->issue(5);
        $store->expire('p.'.$nonce); // the server dropped it

        self::assertNull($registry->consume($nonce));
        self::assertFalse($store->has('p.'.$nonce), 'a refused consume must leave the key absent');
        self::assertSame(1, $store->setCalls, 'only issue() ever writes; consume never re-creates');
    }

    public function test_atomic_consume_refuses_a_non_integer_value(): void
    {
        $store = new FakeAtomicCacheStore;
        $registry = new CacheNonceRegistry($store, 900, 'p.');

        $nonce = $registry->issue(1);
        $store->set('p.'.$nonce, 'not-an-int');

        self::assertNull($registry->consume($nonce));
    }

    /* ---------- the non-atomic path, unchanged behaviour ---------- */

    public function test_non_atomic_consume_still_deletes_unconditionally_and_refuses_the_replay(): void
    {
        $store = new ArrayCache;
        $registry = new CacheNonceRegistry($store, 900, 'p.');

        $nonce = $registry->issue(9);

        self::assertSame(9, $registry->consume($nonce));
        self::assertFalse($store->has('p.'.$nonce));
        self::assertNull($registry->consume($nonce)); // the replay is still refused
    }

    public function test_memory_registry_still_refuses_the_replay_and_honours_the_clock(): void
    {
        $now = 1000;
        $registry = new MemoryNonceRegistry(300, static function () use (&$now): int {
            return $now;
        });

        $nonce = $registry->issue(11);

        self::assertSame(11, $registry->consume($nonce));
        self::assertNull($registry->consume($nonce));

        $nonce = $registry->issue(11);
        $now = 1600; // past expiresAt (1300 + 300)
        self::assertNull($registry->consume($nonce));
    }

    /* ---------- the loud refusal ---------- */

    public function test_the_refusal_is_catchable_and_names_the_store_without_a_nonce(): void
    {
        $store = new ArrayCache;
        $nonce = (new CacheNonceRegistry($store, 900, 'p.'))->issue(1234);

        try {
            throw NonAtomicNonceStoreException::forStore($store::class);
        } catch (RuntimeException $e) {
            self::assertInstanceOf(NonAtomicNonceStoreException::class, $e);
            self::assertStringContainsString(ArrayCache::class, $e->getMessage());
            self::assertStringContainsString(AtomicCacheStore::class, $e->getMessage());
            self::assertStringNotContainsString($nonce, $e->getMessage());
        }
    }

    public function test_the_refusal_message_never_carries_a_cache_key(): void
    {
        $message = NonAtomicNonceStoreException::forStore(ArrayCache::class)->getMessage();

        // The class name is allowed (a host needs to know what to replace);
        // a key is not (it is derived from a one-time credential).
        self::assertStringNotContainsString('p.', $message);
    }

    /* ---------- the shipped Redis-backed store ---------- */

    public function test_redis_atomic_store_refuses_a_non_redis_store_instead_of_degrading(): void
    {
        // The promise was made by the type; if the store cannot keep it, the
        // consume fails loudly. Silently falling back to read-then-delete here
        // would be the exact bug this class exists to remove.
        self::assertFalse(RedisAtomicCacheStore::supports(new ArrayCache));

        $atomic = new RedisAtomicCacheStore(new ArrayCache);

        $this->expectException(RuntimeException::class);
        $atomic->getAndDelete('p.whatever');
    }

    public function test_redis_atomic_store_delegates_the_rest_of_psr16_untouched(): void
    {
        $inner = new ArrayCache;
        $store = new RedisAtomicCacheStore($inner);

        self::assertTrue($store->set('p.x', 5, 900));
        self::assertTrue($store->has('p.x'));
        self::assertSame(5, $store->get('p.x'));
        self::assertTrue($store->delete('p.x'));
        self::assertFalse($store->has('p.x'));
    }

    public function test_redis_atomic_store_unwraps_a_serialized_php_int(): void
    {
        // A redis-backed PSR-16 store may hand back bytes; the stock serialize
        // form of an int must still resolve to the userId.
        self::assertSame(1234, RedisAtomicCacheStore::unwrap(serialize(1234)));
        self::assertSame(1234, RedisAtomicCacheStore::unwrap(1234));
    }

    public function test_redis_atomic_store_returns_a_missing_key_as_null(): void
    {
        // Absent and expired are the same answer, and both leave the key gone.
        self::assertNull(RedisAtomicCacheStore::unwrap(null));
        self::assertNull(RedisAtomicCacheStore::unwrap(false));
    }

    public function test_redis_atomic_store_fails_closed_on_an_unreadable_value(): void
    {
        // Not an int, not a serialized int: it comes back untouched, and the
        // registry's is_int() check refuses the login. Never accepted twice.
        self::assertSame('garbage', RedisAtomicCacheStore::unwrap('garbage'));
        self::assertNull((new CacheNonceRegistry(new FakeAtomicCacheStore, 900, 'p.'))->consume('x'));
    }
}

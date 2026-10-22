<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Passport;

use MeRezaRezaei\Teleframe\Passport\CacheNonceRegistry;
use MeRezaRezaei\Teleframe\Passport\MemoryNonceRegistry;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayCache;
use PHPUnit\Framework\TestCase;

/**
 * NonceRegistryTest — the replay-guard lifecycle (design §2/§4/§11, spec
 * §5 G4): issue(userId) at request time, ONE-TIME consume(nonce) → userId
 * at login time, TTL'd. A second consume of the same nonce (a replayed
 * webhook) hits unknown → null. MemoryNonceRegistry is array-backed with an
 * injected clock seam for fast-forward time; CacheNonceRegistry rides the
 * repo's plain PSR-16 ArrayCache double.
 */
final class NonceRegistryTest extends TestCase
{
    public function test_memory_issue_returns_a_32_hex_nonce_and_consumes_once(): void
    {
        $nonces = new MemoryNonceRegistry(900);

        $nonce = $nonces->issue(7);

        self::assertSame(32, strlen($nonce));
        self::assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $nonce);

        self::assertSame(7, $nonces->consume($nonce));
        self::assertNull($nonces->consume($nonce)); // replay → unknown
    }

    public function test_memory_unknown_nonce_consumes_to_null(): void
    {
        $nonces = new MemoryNonceRegistry(900);

        self::assertNull($nonces->consume('never-issued'));
        self::assertNull($nonces->consume(''));
    }

    public function test_memory_ttl_expiry_via_injected_clock(): void
    {
        $now = 1000;
        $nonces = new MemoryNonceRegistry(300, static function () use (&$now): int {
            return $now;
        });

        $nonce = $nonces->issue(11);
        $now = 1299; // inside the window (expiresAt = 1300)
        self::assertSame(11, $nonces->consume($nonce));

        // re-issue after the first window (expiresAt = 1299+300 = 1599)
        $nonce = $nonces->issue(11);
        $now = 1599; // consumed at exactly the expiry boundary → refused
        self::assertNull($nonces->consume($nonce));
    }

    public function test_memory_consume_at_exactly_the_expiry_is_refused(): void
    {
        $now = 5000;
        $nonces = new MemoryNonceRegistry(100, static function () use (&$now): int {
            return $now;
        });

        $nonce = $nonces->issue(3);

        $now = 5100; // expiresAt 5100 → consumed at exactly expiry is refused
        self::assertNull($nonces->consume($nonce));
    }

    public function test_memory_issue_binds_the_user_id(): void
    {
        $nonces = new MemoryNonceRegistry(900);

        $a = $nonces->issue(41);
        $b = $nonces->issue(42);

        self::assertNotSame($a, $b);
        self::assertSame(41, $nonces->consume($a));
        self::assertSame(42, $nonces->consume($b));
    }

    public function test_memory_clear_forgets_a_nonce_without_consuming(): void
    {
        $nonces = new MemoryNonceRegistry(900);

        $nonce = $nonces->issue(5);
        $nonces->clear($nonce);

        self::assertNull($nonces->consume($nonce));
    }

    /**
     * A clear that does not match a live entry must change NOTHING, and both of
     * its no-op shapes are observable rather than merely "did not throw".
     *
     * `$live` is issued BEFORE the two clears, so a clear that matched anything
     * beyond its own key — a blanket `$this->nonces = []`, a `clear('')` read as
     * a wildcard, a clear that rebuilt the array from a filtered copy — would
     * take it with it and the consume below would read null. And `clear()` must
     * never MINT: after clearing a nonce that was never issued, consuming that
     * name must still be unknown rather than resolving to something.
     */
    public function test_memory_clear_unknown_nonce_is_a_noop(): void
    {
        $nonces = new MemoryNonceRegistry(900);

        $live = $nonces->issue(77);

        $nonces->clear('never-issued');
        $nonces->clear('');

        self::assertSame(
            77,
            $nonces->consume($live),
            'clearing an unknown name left the one live entry intact — a clear is not a wipe'
        );
        self::assertNull(
            $nonces->consume('never-issued'),
            'clearing a name that was never issued minted nothing'
        );
    }

    public function test_cache_issue_writes_the_key_prefix_and_consumes_once(): void
    {
        $store = new ArrayCache;
        $nonces = new CacheNonceRegistry($store, 900, 'teleframe.passport.nonce.');

        $nonce = $nonces->issue(7);

        self::assertSame(32, strlen($nonce));
        self::assertTrue($store->has('teleframe.passport.nonce.'.$nonce));
        self::assertSame(7, $store->get('teleframe.passport.nonce.'.$nonce));

        self::assertSame(7, $nonces->consume($nonce));
        self::assertFalse($store->has('teleframe.passport.nonce.'.$nonce)); // delete ALWAYS runs
        self::assertNull($nonces->consume($nonce)); // replay → unknown
    }

    public function test_cache_unknown_nonce_consumes_to_null_and_deletes_nothing_loud(): void
    {
        $store = new ArrayCache;
        $nonces = new CacheNonceRegistry($store, 900, 'p.');

        self::assertNull($nonces->consume('never-issued'));
        self::assertNull($nonces->consume(''));
    }

    public function test_cache_clear_forgets_the_underlying_key(): void
    {
        $store = new ArrayCache;
        $nonces = new CacheNonceRegistry($store, 900, 'p.');

        $nonce = $nonces->issue(9);
        $nonces->clear($nonce);

        self::assertFalse($store->has('p.'.$nonce));
        self::assertNull($nonces->consume($nonce));
    }

    public function test_cache_consumes_non_integer_stored_values_as_null(): void
    {
        $store = new ArrayCache;
        $nonces = new CacheNonceRegistry($store, 900, 'p.');

        $nonce = $nonces->issue(1);
        $store->set('p.'.$nonce, 'not-an-int');

        self::assertNull($nonces->consume($nonce));
    }
}

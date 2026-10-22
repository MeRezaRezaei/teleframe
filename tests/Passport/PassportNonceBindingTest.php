<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Passport;

use MeRezaRezaei\Teleframe\Laravel\PassportServiceProvider;
use MeRezaRezaei\Teleframe\Passport\AtomicCacheStore;
use MeRezaRezaei\Teleframe\Passport\CacheNonceRegistry;
use MeRezaRezaei\Teleframe\Passport\MemoryNonceRegistry;
use MeRezaRezaei\Teleframe\Passport\NonAtomicNonceStoreException;
use MeRezaRezaei\Teleframe\Passport\NonceRegistry;
use MeRezaRezaei\Teleframe\Passport\PassportSupport;
use MeRezaRezaei\Teleframe\Tests\Schema\TestCase;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayCache;
use Psr\SimpleCache\CacheInterface;
use RuntimeException;

/**
 * PassportNonceBindingTest — the BIND-TIME half of the replay-guard fix.
 *
 * A host has two ways to learn that its cache cannot give it a hard one-time
 * guarantee, and both are pinned here:
 *
 *  - in advance, without using the registry: the seam answers isAtomic();
 *  - as a refusal, when it has said it NEEDS one:
 *    teleframe.passport.require_atomic_nonce turns the weak store into a loud
 *    NonAtomicNonceStoreException at resolve — a failed boot instead of a
 *    double login.
 *
 * Also pins the two things that must NOT move: an unconfigured module still
 * binds the inert memory registry (Passport stays off unless configured), and
 * a host that has not asked for a hard guarantee keeps the historical
 * non-atomic binding rather than being broken by a new requirement.
 */
final class PassportNonceBindingTest extends TestCase
{
    /** @return list<class-string> */
    protected function getPackageProviders(mixed $app): array
    {
        return [PassportServiceProvider::class];
    }

    public function test_unconfigured_module_binds_the_inert_memory_registry(): void
    {
        $nonces = $this->app->make(PassportSupport::NONCES_KEY);

        self::assertInstanceOf(MemoryNonceRegistry::class, $nonces);
        self::assertTrue($nonces->isAtomic());
    }

    public function test_a_configured_plain_store_binds_and_reports_its_weakness_out_loud(): void
    {
        $nonces = $this->registryOver(new ArrayCache);

        self::assertInstanceOf(CacheNonceRegistry::class, $nonces);
        self::assertFalse($nonces->isAtomic(), 'a host can ask BEFORE it relies on the registry');

        $nonce = $nonces->issue(3);
        self::assertSame(3, $nonces->consume($nonce));
        self::assertNull($nonces->consume($nonce));
    }

    public function test_requiring_atomicity_refuses_a_store_that_cannot_deliver_it(): void
    {
        $this->app->instance(CacheInterface::class, new ArrayCache);
        config()->set('teleframe.passport.nonce_store', 'redis');
        config()->set('teleframe.passport.require_atomic_nonce', true);

        $this->forgetNonces();

        try {
            $this->app->make(PassportSupport::NONCES_KEY);
            self::fail('a hard one-time guarantee was demanded and not delivered — this must be loud');
        } catch (NonAtomicNonceStoreException $e) {
            self::assertInstanceOf(RuntimeException::class, $e);
            self::assertStringContainsString(ArrayCache::class, $e->getMessage());
            self::assertStringContainsString(AtomicCacheStore::class, $e->getMessage());
        }
    }

    public function test_without_the_requirement_the_weak_store_is_still_bound(): void
    {
        // Backwards compatibility: a host that never heard of the flag keeps
        // exactly the binding it had before the fix.
        $nonces = $this->registryOver(new ArrayCache);

        self::assertInstanceOf(CacheNonceRegistry::class, $nonces);
        self::assertFalse($nonces->isAtomic());
    }

    public function test_a_redis_backed_store_is_wrapped_so_the_consume_is_atomic(): void
    {
        // A real RedisStore cannot be stood up in this suite, so the wrap is
        // asserted at the seam the provider probes: an AtomicCacheStore the
        // host bound itself is never re-wrapped and reports atomic.
        $atomic = new FakeAtomicCacheStore;
        $this->app->instance(CacheInterface::class, $atomic);
        config()->set('teleframe.passport.nonce_store', 'redis');
        config()->set('teleframe.passport.require_atomic_nonce', true);

        $this->forgetNonces();

        $nonces = $this->app->make(PassportSupport::NONCES_KEY);

        self::assertInstanceOf(CacheNonceRegistry::class, $nonces);
        self::assertTrue($nonces->isAtomic(), 'require_atomic_nonce must pass on a store that can honour it');

        $nonce = $nonces->issue(8);
        self::assertSame(8, $nonces->consume($nonce));
        self::assertNull($nonces->consume($nonce));
        // BOTH consumes went through the atomic op — the replay was not
        // downgraded to a get()+delete() pair to 'check' whether it existed.
        self::assertSame(2, $atomic->getAndDeleteCalls);
        self::assertSame(0, $atomic->getCalls);
        self::assertSame(0, $atomic->deleteCalls);
    }

    /**
     * Bind the nonce seam over a specific PSR-16 store, as a configured host
     * would (nonce_store set, require_atomic_nonce untouched).
     */
    private function registryOver(CacheInterface $store): NonceRegistry
    {
        $this->app->instance(CacheInterface::class, $store);
        config()->set('teleframe.passport.nonce_store', 'array');

        $this->forgetNonces();

        return $this->app->make(PassportSupport::NONCES_KEY);
    }

    private function forgetNonces(): void
    {
        $this->app->forgetInstance(PassportSupport::NONCES_KEY);
    }
}

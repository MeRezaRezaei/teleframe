<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Passport;

use MeRezaRezaei\Teleframe\Passport\AtomicCacheStore;
use MeRezaRezaei\Teleframe\Passport\CachePendingLoginRegistry;
use MeRezaRezaei\Teleframe\Passport\MemoryPendingLoginRegistry;
use MeRezaRezaei\Teleframe\Passport\PendingLoginBinding;
use MeRezaRezaei\Teleframe\Passport\PendingLoginRegistry;
use MeRezaRezaei\Teleframe\Passport\SessionFingerprint;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;

/**
 * PendingLoginRegistry — the store behind the anonymous login's session binding.
 *
 * These tests exist because the binding is the ONLY thing standing between a
 * leaked flow token and an account takeover, and because both earlier
 * takeovers in this module were a check that could be skipped. So the four
 * properties the registry's docblock claims are each asserted directly, on
 * BOTH implementations, rather than inferred from an HTTP test that happened to
 * pass:
 *
 *   1. UNFORGEABLE   — issue() mints the token; no caller supplies one.
 *   2. BOUND AT ISSUE — the fingerprint and the nonce digest are recorded in
 *                       the same write, so no marker exists half-bound.
 *   3. SINGLE-USE    — a second consume finds nothing.
 *   4. EXPIRING      — an aged marker is a refusal, not a revival.
 *
 * Plus the property the HTTP suite depends on but cannot see: what the store
 * HOLDS. A shared cache dump must not yield a usable session or a usable
 * passport nonce, because the nonce is what the decrypted payload is checked
 * against.
 */
final class PendingLoginRegistryTest extends TestCase
{
    // ── the four load-bearing properties, on both implementations ─────

    public function test_the_memory_registry_issues_an_unforgeable_handle_and_a_separate_nonce(): void
    {
        $ticket = (new MemoryPendingLoginRegistry(60))->issue('fingerprint-a');

        // 128 bits of random_bytes, hex — not uniqid(), which leaks time.
        self::assertSame(32, strlen($ticket->token()));
        self::assertSame(32, strlen($ticket->nonce()));
        self::assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $ticket->token());
        self::assertNotSame($ticket->token(), $ticket->nonce(), 'the handle and the nonce must be two secrets');
    }

    public function test_two_issues_never_share_a_handle(): void
    {
        $registry = new MemoryPendingLoginRegistry(60);

        $handles = [];
        for ($i = 0; $i < 50; $i++) {
            $handles[] = $registry->issue('fingerprint-a')->token();
        }

        self::assertCount(50, array_unique($handles));
    }

    public function test_the_memory_registry_records_the_fingerprint_and_the_nonce_digest_in_one_binding(): void
    {
        $registry = new MemoryPendingLoginRegistry(60);
        $ticket = $registry->issue('fingerprint-a');

        $binding = $registry->consume($ticket->token());

        self::assertInstanceOf(PendingLoginBinding::class, $binding);
        self::assertSame('fingerprint-a', $binding->sessionFingerprint());
        self::assertSame(
            hash('sha256', $ticket->nonce()),
            $binding->nonceDigest(),
            'the marker must carry a DIGEST of the nonce, never the nonce',
        );
        self::assertNotSame($ticket->nonce(), $binding->nonceDigest());
    }

    public function test_the_memory_registry_consumes_a_marker_exactly_once(): void
    {
        $registry = new MemoryPendingLoginRegistry(60);
        $ticket = $registry->issue('fingerprint-a');

        self::assertNotNull($registry->consume($ticket->token()));
        self::assertNull($registry->consume($ticket->token()), 'a replayed handle must find nothing');
    }

    public function test_the_memory_registry_refuses_an_expired_marker(): void
    {
        $clock = 1_000;
        $registry = new MemoryPendingLoginRegistry(60, static function () use (&$clock): int {
            return $clock;
        });

        $ticket = $registry->issue('fingerprint-a');
        $clock += 59;
        self::assertNotNull($registry->consume($ticket->token()), 'still inside the TTL');

        $other = $registry->issue('fingerprint-a');
        $clock += 61;
        self::assertNull($registry->consume($other->token()), 'past the TTL is a refusal, not a revival');
    }

    public function test_clear_forgets_a_marker_without_consuming_it(): void
    {
        $registry = new MemoryPendingLoginRegistry(60);
        $ticket = $registry->issue('fingerprint-a');

        $registry->clear($ticket->token());

        self::assertNull($registry->consume($ticket->token()));
    }

    /** An empty handle is never a marker — there is nothing to guess here. */
    public function test_an_empty_handle_is_never_a_marker(): void
    {
        self::assertNull((new MemoryPendingLoginRegistry(60))->consume(''));
        self::assertNull((new CachePendingLoginRegistry(new ArrayCacheDouble, 60, 'p.'))->consume(''));
    }

    public function test_an_unknown_handle_is_a_refusal_not_a_partial_answer(): void
    {
        self::assertNull((new MemoryPendingLoginRegistry(60))->consume(bin2hex(random_bytes(16))));
    }

    // ── the cache implementation ──────────────────────────────────────

    public function test_the_cache_registry_binds_the_same_way_the_memory_one_does(): void
    {
        $registry = new CachePendingLoginRegistry(new ArrayCacheDouble, 60, 'teleframe.passport.pending.');
        $ticket = $registry->issue('fingerprint-a');

        $binding = $registry->consume($ticket->token());

        self::assertInstanceOf(PendingLoginBinding::class, $binding);
        self::assertSame('fingerprint-a', $binding->sessionFingerprint());
        self::assertSame(hash('sha256', $ticket->nonce()), $binding->nonceDigest());
        self::assertNull($registry->consume($ticket->token()));
    }

    /**
     * What the shared cache HOLDS. The registry is keyed by the token, so the
     * token is necessarily in the key — but the VALUE must be two digests and
     * nothing else. A dump of this store must not be usable to finish anybody's
     * login, and must not contain a usable session.
     */
    public function test_the_cache_registry_stores_digests_and_never_a_plaintext_nonce_or_session(): void
    {
        $store = new ArrayCacheDouble;
        $registry = new CachePendingLoginRegistry($store, 60, 'teleframe.passport.pending.');

        $sessionId = 'a-real-laravel-session-id';
        $ticket = $registry->issue(SessionFingerprint::of($sessionId));

        $stored = $store->get('teleframe.passport.pending.'.$ticket->token());

        self::assertIsArray($stored);
        self::assertSame(['sf', 'nd'], array_keys($stored), 'exactly two digests, nothing else');
        self::assertSame(SessionFingerprint::of($sessionId), $stored['sf']);
        self::assertSame(hash('sha256', $ticket->nonce()), $stored['nd']);

        $dump = (string) json_encode($store->dump());
        self::assertStringNotContainsString($ticket->nonce(), $dump, 'a cache dump must not yield a usable passport nonce');
        self::assertStringNotContainsString($sessionId, $dump, 'a cache dump must not yield a usable session');
    }

    public function test_the_cache_registry_reports_a_non_atomic_store_honestly(): void
    {
        $plain = new CachePendingLoginRegistry(new ArrayCacheDouble, 60, 'p.');
        self::assertFalse($plain->isAtomic(), 'a plain PSR-16 store is read-then-delete and must say so');

        $atomic = new CachePendingLoginRegistry(new AtomicCacheDouble(new ArrayCacheDouble), 60, 'p.');
        self::assertTrue($atomic->isAtomic(), 'an AtomicCacheStore is one indivisible GET+DEL');
    }

    /** A refused consume never resurrects the key on a non-atomic store. */
    public function test_a_consumed_marker_stays_consumed(): void
    {
        $store = new ArrayCacheDouble;
        $registry = new CachePendingLoginRegistry($store, 60, 'p.');
        $ticket = $registry->issue('fingerprint-a');

        $registry->consume($ticket->token());

        self::assertFalse($store->has('p.'.$ticket->token()), 'the key must be gone, not merely read');
        self::assertNull($registry->consume($ticket->token()));
    }

    // ── both implementations honour the same contract ─────────────────

    /**
     * @return list<array{0: PendingLoginRegistry}>
     */
    public static function implementations(): array
    {
        return [
            'memory' => [new MemoryPendingLoginRegistry(60)],
            'cache' => [new CachePendingLoginRegistry(new ArrayCacheDouble, 60, 'p.')],
            'atomic cache' => [new CachePendingLoginRegistry(new AtomicCacheDouble(new ArrayCacheDouble), 60, 'p.')],
        ];
    }

    #[DataProvider('implementations')]
    public function test_the_contract_holds_on_every_implementation(PendingLoginRegistry $registry): void
    {
        $ticket = $registry->issue('fingerprint-a');

        self::assertNotSame('', $ticket->token());
        self::assertNotSame('', $ticket->nonce());

        $binding = $registry->consume($ticket->token());
        self::assertInstanceOf(PendingLoginBinding::class, $binding);
        self::assertSame('fingerprint-a', $binding->sessionFingerprint());
        self::assertSame(64, strlen($binding->nonceDigest()));

        self::assertNull($registry->consume($ticket->token()), 'single-use');
        self::assertNull($registry->consume(''), 'an empty handle is never a marker');
        self::assertNull($registry->consume(bin2hex(random_bytes(16))), 'unknown handles are a refusal');
    }
}

/**
 * A minimal recording PSR-16 store: no framework, and it can be dumped so a
 * test can look at what the registry actually persisted.
 */
final class ArrayCacheDouble implements CacheInterface
{
    /** @var array<string, mixed> */
    private array $entries = [];

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->entries[$key] ?? $default;
    }

    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {
        $this->entries[$key] = $value;

        return true;
    }

    public function delete(string $key): bool
    {
        unset($this->entries[$key]);

        return true;
    }

    public function clear(): bool
    {
        $this->entries = [];

        return true;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $out = [];
        foreach ($keys as $key) {
            $out[$key] = $this->get($key, $default);
        }

        return $out;
    }

    public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set((string) $key, $value);
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
        return array_key_exists($key, $this->entries);
    }

    /** @return array<string, mixed> */
    public function dump(): array
    {
        return $this->entries;
    }
}

/**
 * The same store, wrapped so the registry sees an ATOMIC one (one indivisible
 * GET+DEL) — the shape a Redis-backed host gets.
 */
final class AtomicCacheDouble implements AtomicCacheStore, CacheInterface
{
    public function __construct(private readonly ArrayCacheDouble $inner) {}

    public function getAndDelete(string $key): mixed
    {
        $value = $this->inner->get($key);
        $this->inner->delete($key);

        return $value;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->inner->get($key, $default);
    }

    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {
        return $this->inner->set($key, $value);
    }

    public function delete(string $key): bool
    {
        return $this->inner->delete($key);
    }

    public function clear(): bool
    {
        return $this->inner->clear();
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        return $this->inner->getMultiple($keys, $default);
    }

    public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool
    {
        return $this->inner->setMultiple($values);
    }

    public function deleteMultiple(iterable $keys): bool
    {
        return $this->inner->deleteMultiple($keys);
    }

    public function has(string $key): bool
    {
        return $this->inner->has($key);
    }
}

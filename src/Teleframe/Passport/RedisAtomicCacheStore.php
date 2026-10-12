<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Passport;

use DateInterval;
use Illuminate\Cache\RedisStore;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Redis\Connections\PhpRedisConnection;
use Illuminate\Redis\Connections\PredisConnection;
use Psr\SimpleCache\CacheInterface;
use RuntimeException;

/**
 * RedisAtomicCacheStore — the concrete {@see AtomicCacheStore} this package
 * ships, and the reason the replay guard is closed rather than documented.
 *
 * THE WINDOW THIS EXISTS FOR. A one-time credential consumed through plain
 * PSR-16 get() then delete() is a TOCTOU read: two callers (a webhook
 * redelivery, a double-submit) can both observe the value before either
 * delete lands, and both log in. PSR-16 has no primitive for that, so this
 * class borrows exactly ONE thing from the host's cache backend — an
 * indivisible read-and-delete — and delegates the rest of the surface
 * untouched, so it is a drop-in replacement for any PSR-16 store.
 *
 * REDIS, NOT A NEW BINDING. The consume is a single Lua GET+DEL, byte for
 * byte the script RelayTicketBroker already uses to redeem relay tickets:
 * same repo pattern, same drivers (phpredis + predis, reached through
 * illuminate/redis), no new service key, no new config row. GETDEL proper
 * (Redis >= 6.2) is not used directly because the two drivers disagree about
 * how it returns a missing key; the Lua form is one identical script for
 * both, which is why the broker prefers it too.
 *
 * EXPIRY IS NOT RESURRECTED. The script never writes, never PEXPIREs, never
 * re-adds the key: an entry the server has already expired reads as nil and
 * stays absent, so a refused consume leaves no new TTL behind it. This is
 * what a plain delete-after-get cannot even express.
 *
 * NOT A LIE WHEN THERE IS NO REDIS. supports() is the honest predicate, and
 * this class REFUSES rather than degrading: a store that claimed the atomic
 * capability and then cannot honour it throws, so a consume is never quietly
 * downgraded to read-then-delete behind a host's back. The refusal is
 * NonAtomicNonceStoreException's territory (decided at bind time, in
 * PassportServiceProvider); here it is a plain RuntimeException because the
 * promise was already made.
 *
 * VALUE ENCODING, FAILS CLOSED. A raw Redis GET hands back bytes, while
 * PSR-16 get() hands back whatever the driver unserialized. The unserialized
 * shapes a nonce value can legitimately take (int, or an int wrapped in the
 * stock PHP serialize format) are both accepted; anything else is returned
 * untouched, and CacheNonceRegistry refuses it. The direction of the failure
 * is always "refuse the login", never "accept it twice".
 *
 * Zero regex: string functions and unserialize() only.
 */
final class RedisAtomicCacheStore implements AtomicCacheStore
{
    /**
     * GET + DEL as one server-side script: of any number of concurrent
     * callers, the first to run the script takes the value and every later
     * one gets nil. Identical to RelayTicketBroker::REDEEM_SCRIPT.
     */
    private const GETDEL_SCRIPT = <<<'LUA'
local value = redis.call('GET', KEYS[1])
if value then
  redis.call('DEL', KEYS[1])
end
return value
LUA;

    /**
     * @param  CacheInterface  $store  the host's PSR-16 store (delegated for everything but getAndDelete)
     */
    public function __construct(private readonly CacheInterface $store) {}

    /**
     * Whether $store is a Laravel cache repository over RedisStore — the
     * only backing this class can make atomic. Probes the store CLASSES only:
     * it never resolves a connection, so calling it at bind/boot time cannot
     * dial (same discipline as TeleframeServiceProvider's redis guard).
     */
    public static function supports(CacheInterface $store): bool
    {
        return $store instanceof CacheRepository
            && $store->getStore() instanceof RedisStore;
    }

    public function getAndDelete(string $key): mixed
    {
        return self::unwrap($this->eval($this->prefixed($key)));
    }

    /**
     * The value rule, factored out because it is the one piece of this class
     * that must be provable without a live redis server.
     *
     * A raw redis GET hands back bytes; PSR-16 get() hands back what the
     * driver unserialized. A nonce value is an int, possibly in the stock
     * PHP serialize form, so both are accepted; anything else comes back
     * untouched and CacheNonceRegistry refuses it. Fails CLOSED in every
     * case — the failure direction is a refused login, never a second one.
     */
    public static function unwrap(mixed $raw): mixed
    {
        if ($raw === null || $raw === false) {
            return null; // absent, or already expired server-side
        }

        if (! is_string($raw)) {
            return $raw;
        }

        $unwrapped = @unserialize($raw, ['allowed_classes' => false]);

        return $unwrapped === false ? $raw : $unwrapped;
    }

    /* ---- plain PSR-16, delegated verbatim to the host's store ---- */

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->store->get($key, $default);
    }

    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        return $this->store->set($key, $value, $ttl);
    }

    public function delete(string $key): bool
    {
        return $this->store->delete($key);
    }

    public function clear(): bool
    {
        return $this->store->clear();
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        return $this->store->getMultiple($keys, $default);
    }

    public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
    {
        return $this->store->setMultiple($values, $ttl);
    }

    public function deleteMultiple(iterable $keys): bool
    {
        return $this->store->deleteMultiple($keys);
    }

    public function has(string $key): bool
    {
        return $this->store->has($key);
    }

    /**
     * Run the GET+DEL script on the host's redis connection, resolving it
     * lazily (first consume, not construction) so a plain-PHP host can build
     * this object without illuminate/redis ever being present.
     *
     * @throws RuntimeException when the store is not Redis-backed, or the
     *                          connection refused the script — the atomic
     *                          promise cannot be kept, and a consume is
     *                          never silently downgraded.
     */
    private function eval(string $key): mixed
    {
        $store = $this->store instanceof CacheRepository ? $this->store->getStore() : null;

        if (! $store instanceof RedisStore) {
            throw new RuntimeException(
                'RedisAtomicCacheStore was given a non-Redis PSR-16 store ('.$this->store::class
                .') — it cannot make a one-time consume atomic. Bind the registry over a plain '
                .CacheInterface::class.' and accept the documented non-atomic window, or bind a store that '
                .'implements '.AtomicCacheStore::class.'.'
            );
        }

        $connection = $store->connection();

        if (! $connection instanceof PhpRedisConnection && ! $connection instanceof PredisConnection) {
            throw new RuntimeException(
                'RedisAtomicCacheStore needs a phpredis or predis connection, got '.$connection::class
                .' — refusing to degrade an atomic consume to read-then-delete.'
            );
        }

        /** @var mixed $raw */
        $raw = $connection->eval(self::GETDEL_SCRIPT, 1, $key);

        return $raw;
    }

    /**
     * RedisStore prefixes its own keys; the script talks to the server
     * directly and must carry the same prefix or it would GETDEL a different
     * physical key than PSR-16 set() wrote.
     */
    private function prefixed(string $key): string
    {
        $store = $this->store instanceof CacheRepository ? $this->store->getStore() : null;

        return $store instanceof RedisStore ? $store->getPrefix().$key : $key;
    }
}

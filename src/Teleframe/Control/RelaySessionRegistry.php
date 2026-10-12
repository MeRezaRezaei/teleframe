<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Control;

use Illuminate\Contracts\Cache\Repository;
use Psr\SimpleCache\CacheInterface;

/**
 * Presence registry for relayed vault accounts ("sit in the middle").
 *
 * Holds one active session per account in a PSR-16 cache under the opaque
 * key `relay:session:{accountId}` (TTL 300s by default), refreshed on each
 * activity heartbeat so an unattended session expires on its own. The
 * companion `relay:session:index` key carries the account ids a PSR-16
 * store cannot enumerate, letting `active()` report every live session
 * without scanning the whole cache.
 *
 * Index updates (start/write add to it, stop removes from it) are ATOMIC:
 * the read-modify-write runs inside a short cache lock when the store is a
 * Laravel Repository (add() is a single SET NX EX on Redis-backed stores),
 * so concurrent start()/stop() calls serialize and no index entry is lost.
 * On a plain PSR-16 cache — process-scoped stores with no inter-process
 * concurrency — the RMW runs directly and the docblock above holds
 * (best-effort, like CacheNonceRegistry's residual window).
 *
 * Plain PHP, no facades: the cache is injected, so the same registry works
 * over Laravel's array store, Redis, or any PSR-16 implementation. A
 * malformed cache payload is treated as an absent session (never thrown).
 * Zero regex — keys are string concatenation only.
 */
final class RelaySessionRegistry
{
    private const KEY_PREFIX = 'relay:session:';

    private const INDEX_KEY = 'relay:session:index';

    /** Cache lock serializing index read-modify-write on Laravel stores. */
    private const INDEX_LOCK_KEY = 'relay:session:index:lock';

    /** The lock auto-expires after this many seconds even if a holder dies mid-write. */
    private const INDEX_LOCK_TTL_SECONDS = 10;

    private readonly CacheInterface $cache;

    private readonly int $ttl;

    public function __construct(CacheInterface $cache, int $ttl = 300)
    {
        $this->cache = $cache;
        $this->ttl = max(1, $ttl);
    }

    /** Open (or restart) a relay session for an account; stamp is now. */
    public function start(int $accountId, string $relayId): void
    {
        $now = time();
        $this->write($accountId, new RelaySession($accountId, $relayId, $now, $now));
    }

    /** End the account's relay session; a later `status()` is null. */
    public function stop(int $accountId): void
    {
        $this->cache->delete($this->key($accountId));
        $this->dropIndexEntry($accountId);
    }

    /**
     * Refresh the heartbeat of an existing session's TTL. No-op when the
     * account currently has no active session.
     */
    public function touch(int $accountId): void
    {
        $session = $this->status($accountId);
        if ($session === null) {
            return;
        }

        $this->write($accountId, new RelaySession(
            $accountId,
            $session->relayId,
            $session->startedAt,
            time(),
        ));
    }

    /** Replace the stored session wholesale (used by builders/writers). */
    public function write(int $accountId, RelaySession $session): void
    {
        $this->cache->set(
            $this->key($accountId),
            json_encode([
                'relay_id' => $session->relayId,
                'started_at' => $session->startedAt,
                'last_seen_at' => $session->lastSeenAt,
            ], JSON_THROW_ON_ERROR),
            $this->ttl,
        );
        $this->addIndexEntry($accountId);
    }

    /** The account's current session, or null when offline/expired. */
    public function status(int $accountId): ?RelaySession
    {
        $raw = $this->cache->get($this->key($accountId));
        if (! is_string($raw) || $raw === '') {
            return null;
        }

        try {
            $payload = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        if (! is_array($payload)) {
            return null;
        }

        $relayId = $payload['relay_id'] ?? null;
        $startedAt = $payload['started_at'] ?? null;
        $lastSeenAt = $payload['last_seen_at'] ?? null;
        if (! is_string($relayId) || ! is_int($startedAt) || ! is_int($lastSeenAt)) {
            return null;
        }

        return new RelaySession($accountId, $relayId, $startedAt, $lastSeenAt);
    }

    /** @return list<RelaySession> */
    public function active(): array
    {
        $sessions = [];
        foreach ($this->index() as $accountId) {
            $session = $this->status($accountId);
            if ($session !== null) {
                $sessions[] = $session;
            }
        }

        return $sessions;
    }

    private function key(int $accountId): string
    {
        return self::KEY_PREFIX.$accountId;
    }

    /** @return list<int> */
    private function index(): array
    {
        $raw = $this->cache->get(self::INDEX_KEY);
        if (! is_string($raw) || $raw === '') {
            return [];
        }

        try {
            $payload = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [];
        }

        if (! is_array($payload)) {
            return [];
        }

        $ids = array_values(array_filter(
            $payload,
            static fn (mixed $id): bool => is_int($id) && $id >= 1,
        ));

        return array_map(static fn (int $id): int => $id, $ids);
    }

    private function addIndexEntry(int $accountId): void
    {
        $this->updateIndex(function (array &$ids) use ($accountId): void {
            if (! in_array($accountId, $ids, true)) {
                $ids[] = $accountId;
            }
        });
    }

    private function dropIndexEntry(int $accountId): void
    {
        $this->updateIndex(function (array &$ids) use ($accountId): void {
            $ids = array_values(array_filter(
                $ids,
                static fn (int $id): bool => $id !== $accountId,
            ));
        });
    }

    /**
     * Apply a mutation to the index atomically: the read-modify-write runs
     * inside a short cache lock when the store is a Laravel Repository
     * (add() is a single SET NX EX on Redis-backed stores), so concurrent
     * start()/write()/stop() calls serialize and no index entry is lost.
     * The lock is released only by its holder (token check) and expires on
     * its own if a holder dies mid-write. On a plain PSR-16 cache — no
     * inter-process concurrency — the RMW runs directly; if the lock is
     * momentarily held elsewhere, best-effort direct update (the residual
     * window CacheNonceRegistry accepts).
     */
    private function updateIndex(callable $mutate): void
    {
        if ($this->cache instanceof Repository) {
            $token = bin2hex(random_bytes(16));
            if ($this->cache->add(self::INDEX_LOCK_KEY, $token, self::INDEX_LOCK_TTL_SECONDS)) {
                try {
                    $ids = $this->index();
                    $mutate($ids);
                    $this->putIndex($ids);
                } finally {
                    if ($this->cache->get(self::INDEX_LOCK_KEY) === $token) {
                        $this->cache->delete(self::INDEX_LOCK_KEY);
                    }
                }

                return;
            }
        }

        $ids = $this->index();
        $mutate($ids);
        $this->putIndex($ids);
    }

    /** @param list<int> $ids */
    private function putIndex(array $ids): void
    {
        $this->cache->set(self::INDEX_KEY, json_encode($ids, JSON_THROW_ON_ERROR), $this->ttl);
    }
}

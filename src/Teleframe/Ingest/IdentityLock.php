<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Ingest;

use Illuminate\Database\Connection;

/**
 * Duplicate-anchor guard for identity resolution (P2 M3): two workers
 * resolving the same (account, identity) concurrently can both miss the
 * existing-anchor lookup and each mint an anchor — the identity value
 * lives in per-constructor instance tables, so no single DB unique
 * constraint can back the anchor row itself. Pragmatic serialization in
 * two layers instead:
 *
 *  (a) in-process: a static depth-counted key map. PHP workers are
 *      single-threaded, so the map never blocks — it exists to make
 *      nested (reentrant) acquires of the SAME key legal: a region
 *      re-entered inside one execution (ingestResponse → ingest, or two
 *      nodes of one payload carrying the same identity) must not wedge
 *      on its own key. Reentrancy is a documented choice, depth-counted.
 *
 *  (b) cross-worker: on PostgreSQL, a transaction-scoped advisory lock
 *      taken with raw SQL on the caller's connection INSIDE the caller's
 *      transaction — pg_advisory_xact_lock auto-releases at commit or
 *      rollback, so there is no release path to forget and no orphaned
 *      lock after a crash. Advisory locks are reentrant per session, so
 *      nested acquires within one transaction are also safe. sqlite
 *      (single-writer) and mysql (no advisory locks) are a documented
 *      no-op: callers there rely on the anchor discriminator updates
 *      and content aggregation for eventual convergence.
 */
final class IdentityLock
{
    /** @var array<string, int> lock key => current nesting depth in this process */
    private static array $depth = [];

    /**
     * Serialize anchor resolution for one identity. Safe to call nested
     * for the same key within one process (depth-counted reentrancy);
     * on pg the xact lock re-acquires harmlessly within the session.
     */
    public static function acquire(Connection $connection, string $key): void
    {
        self::$depth[$key] = (self::$depth[$key] ?? 0) + 1;

        if ($connection->getDriverName() === 'pgsql') {
            // hashtext() folds the textual key into the int64 advisory
            // lock space; the xact scope ties release to the transaction.
            $connection->select('select pg_advisory_xact_lock(hashtext(?))', [$key]);
        }
    }

    /**
     * Drop one nesting level of the in-process key. The pg side needs no
     * release call: the advisory lock goes away with the transaction.
     */
    public static function release(string $key): void
    {
        if ((self::$depth[$key] ?? 0) > 1) {
            self::$depth[$key]--;
        } else {
            unset(self::$depth[$key]);
        }
    }

    /** Current nesting depth for a key in this process (0 = free). */
    public static function depth(string $key): int
    {
        return self::$depth[$key] ?? 0;
    }
}

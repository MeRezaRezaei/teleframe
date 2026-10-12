<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Ingest;

use Illuminate\Database\ConnectionInterface;
use MeRezaRezaei\Teleframe\Laravel\Models\UpdateRoutingRule;

/**
 * Default-route pre-seed for a freshly discovered account (Cycle-5 item 2
 * "daemon auto-registration"): when the daemon seats an account it did not
 * drive before, this writes the account-level default rule — a
 * tg_update_routing row at the catch-all peer (0, 0) with mode store_only —
 * into the DB source of truth and refreshes the account's Redis-2 routing
 * cache, so a route row exists for the account and the UpdateRouter answers
 * for its peers immediately (store_only default, flip-able to act_on in
 * real time for the whole account).
 *
 * Best-effort by design: a host without the routing table (or without a
 * working bus redis) skips a step silently — a missing default row is
 * semantically identical to store_only (the UpdateRouter's own default), so
 * discovery never hard-fails on a missing settings surface.
 *
 * PRECEDENCE (the semantic decision): the catch-all is a DEFAULT, not an
 * authority. seed() writes the (0,0) row ONLY when it is absent; an existing
 * catch-all row is never rewritten, so a re-seat (or a re-seed after the
 * operator's `teleframe:routing <id> --account-wide --mode=act_on`) can
 * never silently revert an explicit act_on decision back to store_only. The
 * cache is still refreshed on every call, so a host that edited the row
 * out-of-band still converges.
 */
final class AccountRouteSeeder
{
    public function __construct(
        private readonly ConnectionInterface $db,
        private readonly ?UpdateRoutingCache $cache = null,
    ) {}

    /**
     * Write the account-level default rule row (idempotent) and refresh the
     * account's routing cache so the router answers it without a DB hit.
     *
     * Insert-if-absent: an existing catch-all row is left exactly as it is
     * (an explicit operator decision outranks the seeder), only a missing
     * one is created as store_only.
     *
     * @return int Number of routing rows printed for the account (0 = no
     *             routing surface, the store_only default still applies).
     */
    public function seed(int $accountId): int
    {
        try {
            $exists = $this->db->table('tg_update_routing')
                ->where('account_id', $accountId)
                ->where('peer_type', UpdateRoutingRule::DEFAULT_PEER_TYPE)
                ->where('peer_id', UpdateRoutingRule::DEFAULT_PEER_ID)
                ->exists();

            if (! $exists) {
                $this->db->table('tg_update_routing')->insert([
                    'account_id' => $accountId,
                    'peer_type' => UpdateRoutingRule::DEFAULT_PEER_TYPE,
                    'peer_id' => UpdateRoutingRule::DEFAULT_PEER_ID,
                    'mode' => UpdateRoutingRule::MODE_STORE_ONLY,
                    'priority' => 0,
                ]);
            }
        } catch (\Exception) {
            // routing table not migrated yet — a missing default row is
            // store_only anyway, so discovery proceeds silently
            return 0;
        }

        return $this->cache?->refresh($accountId) ?? 0;
    }

    /**
     * Drop the account's hot routing cache (best-effort): called when the
     * daemon unseats the account, so no stale listen/ignore state is served
     * for a departed account. The DB rule row is kept intentionally — it is
     * inert history and re-seating the account later simply re-prints it.
     */
    public function forget(int $accountId): void
    {
        if ($this->cache !== null) {
            $this->cache->forget($accountId);
        }
    }
}

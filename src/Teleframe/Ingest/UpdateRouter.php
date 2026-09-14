<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Ingest;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Laravel\Models\UpdateRoutingRule;

/**
 * Update router — the verbatim's loop-prevention classification layer.
 *
 * Owner verbatim 2026-09-14: "when i make a notify channel for one of the
 * apps i should not handle the updates of that channel as new event but
 * only update that only stores the final truth... so we can controll the
 * things we are going to listen on or ignore in real time".
 *
 * Given (account_id, peer_type, peer_id), returns the routing decision:
 * - act_on:    store the fact AND emit the UpdateStored event (app reacts);
 * - store_only: store the fact but skip event emission (notify/log channels).
 *
 * The verbatim's "two Redis" path: DB is source of truth, Redis holds the
 * hot-reloaded cache. This service answers from the Redis-2 cache first
 * (real-time listen/ignore control without a DB hit per update) and falls
 * back to the DB source of truth on a cache miss. All decisions fall back
 * to act_on (safe default: unknown sources are not silently silenced).
 */
final class UpdateRouter
{
    public function __construct(
        private readonly ConnectionInterface $db,
        private readonly ?UpdateRoutingCache $cache = null,
    ) {}

    /**
     * The stored rule mode for a (account, peer) pair, or null when no
     * explicit rule exists. Unlike mode(), this has NO default — callers
     * that need to distinguish "explicitly store_only" from "no rule"
     * (e.g. the self-originated escape hatch) use this.
     *
     * Redis-2 cache first, then DB source of truth (a cache miss queries
     * the DB exactly as before — behavior identical without the cache).
     */
    public function explicitMode(int $accountId, int $peerType, int $peerId): ?string
    {
        if ($this->cache !== null) {
            $cached = $this->cache->mode($accountId, $peerType, $peerId);
            if ($cached !== null) {
                return $cached;
            }
        }

        try {
            $rule = $this->db->table('tg_update_routing')
                ->where('account_id', $accountId)
                ->where('peer_type', $peerType)
                ->where('peer_id', $peerId)
                ->orderByDesc('priority')
                ->first();
        } catch (\Exception) {
            return null;
        }

        return $rule === null ? null : $rule->mode;
    }

    /**
     * Determine the routing mode for a (account, peer) pair.
     *
     * @return string UpdateRoutingRule::MODE_ACT_ON | UpdateRoutingRule::MODE_STORE_ONLY
     */
    public function mode(int $accountId, int $peerType, int $peerId): string
    {
        if ($this->cache !== null) {
            $cached = $this->cache->mode($accountId, $peerType, $peerId);
            if ($cached !== null) {
                return $cached;
            }
        }

        try {
            $rule = $this->db->table('tg_update_routing')
                ->where('account_id', $accountId)
                ->where('peer_type', $peerType)
                ->where('peer_id', $peerId)
                ->orderByDesc('priority')
                ->first();
        } catch (\Exception $e) {
            // Table not yet migrated — safe default: act on everything.
            return UpdateRoutingRule::MODE_ACT_ON;
        }

        if ($rule === null) {
            return UpdateRoutingRule::MODE_ACT_ON;
        }

        return $rule->mode;
    }

    /**
     * Classify a decoded TL update's peer and return the routing decision.
     *
     * The peer lives in the update payload under 'peer_id' (most update types)
     * or 'channel_id' (channel-specific updates). If no peer is extractable,
     * the update defaults to act_on (safe default: unknown sources are not
     * silently silenced).
     *
     * @param  array<string, mixed>  $payload  decoded TL update
     */
    public function classify(int $accountId, array $payload): string
    {
        $peerType = (int) ($payload['peer_id']['_type'] ?? 0);
        $peerId = (int) ($payload['peer_id']['_id'] ?? 0);

        if ($peerType === 0 && $peerId === 0) {
            $channelId = (int) ($payload['channel_id'] ?? 0);
            if ($channelId !== 0) {
                $peerType = 2; // Telegram peer_type enum: 2 = channel
                $peerId = $channelId;
            }
        }

        if ($peerType === 0) {
            return UpdateRoutingRule::MODE_ACT_ON;
        }

        return $this->mode($accountId, $peerType, $peerId);
    }
}

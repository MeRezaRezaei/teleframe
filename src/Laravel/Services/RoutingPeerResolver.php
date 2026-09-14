<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Services;

use Illuminate\Database\ConnectionInterface;
use MeRezaRezaei\Teleframe\Laravel\Models\UpdateRoutingRule;
use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerShapeTool;

/**
 * Behaviour→core link — the verbatim's "connect the facts" seam.
 *
 * Owner verbatim 2026-09-14: "the core is the telegram nf5 databae the
 * rest is going to only make new tables and link their data to the nf 5
 * core then we can identify the exact set of data and how we should treat
 * them just becuase they are connectd to  the facts that telegram is
 * giving us".
 *
 * tg_update_routing (a behaviour-control table, 'tg' prefix — NOT part of
 * the core) stores peers as (peer_type, peer_id). Those peers live in the
 * core as facts: tf_users (peer_type 1) and tf_chats (peer_type 2 chat /
 * 3 channel — the TL ctors chat, chatForbidden, channel, channelForbidden).
 * This resolver returns the linked core fact row for a rule so the app can
 * "identify the exact set of data" behind a routing decision; a rule whose
 * peer has no core fact yet resolves to null — the settings can exist
 * before the fact arrives (the mirror will create the row on first update).
 */
final class RoutingPeerResolver
{
    public function __construct(
        private readonly ConnectionInterface $db,
    ) {}

    /**
     * The NF5 core table that holds peers of the given canonical type
     * (PeerShapeTool enum: 1=user 2=chat 3=channel).
     */
    public function coreTable(int $peerType): ?string
    {
        return match ($peerType) {
            PeerShapeTool::PEER_USER => 'tf_users',
            PeerShapeTool::PEER_CHAT, PeerShapeTool::PEER_CHANNEL => 'tf_chats',
            default => null,
        };
    }

    /**
     * Resolve a routing rule to its linked core fact row, or null when the
     * fact has not been ingested yet (or the peer type is unknown).
     *
     * @return array<string, mixed>|null
     */
    public function resolve(UpdateRoutingRule $rule): ?array
    {
        $table = $this->coreTable((int) $rule->peer_type);
        if ($table === null) {
            return null;
        }

        $row = $this->db->table($table)
            ->where('account_id', (int) $rule->account_id)
            ->where('id', (int) $rule->peer_id)
            ->first();

        return $row === null ? null : (array) $row;
    }
}

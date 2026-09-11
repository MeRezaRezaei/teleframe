<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Repositories;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Union across tf_messages and tf_messages_service for a single peer — the
 * two tables share the same columns by construction (MirrorCtorSplitter), and
 * Telegram guarantees disjoint id spaces, so UNION ALL cannot duplicate.
 */
final class MessagesUnion
{
    public static function forPeer(int $accountId, int $peerType, int $peerId): Builder
    {
        $base = fn () => DB::table('tf_messages')
            ->where('account_id', $accountId)
            ->where('peer_type', $peerType)
            ->where('peer_id', $peerId);
        $service = fn () => DB::table('tf_messages_service')
            ->where('account_id', $accountId)
            ->where('peer_type', $peerType)
            ->where('peer_id', $peerId);

        return $base()->unionAll($service())->orderByDesc('id');
    }
}

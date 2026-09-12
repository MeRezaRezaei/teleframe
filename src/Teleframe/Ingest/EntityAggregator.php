<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Ingest;

use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUser;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChat;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannel;
use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerIdTool;

/**
 * Referenced-entity aggregation: resolve the domain model for a
 * user/chat/channel by (tenant, telegram id).
 *
 * In the TDLib-style schema, each entity domain has ONE table (tf_users,
 * tf_chats, tf_channels) with the Telegram native ID as PK (or part of
 * the composite PK). Resolution is a simple lookup by (id, account_id).
 */
final class EntityAggregator
{
    /**
     * Deleted users do not resolve: a row whose latest ingested
     * constructor carries the deleted flag is upstream-gone, so it
     * behaves as absent (per-constructor-era contract preserved).
     */
    public function user(int $accountId, int $tgId): ?TlUser
    {
        return TlUser::query()
            ->where('id', $tgId)
            ->where('account_id', $accountId)
            ->where('is_deleted', false)
            ->first();
    }

    public function chat(int $accountId, int $tgId): ?TlChat
    {
        return TlChat::query()
            ->where('id', $tgId)
            ->where('account_id', $accountId)
            ->first();
    }

    /**
     * Channel-facing alias: channels have their own domain table.
     */
    public function channel(int $accountId, int $tgId): ?TlChannel
    {
        return TlChannel::query()
            ->where('id', $tgId)
            ->where('account_id', $accountId)
            ->first();
    }

    /**
     * Resolve a peer-long value to its domain model.
     *
     * @param class-string<TlAnchorModel>|null $modelClass Override the default model class
     */
    public function resolvePeer(int $accountId, int $peerLong, ?string $modelClass = null): ?TlAnchorModel
    {
        $decoded = PeerIdTool::decode($peerLong);

        $class = match ($decoded['kind']) {
            'user' => $modelClass ?? TlUser::class,
            'chat' => $modelClass ?? TlChat::class,
            default => $modelClass ?? TlChannel::class,
        };

        if (!class_exists($class)) {
            return null;
        }

        return $class::query()
            ->where('id', $decoded['id'])
            ->where('account_id', $accountId)
            ->first();
    }
}

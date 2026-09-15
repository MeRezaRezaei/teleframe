<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Ingest;

use MeRezaRezaei\Teleframe\Mirror\Models\TfChannel;
use MeRezaRezaei\Teleframe\Mirror\Models\TfChat;
use MeRezaRezaei\Teleframe\Mirror\Models\TfUser;
use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerIdTool;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

/**
 * Referenced-entity aggregation: resolve the domain model for a
 * user/chat/channel by (tenant, telegram id).
 *
 * Resolution targets the hand-authored curated identity dial (tf_users /
 * tf_chats / tf_channels — the peer FK targets for peer_type 1/2/3), NOT
 * the purged generated mirror surface. Each entity domain has ONE table
 * with the Telegram native ID as (part of) the composite (account_id, id)
 * key; resolution is a simple lookup by (id, account_id) against the
 * curated Tf* models.
 */
final class EntityAggregator
{
    /**
     * Deleted users do not resolve: a row whose latest ingested
     * constructor carries the deleted flag is upstream-gone, so it
     * behaves as absent (curated tf_users boolean column `deleted`).
     */
    public function user(int $accountId, int $tgId): ?TfUser
    {
        $user = TfUser::query()
            ->where('id', $tgId)
            ->where('account_id', $accountId)
            ->where('deleted', false)
            ->first();

        return $user instanceof TfUser ? $user : null;
    }

    public function chat(int $accountId, int $tgId): ?TfChat
    {
        $chat = TfChat::query()
            ->where('id', $tgId)
            ->where('account_id', $accountId)
            ->first();

        return $chat instanceof TfChat ? $chat : null;
    }

    /**
     * Channel-facing alias: channels have their own domain table.
     */
    public function channel(int $accountId, int $tgId): ?TfChannel
    {
        $channel = TfChannel::query()
            ->where('id', $tgId)
            ->where('account_id', $accountId)
            ->first();

        return $channel instanceof TfChannel ? $channel : null;
    }

    /**
     * Resolve a peer-long value to its domain model.
     *
     * @param  class-string<TfMirrorModel>|null  $modelClass  Override the default model class
     */
    public function resolvePeer(int $accountId, int $peerLong, ?string $modelClass = null): ?TfMirrorModel
    {
        $decoded = PeerIdTool::decode($peerLong);

        $class = match ($decoded['kind']) {
            'user' => $modelClass ?? TfUser::class,
            'chat' => $modelClass ?? TfChat::class,
            default => $modelClass ?? TfChannel::class,
        };

        if (! class_exists($class)) {
            return null;
        }

        $peer = $class::query()
            ->where('id', $decoded['id'])
            ->where('account_id', $accountId)
            ->first();

        return $peer instanceof TfMirrorModel ? $peer : null;
    }
}

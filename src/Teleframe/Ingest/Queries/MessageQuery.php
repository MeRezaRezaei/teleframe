<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Ingest\Queries;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * Typed query builder for tf_messages: message history, keyset pagination,
 * and peer-scoped retrieval. Follows TDLib's "no JOINs, query within domain"
 * principle.
 */
final class MessageQuery extends Builder
{
    public function __construct(QueryBuilder $query)
    {
        parent::__construct($query);
    }

    /** Scope to one account's messages. */
    public function forAccount(int $accountId): self
    {
        return $this->where('account_id', $accountId);
    }

    /** Scope to messages belonging to one peer (chat/user/channel). */
    public function forPeer(int $peerId): self
    {
        return $this->where('peer_id', $peerId);
    }

    /**
     * Most-recent-N ordering (TDLib default: descending message_id).
     *
     * Named "recent" instead of "latest" to avoid collision with
     * Illuminate\Database\Eloquent\Builder::latest().
     */
    public function recent(int $limit = 20): self
    {
        return $this->orderByDesc('message_id')->limit($limit);
    }

    /** Messages newer than a unix timestamp. */
    public function since(int $unixTimestamp): self
    {
        return $this->where('created_at', '>=', date('Y-m-d H:i:s', $unixTimestamp));
    }

    /**
     * Keyset pagination (TDLib pattern): fetch messages before a given
     * message_id, avoiding OFFSET. Cursor = (message_id).
     */
    public function beforeId(int $messageId, int $limit = 50): self
    {
        return $this->where('message_id', '<', $messageId)
            ->orderByDesc('message_id')
            ->limit($limit);
    }

    /**
     * Keyset pagination forward: fetch messages after a given message_id.
     */
    public function afterId(int $messageId, int $limit = 50): self
    {
        return $this->where('message_id', '>', $messageId)
            ->orderBy('message_id')
            ->limit($limit);
    }
}

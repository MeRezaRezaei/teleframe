<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Ingest\Queries;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * Typed query builder for tf_messages: message history, keyset pagination,
 * and peer-scoped retrieval. Follows TDLib's "no JOINs, query within domain"
 * principle — column filters only, no cross-table joins.
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

    /**
     * Scope to messages belonging to one peer (chat/user/channel).
     *
     * The curated tf_messages dial stores peers as a (peer_type, peer_id)
     * pair — a user with telegram id 1001 and a chat with id 1001 are
     * different peers. When $peerType is provided, both columns are filtered
     * to disambiguate. Callers that pass only $peerId get the legacy
     * broad-scope filter (matches any peer type with that id).
     */
    public function forPeer(int $peerId, ?int $peerType = null): self
    {
        if ($peerType !== null) {
            return $this->where('peer_type', $peerType)->where('peer_id', $peerId);
        }

        return $this->where('peer_id', $peerId);
    }

    /**
     * Most-recent-N ordering (TDLib default: descending message id).
     *
     * Named "recent" instead of "latest" to avoid collision with
     * Illuminate\Database\Eloquent\Builder::latest().
     */
    public function recent(int $limit = 20): self
    {
        /** @var self $builder */
        $builder = $this->orderByDesc('id')->limit($limit);

        return $builder;
    }

    /**
     * Messages with a wire date at or after a unix timestamp.
     *
     * Filters the `date` column, which is the Telegram wire message date
     * (unix epoch seconds) — NOT an ingest/created timestamp. The curated
     * tf_messages dial has no ingest-time column.
     */
    public function since(int $unixTimestamp): self
    {
        return $this->where('date', '>=', $unixTimestamp);
    }

    /**
     * Keyset pagination (TDLib pattern): fetch messages before a given
     * message id, avoiding OFFSET. Cursor = (id).
     */
    public function beforeId(int $messageId, int $limit = 50): self
    {
        /** @var self $builder */
        $builder = $this->where('id', '<', $messageId)
            ->orderByDesc('id')
            ->limit($limit);

        return $builder;
    }

    /**
     * Keyset pagination forward: fetch messages after a given message id.
     */
    public function afterId(int $messageId, int $limit = 50): self
    {
        /** @var self $builder */
        $builder = $this->where('id', '>', $messageId)
            ->orderBy('id')
            ->limit($limit);

        return $builder;
    }
}

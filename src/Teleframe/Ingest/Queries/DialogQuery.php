<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Ingest\Queries;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * Typed query builder for tf_dialogs: dialog list with pin/folder/unread
 * filtering and keyset pagination.
 */
final class DialogQuery extends Builder
{
    public function __construct(QueryBuilder $query)
    {
        parent::__construct($query);
    }

    public function forAccount(int $accountId): self
    {
        return $this->where('account_id', $accountId);
    }

    /** Pinned dialogs first, then by top_message_id descending. */
    public function ordered(): self
    {
        return $this->orderByDesc('is_pinned')
            ->orderByDesc('top_message_id');
    }

    /** Only pinned dialogs. */
    public function pinned(): self
    {
        return $this->where('is_pinned', true);
    }

    /** Only dialogs with unread messages. */
    public function withUnread(): self
    {
        return $this->where('unread_count', '>', 0);
    }

    /** Scope to a folder. */
    public function inFolder(int $folderId): self
    {
        return $this->where('folder_id', $folderId);
    }
}

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

    /**
     * Pinned dialogs first, then by top_message descending.
     *
     * The columns are `pinned` and `top_message` — see the shipped
     * 2026_09_14_200004 migration. The old spellings (`is_pinned`,
     * `top_message_id`) were not columns at all, and sqlite resolves an
     * unknown column in ORDER BY as a constant, so the ordering silently
     * degraded to row order instead of raising.
     */
    public function ordered(): self
    {
        /** @var self $builder */
        $builder = $this->orderByDesc('pinned')
            ->orderByDesc('top_message');

        return $builder;
    }

    /** Only pinned dialogs. */
    public function pinned(): self
    {
        return $this->where('pinned', true);
    }

    /** Only dialogs with unread messages. */
    public function withUnread(): self
    {
        return $this->where('unread_count', '>', 0);
    }

    /**
     * Scope to a folder.
     *
     * `folder_id` is a 1:1 FACT on the CHILD table `tf_dialogs_folder_id`,
     * keyed by the same (account_id, peer_type, peer_id) as the parent, so a
     * dialog with no folder has no row. Filtering the parent on `folder_id`
     * was a guaranteed SQL error rather than an empty result; the correlated
     * EXISTS is what the flattened shape requires.
     */
    public function inFolder(int $folderId): self
    {
        /** @var self $builder */
        $builder = $this->whereExists(function (QueryBuilder $child) use ($folderId): void {
            $child->selectRaw('1')
                ->from('tf_dialogs_folder_id')
                ->whereColumn('tf_dialogs_folder_id.account_id', 'tf_dialogs.account_id')
                ->whereColumn('tf_dialogs_folder_id.peer_type', 'tf_dialogs.peer_type')
                ->whereColumn('tf_dialogs_folder_id.peer_id', 'tf_dialogs.peer_id')
                ->where('tf_dialogs_folder_id.folder_id', $folderId);
        });

        return $builder;
    }
}

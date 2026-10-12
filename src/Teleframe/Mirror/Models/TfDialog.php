<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Builder;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

/**
 * NF5 mirror dialog (Dialog union: dialog / dialogFolder ctors).
 *
 * Table tf_dialogs — unlike the other identity tables a dialog has no
 * telegram-supplied id: its identity IS its peer, so the PK is the
 * composite (account_id, peer_type, peer_id). The peer shape is inline
 * on the parent row (tinyInt peer_type, bigint peer_id).
 *
 * Counters shared by both ctors and the non-flag top_message /
 * read_inbox_max_id / ... are inline with wire-false defaults.
 * flags.?true → BOOLEAN NOT NULL DEFAULT FALSE.
 * Optional/flag-gated facts (pts, draft, folder_id, ttl_period,
 * notify_settings, folder) live in tf_dialogs_* 1:1 children — row
 * existence = fact existence.
 *
 * Because the PK is composite we use the TfUpdateDifference
 * Builder-accessor pattern instead of Eloquent hasOne.
 *
 * @property int $account_id
 * @property int $peer_type
 * @property int $peer_id
 * @property string $constructor
 * @property int $top_message
 * @property int $read_inbox_max_id
 * @property int $read_outbox_max_id
 * @property int $unread_count
 * @property int $unread_mentions_count
 * @property int $unread_reactions_count
 * @property int $unread_poll_votes_count
 * @property int $unread_muted_peers_count
 * @property int $unread_unmuted_peers_count
 * @property int $unread_muted_messages_count
 * @property int $unread_unmuted_messages_count
 * @property bool $pinned
 * @property bool $unread_mark
 * @property bool $view_forum_as_messages
 */
final class TfDialog extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_dialogs';

    protected $primaryKey = 'account_id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'peer_type' => 'integer',
        'peer_id' => 'integer',
        'top_message' => 'integer',
        'read_inbox_max_id' => 'integer',
        'read_outbox_max_id' => 'integer',
        'unread_count' => 'integer',
        'unread_mentions_count' => 'integer',
        'unread_reactions_count' => 'integer',
        'unread_poll_votes_count' => 'integer',
        'unread_muted_peers_count' => 'integer',
        'unread_unmuted_peers_count' => 'integer',
        'unread_muted_messages_count' => 'integer',
        'unread_unmuted_messages_count' => 'integer',
        'pinned' => 'boolean',
        'unread_mark' => 'boolean',
        'view_forum_as_messages' => 'boolean',
    ];

    /**
     * @return array<string, int>
     */
    public function childKey(): array
    {
        return [
            'account_id' => (int) $this->account_id,
            'peer_type' => (int) $this->peer_type,
            'peer_id' => (int) $this->peer_id,
        ];
    }

    /**
     * @param  class-string<MirrorChildModel>  $child
     */
    private function child(string $child): Builder
    {
        return $child::query()
            ->where('account_id', (int) $this->account_id)
            ->where('peer_type', (int) $this->peer_type)
            ->where('peer_id', (int) $this->peer_id);
    }

    public function notifySettings(): Builder
    {
        return $this->child(TfDialogsNotifySettings::class);
    }

    public function pts(): Builder
    {
        return $this->child(TfDialogsPts::class);
    }

    public function draft(): Builder
    {
        return $this->child(TfDialogsDraft::class);
    }

    public function folderId(): Builder
    {
        return $this->child(TfDialogsFolderId::class);
    }

    public function ttlPeriod(): Builder
    {
        return $this->child(TfDialogsTtlPeriod::class);
    }

    public function folder(): Builder
    {
        return $this->child(TfDialogsFolder::class);
    }
}

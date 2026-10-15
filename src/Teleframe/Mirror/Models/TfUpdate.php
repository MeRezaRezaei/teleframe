<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

/**
 * Hand-authored NF5 mirror of the Update* fact log (table tf_updates).
 *
 * Real key is composite (account_id, seq, position); Eloquent's scalar
 * primaryKey is a best-effort account_id stand-in because composite keys
 * have no Eloquent equivalent. All lookups are account-scoped relations via
 * {@see child()}, never ->find() across accounts.
 *
 * @property int $account_id
 * @property int $seq
 * @property int $position
 * @property string $constructor
 */
final class TfUpdate extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_updates';

    protected $guarded = [];

    protected $primaryKey = 'account_id';

    protected $casts = [
        'seq' => 'int',
        'position' => 'int',
    ];

    /** @return Builder Query for the 1:1 pts child of this update. */
    public function pts(): Builder
    {
        return $this->child(TfUpdatePts::class);
    }

    public function ptsCount(): Builder
    {
        return $this->child(TfUpdatePtsCount::class);
    }

    public function qts(): Builder
    {
        return $this->child(TfUpdateQts::class);
    }

    public function date(): Builder
    {
        return $this->child(TfUpdateDate::class);
    }

    public function message(): Builder
    {
        return $this->child(TfUpdateMessage::class);
    }

    /** 1:N—the Vector<int> of updateDeleteMessages; caller orders by slot. */
    public function messages(): Builder
    {
        return $this->child(TfUpdateMessages::class);
    }

    public function peer(): Builder
    {
        return $this->child(TfUpdatePeer::class);
    }

    public function from(): Builder
    {
        return $this->child(TfUpdateFrom::class);
    }

    public function maxId(): Builder
    {
        return $this->child(TfUpdateMaxId::class);
    }

    public function stillUnreadCount(): Builder
    {
        return $this->child(TfUpdateStillUnreadCount::class);
    }

    public function topMsgId(): Builder
    {
        return $this->child(TfUpdateTopMsgId::class);
    }

    public function folderId(): Builder
    {
        return $this->child(TfUpdateFolderId::class);
    }

    public function status(): Builder
    {
        return $this->child(TfUpdateStatus::class);
    }

    public function action(): Builder
    {
        return $this->child(TfUpdateAction::class);
    }

    // -----------------------------------------------------------------------
    // HORIZONTAL — the entity axis the declared foreign keys cannot carry.
    // See HorizontalEntityRelations for why this is a model-layer declaration
    // and not a constraint.
    //
    // `channel_id` / `chat_id` / `user_id` are COLUMNS on this table, not
    // children, since `2026_10_10_000100_fold_update_scalar_children_onto_the_update_fact.php`
    // — the `Update` union declares each as a bare `long` (api.tl:395, :318,
    // :320), so the wire nests nothing there. Each is 0 on every update whose
    // ctor does not carry it, which is why these are the SCALAR half of the
    // horizontal axis and `tf_updates_peer` / `tf_updates_from` are the
    // polymorphic half.
    //
    // They are NOT named `peer()`-shaped and they carry NO discriminator: each
    // column names exactly one entity, so there is nothing to branch on and a
    // `belongsTo` is the whole relation. No FK is declared on any of them, and
    // none can be: the column exists on every row of the table whether or not
    // that ctor sent a peer, so a constraint would have to accept the 0 that
    // stands for "this update says nothing about that peer".
    // -----------------------------------------------------------------------

    /** The user this update names, via `tf_updates`.`user_id`. 0 when the ctor carries none. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(TfUser::class, 'user_id', 'id');
    }

    /** The chat this update names, via `tf_updates`.`chat_id`. 0 when the ctor carries none. */
    public function chat(): BelongsTo
    {
        return $this->belongsTo(TfChat::class, 'chat_id', 'id');
    }

    /** The channel this update names, via `tf_updates`.`channel_id`. 0 when the ctor carries none. */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(TfChannel::class, 'channel_id', 'id');
    }

    /**
     * The full composite key of this row, for writing a child fact:
     * `$update->pts()->create([...$update->childKey(), 'pts' => 5])`.
     *
     * @return array<string, int>
     */
    public function childKey(): array
    {
        return [
            'account_id' => (int) $this->account_id,
            'seq' => (int) $this->seq,
            'position' => (int) $this->position,
        ];
    }

    /**
     * Account-scoped query for one optional-fact child of this row, keyed by
     * the full composite parent key.
     *
     * @param  class-string<MirrorChildModel>  $child
     */
    private function child(string $child): Builder
    {
        return $child::query()
            ->where('account_id', (int) $this->account_id)
            ->where('seq', (int) $this->seq)
            ->where('position', (int) $this->position);
    }
}

<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Builder;
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

    public function channel(): Builder
    {
        return $this->child(TfUpdateChannel::class);
    }

    public function chat(): Builder
    {
        return $this->child(TfUpdateChat::class);
    }

    public function user(): Builder
    {
        return $this->child(TfUpdateUser::class);
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

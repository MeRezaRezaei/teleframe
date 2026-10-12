<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

/**
 * NF5 dialog filter pinned peer (Peer union) — table
 * tf_dialog_filters_pinned_peers. Real PK (account_id, id, position): `id` is
 * NOT unique, so it is a best-effort scalar stand-in; every query is
 * account-scoped and position-aware. Peer-pair holder — chat_id / user_id /
 * channel_id / msg_id are the union arms, `constructor` names the arm.
 * 1:N vector off the filter.
 */
final class TfDialogFilterPinnedPeers extends TfChildModel
{
    use PinsDeclaredForeignKeyLegs;

    use AccountScoped;

    protected $table = 'tf_dialog_filters_pinned_peers';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'position' => 'integer',
        'chat_id' => 'integer',
        'user_id' => 'integer',
        'access_hash' => 'integer',
        'channel_id' => 'integer',
        'msg_id' => 'integer',
    ];

    public function filter(): BelongsTo
    {
        return $this->belongsTo(TfDialogFilter::class, 'id', 'id');
    }
}
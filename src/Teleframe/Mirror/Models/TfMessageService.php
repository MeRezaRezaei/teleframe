<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

/**
 * NF5 mirror service message — the messageService ctor split out of the
 * Message union into its own subject table (MirrorCtorSplitter contract).
 *
 * Same (account_id, peer_type, peer_id, id) shape as TfMessage so the
 * MessagesUnion UNION ALL works; discriminated by constructor = 'messageService'.
 */
final class TfMessageService extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_messages_service';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'peer_type' => 'integer',
        'peer_id' => 'integer',
        'date' => 'integer',
        'out' => 'boolean',
        'mentioned' => 'boolean',
        'media_unread' => 'boolean',
        'reactions_are_possible' => 'boolean',
        'silent' => 'boolean',
        'post' => 'boolean',
        'legacy' => 'boolean',
    ];

    public function from(): HasOne
    {
        return $this->hasOne(TfMessageServiceFromId::class, 'id', 'id');
    }

    public function replyTo(): HasOne
    {
        return $this->hasOne(TfMessageServiceReplyTo::class, 'id', 'id');
    }

    public function action(): HasOne
    {
        return $this->hasOne(TfMessageAction::class, 'id', 'id');
    }

    public function reactions(): HasOne
    {
        return $this->hasOne(TfMessageServiceReactions::class, 'id', 'id');
    }

    public function ttlPeriod(): HasOne
    {
        return $this->hasOne(TfMessageServiceTtlPeriod::class, 'id', 'id');
    }
}

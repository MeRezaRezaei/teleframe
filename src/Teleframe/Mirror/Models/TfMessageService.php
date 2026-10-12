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
 * Shares the (account_id, peer_type, peer_id, id) KEY shape with TfMessage —
 * that is what lets MessagesUnion filter one peer across both tables and
 * order the merged result by id. It does NOT share the full column set, and
 * never did: MirrorCtorSplitter builds each table by intersecting the catalog
 * with one constructor's params, so tf_messages_service has 13 columns where
 * tf_messages has 22, sharing 12. MessagesUnion therefore projects explicitly
 * and pads a side's missing columns with NULL. Discriminated by
 * constructor = 'messageService'.
 */
final class TfMessageService extends TfMirrorModel
{
    use PinsDeclaredForeignKeyLegs;

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

    public function savedPeer(): HasOne
    {
        return $this->hasOne(TfMessageServiceSavedPeerId::class, 'id', 'id');
    }
}

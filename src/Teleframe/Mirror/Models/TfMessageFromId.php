<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

/**
 * 1:1 child of tf_messages holding the message -> author reference, flattened
 * from Message.from_id (PeerUser / PeerChat / PeerChannel) into the inline
 * pair from_id_type (1=user 2=chat 3=channel) + from_id_id.
 *
 * LIMITATION — no author relation is declared, deliberately. The pair is a
 * polymorphic discriminator: one composite FK cannot branch on from_id_type,
 * and the peer tables disagree in key column count (2 vs the triad's 3), so no
 * single BelongsTo can be correct for all three branches. Declaring one would
 * silently return a wrong peer. Peer correctness is enforced in app code
 * instead, by PeerResolution::resolvePeerModel() + PeerIdTool — but note those
 * resolve to the Generated Tl* models, not to these Mirror models, so wiring
 * that here is a decision deliberately left to the Task-8 cross-domain FK work
 * named in TfMessage's own docblock.
 */
final class TfMessageFromId extends TfChildModel
{
    use PinsDeclaredForeignKeyLegs;

    use AccountScoped;

    protected $table = 'tf_messages_from_id';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'from_id_type' => 'integer',
        'from_id_id' => 'integer',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(TfMessage::class, 'id', 'id');
    }
}

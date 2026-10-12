<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

/**
 * The service-message twin of TfMessageSavedPeerId.
 *
 * `tf_messages_service_saved_peer_id` (2026_09_14_200010:755, PK at :766)
 * declares the same four columns and the same
 * (account_id, peer_type, peer_id, id) primary key as
 * `tf_messages_saved_peer_id` (2026_09_14_200010:268), so it is the same shape
 * under a disjoint id
 * space — which is why the class name carries the `Service` infix rather than
 * deriving from the table name (TfMessageSavedPeerId already owns the
 * non-service table).
 *
 * It exists because TfMessageService::savedPeer() (:72-75) forward-references
 * it. Naming the classes after their tables alone would have produced a fatal
 * redeclare against the plural twin.
 */
final class TfMessageServiceSavedPeerId extends TfChildModel
{
    use PinsDeclaredForeignKeyLegs;

    use AccountScoped;

    protected $table = 'tf_messages_service_saved_peer_id';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'saved_peer_id_type' => 'integer',
        'saved_peer_id_id' => 'integer',
    ];

    public function messageService(): BelongsTo
    {
        return $this->belongsTo(TfMessageService::class, 'id', 'id');
    }
}

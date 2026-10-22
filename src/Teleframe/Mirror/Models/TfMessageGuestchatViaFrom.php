<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

/**
 * 1:1 child of tf_messages holding the guestchat -> via_from reference,
 * flattened from the guestchat via_from peer into the inline pair
 * guestchat_via_from_type (1=user 2=chat 3=channel) + guestchat_via_from_id.
 */
final class TfMessageGuestchatViaFrom extends TfChildModel
{
    use PinsDeclaredForeignKeyLegs;

    use AccountScoped;

    protected $table = 'tf_messages_guestchat_via_from';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'guestchat_via_from_type' => 'integer',
        'guestchat_via_from_id' => 'integer',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(TfMessage::class, 'id', 'id');
    }
}
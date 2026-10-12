<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

/**
 * Positioned 1:N vector child of tf_messages (message.entities payload,
 * MessageEntity union, 25 ctors). position preserved per vector element.
 */
final class TfMessageEntity extends TfChildModel
{
    use PinsDeclaredForeignKeyLegs;

    use ResolvesMessagePeerScope;

    use AccountScoped;

    protected $table = 'tf_messages_entities';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'position' => 'integer',
        'offset' => 'integer',
        'length' => 'integer',
        'user_id' => 'integer',
        'document_id' => 'integer',
        'date' => 'integer',
        'collapsed' => 'boolean',
        'relative' => 'boolean',
        'short_time' => 'boolean',
        'long_time' => 'boolean',
        'short_date' => 'boolean',
        'long_date' => 'boolean',
        'day_of_week' => 'boolean',
    ];

    /**
     * The ENCLOSING MESSAGE, joined on the message's FULL peer-scoped key.
     *
     * NOT `('id','id')` any more. Telegram numbers messages PER CHAT — 136 of
     * one real account's dialogs hold a message with id=1 — so `id` is unique
     * only WITHIN (account_id, peer_type, peer_id). A belongsTo on `id` alone
     * resolved to whichever dialog happened to be written last, which is the
     * same cross-dialog collapse the primary key was widened to end, one level
     * up in the ORM. `account_id` is still supplied by the AccountScope global
     * scope, exactly as before.
     */
    public function message(): BelongsTo
    {
        return $this->pinPeerScopeBelongsTo($this->belongsTo(TfMessage::class, 'id', 'id'));
    }
}

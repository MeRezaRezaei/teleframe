<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

/**
 * Eloquent model for the shipped mirror table `tf_messages_reply_markup`.
 *
 * Columns, casts and relations are REFLECTED from the executed shipped DDL, not
 * transcribed and not inferred from names — see ModelGenerator. A relation
 * appears here only where the engine DECLARES a foreign key.
 *
 * Relations declared on this table: 2.
 */
final class TfMessagesReplyMarkup extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_messages_reply_markup';

    protected $guarded = [];

    /** No autoincrement anywhere on the mirror: keys are Telegram-native. */
    public $incrementing = false;

    /**
     * Eloquent gets ONE scalar key. The real key is the composite (account_id, peer_type, peer_id, id),
     * which the database enforces; `id` is total only WITHIN an account, and
     * AccountScope guarantees that. So `find()` is account-scoped by construction:
     * reach for a relation or an explicit where, never a bare find().
     */
    protected $primaryKey = 'id';

    public $timestamps = false;

    /**
     * Derived from the type the ENGINE reports for each column — never from a
     * docblock, and never from SQLite, whose affinity collapses integer(),
     * bigInteger() and tinyInteger() alike and drops width and signedness.
     *
     * @var array<string,string>
     */
    protected $casts = [
        'account_id' => 'int',
        'id' => 'int',
        'peer_type' => 'int',
        'peer_id' => 'int',
        'constructor' => 'string',
        'resize' => 'boolean',
        'single_use' => 'boolean',
        'selective' => 'boolean',
        'persistent' => 'boolean',
        'placeholder' => 'string',
    ];

    /** Declared foreign key: `tf_messages_reply_markup`(account_id, peer_type, peer_id, id) -> `tf_messages`(account_id, peer_type, peer_id, id). Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function messages(): BelongsTo
    {
        return $this->belongsTo(TlMessage::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_messages_reply_markup_rows`(account_id, peer_type, peer_id, id) -> `tf_messages_reply_markup`(account_id, peer_type, peer_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function messagesReplyMarkupRows(): HasMany
    {
        return $this->hasMany(TfMessagesReplyMarkupRows::class, 'id', 'id');
    }
}

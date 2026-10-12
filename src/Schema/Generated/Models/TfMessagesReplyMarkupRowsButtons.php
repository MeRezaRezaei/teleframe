<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

/**
 * Eloquent model for the shipped mirror table `tf_messages_reply_markup_rows_buttons`.
 *
 * Columns, casts and relations are REFLECTED from the executed shipped DDL, not
 * transcribed and not inferred from names — see ModelGenerator. A relation
 * appears here only where the engine DECLARES a foreign key.
 *
 * Relations declared on this table: 1.
 */
final class TfMessagesReplyMarkupRowsButtons extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_messages_reply_markup_rows_buttons';

    protected $guarded = [];

    /** No autoincrement anywhere on the mirror: keys are Telegram-native. */
    public $incrementing = false;

    /**
     * Eloquent gets ONE scalar key. The real key is the composite (account_id, peer_type, peer_id, id, position, parent_position),
     * which the database enforces; `parent_position` is total only WITHIN an account, and
     * AccountScope guarantees that. So `find()` is account-scoped by construction:
     * reach for a relation or an explicit where, never a bare find().
     */
    protected $primaryKey = 'parent_position';

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
        'position' => 'int',
        'parent_position' => 'int',
        'constructor' => 'string',
        'requires_password' => 'boolean',
        'same_peer' => 'boolean',
        'quiz' => 'boolean',
        'text' => 'string',
        'url' => 'string',
        'data' => 'string',
        'query' => 'string',
        'fwd_text' => 'string',
        'button_id' => 'int',
        'user_id' => 'int',
        'max_quantity' => 'int',
        'copy_text' => 'string',
    ];

    /** Declared foreign key: `tf_messages_reply_markup_rows_buttons`(account_id, peer_type, peer_id, id, parent_position) -> `tf_messages_reply_markup_rows`(account_id, peer_type, peer_id, id, position). Keyed on `parent_position` = `position`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function messagesReplyMarkupRows(): BelongsTo
    {
        return $this->belongsTo(TfMessagesReplyMarkupRows::class, 'parent_position', 'position');
    }
}

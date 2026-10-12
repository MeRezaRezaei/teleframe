<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

/**
 * Eloquent model for the shipped mirror table `tf_bot_inline_results_send_message`.
 *
 * Columns, casts and relations are REFLECTED from the executed shipped DDL, not
 * transcribed and not inferred from names — see ModelGenerator. A relation
 * appears here only where the engine DECLARES a foreign key.
 *
 * Relations declared on this table: 1.
 */
final class TfBotInlineResultsSendMessage extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_bot_inline_results_send_message';

    protected $guarded = [];

    /** No autoincrement anywhere on the mirror: keys are Telegram-native. */
    public $incrementing = false;

    /**
     * Eloquent gets ONE scalar key. The real key is the composite (account_id, id),
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
        'id' => 'string',
        'constructor' => 'string',
        'no_webpage' => 'boolean',
        'invert_media' => 'boolean',
        'shipping_address_requested' => 'boolean',
        'test' => 'boolean',
        'force_large_media' => 'boolean',
        'force_small_media' => 'boolean',
        'manual' => 'boolean',
        'safe' => 'boolean',
        'message' => 'string',
        'title' => 'string',
        'description' => 'string',
        'url' => 'string',
        'phone_number' => 'string',
        'first_name' => 'string',
        'last_name' => 'string',
        'vcard' => 'string',
        'address' => 'string',
        'provider' => 'string',
        'venue_id' => 'string',
        'venue_type' => 'string',
        'currency' => 'string',
        'total_amount' => 'int',
        'heading' => 'int',
        'period' => 'int',
        'proximity_notification_radius' => 'int',
    ];

    /** Declared foreign key: `tf_bot_inline_results_send_message`(account_id, id) -> `tf_bot_inline_results`(account_id, id). Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function botInlineResults(): BelongsTo
    {
        return $this->belongsTo(TfBotInlineResults::class, 'id', 'id');
    }
}

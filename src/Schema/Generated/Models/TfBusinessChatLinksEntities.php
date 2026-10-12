<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

/**
 * Eloquent model for the shipped mirror table `tf_business_chat_links_entities`.
 *
 * Columns, casts and relations are REFLECTED from the executed shipped DDL, not
 * transcribed and not inferred from names — see ModelGenerator. A relation
 * appears here only where the engine DECLARES a foreign key.
 *
 * Relations declared on this table: 1.
 */
final class TfBusinessChatLinksEntities extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_business_chat_links_entities';

    protected $guarded = [];

    /** No autoincrement anywhere on the mirror: keys are Telegram-native. */
    public $incrementing = false;

    /**
     * Eloquent gets ONE scalar key. The real key is the composite (account_id, link, position),
     * which the database enforces; `position` is total only WITHIN an account, and
     * AccountScope guarantees that. So `find()` is account-scoped by construction:
     * reach for a relation or an explicit where, never a bare find().
     */
    protected $primaryKey = 'position';

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
        'link' => 'string',
        'position' => 'int',
        'constructor' => 'string',
        'offset' => 'int',
        'length' => 'int',
        'language' => 'string',
        'url' => 'string',
        'user_id' => 'int',
        'document_id' => 'int',
        'collapsed' => 'boolean',
        'old_text' => 'string',
        'date' => 'int',
        'relative' => 'boolean',
        'short_time' => 'boolean',
        'long_time' => 'boolean',
        'short_date' => 'boolean',
        'long_date' => 'boolean',
        'day_of_week' => 'boolean',
    ];

    /** Declared foreign key: `tf_business_chat_links_entities`(account_id, link) -> `tf_business_chat_links`(account_id, link). Keyed on `link` = `link`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function businessChatLinks(): BelongsTo
    {
        return $this->belongsTo(TfBusinessChatLinks::class, 'link', 'link');
    }
}

<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

/**
 * Eloquent model for the shipped mirror table `tf_saved_star_gifts_collection_id`.
 *
 * Columns, casts and relations are REFLECTED from the executed shipped DDL, not
 * transcribed and not inferred from names — see ModelGenerator. A relation
 * appears here only where the engine DECLARES a foreign key.
 *
 * Relations declared on this table: 1.
 */
final class TfSavedStarGiftsCollectionId extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_saved_star_gifts_collection_id';

    protected $guarded = [];

    /** No autoincrement anywhere on the mirror: keys are Telegram-native. */
    public $incrementing = false;

    /**
     * Eloquent gets ONE scalar key. The real key is the composite (account_id, id, position),
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
        'id' => 'int',
        'position' => 'int',
        'value' => 'int',
    ];

    /** Declared foreign key: `tf_saved_star_gifts_collection_id`(account_id, id) -> `tf_saved_star_gifts`(account_id, id). Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function savedStarGifts(): BelongsTo
    {
        return $this->belongsTo(TfSavedStarGifts::class, 'id', 'id');
    }
}

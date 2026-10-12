<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

/**
 * Eloquent model for the shipped mirror table `tf_update_differences`.
 *
 * Columns, casts and relations are REFLECTED from the executed shipped DDL, not
 * transcribed and not inferred from names — see ModelGenerator. A relation
 * appears here only where the engine DECLARES a foreign key.
 *
 * Relations declared on this table: 4.
 */
final class TfUpdateDifferences extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_update_differences';

    protected $guarded = [];

    /** No autoincrement anywhere on the mirror: keys are Telegram-native. */
    public $incrementing = false;

    /**
     * Eloquent gets ONE scalar key. The real key is the composite (account_id, position),
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
        'position' => 'int',
        'constructor' => 'string',
    ];

    /** Declared foreign key (1:N): `tf_update_differences_date`(account_id, position) -> `tf_update_differences`(account_id, position), ON DELETE CASCADE. Keyed on `position` = `position`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function updateDifferencesDate(): HasMany
    {
        return $this->hasMany(TfUpdateDifferencesDate::class, 'position', 'position');
    }

    /** Declared foreign key (1:N): `tf_update_differences_pts`(account_id, position) -> `tf_update_differences`(account_id, position), ON DELETE CASCADE. Keyed on `position` = `position`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function updateDifferencesPts(): HasMany
    {
        return $this->hasMany(TfUpdateDifferencesPts::class, 'position', 'position');
    }

    /** Declared foreign key (1:N): `tf_update_differences_seq`(account_id, position) -> `tf_update_differences`(account_id, position), ON DELETE CASCADE. Keyed on `position` = `position`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function updateDifferencesSeq(): HasMany
    {
        return $this->hasMany(TfUpdateDifferencesSeq::class, 'position', 'position');
    }

    /** Declared foreign key (1:N): `tf_update_differences_state`(account_id, position) -> `tf_update_differences`(account_id, position), ON DELETE CASCADE. Keyed on `position` = `position`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function updateDifferencesState(): HasMany
    {
        return $this->hasMany(TfUpdateDifferencesState::class, 'position', 'position');
    }
}

<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

/**
 * Eloquent model for the shipped mirror table `tf_admin_log_events`.
 *
 * Columns, casts and relations are REFLECTED from the executed shipped DDL, not
 * transcribed and not inferred from names — see ModelGenerator.
 *
 * A relation appears here in one of two shapes, and the docblock on each says
 * which: one where the engine DECLARES a foreign key, and one HORIZONTAL — an
 * entity-to-entity edge derived from an inline `{base}_type` + `{base}_id` peer
 * pair or from a catalog-declared scalar entity column, which no constraint can
 * express. See HorizontalEntityRelations for why those are not FKs.
 *
 * Relations declared on this table: 2.
 */
final class TfAdminLogEvents extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_admin_log_events';

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
        'id' => 'int',
        'date' => 'int',
        'user_id' => 'int',
    ];

    /** Declared foreign key (1:N): `tf_admin_log_events_action`(account_id, id) -> `tf_admin_log_events`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function adminLogEventsAction(): HasMany
    {
        return $this->hasMany(TfAdminLogEventsAction::class, 'id', 'id');
    }

    /** HORIZONTAL, not a foreign key. `tf_admin_log_events`.`user_id` is a bare `long` in the `Tf` union that `MirrorCatalog` declares on this table, so it names `tf_users`.`id` directly and carries no discriminator. No FK is declared on it because the column exists on every row of the table whether or not that ctor sent one — the value is 0 when it did not. */
    public function userId(): BelongsTo
    {
        return $this->belongsTo(TlUser::class, 'user_id', 'id');
    }
}

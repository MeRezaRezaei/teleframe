<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

/**
 * Eloquent model for the shipped mirror table `tf_dialog_filters`.
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
 * Relations declared on this table: 6.
 */
final class TfDialogFilters extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_dialog_filters';

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
        'constructor' => 'string',
        'contacts' => 'boolean',
        'non_contacts' => 'boolean',
        'groups' => 'boolean',
        'broadcasts' => 'boolean',
        'bots' => 'boolean',
        'exclude_muted' => 'boolean',
        'exclude_read' => 'boolean',
        'exclude_archived' => 'boolean',
        'title_noanimate' => 'boolean',
    ];

    /** Declared foreign key (1:N): `tf_dialog_filters_color`(account_id, id) -> `tf_dialog_filters`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function dialogFiltersColor(): HasMany
    {
        return $this->hasMany(TfDialogFiltersColor::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_dialog_filters_emoticon`(account_id, id) -> `tf_dialog_filters`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function dialogFiltersEmoticon(): HasMany
    {
        return $this->hasMany(TfDialogFiltersEmoticon::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_dialog_filters_exclude_peers`(account_id, id) -> `tf_dialog_filters`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function dialogFiltersExcludePeers(): HasMany
    {
        return $this->hasMany(TfDialogFiltersExcludePeers::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_dialog_filters_include_peers`(account_id, id) -> `tf_dialog_filters`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function dialogFiltersIncludePeers(): HasMany
    {
        return $this->hasMany(TfDialogFiltersIncludePeers::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_dialog_filters_pinned_peers`(account_id, id) -> `tf_dialog_filters`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function dialogFiltersPinnedPeers(): HasMany
    {
        return $this->hasMany(TfDialogFiltersPinnedPeers::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_dialog_filters_title`(account_id, id) -> `tf_dialog_filters`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function dialogFiltersTitle(): HasMany
    {
        return $this->hasMany(TfDialogFiltersTitle::class, 'id', 'id');
    }
}

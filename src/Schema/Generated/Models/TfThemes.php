<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

/**
 * Eloquent model for the shipped mirror table `tf_themes`.
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
 * Relations declared on this table: 4.
 */
final class TfThemes extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_themes';

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
        'access_hash' => 'int',
        'slug' => 'string',
        'title' => 'string',
        'creator' => 'boolean',
        'default' => 'boolean',
        'for_chat' => 'boolean',
    ];

    /** Declared foreign key (1:N): `tf_themes_document`(account_id, id) -> `tf_themes`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function themesDocument(): HasMany
    {
        return $this->hasMany(TfThemesDocument::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_themes_emoticon`(account_id, id) -> `tf_themes`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function themesEmoticon(): HasMany
    {
        return $this->hasMany(TfThemesEmoticon::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_themes_installs_count`(account_id, id) -> `tf_themes`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function themesInstallsCount(): HasMany
    {
        return $this->hasMany(TfThemesInstallsCount::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_themes_settings`(account_id, id) -> `tf_themes`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function themesSettings(): HasMany
    {
        return $this->hasMany(TfThemesSettings::class, 'id', 'id');
    }
}

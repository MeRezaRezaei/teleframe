<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/**
 * Eloquent model for the shipped mirror table `tf_sticker_sets`.
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
 * Relations declared on this table: 5.
 */
final class TlStickerSet extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tf_sticker_sets';

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
        'constructor' => 'string',
        'id' => 'int',
        'access_hash' => 'int',
        'title' => 'string',
        'short_name' => 'string',
        'count' => 'int',
        'hash' => 'int',
        'archived' => 'boolean',
        'official' => 'boolean',
        'masks' => 'boolean',
        'emojis' => 'boolean',
        'text_color' => 'boolean',
        'channel_emoji_status' => 'boolean',
        'creator' => 'boolean',
    ];

    /** Declared foreign key (1:N): `tf_sticker_sets_installed_date`(account_id, id) -> `tf_sticker_sets`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function stickerSetsInstalledDate(): HasMany
    {
        return $this->hasMany(TfStickerSetsInstalledDate::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_sticker_sets_thumb_dc_id`(account_id, id) -> `tf_sticker_sets`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function stickerSetsThumbDcId(): HasMany
    {
        return $this->hasMany(TfStickerSetsThumbDcId::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_sticker_sets_thumb_document_id`(account_id, id) -> `tf_sticker_sets`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function stickerSetsThumbDocumentId(): HasMany
    {
        return $this->hasMany(TfStickerSetsThumbDocumentId::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_sticker_sets_thumb_version`(account_id, id) -> `tf_sticker_sets`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function stickerSetsThumbVersion(): HasMany
    {
        return $this->hasMany(TfStickerSetsThumbVersion::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_sticker_sets_thumbs`(account_id, id) -> `tf_sticker_sets`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function stickerSetsThumbs(): HasMany
    {
        return $this->hasMany(TfStickerSetsThumbs::class, 'id', 'id');
    }
}

<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

/**
 * Eloquent model for the shipped mirror table `tf_saved_star_gifts`.
 *
 * Columns, casts and relations are REFLECTED from the executed shipped DDL, not
 * transcribed and not inferred from names — see ModelGenerator. A relation
 * appears here only where the engine DECLARES a foreign key.
 *
 * Relations declared on this table: 15.
 */
final class TfSavedStarGifts extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_saved_star_gifts';

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
        'name_hidden' => 'boolean',
        'unsaved' => 'boolean',
        'refunded' => 'boolean',
        'can_upgrade' => 'boolean',
        'pinned_to_top' => 'boolean',
        'upgrade_separate' => 'boolean',
        'gift_num' => 'int',
    ];

    /** Declared foreign key (1:N): `tf_saved_star_gifts_can_craft_at`(account_id, id) -> `tf_saved_star_gifts`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function savedStarGiftsCanCraftAt(): HasMany
    {
        return $this->hasMany(TfSavedStarGiftsCanCraftAt::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_saved_star_gifts_can_export_at`(account_id, id) -> `tf_saved_star_gifts`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function savedStarGiftsCanExportAt(): HasMany
    {
        return $this->hasMany(TfSavedStarGiftsCanExportAt::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_saved_star_gifts_can_resell_at`(account_id, id) -> `tf_saved_star_gifts`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function savedStarGiftsCanResellAt(): HasMany
    {
        return $this->hasMany(TfSavedStarGiftsCanResellAt::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_saved_star_gifts_can_transfer_at`(account_id, id) -> `tf_saved_star_gifts`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function savedStarGiftsCanTransferAt(): HasMany
    {
        return $this->hasMany(TfSavedStarGiftsCanTransferAt::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_saved_star_gifts_collection_id`(account_id, id) -> `tf_saved_star_gifts`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function savedStarGiftsCollectionId(): HasMany
    {
        return $this->hasMany(TfSavedStarGiftsCollectionId::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_saved_star_gifts_convert_stars`(account_id, id) -> `tf_saved_star_gifts`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function savedStarGiftsConvertStars(): HasMany
    {
        return $this->hasMany(TfSavedStarGiftsConvertStars::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_saved_star_gifts_drop_original_details_stars`(account_id, id) -> `tf_saved_star_gifts`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function savedStarGiftsDropOriginalDetailsStars(): HasMany
    {
        return $this->hasMany(TfSavedStarGiftsDropOriginalDetailsStars::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_saved_star_gifts_from_id`(account_id, id) -> `tf_saved_star_gifts`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function savedStarGiftsFromId(): HasMany
    {
        return $this->hasMany(TfSavedStarGiftsFromId::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_saved_star_gifts_gift`(account_id, id) -> `tf_saved_star_gifts`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function savedStarGiftsGift(): HasMany
    {
        return $this->hasMany(TfSavedStarGiftsGift::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_saved_star_gifts_message`(account_id, id) -> `tf_saved_star_gifts`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function savedStarGiftsMessage(): HasMany
    {
        return $this->hasMany(TfSavedStarGiftsMessage::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_saved_star_gifts_msg_id`(account_id, id) -> `tf_saved_star_gifts`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function savedStarGiftsMsgId(): HasMany
    {
        return $this->hasMany(TfSavedStarGiftsMsgId::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_saved_star_gifts_prepaid_upgrade_hash`(account_id, id) -> `tf_saved_star_gifts`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function savedStarGiftsPrepaidUpgradeHash(): HasMany
    {
        return $this->hasMany(TfSavedStarGiftsPrepaidUpgradeHash::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_saved_star_gifts_saved_id`(account_id, id) -> `tf_saved_star_gifts`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function savedStarGiftsSavedId(): HasMany
    {
        return $this->hasMany(TfSavedStarGiftsSavedId::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_saved_star_gifts_transfer_stars`(account_id, id) -> `tf_saved_star_gifts`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function savedStarGiftsTransferStars(): HasMany
    {
        return $this->hasMany(TfSavedStarGiftsTransferStars::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_saved_star_gifts_upgrade_stars`(account_id, id) -> `tf_saved_star_gifts`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function savedStarGiftsUpgradeStars(): HasMany
    {
        return $this->hasMany(TfSavedStarGiftsUpgradeStars::class, 'id', 'id');
    }
}

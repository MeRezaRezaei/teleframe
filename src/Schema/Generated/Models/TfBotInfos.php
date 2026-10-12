<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

/**
 * Eloquent model for the shipped mirror table `tf_bot_infos`.
 *
 * Columns, casts and relations are REFLECTED from the executed shipped DDL, not
 * transcribed and not inferred from names — see ModelGenerator. A relation
 * appears here only where the engine DECLARES a foreign key.
 *
 * Relations declared on this table: 8.
 */
final class TfBotInfos extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_bot_infos';

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
        'has_preview_medias' => 'boolean',
        'description' => 'string',
    ];

    /** Declared foreign key (1:N): `tf_bot_infos_app_settings`(account_id, id) -> `tf_bot_infos`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function botInfosAppSettings(): HasMany
    {
        return $this->hasMany(TfBotInfosAppSettings::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_bot_infos_commands`(account_id, id) -> `tf_bot_infos`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function botInfosCommands(): HasMany
    {
        return $this->hasMany(TfBotInfosCommands::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_bot_infos_description_document`(account_id, id) -> `tf_bot_infos`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function botInfosDescriptionDocument(): HasMany
    {
        return $this->hasMany(TfBotInfosDescriptionDocument::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_bot_infos_description_photo`(account_id, id) -> `tf_bot_infos`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function botInfosDescriptionPhoto(): HasMany
    {
        return $this->hasMany(TfBotInfosDescriptionPhoto::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_bot_infos_menu_button`(account_id, id) -> `tf_bot_infos`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function botInfosMenuButton(): HasMany
    {
        return $this->hasMany(TfBotInfosMenuButton::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_bot_infos_privacy_policy_url`(account_id, id) -> `tf_bot_infos`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function botInfosPrivacyPolicyUrl(): HasMany
    {
        return $this->hasMany(TfBotInfosPrivacyPolicyUrl::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_bot_infos_user_id`(account_id, id) -> `tf_bot_infos`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function botInfosUserId(): HasMany
    {
        return $this->hasMany(TfBotInfosUserId::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_bot_infos_verifier_settings`(account_id, id) -> `tf_bot_infos`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function botInfosVerifierSettings(): HasMany
    {
        return $this->hasMany(TfBotInfosVerifierSettings::class, 'id', 'id');
    }
}

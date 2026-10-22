<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/**
 * Eloquent model for the shipped mirror table `tf_updates`.
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
 * Relations declared on this table: 14.
 */
final class TlUpdate extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tf_updates';

    protected $guarded = [];

    /** No autoincrement anywhere on the mirror: keys are Telegram-native. */
    public $incrementing = false;

    /**
     * Eloquent gets ONE scalar key. The real key is the composite (account_id, seq, position),
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
        'seq' => 'int',
        'position' => 'int',
        'constructor' => 'string',
        'channel_id' => 'int',
        'chat_id' => 'int',
        'user_id' => 'int',
    ];

    /** Declared foreign key (1:N): `tf_updates_action`(account_id, seq, position) -> `tf_updates`(account_id, seq, position), ON DELETE CASCADE. Keyed on `position` = `position`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function updatesAction(): HasMany
    {
        return $this->hasMany(TfUpdatesAction::class, 'position', 'position');
    }

    /** Declared foreign key (1:N): `tf_updates_date`(account_id, seq, position) -> `tf_updates`(account_id, seq, position), ON DELETE CASCADE. Keyed on `position` = `position`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function updatesDate(): HasMany
    {
        return $this->hasMany(TfUpdatesDate::class, 'position', 'position');
    }

    /** Declared foreign key (1:N): `tf_updates_folder_id`(account_id, seq, position) -> `tf_updates`(account_id, seq, position), ON DELETE CASCADE. Keyed on `position` = `position`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function updatesFolderId(): HasMany
    {
        return $this->hasMany(TfUpdatesFolderId::class, 'position', 'position');
    }

    /** Declared foreign key (1:N): `tf_updates_from`(account_id, seq, position) -> `tf_updates`(account_id, seq, position), ON DELETE CASCADE. Keyed on `position` = `position`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function updatesFrom(): HasMany
    {
        return $this->hasMany(TfUpdatesFrom::class, 'position', 'position');
    }

    /** Declared foreign key (1:N): `tf_updates_max_id`(account_id, seq, position) -> `tf_updates`(account_id, seq, position), ON DELETE CASCADE. Keyed on `position` = `position`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function updatesMaxId(): HasMany
    {
        return $this->hasMany(TfUpdatesMaxId::class, 'position', 'position');
    }

    /** Declared foreign key (1:N): `tf_updates_message`(account_id, seq, position) -> `tf_updates`(account_id, seq, position), ON DELETE CASCADE. Keyed on `position` = `position`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function updatesMessage(): HasMany
    {
        return $this->hasMany(TfUpdatesMessage::class, 'position', 'position');
    }

    /** Declared foreign key (1:N): `tf_updates_messages`(account_id, seq, position) -> `tf_updates`(account_id, seq, position), ON DELETE CASCADE. Keyed on `position` = `position`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function updatesMessages(): HasMany
    {
        return $this->hasMany(TfUpdatesMessages::class, 'position', 'position');
    }

    /** Declared foreign key (1:N): `tf_updates_peer`(account_id, seq, position) -> `tf_updates`(account_id, seq, position), ON DELETE CASCADE. Keyed on `position` = `position`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function updatesPeer(): HasMany
    {
        return $this->hasMany(TfUpdatesPeer::class, 'position', 'position');
    }

    /** Declared foreign key (1:N): `tf_updates_pts`(account_id, seq, position) -> `tf_updates`(account_id, seq, position), ON DELETE CASCADE. Keyed on `position` = `position`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function updatesPts(): HasMany
    {
        return $this->hasMany(TfUpdatesPts::class, 'position', 'position');
    }

    /** Declared foreign key (1:N): `tf_updates_pts_count`(account_id, seq, position) -> `tf_updates`(account_id, seq, position), ON DELETE CASCADE. Keyed on `position` = `position`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function updatesPtsCount(): HasMany
    {
        return $this->hasMany(TfUpdatesPtsCount::class, 'position', 'position');
    }

    /** Declared foreign key (1:N): `tf_updates_qts`(account_id, seq, position) -> `tf_updates`(account_id, seq, position), ON DELETE CASCADE. Keyed on `position` = `position`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function updatesQts(): HasMany
    {
        return $this->hasMany(TfUpdatesQts::class, 'position', 'position');
    }

    /** Declared foreign key (1:N): `tf_updates_status`(account_id, seq, position) -> `tf_updates`(account_id, seq, position), ON DELETE CASCADE. Keyed on `position` = `position`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function updatesStatus(): HasMany
    {
        return $this->hasMany(TfUpdatesStatus::class, 'position', 'position');
    }

    /** Declared foreign key (1:N): `tf_updates_still_unread_count`(account_id, seq, position) -> `tf_updates`(account_id, seq, position), ON DELETE CASCADE. Keyed on `position` = `position`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function updatesStillUnreadCount(): HasMany
    {
        return $this->hasMany(TfUpdatesStillUnreadCount::class, 'position', 'position');
    }

    /** Declared foreign key (1:N): `tf_updates_top_msg_id`(account_id, seq, position) -> `tf_updates`(account_id, seq, position), ON DELETE CASCADE. Keyed on `position` = `position`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function updatesTopMsgId(): HasMany
    {
        return $this->hasMany(TfUpdatesTopMsgId::class, 'position', 'position');
    }
}

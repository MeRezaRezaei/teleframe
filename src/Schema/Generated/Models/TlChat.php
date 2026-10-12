<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/**
 * Eloquent model for the shipped mirror table `tf_chats`.
 *
 * Columns, casts and relations are REFLECTED from the executed shipped DDL, not
 * transcribed and not inferred from names — see ModelGenerator. A relation
 * appears here only where the engine DECLARES a foreign key.
 *
 * Relations declared on this table: 5.
 */
final class TlChat extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tf_chats';

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
        'title' => 'string',
        'participants_count' => 'int',
        'date' => 'int',
        'version' => 'int',
        'creator' => 'boolean',
        'left' => 'boolean',
        'deactivated' => 'boolean',
        'call_active' => 'boolean',
        'call_not_empty' => 'boolean',
        'noforwards' => 'boolean',
    ];

    /** Declared foreign key (1:N): `tf_chats_access_hash`(account_id, id) -> `tf_chats`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function chatsAccessHash(): HasMany
    {
        return $this->hasMany(TfChatsAccessHash::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_chats_admin_rights`(account_id, id) -> `tf_chats`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function chatsAdminRights(): HasMany
    {
        return $this->hasMany(TfChatsAdminRights::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_chats_default_banned_rights`(account_id, id) -> `tf_chats`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function chatsDefaultBannedRights(): HasMany
    {
        return $this->hasMany(TfChatsDefaultBannedRights::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_chats_migrated_to`(account_id, id) -> `tf_chats`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function chatsMigratedTo(): HasMany
    {
        return $this->hasMany(TfChatsMigratedTo::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_chats_photo`(account_id, id) -> `tf_chats`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function chatsPhoto(): HasMany
    {
        return $this->hasMany(TfChatsPhoto::class, 'id', 'id');
    }
}

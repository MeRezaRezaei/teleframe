<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

/**
 * Eloquent model for the shipped mirror table `tf_attach_menu_bots`.
 *
 * Columns, casts and relations are REFLECTED from the executed shipped DDL, not
 * transcribed and not inferred from names — see ModelGenerator. A relation
 * appears here only where the engine DECLARES a foreign key.
 *
 * Relations declared on this table: 2.
 */
final class TfAttachMenuBots extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_attach_menu_bots';

    protected $guarded = [];

    /** No autoincrement anywhere on the mirror: keys are Telegram-native. */
    public $incrementing = false;

    /**
     * Eloquent gets ONE scalar key. The real key is the composite (account_id, bot_id),
     * which the database enforces; `bot_id` is total only WITHIN an account, and
     * AccountScope guarantees that. So `find()` is account-scoped by construction:
     * reach for a relation or an explicit where, never a bare find().
     */
    protected $primaryKey = 'bot_id';

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
        'bot_id' => 'int',
        'short_name' => 'string',
        'inactive' => 'boolean',
        'has_settings' => 'boolean',
        'request_write_access' => 'boolean',
        'show_in_attach_menu' => 'boolean',
        'show_in_side_menu' => 'boolean',
        'side_menu_disclaimer_needed' => 'boolean',
    ];

    /** Declared foreign key (1:N): `tf_attach_menu_bots_icons`(account_id, bot_id) -> `tf_attach_menu_bots`(account_id, bot_id), ON DELETE CASCADE. Keyed on `bot_id` = `bot_id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function attachMenuBotsIcons(): HasMany
    {
        return $this->hasMany(TfAttachMenuBotsIcons::class, 'bot_id', 'bot_id');
    }

    /** Declared foreign key (1:N): `tf_attach_menu_bots_peer_types`(account_id, bot_id) -> `tf_attach_menu_bots`(account_id, bot_id), ON DELETE CASCADE. Keyed on `bot_id` = `bot_id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function attachMenuBotsPeerTypes(): HasMany
    {
        return $this->hasMany(TfAttachMenuBotsPeerTypes::class, 'bot_id', 'bot_id');
    }
}

<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

/**
 * Eloquent model for the shipped mirror table `tf_channels_admin_rights`.
 *
 * Columns, casts and relations are REFLECTED from the executed shipped DDL, not
 * transcribed and not inferred from names — see ModelGenerator. A relation
 * appears here only where the engine DECLARES a foreign key.
 *
 * Relations declared on this table: 1.
 */
final class TfChannelsAdminRights extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_channels_admin_rights';

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
        'change_info' => 'boolean',
        'post_messages' => 'boolean',
        'edit_messages' => 'boolean',
        'delete_messages' => 'boolean',
        'ban_users' => 'boolean',
        'invite_users' => 'boolean',
        'pin_messages' => 'boolean',
        'add_admins' => 'boolean',
        'anonymous' => 'boolean',
        'manage_call' => 'boolean',
        'other' => 'boolean',
        'manage_topics' => 'boolean',
        'post_stories' => 'boolean',
        'edit_stories' => 'boolean',
        'delete_stories' => 'boolean',
        'manage_direct_messages' => 'boolean',
        'manage_ranks' => 'boolean',
    ];

    /** Declared foreign key: `tf_channels_admin_rights`(account_id, id) -> `tf_channels`(account_id, id). Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function channels(): BelongsTo
    {
        return $this->belongsTo(TlChannel::class, 'id', 'id');
    }
}

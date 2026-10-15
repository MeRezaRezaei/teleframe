<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

/**
 * Eloquent model for the shipped mirror table `tf_channels_default_banned_rights`.
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
 * Relations declared on this table: 1.
 */
final class TfChannelsDefaultBannedRights extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_channels_default_banned_rights';

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
        'view_messages' => 'boolean',
        'send_messages' => 'boolean',
        'send_media' => 'boolean',
        'send_stickers' => 'boolean',
        'send_gifs' => 'boolean',
        'send_games' => 'boolean',
        'send_inline' => 'boolean',
        'embed_links' => 'boolean',
        'send_polls' => 'boolean',
        'change_info' => 'boolean',
        'invite_users' => 'boolean',
        'pin_messages' => 'boolean',
        'manage_topics' => 'boolean',
        'send_photos' => 'boolean',
        'send_videos' => 'boolean',
        'send_roundvideos' => 'boolean',
        'send_audios' => 'boolean',
        'send_voices' => 'boolean',
        'send_docs' => 'boolean',
        'send_plain' => 'boolean',
        'edit_rank' => 'boolean',
        'send_reactions' => 'boolean',
        'until_date' => 'int',
    ];

    /** Declared foreign key: `tf_channels_default_banned_rights`(account_id, id) -> `tf_channels`(account_id, id). Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function channels(): BelongsTo
    {
        return $this->belongsTo(TlChannel::class, 'id', 'id');
    }
}

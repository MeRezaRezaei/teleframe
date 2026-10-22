<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

/**
 * Eloquent model for the shipped mirror table `tf_accounts`.
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
 * Relations declared on this table: 11.
 */
final class TfAccounts extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_accounts';

    protected $guarded = [];

    /** No autoincrement anywhere on the mirror: keys are Telegram-native. */
    public $incrementing = false;

    protected $primaryKey = 'id';

    public $timestamps = true;

    /**
     * Derived from the type the ENGINE reports for each column — never from a
     * docblock, and never from SQLite, whose affinity collapses integer(),
     * bigInteger() and tinyInteger() alike and drops width and signedness.
     *
     * @var array<string,string>
     */
    protected $casts = [
        'id' => 'int',
        'label' => 'string',
        'dc_id' => 'int',
        'port' => 'int',
        'kind' => 'string',
        'session_ref' => 'string',
        'enabled' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /** HORIZONTAL, and the root of them. `tf_accounts` carries NO constraint at all — it has no `account_id` column, so it structurally cannot carry the tenant FK — yet 264 `tf_*` tables point at it. This reaches `tf_channel_participants` for one tenant. The companion tables under it are reached from their ANCHOR, not from here; the anchor set is read from `Naming`, so it cannot drift from the model layer. */
    public function channelParticipants(): HasMany
    {
        return $this->hasMany(TlChannelParticipant::class, 'account_id', 'id');
    }

    /** HORIZONTAL, and the root of them. `tf_accounts` carries NO constraint at all — it has no `account_id` column, so it structurally cannot carry the tenant FK — yet 264 `tf_*` tables point at it. This reaches `tf_channels` for one tenant. The companion tables under it are reached from their ANCHOR, not from here; the anchor set is read from `Naming`, so it cannot drift from the model layer. */
    public function channels(): HasMany
    {
        return $this->hasMany(TlChannel::class, 'account_id', 'id');
    }

    /** HORIZONTAL, and the root of them. `tf_accounts` carries NO constraint at all — it has no `account_id` column, so it structurally cannot carry the tenant FK — yet 264 `tf_*` tables point at it. This reaches `tf_chats` for one tenant. The companion tables under it are reached from their ANCHOR, not from here; the anchor set is read from `Naming`, so it cannot drift from the model layer. */
    public function chats(): HasMany
    {
        return $this->hasMany(TlChat::class, 'account_id', 'id');
    }

    /** HORIZONTAL, and the root of them. `tf_accounts` carries NO constraint at all — it has no `account_id` column, so it structurally cannot carry the tenant FK — yet 264 `tf_*` tables point at it. This reaches `tf_dialogs` for one tenant. The companion tables under it are reached from their ANCHOR, not from here; the anchor set is read from `Naming`, so it cannot drift from the model layer. */
    public function dialogs(): HasMany
    {
        return $this->hasMany(TlDialog::class, 'account_id', 'id');
    }

    /** HORIZONTAL, and the root of them. `tf_accounts` carries NO constraint at all — it has no `account_id` column, so it structurally cannot carry the tenant FK — yet 264 `tf_*` tables point at it. This reaches `tf_documents` for one tenant. The companion tables under it are reached from their ANCHOR, not from here; the anchor set is read from `Naming`, so it cannot drift from the model layer. */
    public function documents(): HasMany
    {
        return $this->hasMany(TlDocument::class, 'account_id', 'id');
    }

    /** HORIZONTAL, and the root of them. `tf_accounts` carries NO constraint at all — it has no `account_id` column, so it structurally cannot carry the tenant FK — yet 264 `tf_*` tables point at it. This reaches `tf_messages` for one tenant. The companion tables under it are reached from their ANCHOR, not from here; the anchor set is read from `Naming`, so it cannot drift from the model layer. */
    public function messages(): HasMany
    {
        return $this->hasMany(TlMessage::class, 'account_id', 'id');
    }

    /** HORIZONTAL, and the root of them. `tf_accounts` carries NO constraint at all — it has no `account_id` column, so it structurally cannot carry the tenant FK — yet 264 `tf_*` tables point at it. This reaches `tf_photos` for one tenant. The companion tables under it are reached from their ANCHOR, not from here; the anchor set is read from `Naming`, so it cannot drift from the model layer. */
    public function photos(): HasMany
    {
        return $this->hasMany(TlPhoto::class, 'account_id', 'id');
    }

    /** HORIZONTAL, and the root of them. `tf_accounts` carries NO constraint at all — it has no `account_id` column, so it structurally cannot carry the tenant FK — yet 264 `tf_*` tables point at it. This reaches `tf_sticker_sets` for one tenant. The companion tables under it are reached from their ANCHOR, not from here; the anchor set is read from `Naming`, so it cannot drift from the model layer. */
    public function stickerSets(): HasMany
    {
        return $this->hasMany(TlStickerSet::class, 'account_id', 'id');
    }

    /** HORIZONTAL, and the root of them. `tf_accounts` carries NO constraint at all — it has no `account_id` column, so it structurally cannot carry the tenant FK — yet 264 `tf_*` tables point at it. This reaches `tf_updates` for one tenant. The companion tables under it are reached from their ANCHOR, not from here; the anchor set is read from `Naming`, so it cannot drift from the model layer. */
    public function updates(): HasMany
    {
        return $this->hasMany(TlUpdate::class, 'account_id', 'id');
    }

    /** HORIZONTAL, and the root of them. `tf_accounts` carries NO constraint at all — it has no `account_id` column, so it structurally cannot carry the tenant FK — yet 264 `tf_*` tables point at it. This reaches `tf_users` for one tenant. The companion tables under it are reached from their ANCHOR, not from here; the anchor set is read from `Naming`, so it cannot drift from the model layer. */
    public function users(): HasMany
    {
        return $this->hasMany(TlUser::class, 'account_id', 'id');
    }

    /** HORIZONTAL, and the root of them. `tf_accounts` carries NO constraint at all — it has no `account_id` column, so it structurally cannot carry the tenant FK — yet 264 `tf_*` tables point at it. This reaches `tf_wallpapers` for one tenant. The companion tables under it are reached from their ANCHOR, not from here; the anchor set is read from `Naming`, so it cannot drift from the model layer. */
    public function wallpapers(): HasMany
    {
        return $this->hasMany(TlWallpaper::class, 'account_id', 'id');
    }
}

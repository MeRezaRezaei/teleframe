<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/**
 * Eloquent model for the shipped mirror table `tf_channel_participants`.
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
final class TlChannelParticipant extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tf_channel_participants';

    protected $guarded = [];

    /** No autoincrement anywhere on the mirror: keys are Telegram-native. */
    public $incrementing = false;

    /**
     * Eloquent gets ONE scalar key. The real key is the composite (account_id, channel_id, user_id),
     * which the database enforces; `user_id` is total only WITHIN an account, and
     * AccountScope guarantees that. So `find()` is account-scoped by construction:
     * reach for a relation or an explicit where, never a bare find().
     */
    protected $primaryKey = 'user_id';

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
        'channel_id' => 'int',
        'user_id' => 'int',
        'constructor' => 'string',
        'date' => 'int',
        'via_request' => 'boolean',
        'is_self' => 'boolean',
        'can_edit' => 'boolean',
        'is_left' => 'boolean',
        'inviter_id' => 'int',
        'kicked_by' => 'int',
        'promoted_by' => 'int',
    ];

    /** Declared foreign key (1:N): `tf_channel_participants_peer`(account_id, channel_id, user_id) -> `tf_channel_participants`(account_id, channel_id, user_id), ON DELETE CASCADE. Keyed on `user_id` = `user_id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function channelParticipantsPeer(): HasMany
    {
        return $this->hasMany(TfChannelParticipantsPeer::class, 'user_id', 'user_id');
    }

    /** Declared foreign key (1:N): `tf_channel_participants_rank`(account_id, channel_id, user_id) -> `tf_channel_participants`(account_id, channel_id, user_id), ON DELETE CASCADE. Keyed on `user_id` = `user_id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function channelParticipantsRank(): HasMany
    {
        return $this->hasMany(TfChannelParticipantsRank::class, 'user_id', 'user_id');
    }

    /** Declared foreign key (1:N): `tf_channel_participants_subscription_until_date`(account_id, channel_id, user_id) -> `tf_channel_participants`(account_id, channel_id, user_id), ON DELETE CASCADE. Keyed on `user_id` = `user_id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function channelParticipantsSubscriptionUntilDate(): HasMany
    {
        return $this->hasMany(TfChannelParticipantsSubscriptionUntilDate::class, 'user_id', 'user_id');
    }

    /** Declared foreign key: `tf_channel_participants`(account_id, channel_id) -> `tf_channels`(account_id, id). Keyed on `channel_id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function channels(): BelongsTo
    {
        return $this->belongsTo(TlChannel::class, 'channel_id', 'id');
    }

    /** HORIZONTAL, not a foreign key. `tf_channel_participants`.`user_id` is a bare `long` in the `Tf` union that `MirrorCatalog` declares on this table, so it names `tf_users`.`id` directly and carries no discriminator. No FK is declared on it because the column exists on every row of the table whether or not that ctor sent one — the value is 0 when it did not. */
    public function userId(): BelongsTo
    {
        return $this->belongsTo(TlUser::class, 'user_id', 'id');
    }
}

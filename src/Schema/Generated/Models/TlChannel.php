<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/**
 * Eloquent model for the shipped mirror table `tf_channels`.
 *
 * Columns, casts and relations are REFLECTED from the executed shipped DDL, not
 * transcribed and not inferred from names — see ModelGenerator. A relation
 * appears here only where the engine DECLARES a foreign key.
 *
 * Relations declared on this table: 19.
 */
final class TlChannel extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tf_channels';

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
        'access_hash' => 'int',
        'date' => 'int',
        'creator' => 'boolean',
        'left' => 'boolean',
        'broadcast' => 'boolean',
        'verified' => 'boolean',
        'megagroup' => 'boolean',
        'restricted' => 'boolean',
        'signatures' => 'boolean',
        'min' => 'boolean',
        'scam' => 'boolean',
        'has_link' => 'boolean',
        'has_geo' => 'boolean',
        'slowmode_enabled' => 'boolean',
        'call_active' => 'boolean',
        'call_not_empty' => 'boolean',
        'fake' => 'boolean',
        'gigagroup' => 'boolean',
        'noforwards' => 'boolean',
        'join_to_send' => 'boolean',
        'join_request' => 'boolean',
        'forum' => 'boolean',
        'stories_hidden' => 'boolean',
        'stories_hidden_min' => 'boolean',
        'stories_unavailable' => 'boolean',
        'signature_profiles' => 'boolean',
        'autotranslation' => 'boolean',
        'broadcast_messages_allowed' => 'boolean',
        'monoforum' => 'boolean',
        'forum_tabs' => 'boolean',
    ];

    /** Declared foreign key (1:N): `tf_channel_participants`(account_id, channel_id) -> `tf_channels`(account_id, id), ON DELETE RESTRICT. Keyed on `channel_id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function channelParticipants(): HasMany
    {
        return $this->hasMany(TlChannelParticipant::class, 'channel_id', 'id');
    }

    /** Declared foreign key (1:N): `tf_channels_admin_rights`(account_id, id) -> `tf_channels`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function channelsAdminRights(): HasMany
    {
        return $this->hasMany(TfChannelsAdminRights::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_channels_banned_rights`(account_id, id) -> `tf_channels`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function channelsBannedRights(): HasMany
    {
        return $this->hasMany(TfChannelsBannedRights::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_channels_bot_verification_icon`(account_id, id) -> `tf_channels`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function channelsBotVerificationIcon(): HasMany
    {
        return $this->hasMany(TfChannelsBotVerificationIcon::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_channels_color`(account_id, id) -> `tf_channels`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function channelsColor(): HasMany
    {
        return $this->hasMany(TfChannelsColor::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_channels_default_banned_rights`(account_id, id) -> `tf_channels`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function channelsDefaultBannedRights(): HasMany
    {
        return $this->hasMany(TfChannelsDefaultBannedRights::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_channels_emoji_status`(account_id, id) -> `tf_channels`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function channelsEmojiStatus(): HasMany
    {
        return $this->hasMany(TfChannelsEmojiStatus::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_channels_level`(account_id, id) -> `tf_channels`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function channelsLevel(): HasMany
    {
        return $this->hasMany(TfChannelsLevel::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_channels_linked_monoforum_id`(account_id, id) -> `tf_channels`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function channelsLinkedMonoforumId(): HasMany
    {
        return $this->hasMany(TfChannelsLinkedMonoforumId::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_channels_participants_count`(account_id, id) -> `tf_channels`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function channelsParticipantsCount(): HasMany
    {
        return $this->hasMany(TfChannelsParticipantsCount::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_channels_photo`(account_id, id) -> `tf_channels`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function channelsPhoto(): HasMany
    {
        return $this->hasMany(TfChannelsPhoto::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_channels_profile_color`(account_id, id) -> `tf_channels`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function channelsProfileColor(): HasMany
    {
        return $this->hasMany(TfChannelsProfileColor::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_channels_restriction_reason`(account_id, id) -> `tf_channels`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function channelsRestrictionReason(): HasMany
    {
        return $this->hasMany(TfChannelsRestrictionReason::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_channels_send_paid_messages_stars`(account_id, id) -> `tf_channels`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function channelsSendPaidMessagesStars(): HasMany
    {
        return $this->hasMany(TfChannelsSendPaidMessagesStars::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_channels_stories_max_id`(account_id, id) -> `tf_channels`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function channelsStoriesMaxId(): HasMany
    {
        return $this->hasMany(TfChannelsStoriesMaxId::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_channels_subscription_until_date`(account_id, id) -> `tf_channels`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function channelsSubscriptionUntilDate(): HasMany
    {
        return $this->hasMany(TfChannelsSubscriptionUntilDate::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_channels_until_date`(account_id, id) -> `tf_channels`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function channelsUntilDate(): HasMany
    {
        return $this->hasMany(TfChannelsUntilDate::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_channels_username`(account_id, id) -> `tf_channels`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function channelsUsername(): HasMany
    {
        return $this->hasMany(TfChannelsUsername::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_channels_usernames`(account_id, id) -> `tf_channels`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function channelsUsernames(): HasMany
    {
        return $this->hasMany(TfChannelsUsernames::class, 'id', 'id');
    }
}

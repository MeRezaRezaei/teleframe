<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/**
 * Eloquent model for the shipped mirror table `tf_messages`.
 *
 * Columns, casts and relations are REFLECTED from the executed shipped DDL, not
 * transcribed and not inferred from names — see ModelGenerator. A relation
 * appears here only where the engine DECLARES a foreign key.
 *
 * Relations declared on this table: 30.
 */
final class TlMessage extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tf_messages';

    protected $guarded = [];

    /** No autoincrement anywhere on the mirror: keys are Telegram-native. */
    public $incrementing = false;

    /**
     * Eloquent gets ONE scalar key. The real key is the composite (account_id, peer_type, peer_id, id),
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
        'peer_type' => 'int',
        'peer_id' => 'int',
        'constructor' => 'string',
        'date' => 'int',
        'message' => 'string',
        'out' => 'boolean',
        'mentioned' => 'boolean',
        'media_unread' => 'boolean',
        'silent' => 'boolean',
        'post' => 'boolean',
        'from_scheduled' => 'boolean',
        'legacy' => 'boolean',
        'edit_hide' => 'boolean',
        'pinned' => 'boolean',
        'noforwards' => 'boolean',
        'invert_media' => 'boolean',
        'offline' => 'boolean',
        'video_processing_pending' => 'boolean',
        'paid_suggested_post_stars' => 'boolean',
        'paid_suggested_post_ton' => 'boolean',
    ];

    /** Declared foreign key (1:N): `tf_messages_edit_date`(account_id, peer_type, peer_id, id) -> `tf_messages`(account_id, peer_type, peer_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function messagesEditDate(): HasMany
    {
        return $this->hasMany(TfMessagesEditDate::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_messages_effect`(account_id, peer_type, peer_id, id) -> `tf_messages`(account_id, peer_type, peer_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function messagesEffect(): HasMany
    {
        return $this->hasMany(TfMessagesEffect::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_messages_entities`(account_id, peer_type, peer_id, id) -> `tf_messages`(account_id, peer_type, peer_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function messagesEntities(): HasMany
    {
        return $this->hasMany(TfMessagesEntities::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_messages_factcheck`(account_id, peer_type, peer_id, id) -> `tf_messages`(account_id, peer_type, peer_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function messagesFactcheck(): HasMany
    {
        return $this->hasMany(TfMessagesFactcheck::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_messages_forwards`(account_id, peer_type, peer_id, id) -> `tf_messages`(account_id, peer_type, peer_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function messagesForwards(): HasMany
    {
        return $this->hasMany(TfMessagesForwards::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_messages_from_boosts_applied`(account_id, peer_type, peer_id, id) -> `tf_messages`(account_id, peer_type, peer_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function messagesFromBoostsApplied(): HasMany
    {
        return $this->hasMany(TfMessagesFromBoostsApplied::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_messages_from_id`(account_id, peer_type, peer_id, id) -> `tf_messages`(account_id, peer_type, peer_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function messagesFromId(): HasMany
    {
        return $this->hasMany(TfMessagesFromId::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_messages_from_rank`(account_id, peer_type, peer_id, id) -> `tf_messages`(account_id, peer_type, peer_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function messagesFromRank(): HasMany
    {
        return $this->hasMany(TfMessagesFromRank::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_messages_fwd_from`(account_id, peer_type, peer_id, id) -> `tf_messages`(account_id, peer_type, peer_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function messagesFwdFrom(): HasMany
    {
        return $this->hasMany(TfMessagesFwdFrom::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_messages_grouped_id`(account_id, peer_type, peer_id, id) -> `tf_messages`(account_id, peer_type, peer_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function messagesGroupedId(): HasMany
    {
        return $this->hasMany(TfMessagesGroupedId::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_messages_guestchat_via_from`(account_id, peer_type, peer_id, id) -> `tf_messages`(account_id, peer_type, peer_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function messagesGuestchatViaFrom(): HasMany
    {
        return $this->hasMany(TfMessagesGuestchatViaFrom::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_messages_media`(account_id, message_peer_type, message_peer_id, id) -> `tf_messages`(account_id, peer_type, peer_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function messagesMedia(): HasMany
    {
        return $this->hasMany(TfMessagesMedia::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_messages_paid_message_stars`(account_id, peer_type, peer_id, id) -> `tf_messages`(account_id, peer_type, peer_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function messagesPaidMessageStars(): HasMany
    {
        return $this->hasMany(TfMessagesPaidMessageStars::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_messages_post_author`(account_id, peer_type, peer_id, id) -> `tf_messages`(account_id, peer_type, peer_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function messagesPostAuthor(): HasMany
    {
        return $this->hasMany(TfMessagesPostAuthor::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_messages_quick_reply_shortcut_id`(account_id, peer_type, peer_id, id) -> `tf_messages`(account_id, peer_type, peer_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function messagesQuickReplyShortcutId(): HasMany
    {
        return $this->hasMany(TfMessagesQuickReplyShortcutId::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_messages_reactions`(account_id, peer_type, peer_id, id) -> `tf_messages`(account_id, peer_type, peer_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function messagesReactions(): HasMany
    {
        return $this->hasMany(TfMessagesReactions::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_messages_replies`(account_id, peer_type, peer_id, id) -> `tf_messages`(account_id, peer_type, peer_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function messagesReplies(): HasMany
    {
        return $this->hasMany(TfMessagesReplies::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_messages_reply_markup`(account_id, peer_type, peer_id, id) -> `tf_messages`(account_id, peer_type, peer_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function messagesReplyMarkup(): HasMany
    {
        return $this->hasMany(TfMessagesReplyMarkup::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_messages_reply_to`(account_id, peer_type, peer_id, id) -> `tf_messages`(account_id, peer_type, peer_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function messagesReplyTo(): HasMany
    {
        return $this->hasMany(TfMessagesReplyTo::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_messages_report_delivery_until_date`(account_id, peer_type, peer_id, id) -> `tf_messages`(account_id, peer_type, peer_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function messagesReportDeliveryUntilDate(): HasMany
    {
        return $this->hasMany(TfMessagesReportDeliveryUntilDate::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_messages_restriction_reason`(account_id, peer_type, peer_id, id) -> `tf_messages`(account_id, peer_type, peer_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function messagesRestrictionReason(): HasMany
    {
        return $this->hasMany(TfMessagesRestrictionReason::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_messages_rich_message`(account_id, peer_type, peer_id, id) -> `tf_messages`(account_id, peer_type, peer_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function messagesRichMessage(): HasMany
    {
        return $this->hasMany(TfMessagesRichMessage::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_messages_saved_peer_id`(account_id, peer_type, peer_id, id) -> `tf_messages`(account_id, peer_type, peer_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function messagesSavedPeerId(): HasMany
    {
        return $this->hasMany(TfMessagesSavedPeerId::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_messages_schedule_repeat_period`(account_id, peer_type, peer_id, id) -> `tf_messages`(account_id, peer_type, peer_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function messagesScheduleRepeatPeriod(): HasMany
    {
        return $this->hasMany(TfMessagesScheduleRepeatPeriod::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_messages_suggested_post`(account_id, peer_type, peer_id, id) -> `tf_messages`(account_id, peer_type, peer_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function messagesSuggestedPost(): HasMany
    {
        return $this->hasMany(TfMessagesSuggestedPost::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_messages_summary_from_language`(account_id, peer_type, peer_id, id) -> `tf_messages`(account_id, peer_type, peer_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function messagesSummaryFromLanguage(): HasMany
    {
        return $this->hasMany(TfMessagesSummaryFromLanguage::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_messages_ttl_period`(account_id, peer_type, peer_id, id) -> `tf_messages`(account_id, peer_type, peer_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function messagesTtlPeriod(): HasMany
    {
        return $this->hasMany(TfMessagesTtlPeriod::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_messages_via_bot_id`(account_id, peer_type, peer_id, id) -> `tf_messages`(account_id, peer_type, peer_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function messagesViaBotId(): HasMany
    {
        return $this->hasMany(TfMessagesViaBotId::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_messages_via_business_bot_id`(account_id, peer_type, peer_id, id) -> `tf_messages`(account_id, peer_type, peer_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function messagesViaBusinessBotId(): HasMany
    {
        return $this->hasMany(TfMessagesViaBusinessBotId::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_messages_views`(account_id, peer_type, peer_id, id) -> `tf_messages`(account_id, peer_type, peer_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function messagesViews(): HasMany
    {
        return $this->hasMany(TfMessagesViews::class, 'id', 'id');
    }
}

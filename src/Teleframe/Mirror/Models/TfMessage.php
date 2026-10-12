<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

/**
 * NF5 mirror content message (Message union: messageEmpty / message ctor).
 *
 * The MessageConsumerFamily service payloads land in TfMessageService; the
 * two tables share the (account_id, peer_type, peer_id, id) KEY — NOT the
 * column set — so MessagesUnion can UNION ALL over both (disjoint telegram id
 * spaces) with an explicit projection that pads the service side's missing
 * columns with NULL. This table is the wider one: 22 columns against
 * tf_messages_service's 13.
 *
 * Cross-domain peer relations (peer → TfUser / TfChat / TfChannel keyed by
 * peer_type 1/2/3) resolve against the identity worktree models and belong to
 * the Task-8 FK wiring — they are added there once tf_users / tf_chats /
 * tf_channels land; directions are documented in the messages-domain plan.
 */
final class TfMessage extends TfMirrorModel
{
    use PinsDeclaredForeignKeyLegs;

    use AccountScoped;
    use ResolvesMessagePeerScope;

    /**
     * THE MESSAGE KEY, and the reason it is not `id`.
     *
     * Telegram numbers messages PER CHAT: `dialog.top_message` is the highest id
     * in that dialog and ids are dense per-dialog sequences from 1, so `id=5`
     * exists in essentially every dialog of a real account — measured on one:
     * 136 dialogs hold a message numbered 1. `messages.getHistory` takes a
     * MANDATORY `peer:InputPeer` and an `offset_id` that is a peer-relative
     * cursor, and `message#7600b9d3` carries `id:int` AND `peer_id:Peer` in the
     * SAME constructor. If ids were globally unique, `peer:` would be
     * decoration.
     *
     * So a message's identity is (account_id, peer_type, peer_id, id), and
     * every relation to a message fact has to name the WHOLE thing. A relation
     * on `id` alone does not merely look up "the wrong row"; it returns
     * whichever dialog wrote last, which is the silent 80.98% overwrite this
     * key exists to prevent. `account_id` is not in the list because it is
     * supplied by the AccountScope global scope on every leg.
     *
     * @var list<string>
     */
    public const MESSAGE_KEY = ['peer_type', 'peer_id', 'id'];

    protected $table = 'tf_messages';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'peer_type' => 'integer',
        'peer_id' => 'integer',
        'date' => 'integer',
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

    /**
     * EVERY relation below is keyed on `self::MESSAGE_KEY`, never on `id`
     * alone — see that constant for the measurement. A child row is a FACT
     * ABOUT one message, and once a message's identity is
     * (account_id, peer_type, peer_id, id), "the author of message 5" is
     * ambiguous: there is one in every dialog. Joining on `id` alone would
     * return whichever dialog wrote last.
     */
    public function from(): HasOne
    {
        return $this->pinPeerScope($this->hasOne(TfMessageFromId::class, 'id', 'id'));
    }

    public function savedPeer(): HasOne
    {
        return $this->pinPeerScope($this->hasOne(TfMessageSavedPeerId::class, 'id', 'id'));
    }

    public function fwdFrom(): HasOne
    {
        return $this->pinPeerScope($this->hasOne(TfMessageFwdFrom::class, 'id', 'id'));
    }

    public function replyTo(): HasOne
    {
        return $this->pinPeerScope($this->hasOne(TfMessageReplyTo::class, 'id', 'id'));
    }

    /**
     * The message's media, on the peer-scoped key like every other relation
     * here — but with the PREFIXED local columns.
     *
     * `tf_messages_media` is the one message child that already owns
     * `peer_type`/`peer_id`, for a peer carried INSIDE its own payload (one of
     * the MessageMedia union constructors), so the enclosing message's scope
     * lives in `message_peer_type`/`message_peer_id`. The prefixed columns are
     * the ones the foreign key references against this table's unprefixed
     * `peer_type`/`peer_id` — a foreign key matches on the REFERENCED column
     * names, not on local ones.
     */
    public function media(): HasOne
    {
        $media = $this->hasOne(TfMessageMedia::class, 'id', 'id');
        $media->where('message_peer_type', $this->peer_type);
        $media->where('message_peer_id', $this->peer_id);

        return $media;
    }

    public function replyMarkup(): HasOne
    {
        return $this->pinPeerScope($this->hasOne(TfMessageReplyMarkup::class, 'id', 'id'));
    }

    public function entities(): HasMany
    {
        return $this->pinPeerScope($this->hasMany(TfMessageEntity::class, 'id', 'id'));
    }

    public function views(): HasOne
    {
        return $this->pinPeerScope($this->hasOne(TfMessageViews::class, 'id', 'id'));
    }

    public function forwards(): HasOne
    {
        return $this->pinPeerScope($this->hasOne(TfMessageForwards::class, 'id', 'id'));
    }

    public function replies(): HasOne
    {
        return $this->pinPeerScope($this->hasOne(TfMessageReplies::class, 'id', 'id'));
    }

    public function reactions(): HasOne
    {
        return $this->pinPeerScope($this->hasOne(TfMessageReactions::class, 'id', 'id'));
    }

    public function factCheck(): HasOne
    {
        return $this->pinPeerScope($this->hasOne(TfMessageFactCheck::class, 'id', 'id'));
    }

    public function suggestedPost(): HasOne
    {
        return $this->pinPeerScope($this->hasOne(TfMessageSuggestedPost::class, 'id', 'id'));
    }

    public function replyMarkupRows(): HasMany
    {
        return $this->pinPeerScope($this->hasMany(TfMessageReplyMarkupRows::class, 'id', 'id'));
    }

    public function guestchatViaFrom(): HasOne
    {
        return $this->pinPeerScope($this->hasOne(TfMessageGuestchatViaFrom::class, 'id', 'id'));
    }

    public function restrictionReasons(): HasMany
    {
        return $this->pinPeerScope($this->hasMany(TfMessageRestrictionReason::class, 'id', 'id'));
    }

    public function richMessage(): HasOne
    {
        return $this->pinPeerScope($this->hasOne(TfMessageRichMessage::class, 'id', 'id'));
    }

    public function editDate(): HasOne
    {
        return $this->pinPeerScope($this->hasOne(TfMessageEditDate::class, 'id', 'id'));
    }

    public function effect(): HasOne
    {
        return $this->pinPeerScope($this->hasOne(TfMessageEffect::class, 'id', 'id'));
    }

    public function fromBoostsApplied(): HasOne
    {
        return $this->pinPeerScope($this->hasOne(TfMessageFromBoostsApplied::class, 'id', 'id'));
    }

    public function fromRank(): HasOne
    {
        return $this->pinPeerScope($this->hasOne(TfMessageFromRank::class, 'id', 'id'));
    }

    public function groupedId(): HasOne
    {
        return $this->pinPeerScope($this->hasOne(TfMessageGroupedId::class, 'id', 'id'));
    }

    public function paidMessageStars(): HasOne
    {
        return $this->pinPeerScope($this->hasOne(TfMessagePaidMessageStars::class, 'id', 'id'));
    }

    public function postAuthor(): HasOne
    {
        return $this->pinPeerScope($this->hasOne(TfMessagePostAuthor::class, 'id', 'id'));
    }

    public function quickReplyShortcutId(): HasOne
    {
        return $this->pinPeerScope($this->hasOne(TfMessageQuickReplyShortcutId::class, 'id', 'id'));
    }

    public function reportDeliveryUntilDate(): HasOne
    {
        return $this->pinPeerScope($this->hasOne(TfMessageReportDeliveryUntilDate::class, 'id', 'id'));
    }

    public function scheduleRepeatPeriod(): HasOne
    {
        return $this->pinPeerScope($this->hasOne(TfMessageScheduleRepeatPeriod::class, 'id', 'id'));
    }

    public function summaryFromLanguage(): HasOne
    {
        return $this->pinPeerScope($this->hasOne(TfMessageSummaryFromLanguage::class, 'id', 'id'));
    }

    public function ttlPeriod(): HasOne
    {
        return $this->pinPeerScope($this->hasOne(TfMessageTtlPeriod::class, 'id', 'id'));
    }

    public function viaBotId(): HasOne
    {
        return $this->pinPeerScope($this->hasOne(TfMessageViaBotId::class, 'id', 'id'));
    }

    public function viaBusinessBotId(): HasOne
    {
        return $this->pinPeerScope($this->hasOne(TfMessageViaBusinessBotId::class, 'id', 'id'));
    }
}

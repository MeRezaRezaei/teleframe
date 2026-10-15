<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/**
 * Eloquent model for the shipped mirror table `tf_users`.
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
 * Relations declared on this table: 109.
 */
final class TlUser extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tf_users';

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
        'self' => 'boolean',
        'contact' => 'boolean',
        'mutual_contact' => 'boolean',
        'deleted' => 'boolean',
        'bot' => 'boolean',
        'bot_chat_history' => 'boolean',
        'bot_nochats' => 'boolean',
        'verified' => 'boolean',
        'restricted' => 'boolean',
        'min' => 'boolean',
        'bot_inline_geo' => 'boolean',
        'support' => 'boolean',
        'scam' => 'boolean',
        'apply_min_photo' => 'boolean',
        'fake' => 'boolean',
        'bot_attach_menu' => 'boolean',
        'premium' => 'boolean',
        'attach_menu_enabled' => 'boolean',
        'bot_can_edit' => 'boolean',
        'close_friend' => 'boolean',
        'stories_hidden' => 'boolean',
        'stories_unavailable' => 'boolean',
        'contact_require_premium' => 'boolean',
        'bot_business' => 'boolean',
        'bot_has_main_app' => 'boolean',
        'bot_forum_view' => 'boolean',
        'bot_forum_can_manage_topics' => 'boolean',
        'bot_can_manage_bots' => 'boolean',
        'bot_guestchat' => 'boolean',
        'bot_guard' => 'boolean',
    ];

    /** HORIZONTAL, not a foreign key: every `tf_messages_service_action` row whose `tf_messages_service_action`.`boost_peer_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function boostPeerMessagesServiceAction(): HasMany
    {
        return $this->hasMany(TfMessagesServiceAction::class, 'boost_peer_id', 'id')
            ->where('tf_messages_service_action.boost_peer_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_messages_from_id` row whose `tf_messages_from_id`.`from_id_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function fromIdMessagesFromId(): HasMany
    {
        return $this->hasMany(TfMessagesFromId::class, 'from_id_id', 'id')
            ->where('tf_messages_from_id.from_id_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_messages_fwd_from` row whose `tf_messages_fwd_from`.`from_id_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function fromIdMessagesFwdFrom(): HasMany
    {
        return $this->hasMany(TfMessagesFwdFrom::class, 'from_id_id', 'id')
            ->where('tf_messages_fwd_from.from_id_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_messages_service_action` row whose `tf_messages_service_action`.`from_id_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function fromIdMessagesServiceAction(): HasMany
    {
        return $this->hasMany(TfMessagesServiceAction::class, 'from_id_id', 'id')
            ->where('tf_messages_service_action.from_id_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_messages_service_from_id` row whose `tf_messages_service_from_id`.`from_id_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function fromIdMessagesServiceFromId(): HasMany
    {
        return $this->hasMany(TfMessagesServiceFromId::class, 'from_id_id', 'id')
            ->where('tf_messages_service_from_id.from_id_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_saved_star_gifts_from_id` row whose `tf_saved_star_gifts_from_id`.`from_id_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function fromIdSavedStarGiftsFromId(): HasMany
    {
        return $this->hasMany(TfSavedStarGiftsFromId::class, 'from_id_id', 'id')
            ->where('tf_saved_star_gifts_from_id.from_id_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_messages_guestchat_via_from` row whose `tf_messages_guestchat_via_from`.`guestchat_via_from_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function guestchatViaFromMessagesGuestchatViaFrom(): HasMany
    {
        return $this->hasMany(TfMessagesGuestchatViaFrom::class, 'guestchat_via_from_id', 'id')
            ->where('tf_messages_guestchat_via_from.guestchat_via_from_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_saved_star_gifts_gift` row whose `tf_saved_star_gifts_gift`.`host_id_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function hostIdSavedStarGiftsGift(): HasMany
    {
        return $this->hasMany(TfSavedStarGiftsGift::class, 'host_id_id', 'id')
            ->where('tf_saved_star_gifts_gift.host_id_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_stars_transactions_stargift` row whose `tf_stars_transactions_stargift`.`host_id_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function hostIdStarsTransactionsStargift(): HasMany
    {
        return $this->hasMany(TfStarsTransactionsStargift::class, 'host_id_id', 'id')
            ->where('tf_stars_transactions_stargift.host_id_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_messages_media` row whose `tf_messages_media`.`message_peer_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function messagePeerMessagesMedia(): HasMany
    {
        return $this->hasMany(TfMessagesMedia::class, 'message_peer_id', 'id')
            ->where('tf_messages_media.message_peer_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_saved_star_gifts_gift` row whose `tf_saved_star_gifts_gift`.`owner_id_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function ownerIdSavedStarGiftsGift(): HasMany
    {
        return $this->hasMany(TfSavedStarGiftsGift::class, 'owner_id_id', 'id')
            ->where('tf_saved_star_gifts_gift.owner_id_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_stars_transactions_stargift` row whose `tf_stars_transactions_stargift`.`owner_id_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function ownerIdStarsTransactionsStargift(): HasMany
    {
        return $this->hasMany(TfStarsTransactionsStargift::class, 'owner_id_id', 'id')
            ->where('tf_stars_transactions_stargift.owner_id_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_channel_participants_peer` row whose `tf_channel_participants_peer`.`peer_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function peerChannelParticipantsPeer(): HasMany
    {
        return $this->hasMany(TfChannelParticipantsPeer::class, 'peer_id', 'id')
            ->where('tf_channel_participants_peer.peer_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_channel_updates_dialog` row whose `tf_channel_updates_dialog`.`peer_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function peerChannelUpdatesDialog(): HasMany
    {
        return $this->hasMany(TfChannelUpdatesDialog::class, 'peer_id', 'id')
            ->where('tf_channel_updates_dialog.peer_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_chats_migrated_to` row whose `tf_chats_migrated_to`.`peer_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function peerChatsMigratedTo(): HasMany
    {
        return $this->hasMany(TfChatsMigratedTo::class, 'peer_id', 'id')
            ->where('tf_chats_migrated_to.peer_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_dialogs` row whose `tf_dialogs`.`peer_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function peerDialogs(): HasMany
    {
        return $this->hasMany(TlDialog::class, 'peer_id', 'id')
            ->where('tf_dialogs.peer_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_dialogs_draft` row whose `tf_dialogs_draft`.`peer_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function peerDialogsDraft(): HasMany
    {
        return $this->hasMany(TfDialogsDraft::class, 'peer_id', 'id')
            ->where('tf_dialogs_draft.peer_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_dialogs_folder` row whose `tf_dialogs_folder`.`peer_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function peerDialogsFolder(): HasMany
    {
        return $this->hasMany(TfDialogsFolder::class, 'peer_id', 'id')
            ->where('tf_dialogs_folder.peer_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_dialogs_folder_id` row whose `tf_dialogs_folder_id`.`peer_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function peerDialogsFolderId(): HasMany
    {
        return $this->hasMany(TfDialogsFolderId::class, 'peer_id', 'id')
            ->where('tf_dialogs_folder_id.peer_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_dialogs_notify_settings` row whose `tf_dialogs_notify_settings`.`peer_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function peerDialogsNotifySettings(): HasMany
    {
        return $this->hasMany(TfDialogsNotifySettings::class, 'peer_id', 'id')
            ->where('tf_dialogs_notify_settings.peer_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_dialogs_pts` row whose `tf_dialogs_pts`.`peer_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function peerDialogsPts(): HasMany
    {
        return $this->hasMany(TfDialogsPts::class, 'peer_id', 'id')
            ->where('tf_dialogs_pts.peer_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_dialogs_ttl_period` row whose `tf_dialogs_ttl_period`.`peer_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function peerDialogsTtlPeriod(): HasMany
    {
        return $this->hasMany(TfDialogsTtlPeriod::class, 'peer_id', 'id')
            ->where('tf_dialogs_ttl_period.peer_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_message_actions` row whose `tf_message_actions`.`peer_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function peerMessageActions(): HasMany
    {
        return $this->hasMany(TfMessageActions::class, 'peer_id', 'id')
            ->where('tf_message_actions.peer_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_message_actions_users` row whose `tf_message_actions_users`.`peer_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function peerMessageActionsUsers(): HasMany
    {
        return $this->hasMany(TfMessageActionsUsers::class, 'peer_id', 'id')
            ->where('tf_message_actions_users.peer_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_message_medias` row whose `tf_message_medias`.`peer_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function peerMessageMedias(): HasMany
    {
        return $this->hasMany(TfMessageMedias::class, 'peer_id', 'id')
            ->where('tf_message_medias.peer_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_message_medias_ttl_seconds` row whose `tf_message_medias_ttl_seconds`.`peer_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function peerMessageMediasTtlSeconds(): HasMany
    {
        return $this->hasMany(TfMessageMediasTtlSeconds::class, 'peer_id', 'id')
            ->where('tf_message_medias_ttl_seconds.peer_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_message_search` row whose `tf_message_search`.`peer_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function peerMessageSearch(): HasMany
    {
        return $this->hasMany(TfMessageSearch::class, 'peer_id', 'id')
            ->where('tf_message_search.peer_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_messages` row whose `tf_messages`.`peer_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function peerMessages(): HasMany
    {
        return $this->hasMany(TlMessage::class, 'peer_id', 'id')
            ->where('tf_messages.peer_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_messages_edit_date` row whose `tf_messages_edit_date`.`peer_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function peerMessagesEditDate(): HasMany
    {
        return $this->hasMany(TfMessagesEditDate::class, 'peer_id', 'id')
            ->where('tf_messages_edit_date.peer_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_messages_effect` row whose `tf_messages_effect`.`peer_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function peerMessagesEffect(): HasMany
    {
        return $this->hasMany(TfMessagesEffect::class, 'peer_id', 'id')
            ->where('tf_messages_effect.peer_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_messages_entities` row whose `tf_messages_entities`.`peer_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function peerMessagesEntities(): HasMany
    {
        return $this->hasMany(TfMessagesEntities::class, 'peer_id', 'id')
            ->where('tf_messages_entities.peer_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_messages_factcheck` row whose `tf_messages_factcheck`.`peer_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function peerMessagesFactcheck(): HasMany
    {
        return $this->hasMany(TfMessagesFactcheck::class, 'peer_id', 'id')
            ->where('tf_messages_factcheck.peer_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_messages_forwards` row whose `tf_messages_forwards`.`peer_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function peerMessagesForwards(): HasMany
    {
        return $this->hasMany(TfMessagesForwards::class, 'peer_id', 'id')
            ->where('tf_messages_forwards.peer_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_messages_from_boosts_applied` row whose `tf_messages_from_boosts_applied`.`peer_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function peerMessagesFromBoostsApplied(): HasMany
    {
        return $this->hasMany(TfMessagesFromBoostsApplied::class, 'peer_id', 'id')
            ->where('tf_messages_from_boosts_applied.peer_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_messages_from_id` row whose `tf_messages_from_id`.`peer_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function peerMessagesFromId(): HasMany
    {
        return $this->hasMany(TfMessagesFromId::class, 'peer_id', 'id')
            ->where('tf_messages_from_id.peer_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_messages_from_rank` row whose `tf_messages_from_rank`.`peer_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function peerMessagesFromRank(): HasMany
    {
        return $this->hasMany(TfMessagesFromRank::class, 'peer_id', 'id')
            ->where('tf_messages_from_rank.peer_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_messages_fwd_from` row whose `tf_messages_fwd_from`.`peer_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function peerMessagesFwdFrom(): HasMany
    {
        return $this->hasMany(TfMessagesFwdFrom::class, 'peer_id', 'id')
            ->where('tf_messages_fwd_from.peer_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_messages_grouped_id` row whose `tf_messages_grouped_id`.`peer_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function peerMessagesGroupedId(): HasMany
    {
        return $this->hasMany(TfMessagesGroupedId::class, 'peer_id', 'id')
            ->where('tf_messages_grouped_id.peer_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_messages_guestchat_via_from` row whose `tf_messages_guestchat_via_from`.`peer_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function peerMessagesGuestchatViaFrom(): HasMany
    {
        return $this->hasMany(TfMessagesGuestchatViaFrom::class, 'peer_id', 'id')
            ->where('tf_messages_guestchat_via_from.peer_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_messages_media` row whose `tf_messages_media`.`peer_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function peerMessagesMedia(): HasMany
    {
        return $this->hasMany(TfMessagesMedia::class, 'peer_id', 'id')
            ->where('tf_messages_media.peer_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_messages_paid_message_stars` row whose `tf_messages_paid_message_stars`.`peer_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function peerMessagesPaidMessageStars(): HasMany
    {
        return $this->hasMany(TfMessagesPaidMessageStars::class, 'peer_id', 'id')
            ->where('tf_messages_paid_message_stars.peer_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_messages_post_author` row whose `tf_messages_post_author`.`peer_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function peerMessagesPostAuthor(): HasMany
    {
        return $this->hasMany(TfMessagesPostAuthor::class, 'peer_id', 'id')
            ->where('tf_messages_post_author.peer_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_messages_quick_reply_shortcut_id` row whose `tf_messages_quick_reply_shortcut_id`.`peer_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function peerMessagesQuickReplyShortcutId(): HasMany
    {
        return $this->hasMany(TfMessagesQuickReplyShortcutId::class, 'peer_id', 'id')
            ->where('tf_messages_quick_reply_shortcut_id.peer_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_messages_reactions` row whose `tf_messages_reactions`.`peer_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function peerMessagesReactions(): HasMany
    {
        return $this->hasMany(TfMessagesReactions::class, 'peer_id', 'id')
            ->where('tf_messages_reactions.peer_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_messages_replies` row whose `tf_messages_replies`.`peer_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function peerMessagesReplies(): HasMany
    {
        return $this->hasMany(TfMessagesReplies::class, 'peer_id', 'id')
            ->where('tf_messages_replies.peer_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_messages_reply_markup` row whose `tf_messages_reply_markup`.`peer_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function peerMessagesReplyMarkup(): HasMany
    {
        return $this->hasMany(TfMessagesReplyMarkup::class, 'peer_id', 'id')
            ->where('tf_messages_reply_markup.peer_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_messages_reply_markup_rows` row whose `tf_messages_reply_markup_rows`.`peer_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function peerMessagesReplyMarkupRows(): HasMany
    {
        return $this->hasMany(TfMessagesReplyMarkupRows::class, 'peer_id', 'id')
            ->where('tf_messages_reply_markup_rows.peer_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_messages_reply_markup_rows_buttons` row whose `tf_messages_reply_markup_rows_buttons`.`peer_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function peerMessagesReplyMarkupRowsButtons(): HasMany
    {
        return $this->hasMany(TfMessagesReplyMarkupRowsButtons::class, 'peer_id', 'id')
            ->where('tf_messages_reply_markup_rows_buttons.peer_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_messages_reply_to` row whose `tf_messages_reply_to`.`peer_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function peerMessagesReplyTo(): HasMany
    {
        return $this->hasMany(TfMessagesReplyTo::class, 'peer_id', 'id')
            ->where('tf_messages_reply_to.peer_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_messages_report_delivery_until_date` row whose `tf_messages_report_delivery_until_date`.`peer_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function peerMessagesReportDeliveryUntilDate(): HasMany
    {
        return $this->hasMany(TfMessagesReportDeliveryUntilDate::class, 'peer_id', 'id')
            ->where('tf_messages_report_delivery_until_date.peer_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_messages_restriction_reason` row whose `tf_messages_restriction_reason`.`peer_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function peerMessagesRestrictionReason(): HasMany
    {
        return $this->hasMany(TfMessagesRestrictionReason::class, 'peer_id', 'id')
            ->where('tf_messages_restriction_reason.peer_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_messages_rich_message` row whose `tf_messages_rich_message`.`peer_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function peerMessagesRichMessage(): HasMany
    {
        return $this->hasMany(TfMessagesRichMessage::class, 'peer_id', 'id')
            ->where('tf_messages_rich_message.peer_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_messages_saved_peer_id` row whose `tf_messages_saved_peer_id`.`peer_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function peerMessagesSavedPeerId(): HasMany
    {
        return $this->hasMany(TfMessagesSavedPeerId::class, 'peer_id', 'id')
            ->where('tf_messages_saved_peer_id.peer_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_messages_schedule_repeat_period` row whose `tf_messages_schedule_repeat_period`.`peer_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function peerMessagesScheduleRepeatPeriod(): HasMany
    {
        return $this->hasMany(TfMessagesScheduleRepeatPeriod::class, 'peer_id', 'id')
            ->where('tf_messages_schedule_repeat_period.peer_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_messages_service` row whose `tf_messages_service`.`peer_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function peerMessagesService(): HasMany
    {
        return $this->hasMany(TfMessagesService::class, 'peer_id', 'id')
            ->where('tf_messages_service.peer_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_messages_service_action` row whose `tf_messages_service_action`.`peer_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function peerMessagesServiceAction(): HasMany
    {
        return $this->hasMany(TfMessagesServiceAction::class, 'peer_id', 'id')
            ->where('tf_messages_service_action.peer_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_messages_service_from_id` row whose `tf_messages_service_from_id`.`peer_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function peerMessagesServiceFromId(): HasMany
    {
        return $this->hasMany(TfMessagesServiceFromId::class, 'peer_id', 'id')
            ->where('tf_messages_service_from_id.peer_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_messages_service_reactions` row whose `tf_messages_service_reactions`.`peer_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function peerMessagesServiceReactions(): HasMany
    {
        return $this->hasMany(TfMessagesServiceReactions::class, 'peer_id', 'id')
            ->where('tf_messages_service_reactions.peer_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_messages_service_reply_to` row whose `tf_messages_service_reply_to`.`peer_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function peerMessagesServiceReplyTo(): HasMany
    {
        return $this->hasMany(TfMessagesServiceReplyTo::class, 'peer_id', 'id')
            ->where('tf_messages_service_reply_to.peer_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_messages_service_saved_peer_id` row whose `tf_messages_service_saved_peer_id`.`peer_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function peerMessagesServiceSavedPeerId(): HasMany
    {
        return $this->hasMany(TfMessagesServiceSavedPeerId::class, 'peer_id', 'id')
            ->where('tf_messages_service_saved_peer_id.peer_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_messages_service_ttl_period` row whose `tf_messages_service_ttl_period`.`peer_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function peerMessagesServiceTtlPeriod(): HasMany
    {
        return $this->hasMany(TfMessagesServiceTtlPeriod::class, 'peer_id', 'id')
            ->where('tf_messages_service_ttl_period.peer_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_messages_suggested_post` row whose `tf_messages_suggested_post`.`peer_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function peerMessagesSuggestedPost(): HasMany
    {
        return $this->hasMany(TfMessagesSuggestedPost::class, 'peer_id', 'id')
            ->where('tf_messages_suggested_post.peer_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_messages_summary_from_language` row whose `tf_messages_summary_from_language`.`peer_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function peerMessagesSummaryFromLanguage(): HasMany
    {
        return $this->hasMany(TfMessagesSummaryFromLanguage::class, 'peer_id', 'id')
            ->where('tf_messages_summary_from_language.peer_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_messages_ttl_period` row whose `tf_messages_ttl_period`.`peer_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function peerMessagesTtlPeriod(): HasMany
    {
        return $this->hasMany(TfMessagesTtlPeriod::class, 'peer_id', 'id')
            ->where('tf_messages_ttl_period.peer_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_messages_via_bot_id` row whose `tf_messages_via_bot_id`.`peer_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function peerMessagesViaBotId(): HasMany
    {
        return $this->hasMany(TfMessagesViaBotId::class, 'peer_id', 'id')
            ->where('tf_messages_via_bot_id.peer_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_messages_via_business_bot_id` row whose `tf_messages_via_business_bot_id`.`peer_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function peerMessagesViaBusinessBotId(): HasMany
    {
        return $this->hasMany(TfMessagesViaBusinessBotId::class, 'peer_id', 'id')
            ->where('tf_messages_via_business_bot_id.peer_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_messages_views` row whose `tf_messages_views`.`peer_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function peerMessagesViews(): HasMany
    {
        return $this->hasMany(TfMessagesViews::class, 'peer_id', 'id')
            ->where('tf_messages_views.peer_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_saved_dialogs` row whose `tf_saved_dialogs`.`peer_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function peerSavedDialogs(): HasMany
    {
        return $this->hasMany(TfSavedDialogs::class, 'peer_id', 'id')
            ->where('tf_saved_dialogs.peer_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_stars_subscriptions` row whose `tf_stars_subscriptions`.`peer_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function peerStarsSubscriptions(): HasMany
    {
        return $this->hasMany(TfStarsSubscriptions::class, 'peer_id', 'id')
            ->where('tf_stars_subscriptions.peer_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_stars_transactions_extended_media` row whose `tf_stars_transactions_extended_media`.`peer_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function peerStarsTransactionsExtendedMedia(): HasMany
    {
        return $this->hasMany(TfStarsTransactionsExtendedMedia::class, 'peer_id', 'id')
            ->where('tf_stars_transactions_extended_media.peer_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_stars_transactions_peer` row whose `tf_stars_transactions_peer`.`peer_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function peerStarsTransactionsPeer(): HasMany
    {
        return $this->hasMany(TfStarsTransactionsPeer::class, 'peer_id', 'id')
            ->where('tf_stars_transactions_peer.peer_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_updates_from` row whose `tf_updates_from`.`peer_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function peerUpdatesFrom(): HasMany
    {
        return $this->hasMany(TfUpdatesFrom::class, 'peer_id', 'id')
            ->where('tf_updates_from.peer_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_updates_message` row whose `tf_updates_message`.`peer_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function peerUpdatesMessage(): HasMany
    {
        return $this->hasMany(TfUpdatesMessage::class, 'peer_id', 'id')
            ->where('tf_updates_message.peer_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_updates_peer` row whose `tf_updates_peer`.`peer_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function peerUpdatesPeer(): HasMany
    {
        return $this->hasMany(TfUpdatesPeer::class, 'peer_id', 'id')
            ->where('tf_updates_peer.peer_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_messages_replies` row whose `tf_messages_replies`.`recent_repliers_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function recentRepliersMessagesReplies(): HasMany
    {
        return $this->hasMany(TfMessagesReplies::class, 'recent_repliers_id', 'id')
            ->where('tf_messages_replies.recent_repliers_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_saved_star_gifts_gift` row whose `tf_saved_star_gifts_gift`.`released_by_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function releasedBySavedStarGiftsGift(): HasMany
    {
        return $this->hasMany(TfSavedStarGiftsGift::class, 'released_by_id', 'id')
            ->where('tf_saved_star_gifts_gift.released_by_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_stars_transactions_stargift` row whose `tf_stars_transactions_stargift`.`released_by_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function releasedByStarsTransactionsStargift(): HasMany
    {
        return $this->hasMany(TfStarsTransactionsStargift::class, 'released_by_id', 'id')
            ->where('tf_stars_transactions_stargift.released_by_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_messages_reply_to` row whose `tf_messages_reply_to`.`reply_to_peer_id_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function replyToPeerIdMessagesReplyTo(): HasMany
    {
        return $this->hasMany(TfMessagesReplyTo::class, 'reply_to_peer_id_id', 'id')
            ->where('tf_messages_reply_to.reply_to_peer_id_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_messages_service_reply_to` row whose `tf_messages_service_reply_to`.`reply_to_peer_id_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function replyToPeerIdMessagesServiceReplyTo(): HasMany
    {
        return $this->hasMany(TfMessagesServiceReplyTo::class, 'reply_to_peer_id_id', 'id')
            ->where('tf_messages_service_reply_to.reply_to_peer_id_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_messages_fwd_from` row whose `tf_messages_fwd_from`.`saved_from_id_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function savedFromIdMessagesFwdFrom(): HasMany
    {
        return $this->hasMany(TfMessagesFwdFrom::class, 'saved_from_id_id', 'id')
            ->where('tf_messages_fwd_from.saved_from_id_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_messages_fwd_from` row whose `tf_messages_fwd_from`.`saved_from_peer_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function savedFromPeerMessagesFwdFrom(): HasMany
    {
        return $this->hasMany(TfMessagesFwdFrom::class, 'saved_from_peer_id', 'id')
            ->where('tf_messages_fwd_from.saved_from_peer_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_messages_saved_peer_id` row whose `tf_messages_saved_peer_id`.`saved_peer_id_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function savedPeerIdMessagesSavedPeerId(): HasMany
    {
        return $this->hasMany(TfMessagesSavedPeerId::class, 'saved_peer_id_id', 'id')
            ->where('tf_messages_saved_peer_id.saved_peer_id_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_messages_service_saved_peer_id` row whose `tf_messages_service_saved_peer_id`.`saved_peer_id_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function savedPeerIdMessagesServiceSavedPeerId(): HasMany
    {
        return $this->hasMany(TfMessagesServiceSavedPeerId::class, 'saved_peer_id_id', 'id')
            ->where('tf_messages_service_saved_peer_id.saved_peer_id_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_stars_transactions_starref_peer` row whose `tf_stars_transactions_starref_peer`.`starref_peer_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function starrefPeerStarsTransactionsStarrefPeer(): HasMany
    {
        return $this->hasMany(TfStarsTransactionsStarrefPeer::class, 'starref_peer_id', 'id')
            ->where('tf_stars_transactions_starref_peer.starref_peer_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_saved_star_gifts_gift` row whose `tf_saved_star_gifts_gift`.`theme_peer_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function themePeerSavedStarGiftsGift(): HasMany
    {
        return $this->hasMany(TfSavedStarGiftsGift::class, 'theme_peer_id', 'id')
            ->where('tf_saved_star_gifts_gift.theme_peer_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_stars_transactions_stargift` row whose `tf_stars_transactions_stargift`.`theme_peer_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function themePeerStarsTransactionsStargift(): HasMany
    {
        return $this->hasMany(TfStarsTransactionsStargift::class, 'theme_peer_id', 'id')
            ->where('tf_stars_transactions_stargift.theme_peer_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_messages_service_action` row whose `tf_messages_service_action`.`to_id_type` is 1, so this reaches `tf_users` without depending on which id space the peer fell in. Both legs are constrained — the id leg by the relation and the kind leg by the predicate — so this cannot match the wrong entity table. */
    public function toIdMessagesServiceAction(): HasMany
    {
        return $this->hasMany(TfMessagesServiceAction::class, 'to_id_id', 'id')
            ->where('tf_messages_service_action.to_id_type', 1);
    }

    /** HORIZONTAL, not a foreign key: every `tf_admin_log_events` row naming this `tf_users` in `user_id`. No FK is declared on it — see the forward side for why. */
    public function userIdAdminLogEvents(): HasMany
    {
        return $this->hasMany(TfAdminLogEvents::class, 'user_id', 'id');
    }

    /** HORIZONTAL, not a foreign key: every `tf_channel_participants` row naming this `tf_users` in `user_id`. No FK is declared on it — see the forward side for why. */
    public function userIdChannelParticipants(): HasMany
    {
        return $this->hasMany(TlChannelParticipant::class, 'user_id', 'id');
    }

    /** HORIZONTAL, not a foreign key: every `tf_updates` row naming this `tf_users` in `user_id`. No FK is declared on it — see the forward side for why. */
    public function userIdUpdates(): HasMany
    {
        return $this->hasMany(TlUpdate::class, 'user_id', 'id');
    }

    /** Declared foreign key (1:N): `tf_users_access_hash`(account_id, id) -> `tf_users`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function usersAccessHash(): HasMany
    {
        return $this->hasMany(TfUsersAccessHash::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_users_bot_active_users`(account_id, id) -> `tf_users`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function usersBotActiveUsers(): HasMany
    {
        return $this->hasMany(TfUsersBotActiveUsers::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_users_bot_info_version`(account_id, id) -> `tf_users`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function usersBotInfoVersion(): HasMany
    {
        return $this->hasMany(TfUsersBotInfoVersion::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_users_bot_inline_placeholder`(account_id, id) -> `tf_users`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function usersBotInlinePlaceholder(): HasMany
    {
        return $this->hasMany(TfUsersBotInlinePlaceholder::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_users_bot_verification_icon`(account_id, id) -> `tf_users`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function usersBotVerificationIcon(): HasMany
    {
        return $this->hasMany(TfUsersBotVerificationIcon::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_users_color`(account_id, id) -> `tf_users`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function usersColor(): HasMany
    {
        return $this->hasMany(TfUsersColor::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_users_emoji_status`(account_id, id) -> `tf_users`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function usersEmojiStatus(): HasMany
    {
        return $this->hasMany(TfUsersEmojiStatus::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_users_first_name`(account_id, id) -> `tf_users`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function usersFirstName(): HasMany
    {
        return $this->hasMany(TfUsersFirstName::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_users_lang_code`(account_id, id) -> `tf_users`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function usersLangCode(): HasMany
    {
        return $this->hasMany(TfUsersLangCode::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_users_last_name`(account_id, id) -> `tf_users`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function usersLastName(): HasMany
    {
        return $this->hasMany(TfUsersLastName::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_users_phone`(account_id, id) -> `tf_users`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function usersPhone(): HasMany
    {
        return $this->hasMany(TfUsersPhone::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_users_photo`(account_id, id) -> `tf_users`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function usersPhoto(): HasMany
    {
        return $this->hasMany(TfUsersPhoto::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_users_profile_color`(account_id, id) -> `tf_users`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function usersProfileColor(): HasMany
    {
        return $this->hasMany(TfUsersProfileColor::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_users_restriction_reason`(account_id, id) -> `tf_users`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function usersRestrictionReason(): HasMany
    {
        return $this->hasMany(TfUsersRestrictionReason::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_users_send_paid_messages_stars`(account_id, id) -> `tf_users`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function usersSendPaidMessagesStars(): HasMany
    {
        return $this->hasMany(TfUsersSendPaidMessagesStars::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_users_status`(account_id, id) -> `tf_users`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function usersStatus(): HasMany
    {
        return $this->hasMany(TfUsersStatus::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_users_stories_max_id`(account_id, id) -> `tf_users`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function usersStoriesMaxId(): HasMany
    {
        return $this->hasMany(TfUsersStoriesMaxId::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_users_username`(account_id, id) -> `tf_users`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function usersUsername(): HasMany
    {
        return $this->hasMany(TfUsersUsername::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_users_usernames`(account_id, id) -> `tf_users`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function usersUsernames(): HasMany
    {
        return $this->hasMany(TfUsersUsernames::class, 'id', 'id');
    }
}

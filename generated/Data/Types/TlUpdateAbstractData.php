<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/**
 * Union DTO base for TL type Update.
 *
 * @method static static hydrate(array $payload)
 */
abstract class TlUpdateAbstractData extends Data
{
    /** @var array<string, class-string<self>> */
    protected const DISPATCH = [
        'updateAiComposeTones' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateAiComposeTonesData::class,
        'updateAttachMenuBots' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateAttachMenuBotsData::class,
        'updateAutoSaveSettings' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateAutoSaveSettingsData::class,
        'updateBotBusinessConnect' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateBotBusinessConnectData::class,
        'updateBotCallbackQuery' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateBotCallbackQueryData::class,
        'updateBotChatBoost' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateBotChatBoostData::class,
        'updateBotChatInviteRequester' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateBotChatInviteRequesterData::class,
        'updateBotCommands' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateBotCommandsData::class,
        'updateBotDeleteBusinessMessage' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateBotDeleteBusinessMessageData::class,
        'updateBotEditBusinessMessage' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateBotEditBusinessMessageData::class,
        'updateBotGuestChatQuery' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateBotGuestChatQueryData::class,
        'updateBotInlineQuery' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateBotInlineQueryData::class,
        'updateBotInlineSend' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateBotInlineSendData::class,
        'updateBotMenuButton' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateBotMenuButtonData::class,
        'updateBotMessageReaction' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateBotMessageReactionData::class,
        'updateBotMessageReactions' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateBotMessageReactionsData::class,
        'updateBotNewBusinessMessage' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateBotNewBusinessMessageData::class,
        'updateBotPrecheckoutQuery' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateBotPrecheckoutQueryData::class,
        'updateBotPurchasedPaidMedia' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateBotPurchasedPaidMediaData::class,
        'updateBotShippingQuery' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateBotShippingQueryData::class,
        'updateBotStopped' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateBotStoppedData::class,
        'updateBotWebhookJSON' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateBotWebhookJSONData::class,
        'updateBotWebhookJSONQuery' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateBotWebhookJSONQueryData::class,
        'updateBusinessBotCallbackQuery' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateBusinessBotCallbackQueryData::class,
        'updateChannel' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateChannelData::class,
        'updateChannelAvailableMessages' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateChannelAvailableMessagesData::class,
        'updateChannelMessageForwards' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateChannelMessageForwardsData::class,
        'updateChannelMessageViews' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateChannelMessageViewsData::class,
        'updateChannelParticipant' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateChannelParticipantData::class,
        'updateChannelReadMessagesContents' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateChannelReadMessagesContentsData::class,
        'updateChannelTooLong' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateChannelTooLongData::class,
        'updateChannelUserTyping' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateChannelUserTypingData::class,
        'updateChannelViewForumAsMessages' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateChannelViewForumAsMessagesData::class,
        'updateChannelWebPage' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateChannelWebPageData::class,
        'updateChat' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateChatData::class,
        'updateChatDefaultBannedRights' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateChatDefaultBannedRightsData::class,
        'updateChatParticipant' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateChatParticipantData::class,
        'updateChatParticipantAdd' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateChatParticipantAddData::class,
        'updateChatParticipantAdmin' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateChatParticipantAdminData::class,
        'updateChatParticipantDelete' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateChatParticipantDeleteData::class,
        'updateChatParticipantRank' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateChatParticipantRankData::class,
        'updateChatParticipants' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateChatParticipantsData::class,
        'updateChatUserTyping' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateChatUserTypingData::class,
        'updateConfig' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateConfigData::class,
        'updateContactsReset' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateContactsResetData::class,
        'updateDcOptions' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateDcOptionsData::class,
        'updateDeleteChannelMessages' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateDeleteChannelMessagesData::class,
        'updateDeleteGroupCallMessages' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateDeleteGroupCallMessagesData::class,
        'updateDeleteMessages' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateDeleteMessagesData::class,
        'updateDeleteQuickReply' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateDeleteQuickReplyData::class,
        'updateDeleteQuickReplyMessages' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateDeleteQuickReplyMessagesData::class,
        'updateDeleteScheduledMessages' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateDeleteScheduledMessagesData::class,
        'updateDialogFilter' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateDialogFilterData::class,
        'updateDialogFilterOrder' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateDialogFilterOrderData::class,
        'updateDialogFilters' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateDialogFiltersData::class,
        'updateDialogPinned' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateDialogPinnedData::class,
        'updateDialogUnreadMark' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateDialogUnreadMarkData::class,
        'updateDraftMessage' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateDraftMessageData::class,
        'updateEditChannelMessage' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateEditChannelMessageData::class,
        'updateEditMessage' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateEditMessageData::class,
        'updateEmojiGameInfo' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateEmojiGameInfoData::class,
        'updateEncryptedChatTyping' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateEncryptedChatTypingData::class,
        'updateEncryptedMessagesRead' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateEncryptedMessagesReadData::class,
        'updateEncryption' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateEncryptionData::class,
        'updateFavedStickers' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateFavedStickersData::class,
        'updateFolderPeers' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateFolderPeersData::class,
        'updateGeoLiveViewed' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateGeoLiveViewedData::class,
        'updateGroupCall' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateGroupCallData::class,
        'updateGroupCallChainBlocks' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateGroupCallChainBlocksData::class,
        'updateGroupCallConnection' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateGroupCallConnectionData::class,
        'updateGroupCallEncryptedMessage' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateGroupCallEncryptedMessageData::class,
        'updateGroupCallMessage' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateGroupCallMessageData::class,
        'updateGroupCallParticipants' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateGroupCallParticipantsData::class,
        'updateInlineBotCallbackQuery' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateInlineBotCallbackQueryData::class,
        'updateJoinChatWebViewDecision' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateJoinChatWebViewDecisionData::class,
        'updateLangPack' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateLangPackData::class,
        'updateLangPackTooLong' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateLangPackTooLongData::class,
        'updateLoginToken' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateLoginTokenData::class,
        'updateManagedBot' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateManagedBotData::class,
        'updateMessageExtendedMedia' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateMessageExtendedMediaData::class,
        'updateMessageID' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateMessageIDData::class,
        'updateMessagePoll' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateMessagePollData::class,
        'updateMessagePollVote' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateMessagePollVoteData::class,
        'updateMessageReactions' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateMessageReactionsData::class,
        'updateMonoForumNoPaidException' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateMonoForumNoPaidExceptionData::class,
        'updateMoveStickerSetToTop' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateMoveStickerSetToTopData::class,
        'updateNewAuthorization' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateNewAuthorizationData::class,
        'updateNewBotConnection' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateNewBotConnectionData::class,
        'updateNewChannelMessage' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateNewChannelMessageData::class,
        'updateNewEncryptedMessage' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateNewEncryptedMessageData::class,
        'updateNewMessage' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateNewMessageData::class,
        'updateNewQuickReply' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateNewQuickReplyData::class,
        'updateNewScheduledMessage' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateNewScheduledMessageData::class,
        'updateNewStickerSet' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateNewStickerSetData::class,
        'updateNewStoryReaction' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateNewStoryReactionData::class,
        'updateNotifySettings' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateNotifySettingsData::class,
        'updatePaidReactionPrivacy' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdatePaidReactionPrivacyData::class,
        'updatePeerBlocked' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdatePeerBlockedData::class,
        'updatePeerHistoryTTL' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdatePeerHistoryTTLData::class,
        'updatePeerLocated' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdatePeerLocatedData::class,
        'updatePeerSettings' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdatePeerSettingsData::class,
        'updatePeerWallpaper' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdatePeerWallpaperData::class,
        'updatePendingJoinRequests' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdatePendingJoinRequestsData::class,
        'updatePhoneCall' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdatePhoneCallData::class,
        'updatePhoneCallSignalingData' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdatePhoneCallSignalingDataData::class,
        'updatePinnedChannelMessages' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdatePinnedChannelMessagesData::class,
        'updatePinnedDialogs' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdatePinnedDialogsData::class,
        'updatePinnedForumTopic' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdatePinnedForumTopicData::class,
        'updatePinnedForumTopics' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdatePinnedForumTopicsData::class,
        'updatePinnedMessages' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdatePinnedMessagesData::class,
        'updatePinnedSavedDialogs' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdatePinnedSavedDialogsData::class,
        'updatePrivacy' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdatePrivacyData::class,
        'updatePtsChanged' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdatePtsChangedData::class,
        'updateQuickReplies' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateQuickRepliesData::class,
        'updateQuickReplyMessage' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateQuickReplyMessageData::class,
        'updateReadChannelDiscussionInbox' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateReadChannelDiscussionInboxData::class,
        'updateReadChannelDiscussionOutbox' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateReadChannelDiscussionOutboxData::class,
        'updateReadChannelInbox' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateReadChannelInboxData::class,
        'updateReadChannelOutbox' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateReadChannelOutboxData::class,
        'updateReadFeaturedEmojiStickers' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateReadFeaturedEmojiStickersData::class,
        'updateReadFeaturedStickers' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateReadFeaturedStickersData::class,
        'updateReadHistoryInbox' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateReadHistoryInboxData::class,
        'updateReadHistoryOutbox' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateReadHistoryOutboxData::class,
        'updateReadMessagesContents' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateReadMessagesContentsData::class,
        'updateReadMonoForumInbox' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateReadMonoForumInboxData::class,
        'updateReadMonoForumOutbox' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateReadMonoForumOutboxData::class,
        'updateReadStories' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateReadStoriesData::class,
        'updateRecentEmojiStatuses' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateRecentEmojiStatusesData::class,
        'updateRecentReactions' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateRecentReactionsData::class,
        'updateRecentStickers' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateRecentStickersData::class,
        'updateSavedDialogPinned' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateSavedDialogPinnedData::class,
        'updateSavedGifs' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateSavedGifsData::class,
        'updateSavedReactionTags' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateSavedReactionTagsData::class,
        'updateSavedRingtones' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateSavedRingtonesData::class,
        'updateSentPhoneCode' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateSentPhoneCodeData::class,
        'updateSentStoryReaction' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateSentStoryReactionData::class,
        'updateServiceNotification' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateServiceNotificationData::class,
        'updateSmsJob' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateSmsJobData::class,
        'updateStarGiftAuctionState' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateStarGiftAuctionStateData::class,
        'updateStarGiftAuctionUserState' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateStarGiftAuctionUserStateData::class,
        'updateStarGiftCraftFail' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateStarGiftCraftFailData::class,
        'updateStarsBalance' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateStarsBalanceData::class,
        'updateStarsRevenueStatus' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateStarsRevenueStatusData::class,
        'updateStickerSets' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateStickerSetsData::class,
        'updateStickerSetsOrder' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateStickerSetsOrderData::class,
        'updateStoriesStealthMode' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateStoriesStealthModeData::class,
        'updateStory' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateStoryData::class,
        'updateStoryID' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateStoryIDData::class,
        'updateTheme' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateThemeData::class,
        'updateTranscribedAudio' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateTranscribedAudioData::class,
        'updateUser' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateUserData::class,
        'updateUserEmojiStatus' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateUserEmojiStatusData::class,
        'updateUserName' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateUserNameData::class,
        'updateUserPhone' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateUserPhoneData::class,
        'updateUserStatus' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateUserStatusData::class,
        'updateUserTyping' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateUserTypingData::class,
        'updateWebBrowserException' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateWebBrowserExceptionData::class,
        'updateWebBrowserSettings' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateWebBrowserSettingsData::class,
        'updateWebPage' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateWebPageData::class,
        'updateWebViewResultSent' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UpdateWebViewResultSentData::class,
    ];

    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [];

    /** Dispatch on the constructor name carried under the '_' key of a decoded wire payload. */
    public static function hydrate(array $payload): static
    {
        $class = static::DISPATCH[$payload['_']]
            ?? throw new \InvalidArgumentException('Unknown constructor ' . $payload['_'] . ' for Update');
        foreach ((new \ReflectionMethod($class, '__construct'))->getParameters() as $param) {
            $name = $param->getName();
            if (array_key_exists($name, $payload)) {
                continue;
            }
            $bits = $class::TL_FLAG_BITS[$name] ?? null;
            if ($bits !== null) {
                $word = (int) ($payload[$bits[0]] ?? 0);
                $payload[$name] = (bool) ($word >> $bits[1] & 1);
                continue;
            }
            $wireKey = self::tlWireKey($name);
            $payload[$name] = array_key_exists($wireKey, $payload) ? $payload[$wireKey] : null;
        }
        /** @var static */
        return $class::from($payload);
    }

    /** camelCase constructor param name to snake_case wire key (regex-free). */
    private static function tlWireKey(string $name): string
    {
        $out = '';
        foreach (str_split($name) as $i => $ch) {
            $out .= $i > 0 && $ch >= 'A' && $ch <= 'Z' ? '_' . strtolower($ch) : $ch;
        }
        return $out;
    }
}

<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/**
 * Union DTO base for TL type MessageAction.
 *
 * @method static static hydrate(array $payload)
 */
abstract class TlMessageActionAbstractData extends Data
{
    /** @var array<string, class-string<self>> */
    protected const DISPATCH = [
        'messageActionBoostApply' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageActionBoostApplyData::class,
        'messageActionBotAllowed' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageActionBotAllowedData::class,
        'messageActionChangeCreator' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageActionChangeCreatorData::class,
        'messageActionChannelCreate' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageActionChannelCreateData::class,
        'messageActionChannelMigrateFrom' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageActionChannelMigrateFromData::class,
        'messageActionChatAddUser' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageActionChatAddUserData::class,
        'messageActionChatCreate' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageActionChatCreateData::class,
        'messageActionChatDeletePhoto' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageActionChatDeletePhotoData::class,
        'messageActionChatDeleteUser' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageActionChatDeleteUserData::class,
        'messageActionChatEditPhoto' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageActionChatEditPhotoData::class,
        'messageActionChatEditTitle' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageActionChatEditTitleData::class,
        'messageActionChatJoinedByLink' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageActionChatJoinedByLinkData::class,
        'messageActionChatJoinedByRequest' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageActionChatJoinedByRequestData::class,
        'messageActionChatMigrateTo' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageActionChatMigrateToData::class,
        'messageActionConferenceCall' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageActionConferenceCallData::class,
        'messageActionContactSignUp' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageActionContactSignUpData::class,
        'messageActionCustomAction' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageActionCustomActionData::class,
        'messageActionEmpty' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageActionEmptyData::class,
        'messageActionGameScore' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageActionGameScoreData::class,
        'messageActionGeoProximityReached' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageActionGeoProximityReachedData::class,
        'messageActionGiftCode' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageActionGiftCodeData::class,
        'messageActionGiftPremium' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageActionGiftPremiumData::class,
        'messageActionGiftStars' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageActionGiftStarsData::class,
        'messageActionGiftTon' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageActionGiftTonData::class,
        'messageActionGiveawayLaunch' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageActionGiveawayLaunchData::class,
        'messageActionGiveawayResults' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageActionGiveawayResultsData::class,
        'messageActionGroupCall' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageActionGroupCallData::class,
        'messageActionGroupCallScheduled' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageActionGroupCallScheduledData::class,
        'messageActionHistoryClear' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageActionHistoryClearData::class,
        'messageActionInviteToGroupCall' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageActionInviteToGroupCallData::class,
        'messageActionManagedBotCreated' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageActionManagedBotCreatedData::class,
        'messageActionNewCreatorPending' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageActionNewCreatorPendingData::class,
        'messageActionNoForwardsRequest' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageActionNoForwardsRequestData::class,
        'messageActionNoForwardsToggle' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageActionNoForwardsToggleData::class,
        'messageActionPaidMessagesPrice' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageActionPaidMessagesPriceData::class,
        'messageActionPaidMessagesRefunded' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageActionPaidMessagesRefundedData::class,
        'messageActionPaymentRefunded' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageActionPaymentRefundedData::class,
        'messageActionPaymentSent' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageActionPaymentSentData::class,
        'messageActionPaymentSentMe' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageActionPaymentSentMeData::class,
        'messageActionPhoneCall' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageActionPhoneCallData::class,
        'messageActionPinMessage' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageActionPinMessageData::class,
        'messageActionPollAppendAnswer' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageActionPollAppendAnswerData::class,
        'messageActionPollDeleteAnswer' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageActionPollDeleteAnswerData::class,
        'messageActionPrizeStars' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageActionPrizeStarsData::class,
        'messageActionRequestedPeer' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageActionRequestedPeerData::class,
        'messageActionRequestedPeerSentMe' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageActionRequestedPeerSentMeData::class,
        'messageActionScreenshotTaken' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageActionScreenshotTakenData::class,
        'messageActionSecureValuesSent' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageActionSecureValuesSentData::class,
        'messageActionSecureValuesSentMe' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageActionSecureValuesSentMeData::class,
        'messageActionSetChatTheme' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageActionSetChatThemeData::class,
        'messageActionSetChatWallPaper' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageActionSetChatWallPaperData::class,
        'messageActionSetMessagesTTL' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageActionSetMessagesTTLData::class,
        'messageActionStarGift' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageActionStarGiftData::class,
        'messageActionStarGiftPurchaseOffer' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageActionStarGiftPurchaseOfferData::class,
        'messageActionStarGiftPurchaseOfferDeclined' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageActionStarGiftPurchaseOfferDeclinedData::class,
        'messageActionStarGiftUnique' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageActionStarGiftUniqueData::class,
        'messageActionSuggestBirthday' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageActionSuggestBirthdayData::class,
        'messageActionSuggestProfilePhoto' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageActionSuggestProfilePhotoData::class,
        'messageActionSuggestedPostApproval' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageActionSuggestedPostApprovalData::class,
        'messageActionSuggestedPostRefund' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageActionSuggestedPostRefundData::class,
        'messageActionSuggestedPostSuccess' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageActionSuggestedPostSuccessData::class,
        'messageActionTodoAppendTasks' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageActionTodoAppendTasksData::class,
        'messageActionTodoCompletions' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageActionTodoCompletionsData::class,
        'messageActionTopicCreate' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageActionTopicCreateData::class,
        'messageActionTopicEdit' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageActionTopicEditData::class,
        'messageActionWebViewDataSent' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageActionWebViewDataSentData::class,
        'messageActionWebViewDataSentMe' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageActionWebViewDataSentMeData::class,
    ];

    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [];

    /** Dispatch on the constructor name carried under the '_' key of a decoded wire payload. */
    public static function hydrate(array $payload): static
    {
        $class = static::DISPATCH[$payload['_']]
            ?? throw new \InvalidArgumentException('Unknown constructor ' . $payload['_'] . ' for MessageAction');
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

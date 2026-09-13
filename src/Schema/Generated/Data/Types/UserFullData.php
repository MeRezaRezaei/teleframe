<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for userFull of UserFull.
 */
final class UserFullData extends TlUserFullAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'blocked' => ['flags', 0],
        'phoneCallsAvailable' => ['flags', 4],
        'phoneCallsPrivate' => ['flags', 5],
        'canPinMessage' => ['flags', 7],
        'hasScheduled' => ['flags', 12],
        'videoCallsAvailable' => ['flags', 13],
        'voiceMessagesForbidden' => ['flags', 20],
        'translationsDisabled' => ['flags', 23],
        'storiesPinnedAvailable' => ['flags', 26],
        'blockedMyStoriesFrom' => ['flags', 27],
        'wallpaperOverridden' => ['flags', 28],
        'contactRequirePremium' => ['flags', 29],
        'readDatesPrivate' => ['flags', 30],
        'sponsoredEnabled' => ['flags2', 7],
        'canViewRevenue' => ['flags2', 9],
        'botCanManageEmojiStatus' => ['flags2', 10],
        'displayGiftsButton' => ['flags2', 16],
        'noforwardsMyEnabled' => ['flags2', 23],
        'noforwardsPeerEnabled' => ['flags2', 24],
        'unofficialSecurityRisk' => ['flags2', 26],
    ];

    public function __construct(
    public int $flags,
    public ?bool $blocked,
    public ?bool $phoneCallsAvailable,
    public ?bool $phoneCallsPrivate,
    public ?bool $canPinMessage,
    public ?bool $hasScheduled,
    public ?bool $videoCallsAvailable,
    public ?bool $voiceMessagesForbidden,
    public ?bool $translationsDisabled,
    public ?bool $storiesPinnedAvailable,
    public ?bool $blockedMyStoriesFrom,
    public ?bool $wallpaperOverridden,
    public ?bool $contactRequirePremium,
    public ?bool $readDatesPrivate,
    public int $flags2,
    public ?bool $sponsoredEnabled,
    public ?bool $canViewRevenue,
    public ?bool $botCanManageEmojiStatus,
    public ?bool $displayGiftsButton,
    public ?bool $noforwardsMyEnabled,
    public ?bool $noforwardsPeerEnabled,
    public ?bool $unofficialSecurityRisk,
    public int $id,
    public ?string $about,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerSettingsAbstractData $settings,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPhotoAbstractData $personalPhoto,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPhotoAbstractData $profilePhoto,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPhotoAbstractData $fallbackPhoto,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerNotifySettingsAbstractData $notifySettings,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlBotInfoAbstractData $botInfo,
    public ?int $pinnedMsgId,
    public int $commonChatsCount,
    public ?int $folderId,
    public ?int $ttlPeriod,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlChatThemeAbstractData $theme,
    public ?string $privateForwardName,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlChatAdminRightsAbstractData $botGroupAdminRights,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlChatAdminRightsAbstractData $botBroadcastAdminRights,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlWallPaperAbstractData $wallpaper,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerStoriesAbstractData $stories,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlBusinessWorkHoursAbstractData $businessWorkHours,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlBusinessLocationAbstractData $businessLocation,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlBusinessGreetingMessageAbstractData $businessGreetingMessage,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlBusinessAwayMessageAbstractData $businessAwayMessage,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlBusinessIntroAbstractData $businessIntro,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlBirthdayAbstractData $birthday,
    public ?int $personalChannelId,
    public ?int $personalChannelMessage,
    public ?int $stargiftsCount,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStarRefProgramAbstractData $starrefProgram,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlBotVerificationAbstractData $botVerification,
    public ?int $sendPaidMessagesStars,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlDisallowedGiftsSettingsAbstractData $disallowedGifts,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStarsRatingAbstractData $starsRating,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStarsRatingAbstractData $starsMyPendingRating,
    public ?int $starsMyPendingRatingDate,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlProfileTabAbstractData $mainTab,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlDocumentAbstractData $savedMusic,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlTextWithEntitiesAbstractData $note,
    public ?int $botManagerId,
    ) {
    }
}

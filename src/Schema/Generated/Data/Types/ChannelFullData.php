<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for channelFull of ChatFull.
 */
final class ChannelFullData extends TlChatFullAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'canViewParticipants' => ['flags', 3],
        'canSetUsername' => ['flags', 6],
        'canSetStickers' => ['flags', 7],
        'hiddenPrehistory' => ['flags', 10],
        'canSetLocation' => ['flags', 16],
        'hasScheduled' => ['flags', 19],
        'canViewStats' => ['flags', 20],
        'blocked' => ['flags', 22],
        'canDeleteChannel' => ['flags2', 0],
        'antispam' => ['flags2', 1],
        'participantsHidden' => ['flags2', 2],
        'translationsDisabled' => ['flags2', 3],
        'storiesPinnedAvailable' => ['flags2', 5],
        'viewForumAsMessages' => ['flags2', 6],
        'restrictedSponsored' => ['flags2', 11],
        'canViewRevenue' => ['flags2', 12],
        'paidMediaAllowed' => ['flags2', 14],
        'canViewStarsRevenue' => ['flags2', 15],
        'paidReactionsAvailable' => ['flags2', 16],
        'stargiftsAvailable' => ['flags2', 19],
        'paidMessagesAvailable' => ['flags2', 20],
    ];

    public function __construct(
    public int $flags,
    public ?bool $canViewParticipants,
    public ?bool $canSetUsername,
    public ?bool $canSetStickers,
    public ?bool $hiddenPrehistory,
    public ?bool $canSetLocation,
    public ?bool $hasScheduled,
    public ?bool $canViewStats,
    public ?bool $blocked,
    public int $flags2,
    public ?bool $canDeleteChannel,
    public ?bool $antispam,
    public ?bool $participantsHidden,
    public ?bool $translationsDisabled,
    public ?bool $storiesPinnedAvailable,
    public ?bool $viewForumAsMessages,
    public ?bool $restrictedSponsored,
    public ?bool $canViewRevenue,
    public ?bool $paidMediaAllowed,
    public ?bool $canViewStarsRevenue,
    public ?bool $paidReactionsAvailable,
    public ?bool $stargiftsAvailable,
    public ?bool $paidMessagesAvailable,
    public int $id,
    public string $about,
    public ?int $participantsCount,
    public ?int $adminsCount,
    public ?int $kickedCount,
    public ?int $bannedCount,
    public ?int $onlineCount,
    public int $readInboxMaxId,
    public int $readOutboxMaxId,
    public int $unreadCount,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPhotoAbstractData $chatPhoto,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerNotifySettingsAbstractData $notifySettings,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlExportedChatInviteAbstractData $exportedInvite,
    public array $botInfo,
    public ?int $migratedFromChatId,
    public ?int $migratedFromMaxId,
    public ?int $pinnedMsgId,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStickerSetAbstractData $stickerset,
    public ?int $availableMinId,
    public ?int $folderId,
    public ?int $linkedChatId,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlChannelLocationAbstractData $location,
    public ?int $slowmodeSeconds,
    public ?int $slowmodeNextSendDate,
    public ?int $statsDc,
    public int $pts,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlInputGroupCallAbstractData $call,
    public ?int $ttlPeriod,
    public ?array $pendingSuggestions,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerAbstractData $groupcallDefaultJoinAs,
    public ?string $themeEmoticon,
    public ?int $requestsPending,
    public ?array $recentRequesters,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerAbstractData $defaultSendAs,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlChatReactionsAbstractData $availableReactions,
    public ?int $reactionsLimit,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerStoriesAbstractData $stories,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlWallPaperAbstractData $wallpaper,
    public ?int $boostsApplied,
    public ?int $boostsUnrestrict,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStickerSetAbstractData $emojiset,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlBotVerificationAbstractData $botVerification,
    public ?int $stargiftsCount,
    public ?int $sendPaidMessagesStars,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlProfileTabAbstractData $mainTab,
    public ?int $guardBotId,
    ) {
    }
}

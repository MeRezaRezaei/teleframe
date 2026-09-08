<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for user of User.
 */
final class UserData extends TlUserAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'self' => ['flags', 10],
        'contact' => ['flags', 11],
        'mutualContact' => ['flags', 12],
        'deleted' => ['flags', 13],
        'bot' => ['flags', 14],
        'botChatHistory' => ['flags', 15],
        'botNochats' => ['flags', 16],
        'verified' => ['flags', 17],
        'restricted' => ['flags', 18],
        'min' => ['flags', 20],
        'botInlineGeo' => ['flags', 21],
        'support' => ['flags', 23],
        'scam' => ['flags', 24],
        'applyMinPhoto' => ['flags', 25],
        'fake' => ['flags', 26],
        'botAttachMenu' => ['flags', 27],
        'premium' => ['flags', 28],
        'attachMenuEnabled' => ['flags', 29],
        'botCanEdit' => ['flags2', 1],
        'closeFriend' => ['flags2', 2],
        'storiesHidden' => ['flags2', 3],
        'storiesUnavailable' => ['flags2', 4],
        'contactRequirePremium' => ['flags2', 10],
        'botBusiness' => ['flags2', 11],
        'botHasMainApp' => ['flags2', 13],
        'botForumView' => ['flags2', 16],
        'botForumCanManageTopics' => ['flags2', 17],
        'botCanManageBots' => ['flags2', 18],
        'botGuestchat' => ['flags2', 19],
        'botGuard' => ['flags2', 20],
    ];

    public function __construct(
    public int $flags,
    public ?bool $self,
    public ?bool $contact,
    public ?bool $mutualContact,
    public ?bool $deleted,
    public ?bool $bot,
    public ?bool $botChatHistory,
    public ?bool $botNochats,
    public ?bool $verified,
    public ?bool $restricted,
    public ?bool $min,
    public ?bool $botInlineGeo,
    public ?bool $support,
    public ?bool $scam,
    public ?bool $applyMinPhoto,
    public ?bool $fake,
    public ?bool $botAttachMenu,
    public ?bool $premium,
    public ?bool $attachMenuEnabled,
    public int $flags2,
    public ?bool $botCanEdit,
    public ?bool $closeFriend,
    public ?bool $storiesHidden,
    public ?bool $storiesUnavailable,
    public ?bool $contactRequirePremium,
    public ?bool $botBusiness,
    public ?bool $botHasMainApp,
    public ?bool $botForumView,
    public ?bool $botForumCanManageTopics,
    public ?bool $botCanManageBots,
    public ?bool $botGuestchat,
    public ?bool $botGuard,
    public int $id,
    public ?int $accessHash,
    public ?string $firstName,
    public ?string $lastName,
    public ?string $username,
    public ?string $phone,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlUserProfilePhotoAbstractData $photo,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlUserStatusAbstractData $status,
    public ?int $botInfoVersion,
    public ?array $restrictionReason,
    public ?string $botInlinePlaceholder,
    public ?string $langCode,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlEmojiStatusAbstractData $emojiStatus,
    public ?array $usernames,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlRecentStoryAbstractData $storiesMaxId,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerColorAbstractData $color,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerColorAbstractData $profileColor,
    public ?int $botActiveUsers,
    public ?int $botVerificationIcon,
    public ?int $sendPaidMessagesStars,
    ) {
    }
}

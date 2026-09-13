<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for channel of Chat.
 */
final class ChannelData extends TlChatAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'creator' => ['flags', 0],
        'left' => ['flags', 2],
        'broadcast' => ['flags', 5],
        'verified' => ['flags', 7],
        'megagroup' => ['flags', 8],
        'restricted' => ['flags', 9],
        'signatures' => ['flags', 11],
        'min' => ['flags', 12],
        'scam' => ['flags', 19],
        'hasLink' => ['flags', 20],
        'hasGeo' => ['flags', 21],
        'slowmodeEnabled' => ['flags', 22],
        'callActive' => ['flags', 23],
        'callNotEmpty' => ['flags', 24],
        'fake' => ['flags', 25],
        'gigagroup' => ['flags', 26],
        'noforwards' => ['flags', 27],
        'joinToSend' => ['flags', 28],
        'joinRequest' => ['flags', 29],
        'forum' => ['flags', 30],
        'storiesHidden' => ['flags2', 1],
        'storiesHiddenMin' => ['flags2', 2],
        'storiesUnavailable' => ['flags2', 3],
        'signatureProfiles' => ['flags2', 12],
        'autotranslation' => ['flags2', 15],
        'broadcastMessagesAllowed' => ['flags2', 16],
        'monoforum' => ['flags2', 17],
        'forumTabs' => ['flags2', 19],
    ];

    public function __construct(
    public int $flags,
    public ?bool $creator,
    public ?bool $left,
    public ?bool $broadcast,
    public ?bool $verified,
    public ?bool $megagroup,
    public ?bool $restricted,
    public ?bool $signatures,
    public ?bool $min,
    public ?bool $scam,
    public ?bool $hasLink,
    public ?bool $hasGeo,
    public ?bool $slowmodeEnabled,
    public ?bool $callActive,
    public ?bool $callNotEmpty,
    public ?bool $fake,
    public ?bool $gigagroup,
    public ?bool $noforwards,
    public ?bool $joinToSend,
    public ?bool $joinRequest,
    public ?bool $forum,
    public int $flags2,
    public ?bool $storiesHidden,
    public ?bool $storiesHiddenMin,
    public ?bool $storiesUnavailable,
    public ?bool $signatureProfiles,
    public ?bool $autotranslation,
    public ?bool $broadcastMessagesAllowed,
    public ?bool $monoforum,
    public ?bool $forumTabs,
    public int $id,
    public ?int $accessHash,
    public string $title,
    public ?string $username,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlChatPhotoAbstractData $photo,
    public int $date,
    public ?array $restrictionReason,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlChatAdminRightsAbstractData $adminRights,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlChatBannedRightsAbstractData $bannedRights,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlChatBannedRightsAbstractData $defaultBannedRights,
    public ?int $participantsCount,
    public ?array $usernames,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlRecentStoryAbstractData $storiesMaxId,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerColorAbstractData $color,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerColorAbstractData $profileColor,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlEmojiStatusAbstractData $emojiStatus,
    public ?int $level,
    public ?int $subscriptionUntilDate,
    public ?int $botVerificationIcon,
    public ?int $sendPaidMessagesStars,
    public ?int $linkedMonoforumId,
    ) {
    }
}

<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for peerSettings of PeerSettings.
 */
final class PeerSettingsData extends TlPeerSettingsAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'reportSpam' => ['flags', 0],
        'addContact' => ['flags', 1],
        'blockContact' => ['flags', 2],
        'shareContact' => ['flags', 3],
        'needContactsException' => ['flags', 4],
        'reportGeo' => ['flags', 5],
        'autoarchived' => ['flags', 7],
        'inviteMembers' => ['flags', 8],
        'requestChatBroadcast' => ['flags', 10],
        'businessBotPaused' => ['flags', 11],
        'businessBotCanReply' => ['flags', 12],
    ];

    public function __construct(
    public int $flags,
    public ?bool $reportSpam,
    public ?bool $addContact,
    public ?bool $blockContact,
    public ?bool $shareContact,
    public ?bool $needContactsException,
    public ?bool $reportGeo,
    public ?bool $autoarchived,
    public ?bool $inviteMembers,
    public ?bool $requestChatBroadcast,
    public ?bool $businessBotPaused,
    public ?bool $businessBotCanReply,
    public ?int $geoDistance,
    public ?string $requestChatTitle,
    public ?int $requestChatDate,
    public ?int $businessBotId,
    public ?string $businessBotManageUrl,
    public ?int $chargePaidMessageStars,
    public ?string $registrationMonth,
    public ?string $phoneCountry,
    public ?int $nameChangeDate,
    public ?int $photoChangeDate,
    ) {
    }
}

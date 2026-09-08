<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for groupCall of GroupCall.
 */
final class GroupCallData extends TlGroupCallAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'joinMuted' => ['flags', 1],
        'canChangeJoinMuted' => ['flags', 2],
        'joinDateAsc' => ['flags', 6],
        'scheduleStartSubscribed' => ['flags', 8],
        'canStartVideo' => ['flags', 9],
        'recordVideoActive' => ['flags', 11],
        'rtmpStream' => ['flags', 12],
        'listenersHidden' => ['flags', 13],
        'conference' => ['flags', 14],
        'creator' => ['flags', 15],
        'messagesEnabled' => ['flags', 17],
        'canChangeMessagesEnabled' => ['flags', 18],
        'min' => ['flags', 19],
    ];

    public function __construct(
    public int $flags,
    public ?bool $joinMuted,
    public ?bool $canChangeJoinMuted,
    public ?bool $joinDateAsc,
    public ?bool $scheduleStartSubscribed,
    public ?bool $canStartVideo,
    public ?bool $recordVideoActive,
    public ?bool $rtmpStream,
    public ?bool $listenersHidden,
    public ?bool $conference,
    public ?bool $creator,
    public ?bool $messagesEnabled,
    public ?bool $canChangeMessagesEnabled,
    public ?bool $min,
    public int $id,
    public int $accessHash,
    public int $participantsCount,
    public ?string $title,
    public ?int $streamDcId,
    public ?int $recordStartDate,
    public ?int $scheduleDate,
    public ?int $unmutedVideoCount,
    public int $unmutedVideoLimit,
    public int $version,
    public ?string $inviteLink,
    public ?int $sendPaidMessagesStars,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerAbstractData $defaultSendAs,
    ) {
    }
}

<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for channelParticipantSelf of ChannelParticipant.
 */
final class ChannelParticipantSelfData extends TlChannelParticipantAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'viaRequest' => ['flags', 0],
    ];

    public function __construct(
    public int $flags,
    public ?bool $viaRequest,
    public int $userId,
    public int $inviterId,
    public int $date,
    public ?int $subscriptionUntilDate,
    public ?string $rank,
    ) {
    }
}

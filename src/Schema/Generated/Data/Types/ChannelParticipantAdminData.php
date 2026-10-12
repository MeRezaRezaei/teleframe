<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for channelParticipantAdmin of ChannelParticipant.
 */
final class ChannelParticipantAdminData extends TlChannelParticipantAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'canEdit' => ['flags', 0],
        'self' => ['flags', 1],
    ];

    public function __construct(
    public int $flags,
    public ?bool $canEdit,
    public ?bool $self,
    public int $userId,
    public ?int $inviterId,
    public int $promotedBy,
    public int $date,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlChatAdminRightsAbstractData $adminRights,
    public ?string $rank,
    ) {
    }
}

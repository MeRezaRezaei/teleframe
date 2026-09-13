<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for updateChannelParticipant of Update.
 */
final class UpdateChannelParticipantData extends TlUpdateAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'viaChatlist' => ['flags', 3],
    ];

    public function __construct(
    public int $flags,
    public ?bool $viaChatlist,
    public int $channelId,
    public int $date,
    public int $actorId,
    public int $userId,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlChannelParticipantAbstractData $prevParticipant,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlChannelParticipantAbstractData $newParticipant,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlExportedChatInviteAbstractData $invite,
    public int $qts,
    ) {
    }
}

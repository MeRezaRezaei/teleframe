<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for groupCallParticipant of GroupCallParticipant.
 */
final class GroupCallParticipantData extends TlGroupCallParticipantAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'muted' => ['flags', 0],
        'left' => ['flags', 1],
        'canSelfUnmute' => ['flags', 2],
        'justJoined' => ['flags', 4],
        'versioned' => ['flags', 5],
        'min' => ['flags', 8],
        'mutedByYou' => ['flags', 9],
        'volumeByAdmin' => ['flags', 10],
        'self' => ['flags', 12],
        'videoJoined' => ['flags', 15],
    ];

    public function __construct(
    public int $flags,
    public ?bool $muted,
    public ?bool $left,
    public ?bool $canSelfUnmute,
    public ?bool $justJoined,
    public ?bool $versioned,
    public ?bool $min,
    public ?bool $mutedByYou,
    public ?bool $volumeByAdmin,
    public ?bool $self,
    public ?bool $videoJoined,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerAbstractData $peer,
    public int $date,
    public ?int $activeDate,
    public int $source,
    public ?int $volume,
    public ?string $about,
    public ?int $raiseHandRating,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlGroupCallParticipantVideoAbstractData $video,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlGroupCallParticipantVideoAbstractData $presentation,
    public ?int $paidStarsTotal,
    ) {
    }
}

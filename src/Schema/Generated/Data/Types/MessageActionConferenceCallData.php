<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for messageActionConferenceCall of MessageAction.
 */
final class MessageActionConferenceCallData extends TlMessageActionAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'missed' => ['flags', 0],
        'active' => ['flags', 1],
        'video' => ['flags', 4],
    ];

    public function __construct(
    public int $flags,
    public ?bool $missed,
    public ?bool $active,
    public ?bool $video,
    public int $callId,
    public ?int $duration,
    public ?array $otherParticipants,
    ) {
    }
}

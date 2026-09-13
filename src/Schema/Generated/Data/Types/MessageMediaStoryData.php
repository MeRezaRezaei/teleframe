<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for messageMediaStory of MessageMedia.
 */
final class MessageMediaStoryData extends TlMessageMediaAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'viaMention' => ['flags', 1],
    ];

    public function __construct(
    public int $flags,
    public ?bool $viaMention,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerAbstractData $peer,
    public int $id,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStoryItemAbstractData $story,
    ) {
    }
}

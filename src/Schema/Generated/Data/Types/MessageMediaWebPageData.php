<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for messageMediaWebPage of MessageMedia.
 */
final class MessageMediaWebPageData extends TlMessageMediaAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'forceLargeMedia' => ['flags', 0],
        'forceSmallMedia' => ['flags', 1],
        'manual' => ['flags', 3],
        'safe' => ['flags', 4],
    ];

    public function __construct(
    public int $flags,
    public ?bool $forceLargeMedia,
    public ?bool $forceSmallMedia,
    public ?bool $manual,
    public ?bool $safe,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlWebPageAbstractData $webpage,
    ) {
    }
}

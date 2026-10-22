<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for inputMediaWebPage of InputMedia.
 */
final class InputMediaWebPageData extends TlInputMediaAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'forceLargeMedia' => ['flags', 0],
        'forceSmallMedia' => ['flags', 1],
        'optional' => ['flags', 2],
    ];

    public function __construct(
    public int $flags,
    public ?bool $forceLargeMedia,
    public ?bool $forceSmallMedia,
    public ?bool $optional,
    public string $url,
    ) {
    }
}

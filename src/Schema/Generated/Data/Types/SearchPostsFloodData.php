<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for searchPostsFlood of SearchPostsFlood.
 */
final class SearchPostsFloodData extends TlSearchPostsFloodAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'queryIsFree' => ['flags', 0],
    ];

    public function __construct(
    public int $flags,
    public ?bool $queryIsFree,
    public int $totalDaily,
    public int $remains,
    public ?int $waitTill,
    public int $starsAmount,
    ) {
    }
}

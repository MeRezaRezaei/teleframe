<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for pageBlockOrderedList of PageBlock.
 */
final class PageBlockOrderedListData extends TlPageBlockAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'reversed' => ['flags', 2],
    ];

    public function __construct(
    public int $flags,
    public ?bool $reversed,
    public array $items,
    public ?int $start,
    public ?string $type,
    ) {
    }
}

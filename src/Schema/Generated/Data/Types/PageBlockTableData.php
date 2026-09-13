<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for pageBlockTable of PageBlock.
 */
final class PageBlockTableData extends TlPageBlockAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'bordered' => ['flags', 0],
        'striped' => ['flags', 1],
    ];

    public function __construct(
    public int $flags,
    public ?bool $bordered,
    public ?bool $striped,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlRichTextAbstractData $title,
    public array $rows,
    ) {
    }
}

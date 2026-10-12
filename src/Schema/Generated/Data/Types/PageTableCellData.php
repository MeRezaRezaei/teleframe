<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for pageTableCell of PageTableCell.
 */
final class PageTableCellData extends TlPageTableCellAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'header' => ['flags', 0],
        'alignCenter' => ['flags', 3],
        'alignRight' => ['flags', 4],
        'valignMiddle' => ['flags', 5],
        'valignBottom' => ['flags', 6],
    ];

    public function __construct(
    public int $flags,
    public ?bool $header,
    public ?bool $alignCenter,
    public ?bool $alignRight,
    public ?bool $valignMiddle,
    public ?bool $valignBottom,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlRichTextAbstractData $text,
    public ?int $colspan,
    public ?int $rowspan,
    ) {
    }
}

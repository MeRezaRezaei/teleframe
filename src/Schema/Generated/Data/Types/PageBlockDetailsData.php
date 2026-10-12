<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for pageBlockDetails of PageBlock.
 */
final class PageBlockDetailsData extends TlPageBlockAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'open' => ['flags', 0],
    ];

    public function __construct(
    public int $flags,
    public ?bool $open,
    public array $blocks,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlRichTextAbstractData $title,
    ) {
    }
}

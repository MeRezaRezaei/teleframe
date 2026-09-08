<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for pageBlockBlockquoteBlocks of PageBlock.
 */
final class PageBlockBlockquoteBlocksData extends TlPageBlockAbstractData
{
    public function __construct(
    public array $blocks,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlRichTextAbstractData $caption,
    ) {
    }
}

<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for pageBlockPullquote of PageBlock.
 */
final class PageBlockPullquoteData extends TlPageBlockAbstractData
{
    public function __construct(
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlRichTextAbstractData $text,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlRichTextAbstractData $caption,
    ) {
    }
}

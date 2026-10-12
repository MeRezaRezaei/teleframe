<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for textPlain of RichText.
 */
final class TextPlainData extends TlRichTextAbstractData
{
    public function __construct(
    public string $text,
    ) {
    }
}

<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for textImage of RichText.
 */
final class TextImageData extends TlRichTextAbstractData
{
    public function __construct(
    public int $documentId,
    public int $w,
    public int $h,
    ) {
    }
}

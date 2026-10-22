<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for documentAttributeImageSize of DocumentAttribute.
 */
final class DocumentAttributeImageSizeData extends TlDocumentAttributeAbstractData
{
    public function __construct(
    public int $w,
    public int $h,
    ) {
    }
}

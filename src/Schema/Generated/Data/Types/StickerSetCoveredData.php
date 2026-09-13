<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for stickerSetCovered of StickerSetCovered.
 */
final class StickerSetCoveredData extends TlStickerSetCoveredAbstractData
{
    public function __construct(
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStickerSetAbstractData $set,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlDocumentAbstractData $cover,
    ) {
    }
}

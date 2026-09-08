<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for stickerSetMultiCovered of StickerSetCovered.
 */
final class StickerSetMultiCoveredData extends TlStickerSetCoveredAbstractData
{
    public function __construct(
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStickerSetAbstractData $set,
    public array $covers,
    ) {
    }
}

<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for starGiftAttributeCounter of StarGiftAttributeCounter.
 */
final class StarGiftAttributeCounterData extends TlStarGiftAttributeCounterAbstractData
{
    public function __construct(
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStarGiftAttributeIdAbstractData $attribute,
    public int $count,
    ) {
    }
}

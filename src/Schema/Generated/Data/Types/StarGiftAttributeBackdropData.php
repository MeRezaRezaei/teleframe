<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for starGiftAttributeBackdrop of StarGiftAttribute.
 */
final class StarGiftAttributeBackdropData extends TlStarGiftAttributeAbstractData
{
    public function __construct(
    public string $name,
    public int $backdropId,
    public int $centerColor,
    public int $edgeColor,
    public int $patternColor,
    public int $textColor,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStarGiftAttributeRarityAbstractData $rarity,
    ) {
    }
}

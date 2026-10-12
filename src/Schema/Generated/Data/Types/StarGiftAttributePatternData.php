<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for starGiftAttributePattern of StarGiftAttribute.
 */
final class StarGiftAttributePatternData extends TlStarGiftAttributeAbstractData
{
    public function __construct(
    public string $name,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlDocumentAbstractData $document,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStarGiftAttributeRarityAbstractData $rarity,
    ) {
    }
}

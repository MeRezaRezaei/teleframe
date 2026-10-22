<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for starGiftBackground of StarGiftBackground.
 */
final class StarGiftBackgroundData extends TlStarGiftBackgroundAbstractData
{
    public function __construct(
    public int $centerColor,
    public int $edgeColor,
    public int $textColor,
    ) {
    }
}

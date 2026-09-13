<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for labeledPrice of LabeledPrice.
 */
final class LabeledPriceData extends TlLabeledPriceAbstractData
{
    public function __construct(
    public string $label,
    public int $amount,
    ) {
    }
}

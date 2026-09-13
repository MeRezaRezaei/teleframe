<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for prepaidGiveaway of PrepaidGiveaway.
 */
final class PrepaidGiveawayData extends TlPrepaidGiveawayAbstractData
{
    public function __construct(
    public int $id,
    public int $months,
    public int $quantity,
    public int $date,
    ) {
    }
}

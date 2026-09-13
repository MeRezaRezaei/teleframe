<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for popularContact of PopularContact.
 */
final class PopularContactData extends TlPopularContactAbstractData
{
    public function __construct(
    public int $clientId,
    public int $importers,
    ) {
    }
}

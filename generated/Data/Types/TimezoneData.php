<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for timezone of Timezone.
 */
final class TimezoneData extends TlTimezoneAbstractData
{
    public function __construct(
    public string $id,
    public string $name,
    public int $utcOffset,
    ) {
    }
}

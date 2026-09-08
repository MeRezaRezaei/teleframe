<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for inputAppEvent of InputAppEvent.
 */
final class InputAppEventData extends TlInputAppEventAbstractData
{
    public function __construct(
    public float $time,
    public string $type,
    public int $peer,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlJSONValueAbstractData $data,
    ) {
    }
}

<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for jsonObject of JSONValue.
 */
final class JsonObjectData extends TlJSONValueAbstractData
{
    public function __construct(
    public array $value,
    ) {
    }
}

<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for jsonObjectValue of JSONObjectValue.
 */
final class JsonObjectValueData extends TlJSONObjectValueAbstractData
{
    public function __construct(
    public string $key,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlJSONValueAbstractData $value,
    ) {
    }
}

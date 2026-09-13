<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for inputSecureFileLocation of InputFileLocation.
 */
final class InputSecureFileLocationData extends TlInputFileLocationAbstractData
{
    public function __construct(
    public int $id,
    public int $accessHash,
    ) {
    }
}

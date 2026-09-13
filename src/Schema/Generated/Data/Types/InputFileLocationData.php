<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for inputFileLocation of InputFileLocation.
 *
 * bytes params carried as base64 strings: file_reference
 */
final class InputFileLocationData extends TlInputFileLocationAbstractData
{
    public function __construct(
    public int $volumeId,
    public int $localId,
    public int $secret,
    public string $fileReference,
    ) {
    }
}

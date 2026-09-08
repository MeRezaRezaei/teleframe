<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for inputDocumentFileLocation of InputFileLocation.
 *
 * bytes params carried as base64 strings: file_reference
 */
final class InputDocumentFileLocationData extends TlInputFileLocationAbstractData
{
    public function __construct(
    public int $id,
    public int $accessHash,
    public string $fileReference,
    public string $thumbSize,
    ) {
    }
}

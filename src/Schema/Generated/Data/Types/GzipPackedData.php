<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for gzip_packed of Object.
 *
 * bytes params carried as base64 strings: packed_data
 */
final class GzipPackedData extends TlObjectAbstractData
{
    public function __construct(
    public string $packedData,
    ) {
    }
}

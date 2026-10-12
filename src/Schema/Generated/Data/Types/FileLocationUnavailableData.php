<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for fileLocationUnavailable of FileLocation.
 */
final class FileLocationUnavailableData extends TlFileLocationAbstractData
{
    public function __construct(
    public int $volumeId,
    public int $localId,
    public int $secret,
    ) {
    }
}

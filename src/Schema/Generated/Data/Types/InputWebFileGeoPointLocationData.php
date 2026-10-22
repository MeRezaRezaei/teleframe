<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for inputWebFileGeoPointLocation of InputWebFileLocation.
 */
final class InputWebFileGeoPointLocationData extends TlInputWebFileLocationAbstractData
{
    public function __construct(
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlInputGeoPointAbstractData $geoPoint,
    public int $accessHash,
    public int $w,
    public int $h,
    public int $zoom,
    public int $scale,
    ) {
    }
}

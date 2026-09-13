<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for mediaAreaGeoPoint of MediaArea.
 */
final class MediaAreaGeoPointData extends TlMediaAreaAbstractData
{
    public function __construct(
    public int $flags,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlMediaAreaCoordinatesAbstractData $coordinates,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlGeoPointAbstractData $geo,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlGeoPointAddressAbstractData $address,
    ) {
    }
}

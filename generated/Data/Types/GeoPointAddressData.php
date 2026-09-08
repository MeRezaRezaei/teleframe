<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for geoPointAddress of GeoPointAddress.
 */
final class GeoPointAddressData extends TlGeoPointAddressAbstractData
{
    public function __construct(
    public int $flags,
    public string $countryIso2,
    public ?string $state,
    public ?string $city,
    public ?string $street,
    ) {
    }
}

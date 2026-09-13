<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for mediaAreaVenue of MediaArea.
 */
final class MediaAreaVenueData extends TlMediaAreaAbstractData
{
    public function __construct(
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlMediaAreaCoordinatesAbstractData $coordinates,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlGeoPointAbstractData $geo,
    public string $title,
    public string $address,
    public string $provider,
    public string $venueId,
    public string $venueType,
    ) {
    }
}

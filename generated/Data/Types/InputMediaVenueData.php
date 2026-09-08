<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for inputMediaVenue of InputMedia.
 */
final class InputMediaVenueData extends TlInputMediaAbstractData
{
    public function __construct(
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlInputGeoPointAbstractData $geoPoint,
    public string $title,
    public string $address,
    public string $provider,
    public string $venueId,
    public string $venueType,
    ) {
    }
}

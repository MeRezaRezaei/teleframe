<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for decryptedMessageMediaVenue of DecryptedMessageMedia.
 */
final class DecryptedMessageMediaVenueData extends TlDecryptedMessageMediaAbstractData
{
    public function __construct(
    public float $lat,
    public float $long,
    public string $title,
    public string $address,
    public string $provider,
    public string $venueId,
    ) {
    }
}

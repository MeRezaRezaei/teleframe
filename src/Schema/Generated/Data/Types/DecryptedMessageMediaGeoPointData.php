<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for decryptedMessageMediaGeoPoint of DecryptedMessageMedia.
 */
final class DecryptedMessageMediaGeoPointData extends TlDecryptedMessageMediaAbstractData
{
    public function __construct(
    public float $lat,
    public float $long,
    ) {
    }
}
